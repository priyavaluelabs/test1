<?php

namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\Auth;

class CreateDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.pages.stripe.discount.create';
    protected static ?string $slug = 'stripe/discounts/create';

    public array $formData = [
        'name' => null,
        'products' => [],
        'discount_type' => 'percentage',
        'value' => null,
        'description' => null,
    ];

    public ?\App\Models\User $user = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        parent::mount();

        if (! $this->stripeAvailable) return;

        $this->user = Auth::user();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('formData')
            ->schema($this->discountFormSchema());
    }

    private function discountFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Discount Details')->schema([
                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name (appears on receipts)')
                        ->required()
                        ->columnSpan(2),
                ]),
                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\Select::make('products')
                        ->label('Product(s)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => ['all' => 'All Products'] + $this->getStripeProducts())
                        ->columnSpan(2),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\ToggleButtons::make('discount_type')
                        ->label('Discount Type')
                        ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed Amount'])
                        ->inline(),
                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->required()
                        ->numeric(),
                ]),
                Forms\Components\Textarea::make('description')
                    ->label('Description (optional)')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ];
    }

    private function getStripeProducts(): array
    {
        $products = $this->stripeClient()->products->all(
            ['active' => true, 'limit' => 100], 
            ['stripe_account' => $this->user?->stripe_account_id]
        );
        return collect($products->data)->mapWithKeys(fn ($product) => [$product->id => $product->name])->toArray();
    }
    
    public function save(): void
    {   
        $this->form->validate();
        $data = $this->formData;
        
        $this->createCoupon($data);
    }

    private function createCoupon(array $data): void
    {
        $couponData = [
            'name' => $data['name'],
            'duration' => 'once',
            'metadata' => ['description' => $data['description'] ?? ''],
        ];

        if (! in_array('all', $data['products'])) {
            $couponData['applies_to'] = ['products' => $data['products']];
            $couponData['metadata']['product_ids'] = implode(',', $data['products']);
        }

        if ($data['discount_type'] === 'percentage') {
            $couponData['percent_off'] = (float) $data['value'];
        } else {
            $account = $this->stripeClient()->accounts->retrieve($this->user?->stripe_account_id);
            $couponData['amount_off'] = (int) ($data['value'] * 100);
            $couponData['currency'] = $account->default_currency;
        }

        $coupon = $this->stripeClient()->coupons->create($couponData, ['stripe_account' => $this->user?->stripe_account_id]);

        Notification::make()
            ->title('Coupon created successfully')
            ->success()
            ->send();

        $this->redirect('/stripe/discounts/' . $coupon->id);
    }
    
    public function getHeading(): string
    {
        return __('stripe.payments');
    }
}

==


<?php

namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EditDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.pages.stripe.discount.edit';
    protected static ?string $slug = 'stripe/discounts/{couponId}';

    public string $couponId;
    public bool $isEditing = false;

    public array $formData = [
        'name' => null,
        'products' => [],
        'products_names' => null,
        'discount_type' => null,
        'value' => null,
        'description' => null,
    ];

    public array $promoCodes = [];
    public $user;

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
        $this->loadPromoCodes();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('formData')
            ->schema($this->discountFormSchema());
    }

    protected function discountFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Discount Details')->schema([
                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name (appears on receipts)')
                        ->required()
                        ->disabled(fn () => ! $this->isEditing)
                        ->columnSpan(2),
                ]),

                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\Select::make('products')
                        ->label('Product(s)')
                        ->multiple()
                        ->options(fn () => ['all' => 'All Products'] + $this->getStripeProducts())
                        ->disabled()
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
                        ->disabled(),

                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->numeric()
                        ->disabled(),
                ]),

                Forms\Components\Textarea::make('description')
                    ->label('Description (optional)')
                    ->rows(4)
                    ->disabled(fn () => ! $this->isEditing)
                    ->columnSpanFull(),
            ]),
        ];
    }

    protected function loadCoupon(): void
    {
        $coupon = $this->stripeClient()->coupons->retrieve(
            $this->couponId,
            [],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $productIds = $coupon->metadata->product_ids ?? 'all';
        $productIds = is_string($productIds)
            ? explode(',', $productIds)
            : ['all'];

        $products = $this->getStripeProducts();

        $this->formData = [
            'name' => $coupon->name,
            'products' => $productIds,
            'products_names' => implode(', ', array_map(
                fn ($id) => $products[$id] ?? $id,
                $productIds
            )),
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $coupon->percent_off
                ? $coupon->percent_off
                : ($coupon->amount_off / 100),
            'value_with_symbol' => $this->formatDiscount($coupon),
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
    }

    protected function formatDiscount($coupon): string
    {
        if (!is_null($coupon->percent_off)) {
            return "{$coupon->percent_off}% off";
        }

        // Fixed amount discount
        if (!is_null($coupon->amount_off)) {
            $currencySymbol = optional($this->user->corporatePartner)->currency_symbol ?? '$';

            return $currencySymbol . number_format($coupon->amount_off / 100, 0) . " off";
        }

        return '—';
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
    }
    
    private function getStripeProducts(): array
    {
        $products = $this->stripeClient()->products->all(
            ['active' => true, 'limit' => 100], 
            ['stripe_account' => $this->user?->stripe_account_id]
        );
        return collect($products->data)->mapWithKeys(fn ($product) => [$product->id => $product->name])->toArray();
    }

    protected function getStripeCustomers(): array
    {
        $customers = $this->stripeClient()->customers->all(['limit' => 100], ['stripe_account' => $this->user?->stripe_account_id]);

        return collect($customers->data)
            ->mapWithKeys(fn ($customer) => [$customer->id => ($customer->name ?? 'No Name') . ' (' . ($customer->email ?? 'No Email') . ')'])
            ->toArray();
    }

    public function save(): void
    {
        $this->form->validate();
        $this->stripeClient()->coupons->update(
            $this->couponId,
            [
                'name' => $this->formData['name'],
                'metadata' => [
                    'description' => $this->formData['description'] ?? '',
                ],
            ],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->isEditing = false;
        $this->notify('Discount updated successfully');
    }

    public function deleteAction(): Actions\Action
    {
        return Actions\Action::make('delete')
            ->label('Delete')
            ->color('primary')
            ->requiresConfirmation()
            ->action(fn () => $this->deleteCoupon());
    }

    protected function deleteCoupon(): void
    {
        $this->stripeClient()->coupons->delete(
            $this->couponId,
            [],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->notify('Coupon deleted');
        $this->redirect('/stripe/discounts');
    }

    /* ---------------- PROMO CODES ---------------- */

    protected function loadPromoCodes(): void
    {
        $promos = $this->stripeClient()->promotionCodes->all(
            ['coupon' => $this->couponId, 'limit' => 100],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->promoCodes = collect($promos->data)->map(fn ($promo) => [
            'id' => $promo->id,
            'code' => $promo->code,
            'active' => $promo->active,
            'times_redeemed' => $promo->times_redeemed,
            'max_redemptions' => $promo->max_redemptions,
            'restrictions' => $promo->expires_at
                ? 'Valid until ' . Carbon::createFromTimestamp($promo->expires_at)->format('M j, Y')
                : '—',
        ])->toArray();
    }

    public function setPromoCodeActiveStatus(string $promoCodeId, bool $active): void
    {
        $this->stripeClient()->promotionCodes->update(
            $promoCodeId,
            ['active' => $active],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->notify('Promo code ' . ($active ? 'unarchived' : 'archived'));
        $this->loadPromoCodes();
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
            $this->loadPromoCodes();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create promo code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function notify(string $message, string $type = 'success'): void
    {
        Notification::make()->title($message)->{$type}()->send();
    }
}
