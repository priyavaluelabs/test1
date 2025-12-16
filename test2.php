<?php

namespace Klyp\Nomergy\Services\Stripe;

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
     *
     * @var trainer
     */
    protected $trainer;

    /**
     * Constructor to initialize the Stripe client with the secret key from config.
     *
     */
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
    }

    /**
     * Retrieve a list of payment history for a given Stripe customer.
     *
     * @param array $data
     *
     */
    public function getPaymentHistory(array $data)
    {
        $paymentIntents = $this->stripe->paymentIntents->all([
            'customer' => $data['customer_id'],
                'expand' => [
                    'data.payment_method',
                    'data.charges.data.balance_transaction',
                ],
                'limit' => $data['limit'],
            ],
            ['stripe_account' => $data['stripe_account_id']]
        );

        $result = [];

        foreach ($paymentIntents->data as $pi) {
            // Handle amount: fallback to `amount` if `amount_received` is missing
            $amount   = $pi->amount_received ?? $pi->amount;
            $currency = strtoupper($pi->currency);

            $pm   = $pi->payment_method;
            $card = ($pm && $pm->type === 'card') ? $pm->card : null;

            $result[] = [
                'id'           => $pi->id,
                'trainer_name' => $pi->metadata->trainer_name ?? 'N/A',
                'product_type' => $pi->metadata->product_type ?? '10 session Packs',

                'purchase_details' => [
                    'product_name'     => $pi->metadata->product_name ?? 'N/A',
                    'transaction_date' => date('M j, Y g:i A', $pi->created),
                    'payment_status'   => $pi->status,
                    'amount'           => number_format($amount / 100, 2),
                    'currency'         => $currency,
                ],

                'payment_methods' => [
                    'payment_method' => strtoupper($pm->type ?? 'N/A'),
                    'car_type'       => strtoupper($card->brand ?? 'N/A'),
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
     * @param array  $data
     *
     */
    public function createCustomer(array $data)
    {
        $customer = $this->stripe->customers->create(
            [
                'email' => $data['email'],
                'name'  => $data['name'],
            ],
            ['stripe_account' => $data['stripe_account_id']]
        );

        return $customer->id;
    }

    /**
     * Create a Stripe PaymentIntent for a trainer’s connected account.
     *
     * @param array $data
     *
     * @return \Stripe\PaymentIntent
     * 
     */
    public function createPaymentIntent(array $data)
    {
        // Create a PaymentIntent on the trainer's connected account
        // The client_secret is used by PaymentSheet to confirm payment
        $paymentIntent = $this->stripe->paymentIntents->create(
            [
                'amount'    => (int) ($data['amount'] * 100),
                'currency'  => $data['currency'],
                'customer'  => $data['customer_id'],
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'trainer_name' => $data['trainer_name'],
                    'product_type' => $data['product_type'],
                    'product_name' => $data['product_name']
                ]
            ],
            ['stripe_account' => $data['stripe_account_id']]
        );

        return $paymentIntent;
    }
}
