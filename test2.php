/ ✅ Validate input
    $validator = Validator::make($this->editForm, [
        'id' => ['required', 'string', 'exists:stripe_products,id'],
        'description' => ['nullable', 'string', 'max:5000'],
        'active' => ['required', 'boolean'],
        'price' => ['nullable', 'numeric', 'min:0.01'],
    ]);

    if ($validator->fails()) {
        Notification::make()
            ->title(__('stripe.validation_failed'))
            ->body($validator->errors()->first())
            ->danger()
            ->send();
        return;
    }

    $data = $validator->validated();