// Validate region exists in config
if (!isset(config('stripe_regions.regions')[$region])) {
    return response("Invalid region", 400);
}

$webhookSecret = config("stripe_regions.regions.$region.webhook_secret");

