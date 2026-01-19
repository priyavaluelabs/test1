use Filament\Forms;
use Filament\Forms\Form;

public function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Discount Details')
                ->schema([

                    // 🔹 NAME
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Name (appears on receipts)')
                                ->required()
                                ->columnSpan(2),
                        ]),

                    // 🔹 PRODUCT
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\Select::make('products')
                                ->label('Product(s)')
                                ->options([
                                    'all' => 'All Products',
                                ])
                                ->columnSpan(2),
                        ]),

                    // 🔹 DISCOUNT TYPE + VALUE (SAME ROW)
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\ToggleButtons::make('discount_type')
                                ->label('Discount Type')
                                ->options([
                                    'percentage' => 'Percentage',
                                    'fixed' => 'Fixed amount',
                                ])
                                ->inline()
                                ->reactive()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('value')
                                ->label('Value')
                                ->numeric()
                                ->suffix(fn ($get) =>
                                    $get('discount_type') === 'percentage' ? '%' : '$'
                                )
                                ->required()
                                ->columnSpan(1)
                                ->disabled(fn ($get) => blank($get('discount_type'))),
                        ]),

                    // 🔹 DESCRIPTION
                    Forms\Components\Textarea::make('description')
                        ->label('Description (optional)')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
}
