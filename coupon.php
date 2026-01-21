wire:click="createPromoCode"



public function createPromoCode(): void
{
    $this->mountAction('createPromoCodeAction');
}

public function createPromoCodeAction(): Actions\Action
{
    return Actions\Action::make('createPromoCodeAction')
        ->label('Add Promo Code')
        ->modalHeading('Create Promo Code')
        ->modalSubmitActionLabel('Create')
        ->form([
            Forms\Components\TextInput::make('code')
                ->label('Promo Code')
                ->required()
                ->uppercase()
                ->unique(ignoreRecord: true)
                ->helperText('Customers will enter this code at checkout'),

            Forms\Components\TextInput::make('max_redemptions')
                ->label('Max redemptions')
                ->numeric()
                ->minValue(1)
                ->nullable(),

            Forms\Components\DatePicker::make('expires_at')
                ->label('Expiration date')
                ->nullable(),
        ])
        ->action(function (array $data) {
            try {
                $payload = [
                    'coupon' => $this->couponId,
                    'code' => strtoupper($data['code']),
                ];

                if (! empty($data['max_redemptions'])) {
                    $payload['max_redemptions'] = (int) $data['max_redemptions'];
                }

                if (! empty($data['expires_at'])) {
                    $payload['expires_at'] = Carbon::parse($data['expires_at'])->timestamp;
                }

                $this->stripeClient()
                    ->promotionCodes
                    ->create($payload);

                Notification::make()
                    ->title('Promo code created')
                    ->success()
                    ->send();

                $this->loadPromoCodes(); // refresh table

            } catch (\Exception $e) {
                Notification::make()
                    ->title('Failed to create promo code')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        });
}
