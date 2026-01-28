use Stripe\StripeClient;

$stripe = new StripeClient(config('services.stripe.secret'));

$product = $stripe->products->retrieve(
    $productId,
    [], // no params
    ['stripe_account' => $stripeAccountId] // optional, for Connect
);
