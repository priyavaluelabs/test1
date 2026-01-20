public function deleteCoupon(): void
{
    if (! $this->isEdit || ! $this->couponId) {
        return;
    }

    try {
        // Stripe allows deleting coupons
        $this->stripeClient()
            ->coupons
            ->delete($this->couponId);

        Notification::make()
            ->title('Coupon deleted successfully')
            ->success()
            ->send();

        // Redirect to listing page
        $this->redirect('/admin/stripe/discounts');

    } catch (\Exception $e) {
        Notification::make()
            ->title('Unable to delete coupon')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}




=======


<x-filament::button
    color="danger"
    wire:click="deleteCoupon"
    wire:confirm="Are you sure you want to delete this coupon? This action cannot be undone."
>
    Delete
</x-filament::button>



