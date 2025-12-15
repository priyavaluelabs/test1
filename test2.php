public function save(StripeClient $stripe): void
    {
        $record = StripeProduct::find($this->editForm['id']);

        if (! $record) {
            Notification::make()
                ->title(__('stripe.no_stripe_product'))
                ->danger()
                ->send();
            return;
        }

        $data = $this->editForm;

        $hasDescriptionChange = filled($data['description']) && $data['description'] !== $record->description;
        $hasActiveChange = isset($data['active']) && (bool) $data['active'] !== (bool) $record->active;
        $hasPriceChange = filled($data['price']) && (float) $data['price'] !== (float) $record->price;

        if (! ($hasDescriptionChange || $hasActiveChange || $hasPriceChange)) {
            Notification::make()
                ->title(__('stripe.error_no_field_update'))
                ->warning()
                ->send();
            return;
        }

        try {
            $trainerAccount = Auth::user()->stripe_account_id;

            // Update product fields
            if ($hasDescriptionChange || $hasActiveChange) {
                $stripe->products->update(
                    $record->id,
                    [
                        'description' => $data['description'],
                        'active' => (bool) $data['active'],
                    ],
                    ['stripe_account' => $trainerAccount]
                );
            }

            // Update price (creates new Stripe price)
            if ($hasPriceChange) {
                $newPrice = $stripe->prices->create(
                    [
                        'product' => $record->id,
                        'unit_amount' => (int) round($data['price'] * 100),
                        'currency' => $record->currency,
                    ],
                    ['stripe_account' => $trainerAccount]
                );

                $stripe->products->update(
                    $record->id,
                    ['default_price' => $newPrice->id],
                    ['stripe_account' => $trainerAccount]
                );
            }

            // Refresh cache & UI
            StripeProduct::clearCacheForUser();
            $this->products = StripeProduct::getCachedRows();

            $this->isEditing = false;
            $this->dispatch('close-modal', id: 'edit-product');

            Notification::make()
                ->title(__('stripe.product_update_success'))
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('stripe.error_update_product'))
                ->danger()
                ->send();
        }
    }