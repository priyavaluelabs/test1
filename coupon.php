Forms\Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('stripe.coupon_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                ]),
