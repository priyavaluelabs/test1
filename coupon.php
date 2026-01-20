public function archivePromoCode(string $promoCodeId): void
{
    try {
        $this->stripeClient()
            ->promotionCodes
            ->update($promoCodeId, [
                'active' => false,
            ]);

        Notification::make()
            ->title('Promo code archived')
            ->success()
            ->send();

        $this->loadPromoCodes(); // refresh table
    } catch (\Exception $e) {
        Notification::make()
            ->title('Failed to archive promo code')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}


=====

public function unArchivePromoCode(string $promoCodeId): void
{
    try {
        $this->stripeClient()
            ->promotionCodes
            ->update($promoCodeId, [
                'active' => true,
            ]);

        Notification::make()
            ->title('Promo code unarchived')
            ->success()
            ->send();

        $this->loadPromoCodes(); // refresh table
    } catch (\Exception $e) {
        Notification::make()
            ->title('Failed to unarchive promo code')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}



