$accessibleClubIds = $this->user->getAccessibleClubs();

$clubs = Club::whereIn('id', $accessibleClubIds)
    ->pluck('title', 'id'); // [id => title]

$userRoles = FodUserRole::where('user_id', $this->user->id)
    ->whereIn('club_id', $accessibleClubIds)
    ->get(['club_id', 'glofox_verified_at']);

$result = $userRoles->map(function ($role) use ($clubs) {
    return [
        'club_id'            => $role->club_id,
        'club_title'         => $clubs[$role->club_id] ?? null,
        'glofox_verified_at' => $role->glofox_verified_at,
    ];
});

dd($result);
