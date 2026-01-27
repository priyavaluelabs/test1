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

        $clubs = Club::whereIn('id', $this->user->getAccessibleClubs())
            ->pluck('title', 'id');
        $userRoles = FodUserRole::where('user_id', $this->user->id)
            ->whereIn('club_id', $this->user->getAccessibleClubs())
            ->get(['club_id', 'glofox_verified_at']);
        $result = $userRoles->map(function ($role) use ($clubs) {
            return [
                'club_id'            => $role->club_id,
                'club_title'         => $clubs[$role->club_id] ?? null,
                'glofox_verified_at' => $role->glofox_verified_at,
            ];
        });

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


================

     <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-700">
                                    {{ $user->location_name ?? '—' }}
                                </span>

                                <span class="inline-flex items-center gap-1 px-3 py-1 text-sm
                                            bg-green-100 text-green-700 rounded-full">
                                    ✔ Glofox Verified
                                </span>
                            </div>
