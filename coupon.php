<?php

namespace App\Filament\Pages;

use App\Filament\Enum\StripeAccountStatus;
use App\Filament\Pages\BaseStripePage;
use App\Jobs\SendTrainerOnboardedMail;
use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StripeOnboarding extends BaseStripePage
{
    protected static string $view = 'filament.pages.stripe.onboarding';
    protected static ?string $slug = 'stripe/onboarding';

    public ?string $accountId = null;
    public ?string $type = 'account-onboarding';
    public ?string $clientSecret = null;

    public ?StripeAccountStatus $stripeStatus = null;
    public array $currentlyDue = [];
    public ?User $user = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        parent::mount();

        if (! $this->stripeAvailable) {
            return;
        }

        $this->user = Auth::user();

        if (! $this->user) {
            return;
        }

        // Create / reuse Stripe account & onboarding session
        if (! $this->user->is_onboarded) {
            $this->initialiseStripeAccount();
        }

        // If still no account ID, stop
        if (! $this->user->stripe_account_id) {
            return;
        }

        // Retrieve Stripe account status
        $account = $this->stripeClient()
            ->accounts
            ->retrieve($this->user->stripe_account_id);

        $requirements = $account->requirements ?? null;

        $this->currentlyDue = $requirements->currently_due ?? [];
        $this->stripeStatus = $this->resolveStripeAccountStatus($account);

        // Mark onboarded ONLY ONCE
        if (
            $this->stripeStatus === StripeAccountStatus::COMPLETE &&
            ! $this->user->is_onboarded
        ) {
            $this->user->update([
                'is_onboarded' => true,
                'onboarded_at' => now(),
            ]);

            SendTrainerOnboardedMail::dispatch($this->user);
        }
    }

    protected function initialiseStripeAccount(): void
    {
        $this->accountId = $this->user->stripe_account_id;

        // Create Stripe account if missing
        if (! $this->accountId) {
            $clubs = Club::whereIn('id', $this->user->getAccessibleClubs())->get();

            $account = $this->stripeClient()->accounts->create([
                'email' => $this->user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers'     => ['requested' => true],
                ],
                'metadata' => array_filter([
                    'club_ids'   => $clubs->pluck('id')->implode(',') ?: null,
                    'club_names' => $clubs->pluck('title')->implode(',') ?: null,
                ]),
            ]);

            $this->accountId = $account->id;

            $this->user->update([
                'stripe_account_id' => $this->accountId,
            ]);
        }

        // Create onboarding session only when needed
        if (! $this->clientSecret) {
            $session = $this->stripeClient()
                ->accountSessions
                ->create([
                    'account' => $this->accountId,
                    'components' => [
                        'account_onboarding' => ['enabled' => true],
                    ],
                ]);

            $this->clientSecret = $session->client_secret;
        }
    }

    public function getHeading(): string
    {
        return __('stripe.personal_training_onboarding');
    }

    protected function resolveStripeAccountStatus(object $account): StripeAccountStatus
    {
        $requirement = $account->requirements ?? null;

        // Rejected
        if (
            isset($requirement->disabled_reason) &&
            in_array($requirement->disabled_reason, [
                'rejected.fraud',
                'rejected.listed',
                'rejected.terms_of_service',
                'rejected.other',
            ], true)
        ) {
            return StripeAccountStatus::REJECTED;
        }

        // Pending verification
        if ($requirement?->disabled_reason === 'requirements.pending_verification') {
            return StripeAccountStatus::PENDING;
        }

        // Restricted
        if (
            ! empty($requirement?->currently_due) &&
            ($account->payouts_enabled === false || $account->charges_enabled === false)
        ) {
            return StripeAccountStatus::RESTRICTED;
        }

        // Restricted soon
        if (
            ! empty($requirement?->currently_due) &&
            ! empty($requirement?->current_deadline)
        ) {
            return StripeAccountStatus::RESTRICTED_SOON;
        }

        // Enabled (but still has future requirements)
        if (
            ! empty($requirement?->eventually_due) &&
            $account->payouts_enabled === true &&
            $account->charges_enabled === true &&
            empty($requirement?->current_deadline)
        ) {
            return StripeAccountStatus::ENABLED;
        }

        // Fully complete
        if (
            empty($requirement?->eventually_due) &&
            $account->payouts_enabled === true &&
            $account->charges_enabled === true
        ) {
            return StripeAccountStatus::COMPLETE;
        }

        return StripeAccountStatus::UNKNOWN;
    }
}
