->after(fn () => Notification::make()
        ->title('Promo code created')
        ->success()
        ->send()
    );
