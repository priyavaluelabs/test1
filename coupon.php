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
    public bool $isEdit = false;
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

    public function form(Form $form): Form
    {
        return $form
            ->statePath('formData')
            ->schema([
                Forms\Components\Section::make('Discount Details')->schema([

                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name (appears on receipts)')
                            ->required()
                            ->disabled(fn () => $this->isEdit && ! $this->isEditing)
                            ->columnSpan(2),
                    ]),

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

        $productIds = isset($coupon->metadata->product_ids)
            ? explode(',', $coupon->metadata->product_ids)
            : ['all'];

        $products = $this->getStripeProducts();

        $this->formData = [
            'name' => $coupon->name,
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $coupon->percent_off ?? ($coupon->amount_off / 100),
            'products' => $productIds,
            'products_names' => implode(', ', array_map(fn ($id) => $products[$id] ?? $id, $productIds)),
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
    }

    public function save(): void
    {
        $data = $this->formData;

        if ($this->isEdit) {
            $this->stripeClient()->coupons->update($this->couponId, [
                'name' => $data['name'],
                'metadata' => [
                    'description' => $data['description'] ?? '',
                ],
            ]);

            Notification::make()->title('Coupon updated')->success()->send();
            $this->isEditing = false;
            return;
        }

        $couponData = [
            'name' => $data['name'],
            'duration' => 'once',
            'metadata' => [
                'description' => $data['description'] ?? '',
            ],
        ];

        if (! in_array('all', $data['products'])) {
            $couponData['applies_to'] = ['products' => $data['products']];
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

        Notification::make()->title('Coupon created')->success()->send();
        $this->redirect('/stripe/discounts/' . $coupon->id);
    }

    protected function getStripeProducts(): array
    {
        $products = $this->stripeClient()->products->all([
            'active' => true,
            'limit' => 100,
        ]);

        return collect($products->data)
            ->mapWithKeys(fn ($product) => [$product->id => $product->name])
            ->toArray();
    }

    protected function getStripeCustomers(): array
    {
        $customers = $this->stripeClient()->customers->all([
            'limit' => 100,
        ]);

        return collect($customers->data)
            ->mapWithKeys(fn ($customer) => [
                $customer->id =>
                    ($customer->name ?? 'No Name') .
                    ' (' . ($customer->email ?? 'No Email') . ')',
            ])
            ->toArray();
    }

    protected function loadPromoCodes(): void
    {
        $promos = $this->stripeClient()->promotionCodes->all([
            'coupon' => $this->couponId,
            'limit' => 100,
        ]);

        $this->promoCodes = collect($promos->data)->map(fn ($promo) => [
            'id' => $promo->id,
            'code' => $promo->code,
            'active' => $promo->active,
            'times_redeemed' => $promo->times_redeemed,
            'max_redemptions' => $promo->max_redemptions,
        ])->toArray();
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
                    ->required(),

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

                Forms\Components\TextInput::make('max_redemptions')
                    ->label('Total Limit')
                    ->numeric()
                    ->nullable(),

                Forms\Components\Checkbox::make('first_purchase_only')
                    ->label('Limit to first purchase'),
            ])
            ->action(function (array $data) {

                $payload = [
                    'promotion' => [
                        'type' => 'coupon',
                        'coupon' => $this->couponId,
                    ],
                    'code' => strtoupper($data['code']),
                ];

                if (! empty($data['expires_at'])) {
                    $payload['expires_at'] = Carbon::parse($data['expires_at'])->timestamp;
                }

                if (! empty($data['max_redemptions'])) {
                    $payload['max_redemptions'] = (int) $data['max_redemptions'];
                }

                if (! empty($data['customer_id'])) {
                    $payload['customer'] = $data['customer_id'];
                }

                if (! empty($data['first_purchase_only'])) {
                    $payload['restrictions'] = [
                        'first_time_transaction' => true,
                    ];
                }

                $this->stripeClient()->promotionCodes->create($payload);

                Notification::make()->title('Promo code created')->success()->send();
                $this->loadPromoCodes();
            });
    }
}
