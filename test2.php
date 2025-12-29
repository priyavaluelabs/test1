<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Klyp\Nomergy\Services\Stripe\PTBillingProductService;
use Klyp\Nomergy\Services\Stripe\PTBillingLoggerService;
use Klyp\Nomergy\Services\Stripe\PTBillingTrainerService;
use Klyp\Nomergy\Http\Traits\TrainerValidationTrait;
use Illuminate\Http\JsonResponse;

class ApiPTBillingProductController extends ApiController
{
    use TrainerValidationTrait;
    
    /**
     * Stripe service instance for product operations.
     */
    protected $productService;

    /**
     * Stripe logger instance.
     */
    protected $logger;

    /**
     * Trainer service used to validate the trainer with respect of login user club trainers.
     */
    protected $trainerService;

    /**
     * Inject StripeProductService, .
     *
     * @param StripeProductService $stripeProductService The service responsible for interacting with Stripe's Product API.
     * @param trainerService $trainerService The service responsible for interacting login user club trainers.
     * @param logger $logger The logger service used to record Stripe events and actions, including user_id.
     *
     */
    public function __construct(
        PTBillingProductService $productService,
        PTBillingTrainerService $trainerService,
        PTBillingLoggerService $logger
    ) {
        $this->productService = $productService;
        $this->trainerService = $trainerService;
        $this->logger = $logger;
    }

    /**
     * Get all active products along with their default price
     * for a given trainer's connected Stripe account.
     *
     * @param integer $trainerId
     * @return JsonResponse
     */
    public function getTrainerProducts($trainerId): JsonResponse
    {
        $user = parent::getAuth();

        $this->logger->info("products.fetch", __('klyp.nomergy::fod.fetching_trainer_product'), [
            'trainer_id' => $trainerId], $user->id ?? null);

        try {
            $trainer = $this->validateTrainerForUserClub($user, $trainerId, $this->trainerService);
            $result = $this->productService->getActiveProductsWithDefaultPrice($trainer);

            $this->logger->info("products.fetch.success", __('klyp.nomergy::fod.fetching_trainer_product'), [
                'trainer_id'    => $trainerId,
                'product_count' => count($result['products'])
            ], $user->id ?? null);
            $this->logger->flush();

            return parent::respond(['status' => 'success',
                'products' => $result['products'],
                'trainer' => $result['trainer']
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error("products.fetch.error", $e->getMessage(), [], $user->id ?? null);
            $this->logger->flush();

            return parent::respondError(__('klyp.nomergy::fod.stripe_unexpected_error'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

=============================

<?php

namespace Klyp\Nomergy\Http\Traits;

use Klyp\Nomergy\Models\UserPortal;
use Symfony\Component\HttpFoundation\Response;

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
            abort(Response::HTTP_BAD_REQUEST, __('klyp.nomergy::fod.user_has_no_club'));
        }

        $trainers = $trainerService->getTrainerIdsForClub($clubId);
        if (! in_array($trainerId, $trainers)) {
            abort(Response::HTTP_BAD_REQUEST, __('klyp.nomergy::fod.trainer_having_different_club_as_login_user'));
        }

        $trainer = UserPortal::find($trainerId);
        if (!$trainer || !$trainer->stripe_account_id || !$trainer->is_onboarded) {
            abort(Response::HTTP_BAD_REQUEST, __('klyp.nomergy::fod.trainer_not_in_stripe'));
        }

        return $trainer;
    }
}
