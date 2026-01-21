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
        $this->couponId = $couponId;
        $this->isEdit = $couponId && $couponId !== 'create';
        $this->isEditing = ! $this->isEdit;

        if ($this->isEdit) {
            $this->loadCoupon();
            $this->loadPromoCodes();
        }
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
                        ->disabled(fn () => $this->isEdit && ! $this->isEditing)
                        ->columnSpan(2),
                ]),
                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\Select::make('products')
                        ->label('Product(s)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => ['all' => 'All Products'] + $this->getStripeProducts())
                        ->disabled(fn () => $this->isEdit)
                        ->columnSpan(2),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\ToggleButtons::make('discount_type')
                        ->label('Discount Type')
                        ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed Amount'])
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
        ];
    }

    protected function loadCoupon(): void
    {
        $coupon = $this->stripeClient()->coupons->retrieve(
            $this->couponId,
            [],
            ['stripe_account' => $this->user?->stripe_account_id]
        );

        $productIds = $coupon->metadata->product_ids ?? 'all';
        $productIds = is_string($productIds) ? explode(',', $productIds) : ['all'];
        $products = $this->getStripeProducts();

        $this->formData = [
            'name' => $coupon->name,
            'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
            'value' => $this->formatDiscount($coupon),
            'products' => $productIds,
            'products_names' => implode(', ', array_map(fn($id) => $products[$id] ?? $id, $productIds)),
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

            return $currencySymbol . number_format($coupon->amount_off / 100, 2) . " off";
        }

        return '—';
    }
    
    public function save(): void
    {
        $data = $this->formData;
        if ($this->isEdit) {
            $this->updateCoupon($data);
        } else {
            $this->createCoupon($data);
        }
    }

    private function updateCoupon(array $data): void
    {
        $this->stripeClient()->coupons->update(
            $this->couponId,
            [
                'name' => $data['name'],
                'metadata' => ['description' => $data['description'] ?? ''],
            ],
            ['stripe_account' => $this->user?->stripe_account_id]
        );

        $this->notify('Coupon updated successfully');
        $this->isEditing = false;
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

        $this->notify('Coupon created successfully');
        $this->redirect('/stripe/discounts/' . $coupon->id);
    }

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
                    $this->stripeClient()->coupons->delete($this->couponId, [], [
                        'stripe_account' => $this->user?->stripe_account_id,
                    ]);

                    $this->notify('Coupon deleted successfully');

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

    private function notify(string $message, string $type = 'success'): void
    {
        Notification::make()->title($message)->{$type}()->send();
    }

    protected function getStripeProducts(): array
    {
        $products = $this->stripeClient()->products->all(['active' => true, 'limit' => 100], ['stripe_account' => $this->user?->stripe_account_id]);
        return collect($products->data)->mapWithKeys(fn ($product) => [$product->id => $product->name])->toArray();
    }

    protected function getStripeCustomers(): array
    {
        $customers = $this->stripeClient()->customers->all(['limit' => 100], ['stripe_account' => $this->user?->stripe_account_id]);

        return collect($customers->data)
            ->mapWithKeys(fn ($customer) => [$customer->id => ($customer->name ?? 'No Name') . ' (' . ($customer->email ?? 'No Email') . ')'])
            ->toArray();
    }

    protected function loadPromoCodes(): void
    {
        if (!$this->couponId) {
            $this->promoCodes = [];
            return;
        }

        $promos = $this->stripeClient()->promotionCodes->all(['coupon' => $this->couponId, 'limit' => 100], ['stripe_account' => $this->user?->stripe_account_id]);

        $this->promoCodes = collect($promos->data)->map(fn ($promo) => [
            'id' => $promo->id,
            'code' => $promo->code,
            'active' => $promo->active,
            'times_redeemed' => $promo->times_redeemed,
            'max_redemptions' => $promo->max_redemptions,
            'restrictions' => $promo->expires_at
                ? 'Valid ' . Carbon::createFromTimestamp($promo->created)->format('M j, Y') . ' - ' . Carbon::createFromTimestamp($promo->expires_at)->format('M j, Y')
                : 'Valid from ' . Carbon::createFromTimestamp($promo->created)->format('M j, Y'),
        ])->toArray();
    }

    public function setPromoCodeActiveStatus(string $promoCodeId, bool $active): void
    {
        try {
            $this->stripeClient()->promotionCodes->update($promoCodeId, ['active' => $active], ['stripe_account' => $this->user?->stripe_account_id]);
            $this->notify('Promo code ' . ($active ? 'unarchived' : 'archived'));
            $this->loadPromoCodes();
        } catch (\Exception $e) {
            Notification::make()->title('Failed to update promo code')->body($e->getMessage())->danger()->send();
        }
    }

    public function archivePromoCode(string $promoCodeId): void
    {
        $this->setPromoCodeActiveStatus($promoCodeId, false);
    }

    public function unArchivePromoCode(string $promoCodeId): void
    {
        $this->setPromoCodeActiveStatus($promoCodeId, true);
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
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
    
    public function getHeading(): string
    {
        return __('stripe.payments');
    }
}


==========


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
                            {{ $formData['value'] }}
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
