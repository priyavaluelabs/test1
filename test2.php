<?php

namespace App\Services\Stripe;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\NomergyUser;
use App\Models\PTBillingUserTrainer;
use Stripe\StripeClient;
use Stripe\Customer;

class HandlePaymentIntentSucceeded
{
    public function handle($event)
    {
        try {
            app(StripeClient::class);

            $connectedAccountId = $event->account ?? null;

            if (!$connectedAccountId) {
                return;
            }

            // Trainer by connected account id
            $trainerUser = User::where('stripe_account_id', $connectedAccountId)->first();
            if (!$trainerUser) {
                return;
            }

            $paymentIntent = $event->data->object;

            /**
             * Retrieve Customer from connected account
             */
            $customer = Customer::retrieve(
                $paymentIntent->customer,
                ['stripe_account' => $connectedAccountId]
            );

            $stripeCustomerId = $customer->id;
            $customerEmail = $customer->email ?? null;
            $customerName  = $customer->name ?? 'Customer';

            $nomergyUserId = NomergyUser::where('stripe_customer_id', $stripeCustomerId)->first()->id;
            $trainerId = $trainerUser->id;

            /**
             * Add entry to PTBillingUserTrainer
             */
            if ($nomergyUserId) {
                PTBillingUserTrainer::firstOrCreate(
                    [
                        'user_id' => $nomergyUserId,
                        'trainer_id' => $trainerId,
                    ]
                );
            }

            /**
             * Send Emails
             */
            if ($trainerUser->email) {
                Mail::to($trainerUser->email)->send(new \App\Mail\TrainerPurchaseMail(
                    $trainerUser->name,
                    $customerName,
                    $customerEmail,
                    number_format($paymentIntent->amount / 100, 2),
                    strtoupper($paymentIntent->currency)
                ));
            }

            if ($customerEmail) {
                Mail::to($customerEmail)->send(new \App\Mail\CustomerPurchaseMail(
                    $customerName,
                    $trainerUser->name,
                    number_format($paymentIntent->amount / 100, 2),
                    strtoupper($paymentIntent->currency)
                ));
            }

        } catch (\Exception $e) {
            Log::error("Failed to process payment_intent.succeeded", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
