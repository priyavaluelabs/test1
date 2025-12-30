<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Models\PTBillingUserStripeProfile;
use Klyp\Nomergy\Http\Traits\HasCurrencySymbolTrait;
use Klyp\Nomergy\Models\UserPortal;
use Stripe\StripeClient;

class PTBillingCustomerService
{
    use HasCurrencySymbolTrait;

    /**
     * Stripe client instance for making ApaymentIntent calls.
     *
     * @var StripeClient
     */
    protected $stripe;

    /**
     * Constructor to initialize the Stripe client with the secret key from config.
     *
     */
    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    /**
     * Retrieves or creates a Stripe customer for a trainer's connected account.
     *
     * @param array $data
     * @return array An array of payment records
     * 
     */
    public function getPaymentHistory(array $data)
    {
        $profiles = PTBillingUserStripeProfile::where('user_id', $data['user_id'])->get();

        $result = [];
        foreach ($profiles as $profile) {
            $trainer = UserPortal::where('stripe_account_id', $profile->stripe_account_id)
                ->with('billing_setting')
                ->first();

            $symbol = $trainer ? $this->getCurrencySymbol($trainer->corp_partner_id) : '$';
            $terms = !empty($trainer->billing_setting->terms) ? $trainer->billing_setting->terms : null;

            $paymentIntents = $this->stripe->paymentIntents->all([
                'customer' => $profile->stripe_customer_id,
                    'expand' => [
                        'data.payment_method',
                        'data.charges.data.balance_transaction',
                    ],
                    'limit' => $data['limit'],
                ],
                ['stripe_account' => $profile->stripe_account_id]
            );

            foreach ($paymentIntents->data as $paymentIntent) {
                // Handle amount: fallback to `amount` if `amount_received` is missing
                $amount  = ($paymentIntent->amount_received && $paymentIntent->amount_received > 0) ? 
                    $paymentIntent->amount_received : $paymentIntent->amount;
                $paymentMethod = $paymentIntent->payment_method;
                $card = ($paymentMethod && $paymentMethod->type === 'card') ? $paymentMethod->card : null;

                $result[] = [
                    'id'           => $paymentIntent->id,
                    'created'      => isset($paymentIntent->created) ? (int)$paymentIntent->created : 0,
                    'trainer_name' => $paymentIntent->metadata->trainer_name ?? 'N/A',
                    'product_type' => $paymentIntent->metadata->product_type ?? '10 session Packs',

                    'purchase_details' => [
                        'product_name'     => $paymentIntent->metadata->product_name ?? 'N/A',
                        'transaction_date' => date('M j, Y g:i A', $paymentIntent->created),
                        'payment_status'   => $paymentIntent->status === 'requires_payment_method'
                            ? 'Incomplete' : ucfirst($paymentIntent->status),
                        'amount'           => number_format($amount / 100, 2),
                        'currency'         => strtoupper($paymentIntent->currency),
                        'symbol'           => $symbol,
                    ],

                    'payment_methods' => [
                        'payment_method'  => strtoupper($paymentMethod ->type ?? 'N/A'),
                        'card_type'       => strtoupper($card->brand ?? 'N/A'),
                        'card_number'     => $card ? "XXXX XXXX XXXX {$card->last4}" : null,
                        'expires'         => $card ? "{$card->exp_month}/{$card->exp_year}" : null,
                    ],

                    'terms' => $terms
                ];
            }
        }

        usort($result, function($a, $b) {
            return $b['created'] - $a['created'];
        });

        return $result;
    }

    /**
     * Creates a Stripe customer for a trainer's connected account.
     *
     * @param array $data
     * @return string The Stripe customer ID (e.g., cus_abc123), or throws an exception if creation fails.
     *
     */
    public function createCustomer(array $data)
    {
        $existingProfile = PTBillingUserStripeProfile::where('user_id', $data['user_id'])
            ->where('stripe_account_id', $data['stripe_account_id'])
            ->first();
        
        if ($existingProfile) {
            $customerId = $existingProfile->stripe_customer_id;
        } else {
            $customer = $this->stripe->customers->create(
                [
                    'email' => $data['email'],
                    'name'  => $data['name'],
                ],
                ['stripe_account' => $data['stripe_account_id']]
            );
            $customerId = $customer->id;

            PTBillingUserStripeProfile::create([
                'user_id'           => $data['user_id'],
                'stripe_customer_id'=> $customerId,
                'stripe_account_id' => $data['stripe_account_id'],
            ]);
        }

        return $customerId;
    }

    /**
     * Creates a Stripe PaymentIntent for a customer, scoped to the trainer's connected account.
     *
     * @param array $data
     * @return \Stripe\PaymentIntent
     * 
     */
    public function createPaymentIntent(array $data)
    {
        $product = $this->stripe->products->retrieve(
            $data['product_id'],
            [
                'expand' => ['default_price'],
            ],
            [
                'stripe_account' => $data['stripe_account_id'],
            ]
        );

        if ($product) {
            $price    = $product->default_price;
            $amount   = $price->unit_amount ?? 0;

            // Create a PaymentIntent on the trainer's connected account
            // The client_secret is used by PaymentSheet to confirm payment
            $paymentIntent = $this->stripe->paymentIntents->create(
                [
                    'amount'    => $amount,
                    'currency'  => $price->currency,
                    'customer'  => $data['customer_id'],
                    'payment_method_types' => ['card'],
                    'metadata' => [
                        'trainer_name'   => $data['trainer_name'],
                        'product_type'   => $product->metadata->product_type ? $product->metadata->product_type : '',
                        'product_name'   => $product->name,
                        'product_amount' => $amount,
                        'session_count'  => $product->metadata->session_count ? $product->metadata->session_count : '',
                    ]
                ],
                ['stripe_account' => $data['stripe_account_id']]
            );

            return $paymentIntent;
        }
    }
}
