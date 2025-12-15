// App\Providers\AppServiceProvider.php

use Stripe\StripeClient;

public function register(): void
{
    $this->app->singleton(StripeClient::class, function () {
        return new StripeClient(config('services.stripe.secret'));
    });
}


=======

public function mount(StripeClient $stripe): void
{
    $session = $stripe->accountSessions->create([
        // ...
    ]);
}
