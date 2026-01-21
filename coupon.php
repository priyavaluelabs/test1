Select::make('customer_id')
    ->label('Restrict to Customer (Optional)')
    ->searchable()
    ->getSearchResultsUsing(function (string $search) {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $customers = $stripe->customers->search([
            'query' => "email:'$search' OR name:'$search'",
            'limit' => 20,
        ], [
            'stripe_account' => $this->connectedAccountId,
        ]);

        return collect($customers->data)
            ->mapWithKeys(fn ($c) => [
                $c->id => ($c->name ?? 'No Name') . ' (' . ($c->email ?? 'No Email') . ')',
            ])
            ->toArray();
    })
    ->getOptionLabelUsing(fn ($value) => $value)
    ->nullable();

=====


$payload = [
    'coupon' => $couponId,
    'code' => $data['code'],
    'expires_at' => Carbon::parse($data['expires_at'])->timestamp,
];

if (! empty($data['customer_id'])) {
    $payload['customer'] = $data['customer_id']; // cus_xxx
}

\Stripe\PromotionCode::create(
    $payload,
    ['stripe_account' => $this->connectedAccountId]
);
