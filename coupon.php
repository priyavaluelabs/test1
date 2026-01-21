<?php

namespace App\Filament\Pages\StripeDiscounts\Concerns;

use Filament\Forms;
use Filament\Notifications\Notification;

trait InteractsWithStripeDiscounts
{
    protected function getStripeProducts(): array
    {
        $products = $this->stripeClient()->products->all(
            ['active' => true, 'limit' => 100],
            ['stripe_account' => $this->user?->stripe_account_id]
        );

        return collect($products->data)
            ->mapWithKeys(fn ($product) => [$product->id => $product->name])
            ->toArray();
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        Notification::make()
            ->title($message)
            ->{$type}()
            ->send();
    }

    protected function formatDiscount($coupon): string
    {
        if (!is_null($coupon->percent_off)) {
            return "{$coupon->percent_off}% off";
        }

        if (!is_null($coupon->amount_off)) {
            $symbol = optional($this->user->corporatePartner)->currency_symbol ?? '$';
            return $symbol . number_format($coupon->amount_off / 100, 0) . ' off';
        }

        return '—';
    }

    protected function baseDiscountFormSchema(
        bool $editable = true,
        bool $productsDisabled = false,
        bool $discountDisabled = false
    ): array {
        return [
            Forms\Components\Section::make('Discount Details')->schema([
                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name (appears on receipts)')
                        ->required()
                        ->disabled(! $editable)
                        ->columnSpan(2),
                ]),

                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\Select::make('products')
                        ->label('Product(s)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => ['all' => 'All Products'] + $this->getStripeProducts())
                        ->disabled($productsDisabled)
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
                        ->disabled($discountDisabled),

                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->numeric()
                        ->required()
                        ->disabled($discountDisabled),
                ]),

                Forms\Components\Textarea::make('description')
                    ->label('Description (optional)')
                    ->rows(4)
                    ->disabled(! $editable)
                    ->columnSpanFull(),
            ]),
        ];
    }
}

=====


use App\Filament\Pages\StripeDiscounts\Concerns\InteractsWithStripeDiscounts;

class CreateDiscount extends BaseStripePage implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;
    use InteractsWithStripeDiscounts;

    protected function discountFormSchema(): array
    {
        return $this->baseDiscountFormSchema(
            editable: true,
            productsDisabled: false,
            discountDisabled: false
        );
    }
}



==



