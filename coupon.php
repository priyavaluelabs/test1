<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Klyp\Nomergy\Http\Traits\HasCurrencySymbolTrait;
use Klyp\Nomergy\Services\Stripe\PTBillingCustomerService;
use Klyp\Nomergy\Services\Stripe\PTBillingLoggerService;
use Klyp\Nomergy\Http\Requests\PTBillingPaymentHistoryByCustomerRequest;
use Klyp\Nomergy\Http\Requests\PTBillingCreateCustomerRequest;
use Klyp\Nomergy\Http\Requests\PTBillingPaymentIntentRequest;
use Klyp\Nomergy\Http\Requests\PTBillingInvoiceCreateRequest;
use Klyp\Nomergy\Http\Requests\PTBillingApplyPromocodeRequest;
use Klyp\Nomergy\Http\Requests\PTBillingRemovePromocodeRequest;
use Klyp\Nomergy\Http\Requests\PaymentHistoryExportRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Stripe\Exception\InvalidRequestException;
use Klyp\Nomergy\Jobs\SendStripeUserPaymentHistory;
use Symfony\Component\HttpFoundation\Response;
use Klyp\Nomergy\Models\UserPortal;

class ApiPTBillingCustomerController extends ApiController
{
    use HasCurrencySymbolTrait;

    /**
     * Customer service instance
     *
     * @var PTBillingCustomerService
     */
    protected $customerService;

    /**
     * Trainer instance (Stripe-connected user)
     *
     * @var UserPortal|null
     */
    protected $trainer;

    /**
     * Stripe logger instance.
     * 
     * @var logger
     */
    protected $logger;

    /**
     * Constructor to inject dependencies.
     *
     * @param PTBillingCustomerService $customerService Service responsible for interacting with stripe customer API.
     * @param PTBillingLoggerService $logger Service used to log stripe events, errors, and system actions.
     *
     */
    public function __construct(
        PTBillingCustomerService $customerService,
        PTBillingLoggerService $logger
    ) {
        $this->trainer = UserPortal::find(request()->trainer_id);
        $this->customerService = $customerService;
        $this->logger = $logger;
    }

    /**
     * Retrieve payment history list for customer.
     *
     * @param  PTBillingPaymentHistoryByCustomerRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getPaymentHistoryByCustomer(PTBillingPaymentHistoryByCustomerRequest $request)
    {
        $user = parent::getAuth();
        
        $this->logger->info(
            "payments.fetch",
            "Fetching payment intents",
            ['limit'  => $request->limit ? $request->limit : 50],
            $user->id ?? null
        );
        
        try {
            $data = [
                'limit' => $request->limit ? $request->limit : 50,
                'user_id' => $user->id
            ];

            $paymentHistoryData = $this->customerService->getPaymentHistory($data);
            $this->logger->info(
                "payments.fetch.success",
                "Payment intents fetched",
                ['count' => count($paymentHistoryData ?? [])],
                $user->id ?? null
            );
            $this->logger->flush();

            return parent::respond([
                'status' => 'success',
                'payments' => $paymentHistoryData
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error(
                "payments.fetch.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );
            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a customer in Stripe account.
     *
     * @param  PTBillingCreateCustomerRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     */
    public function createCustomer(PTBillingCreateCustomerRequest $request)
    {
        $user = parent::getAuth();

        $this->logger->info(
            "customer.create",
            "Creating customer",
            [
                'trainer_id' => $this->trainer->id
            ],
            $user->id ?? null
        );

        try {
            $data = [
                'user_id' => $user->id,
                'name' => $user->full_name ? $user->full_name : $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'stripe_account_id' => $this->trainer->stripe_account_id,
            ];

            $customerId = $this->customerService->createCustomer($data);

            $this->logger->info(
                "customer.create.success",
                "Customer created on Stripe",
                ['customer_id' => $customerId],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respond([
                'status' => 'success',
                'customer_id' => $customerId
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error(
                "customer.create.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );
            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Generate Invoice Id.
     * 
     * @param PTBillingInvoiceCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateInvoiceId(PTBillingInvoiceCreateRequest $request)
    {
        $user = parent::getAuth();

        $this->logger->info(
            "generate.invoice",
            "Generate invoice id",
            [
                'trainer_id' => $this->trainer->id,
                'customer_id' => $request->customer_id,
                'product_id'  => $request->product_id,
            ],
            $user->id ?? null
        );

        try {
            $data = [
                'stripe_account_id' => $this->trainer->stripe_account_id,
                'product_id'        => $request->product_id,
                'customer_id'       => $request->customer_id,
            ];

            // Generate invoice id
            $response = $this->customerService->generateInvoiceId($data);

            $this->logger->info(
                "generate.invoice.success",
                "Generate invoice id successfully",
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respond([
                'status'        => 'success',
                'invoice_id' => $response['invoice_id'],
            ], Response::HTTP_OK);

        } catch (InvalidRequestException $e) {
            $this->logger->warning(
                "generate.invoice.validation_failed",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();
    
            return parent::respondError(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "generate.invoice.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * Apply promo code and return discount details.
     * 
     * @param PTBillingApplyPromocodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyPromoCode(PTBillingApplyPromocodeRequest $request)
    {
        $user = parent::getAuth();

        $this->logger->info(
            "promo_code.apply",
            "Applying promo code",
            [
                'trainer_id' => $this->trainer->id,
                'product_id'  => $request->product_id,
                'promo_code' => $request->promo_code,
                'invoice_id'  => $request->invoice_id,
            ],
            $user->id ?? null
        );

        try {
            $data = [
                'stripe_account_id' => $this->trainer->stripe_account_id,
                'promo_code'        => $request->promo_code,
                'product_id'        => $request->product_id,
                'invoice_id'        => $request->invoice_id,
            ];

            // Only validate promo & calculate discount
            $response = $this->customerService->applyPromoCode($data);

            $this->logger->info(
                "promo_code.apply.success",
                "Promo code applied successfully",
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respond([
                'status'        => 'success',
                'promo_details' => $response['promo_details'],
                'currency'      => $this->getCurrencySymbol($this->trainer)
            ], Response::HTTP_OK);

        } catch (InvalidRequestException $e) {
            $this->logger->warning(
                "promo_code.apply.validation_failed",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();
    
            return parent::respondError(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(
                "promo_code.apply.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * Remove applied promo code from invoice.
     *
     * @param PTBillingRemovePromocodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePromoCode(PTBillingRemovePromocodeRequest $request)
    {
        $user = parent::getAuth();

        $this->logger->info(
            "promo_code.remove",
            "Removing promo code",
            [
                'trainer_id'  => $this->trainer->id,
                'invoice_id'  => $request->invoice_id,
            ],
            $user->id ?? null
        );

        try {
            $data = [
                'stripe_account_id' => $this->trainer->stripe_account_id,
                'invoice_id'        => $request->invoice_id,
            ];

            $this->customerService->removePromoCode($data);

            $this->logger->info(
                "promo_code.remove.success",
                "Promo code removed successfully",
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respond([
                'status'        => 'success',
            ], Response::HTTP_OK);

        } catch (InvalidRequestException $e) {
            $this->logger->warning(
                "promo_code.remove.validation_failed",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();
    
            return parent::respondError(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "promo_code.remove.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * Create a Stripe payment intent for payment.
     *
     * @param PTBillingPaymentIntentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     */
    public function createPaymentIntent(PTBillingPaymentIntentRequest $request)
    {
        $user = parent::getAuth();

        $this->logger->info(
            "payment_intent.create",
            "Creating payment intent",
            [
                'trainer_id'  => $this->trainer->id,
                'customer_id' => $request->customer_id,
                'product_id'  => $request->product_id,
                'invoice_id'  => $request->invoice_id, 
            ],
            $user->id ?? null
        );

        try {
            $data = [
                'product_id' => $request->product_id,
                'customer_id' => $request->customer_id,
                'trainer_name' => $this->trainer->first_name.  ' ' . $this->trainer->last_name,
                'stripe_account_id' => $this->trainer->stripe_account_id,
                'invoice_id'  => $request->invoice_id,
            ];

            $paymentSheetAndEphemeralData = $this->customerService->createPaymentIntent($data);

            $this->logger->info(
                "payment_intent.create.success",
                "Payment intent created",
                [
                    'payment_intent_id' => $paymentSheetAndEphemeralData['payment_intent']['id']
                ],
                $user->id ?? null
            );

            $this->logger->flush();

            return parent::respond([
                'status' => 'success',
                'payment_intent' => $paymentSheetAndEphemeralData['payment_intent']['id'],
                'client_secret'  => $paymentSheetAndEphemeralData['payment_intent']['client_secret'],
                'ephemeral_key'  => $paymentSheetAndEphemeralData['ephemeral_key'],
            ], Response::HTTP_OK);

        } catch (InvalidRequestException $e) {
            $this->logger->warning(
                "payment_intent.create.validation_failed",
                $e->getMessage(),
                [],
                $user->id ?? null
            );

            $this->logger->flush();
    
            return parent::respondError(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->logger->error("payment_intent.create.error",
                $e->getMessage(), 
                [],
                $user->id ?? null
            );
            $this->logger->flush();

            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Export customer's Stripe payment history via email.
     *
     * @param PaymentHistoryExportRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     */
    public function export(PaymentHistoryExportRequest $request)
    {
        $user  = parent::getAuth();
        $email = $request->filled('email') ? $request->email : $user->email;
        $limit = $request->filled('limit') ? $request->limit : 50;

        SendStripeUserPaymentHistory::dispatch($user->id, $email, $limit);

        return parent::respond([
            'status'   => 'success',
            'email'    => __('klyp.nomergy::fod.sending_stripe_payment_history'). $email
        ]);
    }
}
