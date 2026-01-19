How we can do it 


<?php

namespace App\Filament\Pages;

use App\Filament\Pages\BaseStripePage;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StripeDiscounts extends BaseStripePage
{
    protected static string $view = 'filament.pages.stripe.discounts';
    protected static ?string $slug = 'stripe/discounts';

    public Collection $records;

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

        $this->records = $this->getCouponsWithDetails();
    }

    /**
     * Fetch Stripe coupons along with their promotion codes.
     */
    protected function getCouponsWithDetails(): Collection
    {
        $coupons = $this->stripeClient()->coupons->all(['limit' => 50]);

        return collect($coupons->data)
            ->map(fn($coupon) => $this->mapCoupon($coupon))
            ->filter() // remove nulls where promo codes were empty
            ->values();
    }

    protected function mapCoupon($coupon): ?array
    {
        $promoCodes = $this->getPromotionCodes($coupon->id);

        if (empty($promoCodes)) {
            return null; // Skip coupons without active promo codes
        }

        return [
            'coupon_name'     => $coupon->name ?? $coupon->id,
            'description'     => $coupon->metadata['description'] ?? null,
            'products'        => $this->getCouponProducts($coupon),
            'discount'        => $this->formatDiscount($coupon),
            'promotion_codes' => $promoCodes,
        ];
    }

    protected function getCouponProducts($coupon): array
    {
        if (empty($coupon->metadata['product_ids'])) {
            return ['All products'];
        }

        return collect(explode(',', $coupon->metadata['product_ids']))
            ->map(fn($id) => $this->getProductName(trim($id)))
            ->toArray();
    }

    protected function getProductName(string $productId): string
    {
        try {
            $product = $this->stripeClient()->products->retrieve($productId);
            $type = $product->metadata->product_type ?? null;
            return $type ? "{$product->name} - {$type}" : $product->name;
        } catch (\Exception) {
            return $productId; // fallback if product not found
        }
    }

    protected function formatDiscount($coupon): string
    {
        if ($coupon->percent_off) {
            return "{$coupon->percent_off}% off";
        }

        if ($coupon->amount_off) {
            return '₹' . number_format($coupon->amount_off / 100, 2);
        }

        return '—';
    }

    protected function getPromotionCodes(string $couponId): array
    {
        $promotionCodes = $this->stripeClient()->promotionCodes->all([
            'coupon' => $couponId,
            'limit'  => 50,
        ]);

        return collect($promotionCodes->data)
            ->filter(fn($promo) => $promo->active)
            ->map(fn($promo) => $this->mapPromotionCode($promo))
            ->values()
            ->toArray();
    }

    protected function mapPromotionCode($promo): array
    {
        $validFrom = Carbon::createFromTimestamp($promo->created);
        $validTill = $promo->expires_at ? Carbon::createFromTimestamp($promo->expires_at) : null;

        $validity = $validTill
            ? "Valid {$validFrom->format('M j, Y')} - {$validTill->format('M j, Y')}"
            : "Valid from {$validFrom->format('M j, Y')}";

        $isExpired = $promo->expires_at ? now()->timestamp > $promo->expires_at : false;
        $usageText = $promo->max_redemptions ? "{$promo->times_redeemed}/{$promo->max_redemptions} used" : '';

        return [
            'code'      => $promo->code,
            'status'    => ($promo->active && ! $isExpired) ? 'Active' : 'Expired',
            'validity'  => $validity,
            'is_first'  => $promo->restrictions?->first_time_transaction ?? false,
            'usage'     => $usageText,
        ];
    }
}


<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>

    @if (! $stripeAvailable)
        <x-stripe.configuration-error :stripeErrorMessage="$stripeErrorMessage"/>
    @else
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700" wire:ignore>
            <div class="discount">
                <div class="p-6 space-y-4">
                    <h1 class="font-bold text-[28px] text-gray-700">
                        {{ __('stripe.discounts') }}
                    </h1>

                    <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Name & Product</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Details</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Promo Codes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($records as $record)
                                <tr>
                                    <!-- Name & Product -->
                                    <td class="px-4 py-2">
                                        <div class="font-medium">{{ $record['coupon_name'] }}</div>
                                        <ul class="ml-4 list-disc text-sm text-gray-500">
                                            @foreach($record['products'] as $product)
                                                <li>{{ $product }}</li>
                                            @endforeach
                                        </ul>
                                    </td>

                                    <!-- Details -->
                                    <td class="px-4 py-2">
                                        <div>{{ $record['discount'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $record['description'] }}</div>
                                    </td>

                                    <!-- Promo Codes -->
                                    <td class="px-4 py-2">
                                        @foreach($record['promotion_codes'] as $promo)
                                            <div class="mb-2 p-2">
                                                {{-- Promo code and status --}}
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">
                                                        {{ $promo['code'] }}
                                                    </span>
                                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded
                                                        {{ $promo['status'] === 'Active'
                                                            ? 'bg-green-100 text-green-700'
                                                            : 'bg-orange-100 text-orange-700' }}">
                                                        {{ $promo['status'] }}
                                                    </span>
                                                </div>

                                                {{-- Validity --}}
                                                <div class="text-xs text-gray-500 mb-1">
                                                    {{ $promo['validity'] }}
                                                </div>

                                                {{-- First purchase only --}}
                                                @if($promo['is_first'])
                                                    <div class="inline-block px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-700 mb-1">
                                                        1st purchase only
                                                    </div>
                                                @endif

                                                {{-- Usage --}}
                                                <div class="inline-block px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600">
                                                    {{ $promo['usage'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-center text-gray-500">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Create New Button -->
                    <div class="pt-4">
                        <x-filament::button color="danger">
                            Create New
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
