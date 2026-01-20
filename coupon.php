<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700" wire:ignore>
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-bold">
                    Create Discount & Promo Code
                </h2>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                {{ $this->form }}
                <x-filament::button type="submit">
                    Save
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>


===========

<?php
namespace App\Filament\Pages\StripeDiscounts\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use App\Filament\Pages\BaseStripePage;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;

class CreateDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.pages.stripe.discount.create';
    protected static ?string $slug = 'stripe/discounts/create';

    public $user;
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

    public function mount(): void
    {
        parent::mount();
        if (! $this->stripeAvailable) {
            return;
        }
        
        $this->user = Auth::user();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\Section::make('Discount Details')
            ->schema([
                // NAME
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->statePath('formData.name')
                            ->label('Name (appears on receipts)')
                            ->required()
                            ->columnSpan(2),
                    ]),
                // PRODUCT
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('products')
                            ->label('Product(s)')
                            ->statePath('formData.products')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => [
                                    'all' => 'All Products',
                                ] + $this->getStripeProducts())
                            ->columnSpan(2),
                    ]),
                // DISCOUNT TYPE + VALUE (SAME ROW)
                Forms\Components\Grid::make(3)
                    ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\ToggleButtons::make('discount_type')
                            ->label('Discount Type')
                            ->statePath('formData.discount_type')
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

                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->statePath('formData.value')
                        ->numeric()
                        ->extraInputAttributes([
                            'class' => 'max-w-xs h-9',
                        ]),
                ]),
                // DESCRIPTION
                Forms\Components\Grid::make(4)
                    ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Description (optional)')
                        ->statePath('formData.description')
                        ->rows(5)
                        ->columnSpan(3),
                ])
            ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->formData;

        // Base data
        $couponData = [
            'name' => $data['name'],
            'duration' => 'once'
        ];

        if (! in_array('all', $data['products'])) {
            $couponData['applies_to'] = [
                'products' => $data['products'],
            ];
            $couponData['metadata'] = [
                'description' => $data['description'] ?? '',
                'products' => implode(',', $data['products'])
            ];
        }

        if ($data['discount_type'] === 'percentage') {
            $couponData['percent_off'] = floatval($data['value']);
        }
        
        if ($data['discount_type'] === 'fixed') {
            $account = $this->stripeClient()->accounts->retrieve();
            $couponData['amount_off'] = intval($data['value'] * 100);
            $couponData['currency'] = $account->default_currency;
        }

        $this->stripeClient()->coupons->create($couponData);

        Notification::make()
            ->title('Coupon has been created successfully.')
            ->success()
            ->send();
    }

    public function getHeading(): string
    {
        return __('stripe.payments');
    }

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

