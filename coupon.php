<?php
namespace App\Filament\Pages\StripeDiscounts\Pages;

use App\Filament\Pages\BaseStripePage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StripeDiscounts extends BaseStripePage
{
    protected static string $view = 'filament.pages.stripe.discounts';
    protected static ?string $slug = 'stripe/discounts';

    public $user;
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

        $this->user = Auth::user();
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
            'coupon_id'       => $coupon->id,
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
            return $productId;
        }
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
