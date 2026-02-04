<?php

namespace App\Listners;

use App\Jobs\PropagateStripeProductsToTrainer;
use App\Jobs\SendTrainerOnboardedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\User;

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
            $email = $account->email 
                ?? ($account->business_profile->support_email ?? null);

            if (! $email) {
                return;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                return;
            }

            if ($account->details_submitted === true &&
                empty($account->requirements->currently_due ?? []) &&
                empty($account->requirements->past_due ?? []) &&
                ! $user->is_onboarded) {
                PropagateStripeProductsToTrainer::dispatch(
                    $account->id,
                    optional($user->corporatePartner)->stripe_secret_key
                );
            }

            Log::info("Stripe onboarding finished for {$email}");
        } catch (\Exception $e) {
            Log::error("Stripe account onboarded failed: ".$e->getMessage());
        }
    }
}
