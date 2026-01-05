use Stripe\StripeClient;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

public function register(): void
{
    $this->app->bind(StripeClient::class, function ($app) {

        $region = 'US'; // fallback

        try {
            /** @var AuthFactory $auth */
            $auth = $app->make(AuthFactory::class);

            if ($auth->guard()->check()) {
                $user = $auth->guard()->user();

                $region = $user->corporatePartner?->region ?? 'US';
            }
        } catch (\Throwable $e) {
            // CLI / Queue / Webhook safe
            $region = 'US';
        }

        $config = config("stripe_regions.regions.$region");

        if (! $config) {
            throw new \RuntimeException("Stripe region [$region] not configured.");
        }

        return new StripeClient($config['secret']);
    });
}



===

return [
    'regions' => [
        'US' => ['secret' => env('STRIPE_US_SECRET')],
        'EU' => ['secret' => env('STRIPE_EU_SECRET')],
        'IN' => ['secret' => env('STRIPE_IN_SECRET')],
    ],
];



