 protected function loadPromoCodes(): void
    {
        $promos = $this->stripeClient()->promotionCodes->all(
            ['coupon' => $this->couponId, 'limit' => 100],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->promoCodes = collect($promos->data)->map(fn ($promo) => [
            'id' => $promo->id,
            'code' => $promo->code,
            'active' => $promo->active,
            'times_redeemed' => $promo->times_redeemed,
            'max_redemptions' => $promo->max_redemptions,
            'restrictions' => $promo->expires_at
                ? 'Valid until ' . Carbon::createFromTimestamp($promo->expires_at)->format('M j, Y')
                : '—',
        ])->toArray();
    }\


 protected function getPromotionCodes(string $couponId): array
    {
        $promotionCodes = $this->stripeClient()->promotionCodes->all(
            [
                'coupon' => $couponId,
                'limit'  => 50,
            ],
            [
                'stripe_account' => $this->user?->stripe_account_id,
            ]
        );

        return collect($promotionCodes->data)
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
