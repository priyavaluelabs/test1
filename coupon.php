<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Services\Stripe\StripeService;
use Stripe\StripeClient;
use App\Filament\Enum\Role;

abstract class BaseStripePage extends Page
{
    public bool $stripeAvailable = false;
    public ?string $stripeErrorMessage = null;

    protected ?StripeClient $stripe = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole(Role::ZoneInstructor);
    }

    public function mount(): void
    {
        $this->validateStripe();
    }

    protected function validateStripe(): void
    {
        $stripeService = app(StripeService::class);

        $this->stripe = $stripeService->client();

        if (! $this->stripe) {
            $this->failStripe(__('stripe.invalid_stripe_key'));
            return;
        }

        try {
            $this->stripe->accounts->retrieve();
            $this->stripeAvailable = true;
        } catch (\Throwable) {
            $this->failStripe(__('stripe.invalid_stripe_key'));
        }
    }

    protected function stripeClient(): ?StripeClient
    {
        if ($this->stripe) {
            return $this->stripe;
        }

        $stripeService = app(StripeService::class);

        return $this->stripe = $stripeService->client();
    }

    protected function failStripe(string $message): void
    {
        $this->stripeAvailable = false;
        $this->stripeErrorMessage = $message;
    }
}
