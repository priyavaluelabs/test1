<?php

namespace Klyp\Nomergy\Http\Traits;

use Klyp\Nomergy\Models\UserPortal;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

trait TrainerValidationTrait
{
    public function validateTrainerForUserClub($user, $trainerId, $trainerService)
    {
        $clubId = $user->profile ? $user->profile->club_id : null;

        if (empty($clubId)) {
            throw new HttpResponseException(
                response()->json([
                    'status'  => 'error',
                    'message' => __('klyp.nomergy::fod.user_has_no_club'),
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        $trainers = $trainerService->getTrainerIdsForClub($clubId);

        if (! in_array($trainerId, $trainers)) {
            throw new HttpResponseException(
                response()->json([
                    'status'  => 'error',
                    'message' => __('klyp.nomergy::fod.trainer_having_different_club_as_login_user'),
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        $trainer = UserPortal::find($trainerId);

        if (! $trainer || ! $trainer->stripe_account_id || ! $trainer->is_onboarded) {
            throw new HttpResponseException(
                response()->json([
                    'status'  => 'error',
                    'message' => __('klyp.nomergy::fod.trainer_not_in_stripe'),
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        return $trainer;
    }
}
