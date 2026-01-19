<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Collection;

class StripeDiscounts extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $slug = 'stripe/discounts';
    protected static string $view = 'filament.pages.stripe-discounts';

    public string $mode = 'list'; // list | create | edit
    public ?string $couponId = null;

    /** Discount form state */
    public array $data = [];

    /** Promo codes */
    public Collection $promoCodes;

    public function mount(): void
    {
        $this->promoCodes = collect();
    }

    /* ---------------- FORM ---------------- */

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name (appears on receipts)')
                    ->required(),

                Forms\Components\Select::make('products')
                    ->label('Product(s)')
                    ->options([
                        'all' => 'All Products',
                    ])
                    ->default('all'),

                Forms\Components\ToggleButtons::make('discount_type')
                    ->label('Discount Type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed amount',
                    ])
                    ->default('percentage')
                    ->inline(),

                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->suffix(fn ($get) => $get('discount_type') === 'percentage' ? '%' : '$')
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('Description (optional)')
                    ->columnSpanFull(),
            ]);
    }

    /* ---------------- ACTIONS ---------------- */

    public function create(): void
    {
        $this->mode = 'create';
        $this->form->fill();
    }

    public function edit(string $couponId): void
    {
        $this->mode = 'edit';
        $this->couponId = $couponId;

        // Load Stripe coupon
        $coupon = $this->loadCouponFromStripe($couponId);

        $this->form->fill([
            'name' => $coupon['name'],
            'discount_type' => $coupon['type'],
            'value' => $coupon['value'],
        ]);

        $this->promoCodes = collect($coupon['promo_codes']);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if ($this->mode === 'create') {
            $this->createCouponOnStripe($data);
        } else {
            $this->updateCouponOnStripe($this->couponId, $data);
        }

        $this->mode = 'list';
    }

    /* ---------------- STRIPE METHODS (stub) ---------------- */

    protected function loadCouponFromStripe(string $id): array
    {
        return [
            'name' => 'Spring 10% Promo',
            'type' => 'percentage',
            'value' => 10,
            'promo_codes' => [],
        ];
    }

    protected function createCouponOnStripe(array $data): void {}
    protected function updateCouponOnStripe(string $id, array $data): void {}
}



===================


<x-filament-panels::page>

    {{-- LIST --}}
    @if ($mode === 'list')
        <x-filament::button wire:click="create">
            Create Discount
        </x-filament::button>

        {{-- Your custom discount table --}}
    @endif

    {{-- CREATE / EDIT --}}
    @if (in_array($mode, ['create', 'edit']))
        <div class="max-w-3xl space-y-6">

            <div class="flex items-center gap-2">
                <x-filament::icon name="heroicon-o-arrow-left"
                    wire:click="$set('mode','list')"
                    class="cursor-pointer" />
                <h2 class="text-xl font-bold">
                    {{ $mode === 'create' ? 'Create Discount & Promo Code' : 'Edit Discount & Promo Code' }}
                </h2>
            </div>

            {{-- Discount form --}}
            <form wire:submit.prevent="save" class="space-y-4">
                {{ $this->form }}

                <x-filament::button type="submit" color="danger">
                    Save
                </x-filament::button>
            </form>

            {{-- Promo Codes --}}
            <div class="pt-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold">Promo Codes</h3>
                    <x-filament::button size="sm">
                        + Add New
                    </x-filament::button>
                </div>

                <x-filament::table>
                    <x-slot name="header">
                        <x-filament::table.heading>Code</x-filament::table.heading>
                        <x-filament::table.heading>Status</x-filament::table.heading>
                        <x-filament::table.heading>Redemptions</x-filament::table.heading>
                        <x-filament::table.heading>Restrictions</x-filament::table.heading>
                        <x-filament::table.heading>Actions</x-filament::table.heading>
                    </x-slot>

                    @forelse ($promoCodes as $code)
                        <x-filament::table.row>
                            <x-filament::table.cell>{{ $code['code'] }}</x-filament::table.cell>
                            <x-filament::table.cell>{{ $code['status'] }}</x-filament::table.cell>
                            <x-filament::table.cell>{{ $code['redemptions'] }}</x-filament::table.cell>
                            <x-filament::table.cell>{{ $code['restrictions'] }}</x-filament::table.cell>
                            <x-filament::table.cell>
                                <x-filament::button size="xs">Edit</x-filament::button>
                            </x-filament::table.cell>
                        </x-filament::table.row>
                    @empty
                        <x-filament::table.row>
                            <x-filament::table.cell colspan="5">
                                No promo codes yet.
                            </x-filament::table.cell>
                        </x-filament::table.row>
                    @endforelse
                </x-filament::table>
            </div>
        </div>
    @endif

</x-filament-panels::page>
