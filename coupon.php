/**
     * FILAMENT DELETE ACTION (used inside Blade)
     */
    public function deleteAction(): Actions\Action
    {
        return Actions\Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Coupon')
            ->modalDescription('Are you sure you want to delete this coupon? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete')
            ->action(function () {
                try {
                    $this->stripeClient()
                        ->coupons
                        ->delete($this->couponId);

                    Notification::make()
                        ->title('Coupon deleted successfully')
                        ->success()
                        ->send();

                    $this->redirect('/admin/stripe/discounts');

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Unable to delete coupon')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
