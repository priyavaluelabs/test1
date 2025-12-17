<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeProductService;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeLoggerService;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeTrainerService;
use Klyp\Nomergy\Models\UserPortal;
use Illuminate\Http\JsonResponse;

class ApiPTBillingStripeProductController extends ApiController
{   
    /**
     * Stripe service instance for product operations.
     *
     * @var StripeProductService
     */
    protected $stripeProductService;

    /**
     * Stripe logger instance.
     *
     * @var logger
     */
    protected $logger;

    /**
     * Trainer service for fetching and formatting trainer data.
     *
     * @var PTBillingStripeTrainerService
     */
    protected $trainerService;

    /**
     * Inject StripeProductService.
     *
     * @param StripeProductService $stripeProductService The service responsible for interacting with Stripe's Product API.
     * @param PTBillingStripeLoggerService $logger The logger service used to record Stripe events and actions, including user_id.
     *
     */
    public function __construct(
        PTBillingStripeProductService $stripeProductService,
        PTBillingStripeTrainerService $trainerService,
        PTBillingStripeLoggerService $logger
    ) {
        $this->stripeProductService = $stripeProductService;
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
            $trainer = UserPortal::find($trainerId);
            if (!$trainer || !$trainer->stripe_account_id || !$trainer->is_onboarded ) {
                $this->logger->warning("products.fetch.invalid_trainer", __('klyp.nomergy::fod.trainer_not_in_stripe'), [
                    'trainer_id' => $trainerId], $user->id ?? null);
                $this->logger->flush();

                return parent::respondError(__('klyp.nomergy::fod.trainer_not_in_stripe'),Response::HTTP_BAD_REQUEST);
            }

            $clubId = $user->profile_club_id;
            if (empty($clubId)) {
                return parent::respondError(__('klyp.nomergy::fod.user_has_no_club'), Response::HTTP_BAD_REQUEST);
            }

            $trainers = $this->trainerService->getTrainerIdsForClub($clubId);
            if (! in_array($trainerId, $trainers)) {
                $this->logger->warning("products.fetch.invalid_trainer", __('klyp.nomergy::fod.trainer_having_different_club_as_login_user'), [
                    'trainer_id' => $trainerId], $user->id ?? null);
                $this->logger->flush();

                return parent::respondError(__('klyp.nomergy::fod.trainer_having_different_club_as_login_user'),Response::HTTP_BAD_REQUEST);
            }

            $terms = !empty($trainer->billing_setting->terms) ? $trainer->billing_setting->terms : null;

            $products = $this->stripeProductService->getActiveProductsWithDefaultPrice($trainer->stripe_account_id, $trainer->corp_partner_id);
            $this->logger->info("products.fetch.success", __('klyp.nomergy::fod.fetching_trainer_product'), [
                'trainer_id'    => $trainerId,
                'product_count' => count($products)
            ], $user->id ?? null);
            $this->logger->flush();

            $trainerPayload = [
                'name' => $trainer->first_name . ' ' . $trainer->last_name,
                'email' => $trainer->email,
                'image_url' => 'https://cdn.glofox.com/platform/liftcrmdemobff/branches/65b0ff5177129a7f7a049ec7/trainers/693815351a4a0899b709bb27/default.png?v=1765282952',
                'terms' => $terms,
            ];

            return parent::respond(['status' => 'success',
                'products' => $products,
                'trainer' => $trainerPayload
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error("products.fetch.error", $e->getMessage(), [], $user->id ?? null);
            $this->logger->flush();

            return parent::respondError(__('klyp.nomergy::fod.stripe_unexpected_error'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}