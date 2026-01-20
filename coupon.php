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

    // IMPORTANT: optional parameter
    protected static ?string $slug = 'stripe/discounts/{couponId?}';

    public ?string $couponId = null;

    public bool $isCreate = false;
    public bool $isView = false;
    public bool $editing = false;

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

    public function mount(?string $couponId = null): void
    {
        parent::mount();

        $this->couponId = $couponId;

        // CREATE
        if ($couponId === null || $couponId === 'create') {
            $this->isCreate = true;
            return;
        }

        // VIEW (default edit page)
        $this->isView = true;
        $this->loadCoupon();
    }

    /**
     * Load coupon for view/edit
     */
    protected function loadCoupon(): void
    {
        $coupon = $this->stripeClient()
            ->coupons
            ->retrieve($this->couponId);

        $this->formData = [
            'name' => $coupon->name,
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $coupon->percent_off ?? ($coupon->amount_off / 100),
            'products' => $coupon->applies_to->products ?? ['all'],
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
    }

    /**
     * FORM
     */
    public function form(Form $form): Form
    {
        return $form
            ->statePath('formData')
            ->schema([
                Forms\Components\Section::make('Discount Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->disabled(fn () => $this->isView && ! $this->editing),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->disabled(fn () => $this->isView && ! $this->editing),

                        Forms\Components\ToggleButtons::make('discount_type')
                            ->label('Discount Type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Select::make('products')
                            ->label('Products')
                            ->multiple()
                            ->options(fn () => [
                                'all' => 'All Products',
                            ] + $this->getStripeProducts())
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * CREATE / UPDATE
     */
    public function save(): void
    {
        if ($this->isCreate) {
            $this->createCoupon();
            return;
        }

        // UPDATE (only name & description)
        $this->stripeClient()
            ->coupons
            ->update($this->couponId, [
                'name' => $this->formData['name'],
                'metadata' => [
                    'description' => $this->formData['description'] ?? '',
                ],
            ]);

        Notification::make()
            ->title('Coupon updated successfully')
            ->success()
            ->send();

        $this->editing = false;
        $this->isView = true;
    }

    /**
     * CREATE coupon
     */
    protected function createCoupon(): void
    {
        $data = $this->formData;

        $couponData = [
            'name' => $data['name'],
            'duration' => 'once',
        ];

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
     * Edit button action
     */
    public function enableEdit(): void
    {
        $this->editing = true;
        $this->isView = false;
    }

    /**
     * Delete coupon
     */
    public function delete(): void
    {
        $this->stripeClient()
            ->coupons
            ->delete($this->couponId);

        Notification::make()
            ->title('Coupon deleted')
            ->success()
            ->send();

        $this->redirect('/admin/pages/stripe/discounts');
    }

    public function getHeading(): string
    {
        if ($this->isCreate) {
            return 'Create Discount';
        }

        if ($this->editing) {
            return 'Edit Discount';
        }

        return 'Discount Details';
    }

    protected function getStripeProducts(): array
    {
        $products = $this->stripeClient()
            ->products
            ->all(['active' => true]);

        return collect($products->data)
            ->mapWithKeys(fn ($product) => [
                $product->id => $product->name,
            ])
            ->toArray();
    }
}




=======



<x-filament-panels::page>

    {{-- ACTION BUTTONS --}}
    <div class="flex gap-2 mb-4">

        @if ($isView)
            <x-filament::button wire:click="enableEdit">
                Edit
            </x-filament::button>

            <x-filament::button
                color="danger"
                wire:click="delete"
                wire:confirm="Are you sure you want to delete this coupon?"
            >
                Delete
            </x-filament::button>
        @endif

    </div>

    {{-- FORM --}}
    <form wire:submit.prevent="save">
        {{ $this->form }}

        @if ($isCreate || $editing)
            <x-filament::button type="submit" class="mt-4">
                {{ $isCreate ? 'Save' : 'Update' }}
            </x-filament::button>
        @endif
    </form>

</x-filament-panels::page>
