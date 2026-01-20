<?php

namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class ManageDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.stripe.discount.manage';
    protected static ?string $slug = 'stripe/discounts/{couponId}';

    public ?string $couponId = null;
    public bool $isEdit = false;

    public $formData = [
        'name' => null,
        'products' => [],
        'discount_type' => 'percentage',
        'value' => null,
        'description' => null,
    ];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * ✅ Detect create / edit
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
        }
    }

    /**
     * ✅ Form
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
                                ->columnSpan(2),
                        ]),

                        // PRODUCTS
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Select::make('products')
                                ->label('Product(s)')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->options(fn () => [
                                    'all' => 'All Products',
                                ] + $this->getStripeProducts())
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
                                ->live()
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
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * ✅ Load coupon for edit
     */
    protected function loadCoupon(): void
    {
        $coupon = $this->stripeClient()
            ->coupons
            ->retrieve($this->couponId);

        $this->formData = [
            'name' => $coupon->name,
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $coupon->percent_off
                ?? ($coupon->amount_off / 100),
            'products' => $coupon->applies_to->products ?? ['all'],
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
    }

    /**
     * ✅ Save (create / update)
     */
    public function save(): void
    {
        $data = $this->formData;

        // EDIT
        if ($this->isEdit) {
            $this->stripeClient()
                ->coupons
                ->update($this->couponId, [
                    'name' => $data['name'],
                    'metadata' => [
                        'description' => $data['description'] ?? '',
                    ],
                ]);

            Notification::make()
                ->title('Coupon updated successfully')
                ->success()
                ->send();

            return;
        }

        // CREATE
        $couponData = [
            'name' => $data['name'],
            'duration' => 'once',
        ];

        if (! in_array('all', $data['products'])) {
            $couponData['applies_to'] = [
                'products' => $data['products'],
            ];
        }

        if ($data['discount_type'] === 'percentage') {
            $couponData['percent_off'] = (float) $data['value'];
        } else {
            $account = $this->stripeClient()->accounts->retrieve();
            $couponData['amount_off'] = (int) ($data['value'] * 100);
            $couponData['currency'] = $account->default_currency;
        }

        $this->stripeClient()->coupons->create($couponData);

        Notification::make()
            ->title('Coupon created successfully')
            ->success()
            ->send();
    }

    /**
     * ✅ Page heading
     */
    public function getHeading(): string
    {
        return $this->isEdit
            ? 'Edit Discount'
            : 'Create Discount';
    }

    /**
     * ✅ Stripe products
     */
    protected function getStripeProducts(): array
    {
        $products = $this->stripeClient()
            ->products
            ->all(['active' => true, 'limit' => 100]);

        return collect($products->data)
            ->mapWithKeys(fn ($product) => [
                $product->id => $product->name,
            ])
            ->toArray();
    }
}
