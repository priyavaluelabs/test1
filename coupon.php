$paymentIntent = $this->stripeClient()->paymentIntents->retrieve('pi_3SwOOJB0UJ8WBOPV0NpVViBU', [],  ['stripe_account' => 'acct_1SslGjB0UJ8WBOPV']);
        $paymentMethodId = $paymentIntent->payment_method;
        $paymentMethod = $this->stripeClient()->paymentMethods->retrieve($paymentMethodId, [],  ['stripe_account' => 'acct_1SslGjB0UJ8WBOPV']);

        if ($paymentMethod->type === 'card') {
            $card = $paymentMethod->card;
            
            SendCustomerPurchaseMail::dispatch(
                trainer: $this->user,
                clientName: 'testing',
                productName: 'AUD Product',
                paymentMethod: "{$card->brand} ending in {$card->last4}",
                amount: '200.00',
                purchasedAt: now()
            );
        }


===


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
    protected $platform;

    public function handle($event)
    {
        $stripeEvent = $event->event;
        $connectedAccountId = $stripeEvent->account ?? null;
        try {
            if (! $connectedAccountId) {
                return;
            }

            $trainerUser = User::where('stripe_account_id', $connectedAccountId)->first();

            if (! $trainerUser) {
                return;
            }
            
            $trainerStripeSecretKey = optional($trainerUser->corporatePartner)->stripe_secret_key;
            if (! $trainerStripeSecretKey) {
                return;
            }

            // Create ONCE per job
            $this->platform = new StripeClient($trainerStripeSecretKey);
            $paymentIntent = $stripeEvent->data->object;

            $customer = $this->platform->customers->retrieve(
                $paymentIntent->customer,
                [],
                ['stripe_account' => $connectedAccountId]
            );
         
            $stripeCustomerId = $customer->id;
            $customerEmail = $customer->email ?? null;
            $customerName  = $customer->name ?? 'Customer';

            $userStripeProfileExist = PTBillingUserStripeProfile::where('stripe_customer_id', $stripeCustomerId)
                ->where('stripe_account_id', $connectedAccountId)
                ->first();

            if ($userStripeProfileExist) {
                PTBillingUserTrainer::firstOrCreate(
                    [
                        'user_id' => $userStripeProfileExist->user_id,
                        'trainer_id' => $trainerUser->id,
                    ]
                );
            }

            //Add punch card
            $punchCardData =  [
                'user_id' => $userStripeProfileExist->user_id,
                'trainer_id' => $trainerUser->id,
                'product_name' => $paymentIntent->metadata->product_name,
                'total_session' => $paymentIntent->metadata->session_count,
                'purchased_at'   => now(),
            ];
            PTBillingUserPunchCard::Create($punchCardData);
            
            // Get currency symbol
            $currencySymbol = optional($trainerUser->corporatePartner)->currency_symbol ?? '$';

            // Get payment method
            $paymentMethod = $paymentIntent->payment_method_types[0] ?? 'card';

            // Send trainer purchase email
            SendTrainerPurchaseMail::dispatch(
                trainer: $trainerUser,
                clientName: $customerName,
                productName: $paymentIntent->metadata->product_name,
                amount: $currencySymbol.number_format($paymentIntent->amount / 100, 2),
                purchasedAt: now()
            );

            // Send customer purchase email
            SendCustomerPurchaseMail::dispatch(
                trainer: $trainerUser,
                clientName: $customerName,
                productName: $paymentIntent->metadata->product_name,
                paymentMethod: $paymentMethod,
                amount: $currencySymbol.number_format($paymentIntent->amount / 100, 2),
                purchasedAt: now()
            );

           Log::info("Stripe payment success for {$customerEmail}");
        } catch (\Exception $e) {
            Log::error("Stripe payment successed failed: ".$e->getMessage());
        }
    }
}
