<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Models\PTBillingUserStripeProfile;
use Klyp\Nomergy\Http\Traits\TrainerInfoTrait;
use Klyp\Nomergy\Http\Traits\HasCurrencySymbolTrait;
use Klyp\Nomergy\Models\UserPortal;
use Stripe\StripeClient;

class PTBillingCustomerProductService
{
    use HasCurrencySymbolTrait, TrainerInfoTrait;

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
     * Get unique products purchased by a customer for a specific trainer.
     *
     * @param int $userId    Authenticated customer ID
     * @param int $trainerId Trainer ID (Stripe connected account owner)
     *
     * @return array
     */
    public function getCustomerProducts($userId, $trainerId)
    {
        $products = [];
        $trainerData = null;

        $trainer = UserPortal::find($trainerId);
        $profile = PTBillingUserStripeProfile::where('user_id', $userId)
            ->where('stripe_account_id', $trainer->stripe_account_id)
            ->first();

        $symbol = '$';
        if ($trainer) {
            $symbol = $this->getCurrencySymbol($trainer->corp_partner_id);
        }
    
        if($profile) {
            $paymentIntents = $this->stripe->paymentIntents->all(
                ['customer' => $profile->stripe_customer_id],
                ['stripe_account' => $trainer->stripe_account_id]
            );

            foreach ($paymentIntents->data as $paymentIntent) {
                $productName = $paymentIntent->metadata->product_name ?? null;
                if (!$productName) {
                    continue;
                }

                if (collect($products)->contains('product_name', $productName)) {
                    continue;
                }

                $amount   = ($paymentIntent->amount_received && $paymentIntent->amount_received > 0) ? 
                    $paymentIntent->amount_received : $paymentIntent->amount;
                $currency = strtoupper($paymentIntent->currency);

                $products[] = [
                    'product_name'     => $paymentIntent->metadata->product_name ?? 'N/A',
                    'product_price'    => $paymentIntent->metadata->product_price ? $paymentIntent->metadata->product_price : number_format($amount / 100, 2),
                    'currency'         => $currency,
                    'symbol'           => $symbol,
                ];
            }

            $trainerData = $this->formatTrainer($trainer);
        }

        return [
            'products' => $products,
            'trainer'  => $trainerData,
        ];
    }
}
