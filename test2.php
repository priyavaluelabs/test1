<?php

namespace Klyp\Nomergy\Services\Stripe;

use Illuminate\Support\Collection;
use Klyp\Nomergy\Models\FodUserRole;
use Klyp\Nomergy\Models\PTBillingUserTrainer;
use Klyp\Nomergy\Models\UserPortal;

class PTBillingStripeTrainerService
{
    private const DEFAULT_TRAINER_IMAGE =
        'https://cdn.glofox.com/platform/liftcrmdemobff/branches/65b0ff5177129a7f7a049ec7/trainers/693815351a4a0899b709bb27/default.png?v=1765282952';

    /**
     * Get all onboarded trainers available for a club.
     */
    public function getTrainersForClub(int $clubId): array
    {
        $trainerIds = $this->getTrainerIdsForClub($clubId);

        return $this->resolveTrainersByIds($trainerIds);
    }

    /**
     * Get trainer IDs associated with a club.
     */
    public function getTrainerIdsForClub(int $clubId): array
    {
        return FodUserRole::query()
            ->where('club_id', $clubId)
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Get trainers assigned to a specific customer.
     */
    public function getAssignedTrainersForUser(int $userId): array
    {
        $trainerIds = PTBillingUserTrainer::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('trainer_id')
            ->toArray();

        return $this->resolveTrainersByIds($trainerIds);
    }

    /**
     * Resolve trainer records and format for API response.
     */
    private function resolveTrainersByIds(array $trainerIds): array
    {
        if ($trainerIds === []) {
            return [];
        }

        $trainers = $this->fetchOnboardedTrainers($trainerIds);

        return $this->mapTrainersForResponse($trainers);
    }

    /**
     * Fetch onboarded trainers from portal.
     */
    private function fetchOnboardedTrainers(array $trainerIds): Collection
    {
        return UserPortal::query()
            ->whereIn('id', $trainerIds)
            ->where('is_onboarded', true)
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get();
    }

    /**
     * Map trainer models into API response structure.
     */
    private function mapTrainersForResponse(Collection $trainers): array
    {
        return $trainers
            ->map(fn (UserPortal $trainer) => [
                'id'         => $trainer->id,
                'name'       => trim("{$trainer->first_name} {$trainer->last_name}"),
                'email'      => $trainer->email,
                'image_url'  => self::DEFAULT_TRAINER_IMAGE,
            ])
            ->values()
            ->all();
    }
}




===========

<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeTrainerService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiPTBillingStripeCustomerTrainerController extends ApiController
{
    public function __construct(
        protected PTBillingStripeTrainerService $trainerService
    ) {}

    /**
     * Return all trainers available for the customer's club.
     */
    public function getClubTrainers()
    {
        $user = parent::getAuth();

        if (! $user->profile_club_id) {
            return parent::respondError(
                __('klyp.nomergy::fod.user_has_no_club'),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            return $this->respondSuccess([
                'club'     => $user->profile_club_title,
                'trainers' => $this->trainerService
                    ->getTrainersForClub($user->profile_club_id),
            ]);
        } catch (Throwable) {
            return parent::respondError(
                __('klyp.nomergy::fod.user_has_no_trainer_list'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Return trainers assigned to the customer.
     */
    public function getAssignedTrainers()
    {
        $user = parent::getAuth();

        if (! $user->id) {
            return parent::respondError(
                __('klyp.nomergy::fod.user_not_found'),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            return $this->respondSuccess([
                'trainers' => $this->trainerService
                    ->getAssignedTrainersForUser($user->id),
            ]);
        } catch (Throwable) {
            return parent::respondError(
                __('klyp.nomergy::fod.user_has_no_trainer_list'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function respondSuccess(array $payload)
    {
        return parent::respond(
            array_merge(['status' => 'success'], $payload),
            Response::HTTP_OK
        );
    }
}
