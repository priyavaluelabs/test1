<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Sushi\Sushi;
use Stripe\StripeClient;

class StripeProduct extends Model
{
    use Sushi;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $schema = [
        'id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'price' => 'float',
        'currency' => 'string',
        'active' => 'boolean',
    ];

    protected $fillable = ['price', 'description', 'active'];

    /**
     * Sushi rows - pull from cache or Stripe
     */
    public function getRows()
    {
        return self::getCachedRows();
    }

    /**
     * Fetch products from Stripe and cache
     */
    public static function getCachedRows(): array
    {
        $userId = Auth::id();
        $cacheKey = "stripe_products_user_{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            return self::fetchStripeProducts();
        });
    }

    /**
     * Clear cache of products for current user
     */
    public static function clearCacheForUser()
    {
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $cacheKey = "stripe_products_user_{$userId}";
        Cache::forget($cacheKey);
    }

    /**
     * Actually fetch from Stripe
     */
    private static function fetchStripeProducts(): array
    {
        $stripe = new StripeClient(config('services.stripe.secret'));
    
        $products = $stripe->products->all(
            ['limit' => config('services.stripe.product_limit'), 'expand' => ['data.default_price']],
            ['stripe_account' => Auth::user()->stripe_account_id]
        );

        $rows = [];
        foreach ($products->data as $product) {
            $priceObj = $product->default_price;
            $priceValue = $priceObj?->unit_amount ? $priceObj->unit_amount / 100 : null;
            $currency = $priceObj?->currency ?? null;

            $rows[] = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description ?? '',
                'active' => (bool)$product->active,
                'price' => $priceValue,
                'currency' => $currency,
            ];
        }

        return $rows;
    }
}
