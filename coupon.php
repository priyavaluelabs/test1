namespace App\Filament\Enum;

enum StripeAccountStatus: string
{
    case PENDING         = 'pending';
    case RESTRICTED      = 'restricted';
    case RESTRICTED_SOON = 'restricted_soon';
    case ENABLED         = 'enabled';
    case COMPLETE        = 'complete';
    case REJECTED        = 'rejected';
    case UNKNOWN         = 'unknown';

    public function title(): string
    {
        return match ($this) {
            self::PENDING         => 'Onboarding in Progress',
            self::RESTRICTED      => 'Account Restricted',
            self::RESTRICTED_SOON => 'Action Required Soon',
            self::ENABLED         => 'Account Enabled',
            self::COMPLETE        => 'Onboarding Completed!',
            self::REJECTED        => 'Account Not Approved',
            self::UNKNOWN         => 'Account Status Unknown',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::PENDING =>
                'Your account details are under review. Please complete the pending verification steps to continue.',

            self::RESTRICTED =>
                'Your Stripe account has some pending requirements. Payouts and charges are temporarily unavailable until these are resolved.',

            self::RESTRICTED_SOON =>
                'Additional information is needed to keep your Stripe account active. Please complete the required steps before the deadline.',

            self::ENABLED =>
                'Your Stripe account is active. Some additional details may be required later.',

            self::COMPLETE =>
                'Your Stripe account is active and ready to use.',

            self::REJECTED =>
                'Your Stripe account could not be approved. Please contact support for more information.',

            self::UNKNOWN =>
                'We are unable to determine your Stripe account status at the moment.',
        };
    }

    /**
     * Tailwind UI classes
     */
    public function uiColors(): array
    {
        return match ($this) {
            self::COMPLETE => [
                'bg'     => 'bg-green-100',
                'border' => 'border-green-300',
                'text'   => 'text-green-800',
            ],

            self::ENABLED => [
                'bg'     => 'bg-blue-100',
                'border' => 'border-blue-300',
                'text'   => 'text-blue-800',
            ],

            self::PENDING,
            self::RESTRICTED_SOON => [
                'bg'     => 'bg-yellow-100',
                'border' => 'border-yellow-300',
                'text'   => 'text-yellow-800',
            ],

            self::RESTRICTED,
            self::REJECTED,
            self::UNKNOWN => [
                'bg'     => 'bg-red-100',
                'border' => 'border-red-300',
                'text'   => 'text-red-800',
            ],
        };
    }
}



=====

@if ($stripeStatus && empty($currentlyDue))
    @php($color = $stripeStatus->uiColors())

    <div class="p-4 rounded-lg {{ $color['bg'] }} {{ $color['border'] }} {{ $color['text'] }}">
        <strong>{{ $stripeStatus->title() }}</strong><br>
        {{ $stripeStatus->message() }}
    </div>
@endif
