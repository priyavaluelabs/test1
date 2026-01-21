<?php

namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Carbon\Carbon;

class ManageDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.pages.stripe.discount.manage';
    protected static ?string $slug = 'stripe/discounts/{couponId}';

    public ?string $couponId = null;

    /** Coupon exists */
    public bool $isEdit = false;

    /** User clicked Edit */
    public bool $isEditing = false;

    public array $formData = [
        'name' => null,
        'products' => [],
        'discount_type' => 'percentage',
        'value' => null,
        'description' => null,
    ];

    
    public array $promoCodes = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Detect create / view
     */
    public function mount(?string $couponId = null): void
    {
        parent::mount();

        if (! $this->stripeAvailable) {
            return;
        }

        $this->couponId = $couponId;
        $this->isEdit = $couponId !== null && $couponId !== 'create';

        if ($this->isEdit) {
            $this->loadCoupon();
            $this->loadPromoCodes();
            $this->isEditing = false;
        } else {
            $this->isEditing = true;
        }
    }

    /**
     * Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->statePath('formData')
            ->schema([
                Forms\Components\Section::make('Discount Details')
                ->schema([
                    // NAME
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name (appears on receipts)')
                            ->required()
                            ->disabled(fn () => $this->isEdit && ! $this->isEditing)
                            ->columnSpan(2),
                    ]),

                    // PRODUCTS (never editable after create)
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\Select::make('products')
                            ->label('Product(s)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => [
                                'all' => 'All Products',
                            ] + $this->getStripeProducts())
                            ->disabled(fn () => $this->isEdit)
                            ->columnSpan(2),
                    ]),

                    // TYPE + VALUE
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\ToggleButtons::make('discount_type')
                            ->label('Discount Type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->inline()
                            ->disabled(fn () => $this->isEdit),

                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->disabled(fn () => $this->isEdit),
                    ]),

                    // DESCRIPTION
                    Forms\Components\Textarea::make('description')
                        ->label('Description (optional)')
                        ->rows(4)
                        ->disabled(fn () => $this->isEdit && ! $this->isEditing)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    protected function loadCoupon(): void
    {
        $coupon = $this->stripeClient()->coupons->retrieve($this->couponId);

        $productIds = isset($coupon->metadata->product_ids) ?
            explode(',', $coupon->metadata->product_ids)
            : ['all'];
        $products = $this->getStripeProducts();

        $this->formData = [
            'name' => $coupon->name,
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $coupon->percent_off ?? ($coupon->amount_off / 100),
            'products' => $productIds,
            'products_names' => implode(', ', array_map(fn($id) => $products[$id] ?? $id, $productIds)),
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
    }

    /**
     * FILAMENT DELETE ACTION (used inside Blade)
     */
    public function deleteAction(): Actions\Action
    {
        return Actions\Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Coupon')
            ->modalDescription('Are you sure you want to delete this coupon? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete')
            ->action(function () {
                try {
                    $this->stripeClient()
                        ->coupons
                        ->delete($this->couponId);

                    Notification::make()
                        ->title('Coupon deleted successfully')
                        ->success()
                        ->send();

                    $this->redirect('/stripe/discounts');

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Unable to delete coupon')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
    
    public function save(): void
    {
        $data = $this->formData;

        // UPDATE
        if ($this->isEdit) {
            $this->stripeClient()->coupons->update($this->couponId, [
                'name' => $data['name'],
                'metadata' => [
                    'description' => $data['description'] ?? '',
                ],
            ]);

            Notification::make()
                ->title('Coupon updated successfully')
                ->success()
                ->send();

            $this->isEditing = false;
            return;
        }

        // CREATE
        $couponData = [
            'name' => $data['name'],
            'duration' => 'once',
            'metadata' => [
                'description' => $data['description'] ?? '',
            ]
        ];

        if (! in_array('all', $data['products'])) {
            $couponData['applies_to'] = [
                'products' => $data['products'],
            ];
            $couponData['metadata']['product_ids'] = implode(',', $data['products']);
        }

        if ($data['discount_type'] === 'percentage') {
            $couponData['percent_off'] = (float) $data['value'];
        } else {
            $account = $this->stripeClient()->accounts->retrieve();
            $couponData['amount_off'] = (int) ($data['value'] * 100);
            $couponData['currency'] = $account->default_currency;
        }
  
        $coupon = $this->stripeClient()->coupons->create($couponData);

        Notification::make()
            ->title('Coupon created successfully')
            ->success()
            ->send();

        $this->redirect('/stripe/discounts/'. $coupon->id);
    }

    protected function getStripeProducts(): array
    {
        $products = $this->stripeClient()->products->all([
            'active' => true,
            'limit' => 100,
        ]);

        return collect($products->data)
            ->mapWithKeys(fn ($product) => [
                $product->id => $product->name,
            ])
            ->toArray();
    }

    protected function loadPromoCodes(): void
    {
        if (! $this->couponId) {
            $this->promoCodes = [];
            return;
        }

        $promos = $this->stripeClient()->promotionCodes->all([
            'coupon' => $this->couponId,
            'limit' => 100,
        ]);

        $this->promoCodes = collect($promos->data)->map(function ($promo) {
            $validFrom = Carbon::createFromTimestamp($promo->created);
            $validTill = $promo->expires_at ? Carbon::createFromTimestamp($promo->expires_at) : null;

            $validity = $validTill
                ? "Valid {$validFrom->format('M j, Y')} - {$validTill->format('M j, Y')}"
                : "Valid from {$validFrom->format('M j, Y')}";

            return [
                'id' => $promo->id,
                'code' => $promo->code,
                'active' => $promo->active,
                'times_redeemed' => $promo->times_redeemed,
                'max_redemptions' => $promo->max_redemptions,
                'restrictions' => $validity,
            ];
        })->toArray();
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
            ->form([
                Forms\Components\TextInput::make('code')
                    ->label('Code')
                    ->placeholder('e.g. SPRING10')
                    ->required()
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
                        'promotion' => [
                            'type' => 'coupon',
                            'coupon' => $this->couponId,
                        ],
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

    public function archivePromoCode(string $promoCodeId): void
    {
        try {
            $this->stripeClient()
                ->promotionCodes
                ->update($promoCodeId, [
                    'active' => false,
                ]);

            Notification::make()
                ->title('Promo code archived')
                ->success()
                ->send();

            $this->loadPromoCodes(); // refresh table
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to archive promo code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function unArchivePromoCode(string $promoCodeId): void
    {
        try {
            $this->stripeClient()
                ->promotionCodes
                ->update($promoCodeId, [
                    'active' => true,
                ]);

            Notification::make()
                ->title('Promo code unarchived')
                ->success()
                ->send();

            $this->loadPromoCodes(); // refresh table
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to unarchive promo code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
