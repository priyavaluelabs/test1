Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

Webhook route uses default API rate limiting (60/min), which may be too restrictive for Stripe webhooks during high traffic periods. Exclude webhook from rate limiting or increase this limit