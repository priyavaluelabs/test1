$clubId = 15; // your club id

// find trainer role id (no hardcoding)
$trainerRoleId = FodRole::where('code', 'trainer')->value('id');

$trainers = UserPortal::whereHas('roles', function ($q) use ($clubId, $trainerRoleId) {
    $q->where('fod_role_id', $trainerRoleId)
      ->wherePivot('club_id', $clubId);
})
->get([
    'id',
    'first_name',
    'last_name'
]);
