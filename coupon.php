$paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

// Get first charge (most PaymentIntents have 1)
$charge = $paymentIntent->charges->data[0] ?? null;

if ($charge && isset($charge->payment_method_details->card)) {
    $card = $charge->payment_method_details->card;
    $brand = $card->brand; // e.g., Visa, Mastercard
    $last4 = $card->last4;
    echo "{$brand} ending in {$last4}";
}
