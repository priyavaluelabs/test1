<?php

namespace App\Listners;

use App\Jobs\PropagateStripeProductsToTrainer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Stripe\StripeClient;

class HandleStripeAccountOnboarded implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 30;

    public function handle($event)
    {
        $stripeEvent = $event->event;
        $account = $stripeEvent->data->object;

        try {
            // 1. Resolve email
            $email = $account->email
                ?? ($account->business_profile->support_email ?? null);

            if (! $email) {
                return;
            }

            // 2. Resolve user
            $user = User::where('email', $email)->first();

            if (! $user) {
                return;
            }

            // 3. Check Stripe account readiness
            if (
                $account->details_submitted !== true ||
                ! empty($account->requirements->currently_due ?? []) ||
                ! empty($account->requirements->past_due ?? []) ||
                $user->is_onboarded
            ) {
                return;
            }

            // 4. Stripe client (platform key)
            $stripe = new StripeClient(
                optional($user->corporatePartner)->stripe_secret_key
            );

            // 5. Check if products already exist on connected account
            $products = $stripe->products->all(
                ['limit' => 1],
                ['stripe_account' => $account->id]
            );

            if (! empty($products->data)) {
                Log::info("Skipping product propagation – products already exist for {$account->id}");
                return;
            }

            // 6. Dispatch propagation job
            PropagateStripeProductsToTrainer::dispatch(
                $account->id,
                optional($user->corporatePartner)->stripe_secret_key
            );

            Log::info("Stripe onboarding finished & products propagated for {$email}");

        } catch (\Exception $e) {
            Log::error("Stripe account onboarded failed: " . $e->getMessage(), [
                'account_id' => $account->id ?? null,
            ]);
        }
    }
}
