<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Models\UserPortal;
use Stripe\StripeClient;

class PTBillingStripeCustomerService
{
    /**
     * @var StripeClient
     */
    protected $stripe;

    /**
     * @var UserPortal|\Illuminate\Support\Collection|null
     */
    protected $trainer;

    /**
     * Initialize Stripe client only (no request side effects).
     */
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
    }

    /**
     * Inject trainer (single or multiple trainers).
     */
    public function setTrainer($trainer)
    {
        $this->trainer = $trainer;
        return $this;
    }

    /**
     * Retrieve payment history.
     */
    public function getPaymentHistory($request): array
    {
        if (!$this->trainer) {
            throw new \Exception("Trainer not set. Call setTrainer() first.");
        }

        $paymentIntents = $this->stripe->paymentIntents->all(
            [
                'customer' => $request->customer_id,
                'expand' => [
                    'data.payment_method',
                    'data.charges.data.balance_transaction',
                ],
                'limit' => $request->limit ?? 10,
            ],
            ['stripe_account' => $this->trainer->stripe_account_id]
        );

        $result = [];

        foreach ($paymentIntents->data as $pi) {
            $amount = $pi->amount_received ?? $pi->amount;
            $currency = strtoupper($pi->currency);

            $pm = $pi->payment_method;
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
     * Create a customer in a trainer's Stripe connected account.
     */
    public function createCustomer($request)
    {
        if (!$this->trainer) {
            throw new \Exception("Trainer not set. Call setTrainer() first.");
        }

        // Check if customer already exists in trainer account
        $existing = $this->stripe->customers->all(
            ['email' => $request->email],
            ['stripe_account' => $this->trainer->stripe_account_id]
        );

        if (!empty($existing->data)) {
            return $existing->data[0];
        }

        // Create new customer
        $customer = $this->stripe->customers->create(
            [
                'email' => $request->email,
                'name'  => $request->name,
            ],
            ['stripe_account' => $this->trainer->stripe_account_id]
        );

        // Save customer locally
        $portal = UserPortal::find($request->trainer_id);
        if ($portal) {
            $portal->stripe_customer_id = $customer->id;
            $portal->save();
        }

        return $customer;
    }

    /**
     * Create a PaymentIntent in a trainer’s connected Stripe account.
     */
    public function createPaymentIntent($request)
    {
        if (!$this->trainer) {
            throw new \Exception("Trainer not set. Call setTrainer() first.");
        }

        $trainerName = trim($this->trainer->first_name . ' ' . $this->trainer->last_name);

        $paymentIntent = $this->stripe->paymentIntents->create(
            [
                'amount'    => (int) ($request->amount * 100),
                'currency'  => $request->currency,
                'customer'  => $request->customer_id,

                'automatic_payment_methods' => ['enabled' => true],

                'metadata' => [
                    'trainer_name' => $trainerName,
                    'session_type' => $request->session_type,
                    'product_name' => $request->product_name
                ]
            ],
            [
                'stripe_account' => $this->trainer->stripe_account_id
            ]
        );

        return $paymentIntent;
    }
}
