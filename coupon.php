<?php

namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;

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

        $this->formData = [
            'name' => $coupon->name,
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $coupon->percent_off ?? ($coupon->amount_off / 100),
            'products' => $coupon->metadata->product_ids ?? ['all'],
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
}



<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>
    <div class="rounded-xl bg-white border border-gray-200">
        <div class="p-6 space-y-6">
            <h2 class="text-xl font-bold">
                {{ $isEdit ? 'Edit Discount & Promo Code' : 'Create Discount & Promo Code' }}
            </h2>
            <h2 class="text-lg font-semibold">{{ $isEdit ? 'Discount Details' : '' }}</h2>
            @if ($isEdit && ! $isEditing)
                <div class="space-y-5">
                    <div>
                        <p class="text-sm text-gray-500">Name (appears on receipts)</p>
                        <p class="text-base font-medium">{{ $formData['name'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Product(s)</p>
                        <p class="text-base font-medium">{{ implode(', ', $formData['products']) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Discount Type</p>
                        <p class="text-base font-medium">
                            {{ $formData['discount_type'] === 'percentage'
                                ? $formData['value'].'% Off'
                                : '$'.$formData['value'].' Off' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Description (optional)</p>
                        <p class="text-base font-medium">{{ $formData['description'] }}</p>
                    </div>
                </div>
                <div class="flex gap-2 pt-4">
                    <x-filament::button wire:click="startEditing">
                        Edit
                    </x-filament::button>
                    {{ $this->deleteAction }}
                </div>
            @endif
            @if ($isEditing)
                {{ $this->form }}
                <div class="flex gap-2 pt-4">
                    <x-filament::button wire:click="save">
                        Save
                    </x-filament::button>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
