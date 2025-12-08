<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Models\UserPortal;
use Stripe\StripeClient;

class PTBillingStripeCustomerService
{
    /**
     * Stripe client instance for making API calls.
     *
     * @var StripeClient
     */
    protected $stripe;

    /**
     * Trainer model (optional — only if you want to store trainer info here)
     *
     * @var User
     */
    protected $trainer;

    /**
     * Constructor to initialize the Stripe client with the secret key from config.
     *
     */
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
        $this->trainer = UserPortal::find(request()->trainer_id);
    }

    /**
     * Retrieve a list of payment history for a given Stripe customer.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function getPaymentHistory($request): array
    {
        // Fetch payment intents from Stripe with expansion of related data
        $paymentIntents = $this->stripe->paymentIntents->all([
            'customer' => $request->customer_id,
                'expand' => [
                    'data.payment_method',
                    'data.charges.data.balance_transaction',
                ],
                'limit' => $request->limit,
            ],
            ['stripe_account' => $this->trainer->stripe_account_id]
        );

        $result = [];

        // Process each payment intent
        foreach ($paymentIntents->data as $pi) {
            // Handle amount: fallback to `amount` if `amount_received` is missing

            $amount   = $pi->amount_received ?? $pi->amount;
            $currency = strtoupper($pi->currency);

            // Extract payment method (if exists and is a card)
            $pm   = $pi->payment_method;
            $card = ($pm && $pm->type === 'card') ? $pm->card : null;

            $result[] = [
                'id'           => $pi->id,
                'trainer_name' => $pi->metadata->trainer_name ?? 'N/A',
                'session_type' => $pi->metadata->session_type ?? 'packs',

                'purchase_details' => [
                    'product_name'     => $pi->metadata->product_name ?? 'N/A',
                    'transaction_date' => date('d M Y', $pi->created),
                    'payment_status'   => $pi->status,
                    'amount'           => number_format($amount / 100, 2),
                    'currency'         => $currency,
                ],

                'payment_methods' => [
                    'payment_method' => strtoupper($pm->type ?? 'N/A'),
                    'card_number'    => $card ? "XXXX XXXX XXXX {$card->last4}" : null,
                    'expires'        => $card ? "{$card->exp_month}/{$card->exp_year}" : null,
                ],
            ];
        }

        return $result;
    }

    /**
     * Create a customer on Stripe for trainer account.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function createCustomer($request)
    {
        // Fetch trainer from another database connection
        $trainer = UserPortal::find($request->trainer_id);

        // Check if customer already exists in this connected account
        $existing = $this->stripe->customers->all(
            ['email' => $request->email],
            ['stripe_account' => $this->trainer->stripe_account_id]
        );

        if (empty($existing->data)) {
            // Create new customer in connected account
            $customer = $this->stripe->customers->create(
                [
                    'email' => $request->email,
                    'name'  => $request->name,
                ],
                ['stripe_account' => $trainer->stripe_account_id]
            );

            // Add stripe_customer_id to UserPortal
            $portal = UserPortal::find($request->trainer_id);
            if ($portal) {
                $portal->stripe_customer_id = $customer->id;
                $portal->save();
            }

            return $customer;
        } else {
            return $existing->data[0];
        }
    }

    /**
     * Create a Stripe PaymentIntent for a trainer’s connected account.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Stripe\PaymentIntent
     *
     * @throws \Exception
     */
    public function createPaymentIntent($request)
    {
        // Fetch trainer from another database connection
        $request['trainer_name'] = !empty($this->trainer) ?  $this->trainer->first_name . ' ' .  $this->trainer->last_name : null;
        $request['stripe_account_id'] = !empty($this->trainer) ? $this->trainer->stripe_account_id : null;

        // Create a PaymentIntent on the trainer's connected account
        // The client_secret is used by PaymentSheet to confirm payment
        $paymentIntent = $this->stripe->paymentIntents->create(
            [
                'amount'    => (int) ($request->amount * 100),
                'currency'  => $request->currency,
                'customer'  => $request->customer_id,
                'automatic_payment_methods' => ['enabled' => true],
                /*'payment_method' => 'pm_card_visa',
                'off_session' => true,
                'confirm' => true,*/
                'metadata' => [
                    'trainer_name' => $request->trainer_name,
                    'session_type' => $request->session_type,
                    'product_name' => $request->product_name
                ]
            ],
            ['stripe_account' => $request->stripe_account_id]
        );

        return $paymentIntent;
    }
}






================







<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeCustomerService;
use Klyp\Nomergy\Services\Stripe\PTBillingStripeLoggerService;
use Klyp\Nomergy\Http\Requests\PTBillingStripePaymentHistoryByCustomerRequest;
use Klyp\Nomergy\Http\Requests\PTBillingStripeCreateCustomerRequest;
use Klyp\Nomergy\Http\Requests\PTBillingStripePaymentIntentRequest;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class ApiPTBillingStripeCustomerController extends ApiController
{
    /**
     *  PT billing stripe customer service instance for handling Stripe API interactions.
     *
     * @var StripeCustomerService
     */
    protected $stripeCustomerService;

    /**
     * Stripe logger instance.
     *
     * @var logger
     */
    protected $logger;

    /**
     * Constructor to inject the Stripe customer service.
     *
     * @param StripeCustomerService $stripeCustomerService The service responsible for interacting with Stripe's Customer API.
     * @param PTBillingStripeLoggerService $logger The logger service used to record Stripe events and actions, including user_id.
     *
     */
    public function __construct(
        PTBillingStripeCustomerService $stripeCustomerService,
        PTBillingStripeLoggerService $logger
    ) {
        // Assign service to controller property
        $this->stripeCustomerService = $stripeCustomerService;
        $this->logger = $logger;
    }

    /**
     * Retrieve a list of payment history for a given Stripe customer.s
     *
     * @param  PTBillingStripeCreateCustomerRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getPaymentHistoryByCustomer(PTBillingStripePaymentHistoryByCustomerRequest $request): JsonResponse
    {
        // Ensure user is logged in
        $user = parent::getAuth();

        $this->logger->info(
            "payments.fetch",
            "Fetching payment intents",
            ['customer_id' => $request->customer_id],
            $user->id ?? null
        );

        try {
            // Fetch payment intents from Stripe for customer
            $paymentIntents = $this->stripeCustomerService->getPaymentHistory($request);

            $this->logger->info(
                "payments.fetch.success",
                "Payment intents fetched",
                ['count' => count($paymentIntents ?? [])],
                $user->id ?? null
            );
            $this->logger->flush();

            // Return success response with payment intents
            return parent::respond([
                'status' => 'success',
                'payments' => $paymentIntents
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error(
                "payments.fetch.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );
            $this->logger->flush();

            // Return 500 Internal Server Error for unexpected failures
            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a customer on stripe.
     *
     * This endpoint registers a customer in your Stripe account using the provided email, name and trainer id.
     *
     * @param  PTBillingStripePaymentHistoryByCustomerRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     */
    public function createCustomer(PTBillingStripeCreateCustomerRequest $request)
    {
        // Ensure user is logged in
        $user = parent::getAuth();

        $this->logger->info(
            "customer.create",
            "Creating customer",
            [
                'email'      => $request->email,
                'trainer_id' => $request->trainer_id
            ],
            $user->id ?? null
        );

        try {
            // Create a new customer in Stripe
            $customer = $this->stripeCustomerService->createCustomer($request);

            $this->logger->info(
                "customer.create.success",
                "Customer created on Stripe",
                ['customer_id' => $customer->id],
                $user->id ?? null
            );

            $this->logger->flush();

            // Return success response with customer id
            return parent::respond([
                'status' => 'success',
                'customer_id' => $customer->id
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error(
                "customer.create.error",
                $e->getMessage(),
                [],
                $user->id ?? null
            );
            $this->logger->flush();

            // Return 500 Internal Server Error for unexpected failures
            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a Stripe PaymentIntent for PaymentSheet.
     *
     * @param PTBillingStripePaymentIntentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     */
    public function createPaymentIntent(PTBillingStripePaymentIntentRequest $request)
    {
        // Ensure user is logged in
        $user = parent::getAuth();

        $this->logger->info(
            "payment_intent.create",
            "Creating payment intent",
            [
                'trainer_id'  => $request->trainer_id,
                'customer_id' => $request->customer_id,
                'amount'      => $request->amount
            ],
            $user->id ?? null
        );

        try {
            // Return PaymentIntent ID + client secret required for PaymentSheet
            $paymentIntent = $this->stripeCustomerService->createPaymentIntent($request);
            $this->logger->info(
                "payment_intent.create.success",
                "Payment intent created",
                [
                    'payment_intent_id' => $paymentIntent->id
                ],
                $user->id ?? null
            );

            $this->logger->flush();

            // Return PaymentIntent ID + client secret required for PaymentSheet
            return parent::respond([
                'status'         => 'success',
                'payment_intent' => $paymentIntent->id,
                'client_secret'  => $paymentIntent->client_secret,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error("payment_intent.create.error",
                $e->getMessage(), 
                [],
                $user->id ?? null
            );
            $this->logger->flush();

            // Return generic Stripe error
            return parent::respondError(
                __('klyp.nomergy::fod.stripe_unexpected_error'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

