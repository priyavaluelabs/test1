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
    }

    public function setPromoCodeActiveStatus(string $promoCodeId, bool $active): void
    {
        $this->stripeClient()->promotionCodes->update(
            $promoCodeId,
            ['active' => $active],
            ['stripe_account' => $this->user->stripe_account_id]
        );

        $this->notify('Promo code ' . ($active ? 'unarchived' : 'archived'));
        $this->loadPromoCodes();
    }

    public function archivePromoCode(string $promoCodeId): void
    {
        $this->setPromoCodeActiveStatus($promoCodeId, false);
    }

    public function unArchivePromoCode(string $promoCodeId): void
    {
        $this->setPromoCodeActiveStatus($promoCodeId, true);
    }
