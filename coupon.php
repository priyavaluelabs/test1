<p class="text-base font-medium">
    {{ implode(', ', collect($formData['products'])->map(fn($id) => $this->getStripeProducts()[$id] ?? $id)) }}
</p>
