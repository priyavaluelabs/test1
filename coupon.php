<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700" wire:ignore>
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-2">
                <x-filament::icon name="heroicon-o-arrow-left"
                    wire:click="$set('mode','list')"
                    class="cursor-pointer" />
                <h2 class="text-xl font-bold">
                    Create Discount & Promo Code
                </h2>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                {{ $this->form }}
                <x-filament::button type="submit" color="danger">
                    Save
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>


===========================


<?php
namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;

class CreateDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    protected static string $view = 'filament.pages.stripe.discount.create';
    protected static ?string $slug = 'stripe/discounts/create';

    public $user;

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
                            ->label('Name (appears on receipts)')
                            ->required()
                            ->columnSpan(2),
                    ]),
                // PRODUCT
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('products')
                            ->label('Product(s)')
                            ->options([
                                'all' => 'All Products',
                            ])
                            ->columnSpan(2),
                    ]),
                // DISCOUNT TYPE + VALUE (SAME ROW)
                Forms\Components\Grid::make(3)
                    ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\ToggleButtons::make('discount_type')
                            ->label(null)
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
                        ->rows(5)
                        ->columnSpan(3),
                ])
            ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        dd($data);
    }

    public function getHeading(): string
    {
        return __('stripe.payments');
    }
}
