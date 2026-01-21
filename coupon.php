    public array $promoCodes = [];
    public ?\App\Models\User $user = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

   public function mount(?string $couponId = null): void
    {
        parent::mount();

        if (! $this->stripeAvailable) return;

        $this->couponId = $couponId;
        $this->user = Auth::user();

        $this->loadCoupon();
        $this->promoCodes = $this->getPromotionCodes($this->couponId);
    }

======================



<?php

namespace App\Filament\Traits;

use Filament\Forms;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;

trait InteractsWithStripePromoCodes
{
    protected function getPromotionCodes(string $couponId): array
    {
        $promotionCodes = $this->stripeClient()->promotionCodes->all(
            [
                'coupon' => $couponId,
                'limit'  => 50,
            ],
            [
                'stripe_account' => $this->user?->stripe_account_id,
            ]
        );

        return collect($promotionCodes->data)
            ->map(fn ($promo) => $this->mapPromotionCode($promo))
            ->values()
            ->toArray();
    }

    protected function mapPromotionCode($promo): array
    {
        $now = now()->timestamp;
        $isExpired  = $promo->expires_at && $now > $promo->expires_at;
        $isInactive = ! $promo->active;

        $validFrom = Carbon::createFromTimestamp($promo->created);
        $validTill = $promo->expires_at ? Carbon::createFromTimestamp($promo->expires_at) : null;

        $validity = $validTill
            ? "Valid {$validFrom->format('M j, Y')} - {$validTill->format('M j, Y')}"
            : "Valid from {$validFrom->format('M j, Y')}";

        $isExpired = $promo->expires_at ? now()->timestamp > $promo->expires_at : false;

        $status = match (true) {
            $isExpired  => 'Expired',
            $isInactive => 'Inactive',
            default     => 'Active',
        };

        return [
            'id'               => $promo->id,
            'code'             => $promo->code,
            'status'           => $status,
            'redemption'       => $promo->max_redemptions ? "{$promo->times_redeemed}/{$promo->max_redemptions}" : '-',
            'validity'         => $validity,
            'is_first'         => $promo->restrictions?->first_time_transaction ?? false,
            'usage'            => $promo->max_redemptions ? "{$promo->times_redeemed}/{$promo->max_redemptions} used" : '',
        ];
    }

    public function setPromoCodeActiveStatus(string $promoCodeId, bool $active): void
    {
        $this->stripeClient()->promotionCodes->update(
            $promoCodeId,
            ['active' => $active],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->notify('Promo code ' . ($active ? 'unarchived' : 'archived'));
        $this->promoCodes = collect($this->promoCodes)
            ->map(fn ($promo) => 
                $promo['id'] === $promoCodeId
                    ? array_merge($promo, ['active' => $active])
                    : $promo
            )
            ->toArray();
    }

    public function archivePromoCode(string $promoCodeId): void
    {
        $this->setPromoCodeActiveStatus($promoCodeId, false);
    }

    public function unArchivePromoCode(string $promoCodeId): void
    {
        $this->setPromoCodeActiveStatus($promoCodeId, true);
    }

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
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create promo code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getStripeCustomers(): array
    {
        $customers = $this->stripeClient()->customers->all(['limit' => 100], ['stripe_account' => $this->user?->stripe_account_id]);

        return collect($customers->data)
            ->mapWithKeys(fn ($customer) => [$customer->id => ($customer->name ?? 'No Name') . ' (' . ($customer->email ?? 'No Email') . ')'])
            ->toArray();
    }
}
