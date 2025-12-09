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
    public function __construct(UserPortal $trainer)
    {
        $this->trainer = $trainer;
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
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
                ['stripe_account' => $this->trainer->stripe_account_id]
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
            ['stripe_account' => $this->trainer->stripe_account_id]
        );

        return $paymentIntent;
    }
}




Please avoid sending the entire request instance to the service class (it's okay for the controller helper method) as this method then becomes tightly coupled with the controller. If later on we want to use this in jobs, the request instance will not be available, making it harder.