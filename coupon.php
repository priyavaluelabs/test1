<?php

namespace App\Filament\Pages;

use App\Filament\Pages\BaseStripePage;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendTrainerOnboardedMail;
use App\Filament\Enum\StripeAccountStatus;
use App\Models\Club;

class StripeOnboarding extends BaseStripePage
{
    protected static string $view = 'filament.pages.stripe.onboarding';
    protected static ?string $slug = 'stripe/onboarding';

    public ?string $accountId = null;
    public ?string $type = 'account-onboarding';
    public ?string $clientSecret = null;

    public $stripeStatus;
    public array $currentlyDue = [];
    public ?\App\Models\User $user = null;

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

        $clubData = Club::whereIn('id', $this->user->getAccessibleClubs())->get();
        $clubIds   = $clubData->pluck('id')->implode(',');
        $clubNames = $clubData->pluck('title')->implode(',');

        if (! $this->user->is_onboarded) {
            if ($this->user->stripe_account_id) {
                $this->accountId = $this->user->stripe_account_id;

                $session = $this->stripeClient()->accountSessions->create([
                    'account' => $this->accountId,
                    'components' => [
                        'account_onboarding' => ['enabled' => true],
                    ],
                ]);

                $this->clientSecret = $session->client_secret;
            } else {
                $account = $this->stripeClient()->accounts->create([
                    'email' => $this->user->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers'     => ['requested' => true],
                    ],
                    'metadata' => [
                        'club_ids'   => $clubIds,
                        'club_names' => $clubNames,
                    ],
                ]);

                $this->accountId = $account->id;

                $session = $this->stripeClient()->accountSessions->create([
                    'account' => $this->accountId,
                    'components' => [
                        'account_onboarding' => ['enabled' => true],
                    ],
                ]);

                $this->clientSecret = $session->client_secret;
                $this->user->update([
                    'stripe_account_id' => $this->accountId,
                ]);
            }
        }

        // Get stripe account status
        $account = $this->stripeClient()->accounts->retrieve($this->user->stripe_account_id);
        $this->currentlyDue = $account->requirements->currently_due;
        $this->stripeStatus = $this->resolveStripeAccountStatus($account);
        if ($this->stripeStatus === StripeAccountStatus::COMPLETE) {
            $this->user->update([
                'is_onboarded' => true,
                'onboarded_at' => now(),
            ]);
            SendTrainerOnboardedMail::dispatch($this->user);
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
            ])
        ) {
            return StripeAccountStatus::REJECTED;
        }

        // Pending
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

        // Enabled
        if (
            ! empty($requirement?->eventually_due) &&
            $account->payouts_enabled === true &&
            $account->charges_enabled === true &&
            empty($requirement?->current_deadline)
        ) {
            return StripeAccountStatus::ENABLED;
        }

        // Complete
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
