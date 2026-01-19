namespace App\Filament\Pages;

use Filament\Pages\Page;

class ReportsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Reports';

    // Optional: this mimics getPages() from Resource
    public static function getSubPages(): array
    {
        return [
            'summary' => SummaryReportPage::class,
            'details' => DetailsReportPage::class,
            'stats' => StatisticsReportPage::class,
        ];
    }
}
