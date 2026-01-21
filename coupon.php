<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>
    <div class="rounded-xl bg-white border border-gray-200">
        <div class="p-6 space-y-6">
            <h2 class="text-xl font-bold">
                {{ $isEdit ? 'Edit Discount & Promo Code' : 'Create Discount & Promo Code' }}
            </h2>
            <h2 class="text-lg font-semibold">{{ $isEdit && ! $isEditing ? 'Discount Details' : '' }}</h2>
            @if ($isEdit && ! $isEditing)
                <div class="space-y-5">
                    <div>
                        <p class="text-sm text-gray-500">Name (appears on receipts)</p>
                        <p class="text-base font-medium">{{ $formData['name'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Product(s)</p>
                        <p class="text-base font-medium">
                            {{ $formData['products_names'] }}
                        </p>
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
    @if ($isEdit && ! $isEditing)
        <div class="rounded-xl border border-gray-200 bg-white mt-6">
            {{-- HEADER (TITLE + ADD BUTTON) --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Promo Codes
                </h2>

                <x-filament::button
                    size="sm"
                    color="primary"
                    wire:click="createPromoCode">
                    + Add New
                </x-filament::button>
            </div>

            {{-- TABLE --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-gray-600">
                            <th class="px-4 py-3 text-left font-medium">Code</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Redemptions</th>
                            <th class="px-4 py-3 text-left font-medium">Restrictions</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($promoCodes as $promo)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium">
                                    {{ $promo['code'] }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($promo['active'])
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                            Archived
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    {{ $promo['times_redeemed'] }}
                                    @if($promo['max_redemptions'])
                                        <span class="text-gray-400">/</span>
                                        {{ $promo['max_redemptions'] ?? '' }}
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-600">
                                    {{ $promo['restrictions'] ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-right space-x-2">
                                    @if($promo['active'])
                                        <x-filament::button
                                            size="sm"
                                            outlined
                                            color="primary"
                                            wire:click="archivePromoCode('{{ $promo['id'] }}')">
                                            Archive
                                        </x-filament::button>
                                    @endif
                                    @if(! $promo['active'])
                                        <x-filament::button
                                            size="sm"
                                            outlined
                                            color="primary"
                                            wire:click="unArchivePromoCode('{{ $promo['id'] }}')">
                                            Unarchive
                                        </x-filament::button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                    No promo codes created yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>


===========

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

            

