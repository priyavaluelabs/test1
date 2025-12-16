<?php

namespace App\Jobs;

use App\Mail\TrainerOnboardedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTrainerOnboardedMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh model in case of delay
        $user = $this->user->fresh();

        if (! $user) {
            Log::warning('SendTrainerOnboardedMail: User not found');
            return;
        }

        Mail::to($user->email)
            ->send(new TrainerOnboardedMail($user));

        Log::info('Trainer onboarded mail sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}

==============

<?php

namespace App\Jobs;

use App\Services\Stripe\ProductPropagationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PropagateStripeProductsToTrainer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $stripeAccountId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Stripe product propagation', [
            'account_id' => $this->stripeAccountId,
        ]);

        try {
            // Resolve service INSIDE job
            $service = app(ProductPropagationService::class);

            $service->propagateToTrainer($this->stripeAccountId);

            Log::info('Stripe product propagation completed', [
                'account_id' => $this->stripeAccountId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe product propagation failed', [
                'account_id' => $this->stripeAccountId,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to allow retries
            throw $e;
        }
    }
}



======


<?php

namespace App\Listeners;

use App\Events\StripeAccountOnboarded;
use App\Jobs\PropagateStripeProductsToTrainer;
use App\Jobs\SendTrainerOnboardedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleStripeAccountOnboarded implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 30;

    public function handle(StripeAccountOnboarded $event): void
    {
        $account = $event->event->data->object;

        $email = $account->email
            ?? ($account->business_profile->support_email ?? null);

        if (! $email) {
            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $onboardingCompleted =
            $account->details_submitted &&
            $account->charges_enabled &&
            $account->payouts_enabled;

        if (! $onboardingCompleted) {
            return;
        }

        // ✅ Sync-safe DB update only
        $user->update([
            'is_onboarded' => true,
            'onboarded_at' => now(),
        ]);

        // ✅ ASYNC: send mail
        SendTrainerOnboardedMail::dispatch($user)
            ->onQueue('emails');

        // ✅ ASYNC: product propagation
        PropagateStripeProductsToTrainer::dispatch($account->id)
            ->onQueue('stripe-long');

        Log::info("Stripe onboarding async jobs dispatched", [
            'user' => $user->id,
            'account' => $account->id,
        ]);
    }
}
