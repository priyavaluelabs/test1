$accessibleClubIds = array_map('intval', $this->user->getAccessibleClubs());

/**
 * SECURITY:
 * - Only clubs the user can access
 * - Only roles belonging to the authenticated user
 * - No trust in frontend
 */
$clubs = Club::whereIn('id', $accessibleClubIds)
    ->pluck('title', 'id');

$userRoles = FodUserRole::where('user_id', $this->user->id)
    ->whereIn('club_id', $accessibleClubIds)
    ->get(['club_id', 'glofox_verified_at']);

$this->clubsWithGlofoxStatus = $userRoles->map(function ($role) use ($clubs) {
    return [
        'club_id'     => $role->club_id,
        'club_title'  => $clubs[$role->club_id] ?? '—',
        'is_verified' => ! is_null($role->glofox_verified_at),
        'verified_at' => $role->glofox_verified_at,
    ];
})->values()->toArray();


@foreach ($clubsWithGlofoxStatus as $club)
    <div class="flex items-center justify-between gap-3">
        <span class="text-sm font-medium text-gray-700">
            {{ $club['club_title'] }}
        </span>

        @if ($club['is_verified'])
            <span class="inline-flex items-center gap-1 px-3 py-1 text-sm
                        bg-green-100 text-green-700 rounded-full">
                ✔ Glofox Verified
            </span>
        @else
            <span class="inline-flex items-center gap-1 px-3 py-1 text-sm
                        bg-red-100 text-red-700 rounded-full">
                ✖ Not Verified
            </span>
        @endif
    </div>
@endforeach
