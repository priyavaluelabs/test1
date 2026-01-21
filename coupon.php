public function createPromoCodeAction(): Actions\Action
{
    return Actions\Action::make('createPromoCodeAction')
        ->label('Add Promo Code')
        ->modalHeading('Add a Promo Code')
        ->modalSubmitActionLabel('Finish')
        ->modalWidth('lg')
        ->form([
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->placeholder('e.g. SPRING10')
                ->required()
                ->uppercase()
                ->unique(ignoreRecord: true)
                ->helperText('Customers will enter this code at checkout'),

            Forms\Components\DatePicker::make('expires_at')
                ->label('Expire Date')
                ->native(false)
                ->nullable(),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('max_redemptions')
                    ->label('Total Limit')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('Unlimited')
                    ->nullable(),

                Forms\Components\TextInput::make('per_customer_limit')
                    ->label('Limit Per Customer')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
            ]),

            Forms\Components\Checkbox::make('first_purchase_only')
                ->label('Limit to first purchase')
                ->columnSpanFull(),
        ])
        ->action(function (array $data) {
            try {
                $payload = [
                    'coupon' => $this->couponId,
                    'code'   => strtoupper($data['code']),
                ];

                // Total usage limit
                if (! empty($data['max_redemptions'])) {
                    $payload['max_redemptions'] = (int) $data['max_redemptions'];
                }

                // Expiry date (Stripe expects timestamp)
                if (! empty($data['expires_at'])) {
                    $payload['expires_at'] = \Carbon\Carbon::parse($data['expires_at'])->timestamp;
                }

                // First purchase only (Stripe restriction)
                if (! empty($data['first_purchase_only'])) {
                    $payload['restrictions'] = [
                        'first_time_transaction' => true,
                    ];
                }

                $this->stripeClient()
                    ->promotionCodes
                    ->create($payload);

                \Filament\Notifications\Notification::make()
                    ->title('Promo code created')
                    ->success()
                    ->send();

                $this->loadPromoCodes(); // Refresh promo codes list

            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Failed to create promo code')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        });
}
