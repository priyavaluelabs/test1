<?php

namespace App\Services;

use App\Models\Club;
use App\Models\FodUserRole;
use App\Models\User;

class ClubGlofoxStatusService
{
    public function getForUser(User $user): array
    {
        $accessibleClubIds = $user->getAccessibleClubs();

        // Get club titles keyed by ID
        $clubs = Club::whereIn('id', $accessibleClubIds)
            ->pluck('title', 'id');

        // Get user roles with glofox status
        $userRoles = FodUserRole::where('user_id', $user->id)
            ->whereIn('club_id', $accessibleClubIds)
            ->get(['club_id', 'glofox_verified_at']);

        return $userRoles->map(function ($role) use ($clubs) {
            return [
                'club_id'     => $role->club_id,
                'club_title'  => $clubs[$role->club_id] ?? '—',
                'is_verified' => ! is_null($role->glofox_verified_at),
                'verified_at' => $role->glofox_verified_at,
            ];
        })->values()->toArray();
    }
}


$this->clubsWithGlofoxStatus = $clubService->getForUser($this->user);
