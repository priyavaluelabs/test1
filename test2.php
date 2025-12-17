/**
     * Validate trainer, club, and Stripe eligibility.
     *
     * @throws ApplicationException
     */
    private function validateTrainerAccess(int $trainerId, ?int $clubId): UserPortal
    {
        if (empty($clubId)) {
            throw new ApplicationException(
                __('klyp.nomergy::fod.user_has_no_club')
            );
        }

        $trainer = UserPortal::find($trainerId);

        if (! $trainer) {
            throw new ApplicationException(
                __('klyp.nomergy::fod.trainer_not_found')
            );
        }

        if (! $trainer->stripe_account_id || ! $trainer->is_onboarded) {
            throw new ApplicationException(
                __('klyp.nomergy::fod.trainer_not_in_stripe')
            );
        }

        $trainerIds = $this->trainerService
            ->getTrainerIdsForClub($clubId);

        if (! in_array($trainerId, $trainerIds, true)) {
            throw new ApplicationException(
                __('klyp.nomergy::fod.trainer_having_different_club_as_login_user')
            );
        }

        return $trainer;
    }

    private function getDefaultTrainerImage(): string
    {
        return 'https://cdn.glofox.com/platform/liftcrmdemobff/branches/65b0ff5177129a7f7a049ec7/trainers/693815351a4a0899b709bb27/default.png?v=1765282952';
    }


     return $this->respond([
                'status'   => 'success',
                'products' => $products,
                'trainer'  => [
                    'name'      => trim("{$trainer->first_name} {$trainer->last_name}"),
                    'email'     => $trainer->email,
                    'image_url' => $this->getDefaultTrainerImage(),
                    'terms'     => $trainer->billing_setting->terms ?? null,
                ],
            ], Response::HTTP_OK);