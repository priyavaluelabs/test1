public function handle(): void
{
    $this->resetAllGlofoxVerification();

    $accessibleClubs = Club::whereIn('id', $this->user->getAccessibleClubs())->get();
    $userEmail = strtolower('priya.singh+1991@valuelabs.com');

    foreach ($accessibleClubs as $club) {
        if (empty($club->glofox_branch_id)) {
            continue;
        }

        $staff   = new Staff($this->getGlofoxConfig($club->glofox_branch_id));
        $page    = 1;

        do {
            $response = $staff->get(null, $page);
            $data     = $response->data ?? [];

            if (!is_array($data)) {
                break;
            }

            foreach ($data as $trainer) {
                if (
                    strtolower($trainer['email'] ?? '') === $userEmail &&
                    $trainer['branch_id'] == $club->glofox_branch_id
                ) {
                    FodUserRole::where('club_id', $club->id)
                        ->where('user_id', $this->user->id)
                        ->update([
                            'glofox_verified_at' => now(),
                        ]);

                    break 3; // exit trainer loop, pagination loop, club loop
                }
            }

            $page++;

        } while ($response->has_more ?? false);
    }
}
