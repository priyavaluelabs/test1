protected function loadCoupon(): void
{
    $coupon = $this->stripeClient()->coupons->retrieve($this->couponId);

    $productIds = isset($coupon->metadata->product_ids)
        ? explode(',', $coupon->metadata->product_ids)
        : ['all'];

    $products = $this->getStripeProducts(); // [id => name]

    $this->formData = [
        'name' => $coupon->name,
        'discount_type' => $coupon->percent_off ? 'percentage' : 'fixed',
        'value' => $coupon->percent_off ?? ($coupon->amount_off / 100),
        'products' => $productIds, // keep IDs for form select
        'products_names' => implode(', ', array_map(fn($id) => $products[$id] ?? $id, $productIds)),
        'description' => $coupon->metadata->description ?? null,
    ];

    $this->form->fill($this->formData);
}
