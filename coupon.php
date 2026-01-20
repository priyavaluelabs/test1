use Filament\Forms\Components\Select;


protected function getStripeProducts(): array
{
    $products = $this->stripeClient()
        ->products
        ->all(['active' => true, 'limit' => 100]);

    return collect($products->data)
        ->mapWithKeys(fn ($product) => [
            $product->id => $product->name,
        ])
        ->toArray();
}




Select::make('products')
    ->label('Product(s)')
    ->statePath('formData.products')
    ->multiple()
    ->searchable()
    ->preload()
    ->options(fn () => [
            'all' => 'All Products',
        ] + $this->getStripeProducts())
    ->columnSpan(2),


'metadata' => [
    'description' => $data['description'] ?? '',
    'products' => implode(',', $data['products'] ?? []),
],
