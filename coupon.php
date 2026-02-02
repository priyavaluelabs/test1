Forms\Components\Select::make('customer_id')
                    ->label(__('stripe.restrict_customer'))
                    ->searchable()
                    ->options(fn () => $this->getStripeCustomers())
                    ->placeholder(__('stripe.all_customers'))
                    ->nullable(),
