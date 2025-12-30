<?php

namespace Klyp\Nomergy\Http\Traits;

use Klyp\Nomergy\Models\UserPortal;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Klyp\Nomergy\Services\PTBillingTrainerService;

trait TrainerValidationTrait
{
    /**
     * Service injected from the class using this trait
     */
    protected PTBillingTrainerService $trainerService;

    /**
     * Validate trainer belongs to the user's club and is onboarded on Stripe.
     *
     * @param object $user
     * @param int    $trainerId
     *
     * @return UserPortal
     */
    public function validateTrainerForUserClub($user, $trainerId)
    {
        $clubId = $user->profile?->club_id;

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

        $trainerIds = $this->trainerService->getTrainerIdsForClub($clubId);

        if (! in_array($trainerId, $trainerIds)) {
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
                        'message' => __('klyp.nomergy::fod.trainer_not_in_stripe'),
                        'status_code' => Response::HTTP_BAD_REQUEST,
                    ],
                ], Response::HTTP_BAD_REQUEST)
            );
        }

        return $trainer;
    }
}
