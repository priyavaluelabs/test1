<?php

namespace Klyp\Nomergy\Http\Traits;

use Klyp\Nomergy\Models\UserPortal;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

trait TrainerValidationTrait
{
    /**
     * Validate trainer belongs to the user's club and is onboarded on Stripe.
     *
     * @param object $user      Authenticated user
     * @param int    $trainerId Trainer ID to validate
     * @param object $trainerService Instance of trainer service for fetching club trainers
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function validateTrainerForUserClub($user, $trainerId, $trainerService)
    {
        $clubId = $user->profile ? $user->profile->club_id : null;

        if (empty($clubId)) {
            throw new HttpResponseException(
                response()->json([
                    'error' => [
                        'message' => __('klyp.nomergy::fod.user_has_no_club'),
                        'status_code' => Response::HTTP_BAD_REQUEST,
                    ],
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        $trainers = $trainerService->getTrainerIdsForClub($clubId);

        if (! in_array($trainerId, $trainers)) {
            throw new HttpResponseException(
                response()->json([
                    'error' => [
                        'message' => __('klyp.nomergy::fod.trainer_having_different_club_as_login_user'),
                        'status_code' => Response::HTTP_BAD_REQUEST,
                    ],
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        $trainer = UserPortal::find($trainerId);

        if (! $trainer || ! $trainer->stripe_account_id || ! $trainer->is_onboarded) {
            throw new HttpResponseException(
                response()->json([
                    'error' => [
                        'message' =>  __('klyp.nomergy::fod.trainer_not_in_stripe'),
                        'status_code' => Response::HTTP_BAD_REQUEST,
                    ],
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        return $trainer;
    }
}
