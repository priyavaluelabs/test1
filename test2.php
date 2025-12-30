public function getCustomerProducts($userId, $trainerId)
{
    $trainer = UserPortal::find($trainerId);

    if (! $trainer) {
        return [
            'products' => [],
            'trainer'  => null,
        ];
    }

    $profile = PTBillingUserStripeProfile::where('user_id', $userId)
        ->where('stripe_account_id', $trainer->stripe_account_id)
        ->first();

    if (! $profile) {
        return [
            'products' => [],
            'trainer'  => $this->formatTrainer($trainer),
        ];
    }

    $symbol = '$';
    if (! empty($trainer->corp_partner_id)) {
        $symbol = $this->getCurrencySymbol($trainer->corp_partner_id);
    }

    $paymentIntents = $this->stripe->paymentIntents->all(
        ['customer' => $profile->stripe_customer_id],
        ['stripe_account' => $trainer->stripe_account_id]
    );

    $products = collect($paymentIntents->data)
        ->filter(function ($intent) {
            return ! empty($intent->metadata->product_name);
        })
        ->map(function ($intent) use ($symbol) {
            $amount = ($intent->amount_received && $intent->amount_received > 0)
                ? $intent->amount_received
                : $intent->amount;

            return [
                'product_name'  => $intent->metadata->product_name,
                'product_price' => ! empty($intent->metadata->product_price)
                    ? $intent->metadata->product_price
                    : number_format($amount / 100, 2),
                'currency'      => strtoupper($intent->currency),
                'symbol'        => $symbol,
            ];
        })
        ->unique('product_name')
        ->values()
        ->toArray();

    return [
        'products' => $products,
        'trainer'  => $this->formatTrainer($trainer),
    ];
}
