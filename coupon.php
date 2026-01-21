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
    protected static ?string $slug = 'stripe/discounts/{couponId}/edit';

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

    public function mount(string $couponId): void
    {
        parent::mount();

        if (! $this->stripeAvailable) {
            return;
        }

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
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
    }

    public function save(): void
    {
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

        Notification::make()
            ->title('Discount updated successfully')
            ->success()
            ->send();
    }

    public function deleteAction(): Actions\Action
    {
        return Actions\Action::make('delete')
            ->label('Delete')
            ->color('danger')
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

        Notification::make()
            ->title('Coupon deleted')
            ->success()
            ->send();

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

        Notification::make()
            ->title('Promo code ' . ($active ? 'unarchived' : 'archived'))
            ->success()
            ->send();

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
}


===


<x-filament-panels::page>
    <div class="rounded-xl bg-white border border-gray-200 p-6 space-y-6">

        <h2 class="text-xl font-bold">Edit Discount & Promo Code</h2>

        {{-- VIEW MODE --}}
        @if(! $isEditing)
            <div class="space-y-5">
                <div>
                    <p class="text-sm text-gray-500">Name</p>
                    <p class="font-medium">{{ $formData['name'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Products</p>
                    <p class="font-medium">{{ $formData['products_names'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Discount</p>
                    <p class="font-medium">
                        {{ $formData['discount_type'] === 'percentage'
                            ? $formData['value'].'% off'
                            : '$'.number_format($formData['value'], 2).' off'
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Description</p>
                    <p class="font-medium">{{ $formData['description'] ?? '—' }}</p>
                </div>

                <div class="flex gap-2">
                    <x-filament::button wire:click="startEditing">
                        Edit
                    </x-filament::button>

                    {{ $this->deleteAction }}
                </div>
            </div>
        @endif

        {{-- EDIT MODE --}}
        @if($isEditing)
            {{ $this->form }}

            <div class="flex gap-2 pt-4">
                <x-filament::button wire:click="save">
                    Save
                </x-filament::button>
            </div>
        @endif
    </div>

    {{-- PROMO CODES --}}
    @if(! $isEditing)
        <div class="rounded-xl bg-white border border-gray-200 mt-6">
            <div class="flex justify-between items-center px-4 py-3 border-b">
                <h3 class="font-semibold">Promo Codes</h3>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Code</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Redemptions</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promoCodes as $promo)
                        <tr class="border-t">
                            <td class="px-4 py-2 font-mono">{{ $promo['code'] }}</td>
                            <td class="px-4 py-2">
                                {{ $promo['active'] ? 'Active' : 'Archived' }}
                            </td>
                            <td class="px-4 py-2">
                                {{ $promo['times_redeemed'] }}
                                @if($promo['max_redemptions'])
                                    / {{ $promo['max_redemptions'] }}
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                @if($promo['active'])
                                    <x-filament::button
                                        size="sm"
                                        outlined
                                        wire:click="archivePromoCode('{{ $promo['id'] }}')">
                                        Archive
                                    </x-filament::button>
                                @else
                                    <x-filament::button
                                        size="sm"
                                        outlined
                                        wire:click="unArchivePromoCode('{{ $promo['id'] }}')">
                                        Unarchive
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">
                                No promo codes
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
