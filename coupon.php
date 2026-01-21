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

    public function mount(?string $couponId = null): void
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
            ->danger()
            ->send();

        $this->redirect('/stripe/discounts/' . $coupon->id);
    }
    
    public function getHeading(): string
    {
        return __('stripe.payments');
    }
}

========

<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>
    <div class="rounded-xl bg-white border border-gray-200">
        <div class="p-6 space-y-6">
            <h2 class="text-xl font-bold">
                Create Discount & Promo Code
            </h2>
            {{ $this->form }}
            <div class="flex gap-2 pt-4">
                <x-filament::button wire:click="save">
                    Save
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>

