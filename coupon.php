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
