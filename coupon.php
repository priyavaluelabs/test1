public function createPromoCode(): void
{
    $this->mountAction('createPromoCodeAction');
}

public function createPromoCodeAction(): Actions\Action
{
    return Actions\Action::make('createPromoCodeAction')
        ->label('Add Promo Code')
        ->modalHeading('Add a Promo Code')
        ->modalSubmitActionLabel('Finish')
        ->modalWidth('lg')
        ->form($this->promoCodeFormSchema())
        ->action(fn(array $data) => $this->handleCreatePromoCode($data));
}

private function promoCodeFormSchema(): array
{
    return [
        Forms\Components\TextInput::make('code')
            ->label('Code')
            ->placeholder('e.g. SPRING10')
            ->required()
            ->unique(ignoreRecord: true)
            ->helperText('Customers will enter this code at checkout'),

        Forms\Components\Grid::make(2)->schema([
            Forms\Components\DatePicker::make('expires_at')
                ->label('Expire Date')
                ->native(false)
                ->nullable(),

            Forms\Components\Select::make('customer_id')
                ->label('Restrict to Customer (Optional)')
                ->searchable()
                ->options(fn () => $this->getStripeCustomers())
                ->placeholder('All customers')
                ->nullable(),
        ]),

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
    ];
}

private function handleCreatePromoCode(array $data): void
{
    try {
        $payload = [
            'promotion' => ['type' => 'coupon', 'coupon' => $this->couponId],
            'code' => strtoupper($data['code']),
        ];

        if (!empty($data['max_redemptions'])) {
            $payload['max_redemptions'] = (int)$data['max_redemptions'];
        }

        if (!empty($data['customer_id'])) {
            $payload['customer'] = $data['customer_id'];
        }

        if (!empty($data['expires_at'])) {
            $payload['expires_at'] = Carbon::parse($data['expires_at'])->timestamp;
        }

        if (!empty($data['first_purchase_only'])) {
            $payload['restrictions'] = ['first_time_transaction' => true];
        }

        $this->stripeClient()->promotionCodes->create(
            $payload,
            ['stripe_account' => $this->user?->stripe_account_id]
        );

        $this->notify('Promo code created');
        $this->loadPromoCodes();

    } catch (\Exception $e) {
        Notification::make()
            ->title('Failed to create promo code')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}
