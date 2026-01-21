protected function getPromotionCodes(string $couponId): array
{
    $promotionCodes = $this->stripeClient()->promotionCodes->all(
        [
            'coupon' => $couponId,
            'limit'  => 100,
        ],
        [
            'stripe_account' => $this->user?->stripe_account_id,
        ]
    );

    return collect($promotionCodes->data)
        ->map(fn ($promo) => $this->mapPromotionCode($promo))
        ->values()
        ->toArray();
}

==


protected function mapPromotionCode($promo): array
{
    $now = now()->timestamp;

    $isExpired  = $promo->expires_at && $now > $promo->expires_at;
    $isInactive = ! $promo->active;
    $isActive   = $promo->active && ! $isExpired;

    $status = match (true) {
        $isExpired  => 'Expired',
        $isInactive => 'Inactive',
        default     => 'Active',
    };

    return [
        'id'               => $promo->id,
        'code'             => $promo->code,
        'status'           => $status,

        'active'           => $promo->active,
        'is_expired'       => $isExpired,

        'times_redeemed'   => $promo->times_redeemed,
        'max_redemptions'  => $promo->max_redemptions,
        'usage'            => $promo->max_redemptions
            ? "{$promo->times_redeemed}/{$promo->max_redemptions} used"
            : '—',

        'validity'         => $promo->expires_at
            ? 'Valid until ' . Carbon::createFromTimestamp($promo->expires_at)->format('M j, Y')
            : 'No expiry',

        'is_first'         => $promo->restrictions?->first_time_transaction ?? false,
    ];
}
