$data = $this->formData;

// Base data
$couponData = [
    'name' => $data['name'],
    'duration' => 'once',
    'metadata' => [
        'description' => $data['description'] ?? '',
        'products' => $data['products'] ?? 'all',
    ],
];

// Only add percent_off if discount type is percentage
if ($data['discount_type'] === 'percentage') {
    $couponData['percent_off'] = floatval($data['value']);
}

// Only add amount_off and currency if discount type is fixed
if ($data['discount_type'] === 'fixed') {
    $couponData['amount_off'] = intval($data['value'] * 100); // cents
    $couponData['currency'] = 'usd'; // or your currency
}

// Create the coupon
$coupon = \Stripe\Coupon::create($couponData);

// Optional: create a promotion code
$promotionCode = \Stripe\PromotionCode::create([
    'coupon' => $coupon->id,
    'active' => true,
]);
