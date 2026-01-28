public static function canAccess(): bool
    {
        $user = Auth::user();

        // redirect to not found if they are not verified in glofox
        $clubs = app(ClubGlofoxStatusService::class)
            ->getForUser($user);
        $isGlofoxVerified = collect($clubs)
            ->every(fn ($club) => $club['is_verified'] === true);

        return $user && $user->hasRole(Role::ZoneInstructor);
    }
