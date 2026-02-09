<?php

namespace App\Filament\Pages;

use App\Filament\Enum\StripeAccountStatus;
use App\Jobs\SendTrainerOnboardedMail;
use App\Jobs\VerifyGlofoxMember;
use App\Services\Stripe\ClubGlofoxStatusService;
use App\Models\Club;
use Illuminate\Support\Facades\Auth;

class StripeOnboarding extends BaseStripePage
{
    protected static string $view = 'filament.pages.stripe.onboarding';
    protected static ?string $slug = 'stripe/onboarding';

    public ?string $type = 'account-onboarding';
    public ?string $clientSecret = null;

    public ?StripeAccountStatus $stripeStatus = null;
    public array $currentlyDue = [];
    public ?\App\Models\User $user = null;

    public bool $showOnboarding = false;
    public bool $isUserGlofoxFullyVerified = false;
    public bool $clubNotVerifiedMessage = false;

    public array $clubsWithGlofoxStatus = [];

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

        $this->loadGlofoxStatus();
        if ($this->isUserGlofoxFullyVerified) {
            $this->showOnboarding = true;
            $this->ensureStripeAccount();
            $this->syncStripeAccountStatus();
        }
    }

    public function verify(): void
    {
        $this->loadGlofoxStatus();

        if (! $this->isUserGlofoxFullyVerified) {
            $this->clubNotVerifiedMessage = true;
        }
    }

    public function continue(): void
    {
        $this->showOnboarding = true;
        $this->ensureStripeAccount();
        $this->syncStripeAccountStatus();
    }

    protected function loadGlofoxStatus(): void
    {
        VerifyGlofoxMember::dispatch($this->user);

        $this->dispatch('glofox-verified')->to('payment-tab');

        $service = app(ClubGlofoxStatusService::class);

        $this->clubsWithGlofoxStatus = $service->getForUser($this->user);
        $this->isUserGlofoxFullyVerified = $service->isUserGlofoxFullyVerified($this->user);
    }

    protected function ensureStripeAccount(): void
    {
        if ($this->user->is_onboarded) {
            return;
        }

        if ($this->user->stripe_account_id) {
            $this->createAccountSession($this->user->stripe_account_id);
            return;
        }

        $account = $this->createStripeAccount();

        $this->user->update([
            'stripe_account_id' => $account->id,
        ]);

        $this->createAccountSession($account->id);
    }

    protected function createStripeAccount(): object
    {
        $mainAccount = $this->stripeClient()->accounts->retrieve();
        $clubs = Club::whereIn('id', $this->user->getAccessibleClubs())->get();

        return $this->stripeClient()->accounts->create([
            'email'   => $this->user->email,
            'country' => $mainAccount->country,

            'controller' => [
                'stripe_dashboard' => ['type' => 'none'],
                'fees' => ['payer' => 'application'],
                'losses' => ['payments' => 'application'],
                'requirement_collection' => 'application',
            ],

            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],

            'metadata' => [
                'club_ids'   => $clubs->pluck('id')->implode(',') ?: null,
                'club_names' => $clubs->pluck('title')->implode(',') ?: null,
            ],
        ]);
    }

    protected function createAccountSession(string $accountId): void
    {
        $session = $this->stripeClient()->accountSessions->create([
            'account' => $accountId,
            'components' => [
                'account_onboarding' => ['enabled' => true],
            ],
        ]);

        $this->clientSecret = $session->client_secret;
    }

    protected function syncStripeAccountStatus(): void
    {
        if (! $this->user->stripe_account_id) {
            return;
        }

        $account = $this->stripeClient()
            ->accounts
            ->retrieve($this->user->stripe_account_id);

        $this->currentlyDue = $account->requirements->currently_due ?? [];
        $this->stripeStatus = StripeAccountStatus::fromStripeAccount($account);

        if (
            $this->stripeStatus === StripeAccountStatus::COMPLETE &&
            ! $this->user->is_onboarded
        ) {
            $this->markUserOnboarded();
        }
    }

    protected function markUserOnboarded(): void
    {
        $this->user->update([
            'is_onboarded' => true,
            'onboarded_at' => now(),
        ]);

        SendTrainerOnboardedMail::dispatch($this->user);
    }

    public function getHeading(): string
    {
        return __('stripe.personal_training_onboarding');
    }
}
