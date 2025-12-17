<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Models\FodUserRole;
use Klyp\Nomergy\Models\UserPortal;
use Klyp\Nomergy\Models\PTBillingUserTrainer;

class PTBillingStripeTrainerService
{
    /**
     * Get all trainers available for a club.
     *
     * @param int $clubId
     * @return array
     */
    public function getTrainersByClub(int $clubId): array
    {
        $portalUserIds = FodUserRole::where('club_id', $clubId)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        if (empty($portalUserIds)) {
            return [];
        }

        return $this->fetchAndFormatTrainers($portalUserIds);
    }

    /**
     * Get all trainer ids available for a club.
     *
     * @param int $clubId
     * @return array
     */
    public function getTrainerIdsByClub(int $clubId): array
    {
        $portalUserIds = FodUserRole::where('club_id', $clubId)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        if (empty($portalUserIds)) {
            return [];
        }

        return $portalUserIds;
    }

    /**
     * Get trainers assigned to a specific user.
     *
     * @param int $userId
     * @return array
     */
    public function getAssignedTrainersByUser(int $userId): array
    {
        $trainerIds = PTBillingUserTrainer::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('trainer_id')
            ->toArray();

        if (empty($trainerIds)) {
            return [];
        }

        return $this->fetchAndFormatTrainers($trainerIds);
    }

    /**
     * Fetch trainers from portal and format them.
     *
     * @param array $trainerIds
     * @return array
     */
    private function fetchAndFormatTrainers(array $trainerIds): array
    {
        $trainers = UserPortal::whereIn('id', $trainerIds)
            ->where('is_onboarded', 1)
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        return $this->formatPortalUsers($trainers);
    }

    /**
     * Map portal users into simplified array structure used by the API.
     *
     * @param \Illuminate\Support\Collection $users
     * @return array
     */
    private function formatPortalUsers($users): array
    {
        return $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'email' => $u->email,
                'image_url' => 'https://cdn.glofox.com/platform/liftcrmdemobff/branches/65b0ff5177129a7f7a049ec7/trainers/693815351a4a0899b709bb27/default.png?v=1765282952'
            ];
        })->values()->all();
    }
}


=======================


<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeTrainerService;
use Symfony\Component\HttpFoundation\Response;

class ApiPTBillingStripeCustomerTrainerController extends ApiController
{
    /**
     * Trainer service for fetching and formatting trainer data.
     *
     * @var PTBillingStripeTrainerService
     */
    protected $trainerService;

    /**
     * Constructor to inject the trainer service.
     *
     * @param PTBillingStripeTrainerService $trainerService
     */
    public function __construct(PTBillingStripeTrainerService $trainerService)
    {
        $this->trainerService = $trainerService;
    }
    /**
     * Retrieve a list of trainer of customer.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getTrainerListByCustomer()
    {
        $user = parent::getAuth();

        $clubId = $user->profile_club_id;
        
        if (empty($clubId)) {
            return parent::respondError(__('klyp.nomergy::fod.user_has_no_club'), Response::HTTP_BAD_REQUEST);
        }

        try {
            $trainers = $this->trainerService->getTrainersByClub($clubId);

            return parent::respond([
                'status' => 'success',
                'trainers' => $trainers,
                'club' => $user->profile_club_title
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return parent::respondError(__('klyp.nomergy::fod.user_has_no_trainer_list'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


    /**
     * Retrieve a assigned list of trainer of customer.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getAssignedTrainerListByCustomer()
    {
        $user = parent::getAuth();

        if (empty($user->id)) {
            return parent::respondError(__('klyp.nomergy::fod.user_not_found'), Response::HTTP_BAD_REQUEST);
        }

        try {
            $trainers = $this->trainerService->getAssignedTrainersByUser($user->id);

            return parent::respond([
                'status' => 'success',
                'trainers' => $trainers
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return parent::respondError(__('klyp.nomergy::fod.user_has_no_trainer_list'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}