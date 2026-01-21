$this->promoCodes[] = [
        'id' => $promo->id,
        'code' => $promo->code,
        'active' => $promo->active,
        'times_redeemed' => $promo->times_redeemed,
        'max_redemptions' => $promo->max_redemptions,
        'restrictions' => $promo->expires_at
            ? Carbon::createFromTimestamp($promo->expires_at)->format('M j, Y')
            : '—',
    ];
