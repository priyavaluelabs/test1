<?php namespace Klyp\Nomergy\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Klyp\Nomergy\Models\User;
use Klyp\Nomergy\Models\UserProfile;
use Klyp\Nomergy\Models\UserProfileBasic;

use Klyp\Nomergy\Observers\UserObserver;
use Klyp\Nomergy\Observers\UserProfileObserver;
use Klyp\Nomergy\Observers\UserProfileBasicObserver;

use Stripe\StripeClient;

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
		$this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('services.stripe.secret_key'));
        });
	}
}
