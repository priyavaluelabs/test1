use Illuminate\Support\Facades\Auth;
use App\Services\Stripe\ClubGlofoxStatusService;
use App\Filament\Enum\Role;

public static function canAccess(): bool
{
    $user = Auth::user();

    // Basic access check
    if (! $user || ! $user->hasRole(Role::ZoneInstructor)) {
        return false;
    }

    // Stripe onboarding does NOT require Glofox verification
    if (static::isStripeOnboardingRoute()) {
        return true;
    }

    // All other routes require Glofox verification
    return static::isGlofoxVerified($user);
}

protected static function isStripeOnboardingRoute(): bool
{
    return request()->is('*/stripe/onboarding');
}

protected static function isGlofoxVerified($user): bool
{
    $clubs = app(ClubGlofoxStatusService::class)->getForUser($user);

    return collect($clubs)
        ->every(fn ($club) => (bool) ($club['is_verified'] ?? false));
}
