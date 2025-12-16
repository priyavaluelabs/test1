<?php

namespace App\Listners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleStripeAccountOnboarded implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 30;

    public function handle($event)
    {
        $stripeEvent = $event->event;
        $account = $stripeEvent->data->object;

        try {
            $email = $account->email 
                ?? ($account->business_profile->support_email ?? null);

            if (!$email) {
                return;
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return;
            }

            $onboardingCompleted =
                $account->details_submitted &&
                $account->charges_enabled &&
                $account->payouts_enabled;

            if ($onboardingCompleted) {
                $user->update([
                    'is_onboarded' => true,
                    'onboarded_at' => now(),
                ]);

                // Send onboarding email asynchronously
                Mail::to($user->email)
                    ->queue(new \App\Mail\TrainerOnboardedMail($user));

                // Trigger product propagation asynchronously
                dispatch(function () use ($account) {
                    app(\App\Services\Stripe\ProductPropagationService::class)
                        ->propagateToTrainer($account->id);
                });
            }
            Log::info("Stripe onboarding finished for {$email}");
        } catch (\Exception $e) {
            Log::error("HandleStripeAccountOnboarded failed: ".$e->getMessage());
        }
    }
}


If you want to process this async, instead of using this helper, use Job and then dispatch that job.