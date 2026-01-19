Grid::make(3)
    ->schema([
        Group::make([
            ToggleButtons::make('discount_type')
                ->label(null)
                ->options([
                    'percentage' => 'Percentage',
                    'fixed' => 'Fixed amount',
                ])
                ->inline()
                ->live()
                ->extraAttributes([
                    'class' => 'w-fit',
                ]),
        ]),

        TextInput::make('value')
            ->label('Value')
            ->numeric()
            ->suffix(fn ($get) =>
                $get('discount_type') === 'percentage' ? '%' : '$'
            )
            ->disabled(fn ($get) => ! filled($get('discount_type')))
            ->extraInputAttributes([
                'class' => 'max-w-xs h-9',
            ]),
    ]);
