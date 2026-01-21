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
            'value' => $coupon->percent_off ?? ($coupon->amount_off / 100),
            'products' => $productIds,
            'products_names' => implode(', ', array_map(fn($id) => $products[$id] ?? $id, $productIds)),
            'description' => $coupon->metadata->description ?? null,
        ];

        $this->form->fill($this->formData);
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
            $account = $this->stripeClient()->accounts->retrieve(null, ['stripe_account' => $this->user?->stripe_account_id]);
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
}
