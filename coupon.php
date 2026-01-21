$this->promoCodes = collect($this->promoCodes)
    ->map(fn ($promo) => 
        $promo['id'] === $promoCodeId
            ? array_merge($promo, ['active' => $active])
            : $promo
    )
    ->toArray();
