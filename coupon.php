<?php

namespace App\Listners;

use App\Jobs\SendTrainerPurchaseMail;
use App\Jobs\SendCustomerPurchaseMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\PTBillingUserStripeProfile;
use App\Models\PTBillingUserTrainer;
use App\Models\PTBillingUserPunchCard;
use Stripe\StripeClient;

class HandlePaymentIntentSucceeded implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 120;

    protected StripeClient $stripe;

    public function handle($event)
    {
        $stripeEvent = $event->event;
        $connectedAccountId = $stripeEvent->account ?? null;

        if (! $connectedAccountId) {
            return;
        }

        try {
            $trainerUser = User::where('stripe_account_id', $connectedAccountId)->first();
            if (! $trainerUser) {
                return;
            }

            $trainerStripeSecretKey = optional($trainerUser->corporatePartner)->stripe_secret_key;
            if (! $trainerStripeSecretKey) {
                return;
            }

            // Create Stripe client once
            $this->stripe = new StripeClient($trainerStripeSecretKey);

            /** ---------------------------------
             * Retrieve full PaymentIntent
             * --------------------------------- */
            $paymentIntentId = $stripeEvent->data->object->id;

            $paymentIntent = $this->stripe->paymentIntents->retrieve(
                $paymentIntentId,
                [],
                ['stripe_account' => $connectedAccountId]
            );

            /** ---------------------------------
             * Retrieve Customer
             * --------------------------------- */
            $customer = $this->stripe->customers->retrieve(
                $paymentIntent->customer,
                [],
                ['stripe_account' => $connectedAccountId]
            );

            $stripeCustomerId = $customer->id;
            $customerEmail    = $customer->email ?? null;
            $customerName     = $customer->name ?? 'Customer';

            $userStripeProfile = PTBillingUserStripeProfile::where('stripe_customer_id', $stripeCustomerId)
                ->where('stripe_account_id', $connectedAccountId)
                ->first();

            if ($userStripeProfile) {
                PTBillingUserTrainer::firstOrCreate([
                    'user_id'    => $userStripeProfile->user_id,
                    'trainer_id' => $trainerUser->id,
                ]);
            }

            /** ---------------------------------
             * Create Punch Card
             * --------------------------------- */
            PTBillingUserPunchCard::create([
                'user_id'        => $userStripeProfile->user_id,
                'trainer_id'     => $trainerUser->id,
                'product_name'   => $paymentIntent->metadata->product_name ?? '',
                'total_session'  => $paymentIntent->metadata->session_count ?? 0,
                'purchased_at'   => now(),
            ]);

            /** ---------------------------------
             * Get Payment Method (card brand + last4)
             * --------------------------------- */
            $paymentMethodLabel = 'Card';

            if ($paymentIntent->payment_method) {
                $paymentMethod = $this->stripe->paymentMethods->retrieve(
                    $paymentIntent->payment_method,
                    [],
                    ['stripe_account' => $connectedAccountId]
                );

                if ($paymentMethod->type === 'card') {
                    $card = $paymentMethod->card;
                    $paymentMethodLabel = ucfirst($card->brand) . ' ending in ' . $card->last4;
                }
            }

            /** ---------------------------------
             * Amount & Currency
             * --------------------------------- */
            $currencySymbol = optional($trainerUser->corporatePartner)->currency_symbol ?? '$';
            $amount = $currencySymbol . number_format($paymentIntent->amount / 100, 2);

            /** ---------------------------------
             * Send Emails
             * --------------------------------- */
            SendTrainerPurchaseMail::dispatch(
                trainer: $trainerUser,
                clientName: $customerName,
                productName: $paymentIntent->metadata->product_name ?? '',
                amount: $amount,
                purchasedAt: now()
            );

            SendCustomerPurchaseMail::dispatch(
                trainer: $trainerUser,
                clientName: $customerName,
                productName: $paymentIntent->metadata->product_name ?? '',
                paymentMethod: $paymentMethodLabel,
                amount: $amount,
                purchasedAt: now()
            );

            Log::info("Stripe payment succeeded for {$customerEmail}");
        } catch (\Throwable $e) {
            Log::error('Stripe payment succeeded handler failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
