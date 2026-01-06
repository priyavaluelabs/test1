<?php namespace Klyp\Nomergy\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Klyp\Nomergy\Models\User;
use Klyp\Nomergy\Models\UserProfile;
use Klyp\Nomergy\Models\UserPortal;
use Klyp\Nomergy\Models\UserProfileBasic;

use Klyp\Nomergy\Observers\UserObserver;
use Klyp\Nomergy\Observers\UserProfileObserver;
use Klyp\Nomergy\Observers\UserProfileBasicObserver;

use Klyp\Nomergy\Services\Stripe\PTbillingBaseStripeService;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        User::observe(UserObserver::class);
        UserProfile::observe(UserProfileObserver::class);
        UserProfileBasic::observe(UserProfileBasicObserver::class);

		$this->configureEmailInterception();
	}

	/**
	 * Configure email interception for non-production environments
	 *
	 * @return void
	 */
	protected function configureEmailInterception()
	{
		if (app()->environment() !== 'production') {
			$devEmails = env('DEV_EMAIL_RECIPIENTS');

			if ($devEmails) {
				$recipients = explode(',', $devEmails);
				$recipients = array_map('trim', $recipients);
				Mail::alwaysTo($recipients);
			}
		}
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->when(PTbillingBaseStripeService::class)
			->needs('$region')
			->give(function ($app) {
				/** @var Request $request */
				$request = $app->make(Request::class);

				// ✔ Refactored trainer_id resolution
				$trainerId = $request->route('trainer_id')
					?? $request->route('trainer')
					?? $request->input('trainer_id');

				if (! $trainerId) {
					throw new \RuntimeException('trainer_id is required');
				}

				print_r($trainerId);
				die;

				$trainer = UserPortal::findOrFail($trainerId);

				$service->__construct('US');
        	});
	}
}


=============

<?php

namespace Klyp\Nomergy\Services\Stripe;

use Stripe\StripeClient;

abstract class PTbillingBaseStripeService
{
    protected StripeClient $stripe;

    protected function __construct(string $region)
    {
        $key = config("services.stripe.secret_key");

        $this->stripe = new StripeClient($key);
    }
}

====

class PTBillingProductService extends PTbillingBaseStripeService

====

[2026-01-06 03:31:53] production.ERROR: Illuminate\Contracts\Container\BindingResolutionException: Target [Klyp\Nomergy\Services\Stripe\PTBillingProductService] is not instantiable while building [Klyp\Nomergy\Http\Controllers\Stripe\ApiPTBillingProductController]. in D:\myproject\liftbrand-app-new\vendor\laravel\framework\src\Illuminate\Container\Container.php:978
