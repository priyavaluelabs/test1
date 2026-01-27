 $accessibleClubs = Club::whereIn('id', $this->user->getAccessibleClubs())->get();

        print_r($accessibleClubs);
        die;

        $clubId = FodUserRole::where('user_id', $this->user->id)
            ->get();
        print_r($clubId);
        die;
