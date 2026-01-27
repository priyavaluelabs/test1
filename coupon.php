<?php

namespace App\Filament\Pages;

use App\Models\FodUserRole;
use App\Models\Club;
use App\Filament\Pages\BaseStripePage;
use Illuminate\Support\Facades\Auth;

class StripeOnboarding extends BaseStripePage
{
    protected static string $view = 'filament.pages.stripe.onboarding';
    protected static ?string $slug = 'stripe/onboarding';

    public ?string $accountId = null;
    public ?string $type = 'account-onboarding';
    public ?string $clientSecret = null;
    public bool $showOnboarding = false;

    public $user;
    public array $clubsWithGlofoxStatus = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function continue()
    {
        $this->showOnboarding = true;
    }

    public function mount(): void
    {
        parent::mount();
        if (! $this->stripeAvailable) {
            return;
        }

        $this->user = Auth::user();

        // Get club name with glofox verified at
        $clubs = Club::whereIn('id', $this->user->getAccessibleClubs())
            ->pluck('title', 'id');

        $userRoles = FodUserRole::where('user_id', $this->user->id)
            ->whereIn('club_id', $this->user->getAccessibleClubs())
            ->get(['club_id', 'glofox_verified_at']);

        $this->clubsWithGlofoxStatus = $userRoles->map(function ($role) use ($clubs) {
            return [
                'club_id'     => $role->club_id,
                'club_title'  => $clubs[$role->club_id] ?? '—',
                'is_verified' => ! is_null($role->glofox_verified_at),
                'verified_at' => $role->glofox_verified_at,
            ];
        })->values()->toArray();


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
                    'type'  => 'express',
                    'email' => $this->user->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers'     => ['requested' => true],
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
    }

    public function getHeading(): string
    {
        return __('stripe.personal_training_onboarding');
    }
}
