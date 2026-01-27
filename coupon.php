$clubs = FodUserRole::with('club:id,title')
    ->where('user_id', $this->user->id)
    ->whereIn('club_id', $this->user->getAccessibleClubs())
    ->get()
    ->map(function ($role) {
        return [
            'club_title' => $role->club->title ?? null,
            'glofox_verified_at' => $role->glofox_verified_at,
        ];
    });

dd($clubs);
