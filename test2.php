public function getPaymentHistory(array $data)
    {
        $profiles = PTBillingUserStripeProfile::where('user_id', $data['user_id'])
            ->get();

        $result = [];
        foreach ($profiles as $profile) {
            $customerId = $profile->stripe_customer_id;
            $accountId = $profile->stripe_account_id;
            
            $symbol = '$';
            $trainer = UserPortal::where('stripe_account_id', $profile->stripe_account_id)->first();
            if ($trainer) {
                $symbol = $this->getCurrencySymbol($trainer->corp_partner_id);
            }
            $terms = !empty($trainer->billing_setting->terms) ? $trainer->billing_setting->terms : null;

            $paymentIntents = $this->stripe->paymentIntents->all([
                'customer' => $customerId,
                    'expand' => [
                        'data.payment_method',
                        'data.charges.data.balance_transaction',
                    ],
                    'limit' => $data['limit'],
                ],
                ['stripe_account' => $accountId]
            );

            foreach ($paymentIntents->data as $paymentIntent) {
                // Handle amount: fallback to `amount` if `amount_received` is missing
                $amount  = ($paymentIntent->amount_received && $paymentIntent->amount_received > 0) ? 
                    $paymentIntent->amount_received : $paymentIntent->amount;
                $currency = strtoupper($paymentIntent->currency);

                $paymentMethod = $paymentIntent->payment_method;
                $card = ($paymentMethod && $paymentMethod ->type === 'card') ? $paymentMethod ->card : null;

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
                        'currency'         => $currency,
                        'symbol'           => $symbol,
                    ],

                    'payment_methods' => [
                        'payment_method' => strtoupper($paymentMethod ->type ?? 'N/A'),
                        'card_type'       => strtoupper($card->brand ?? 'N/A'),
                        'card_number'    => $card ? "XXXX XXXX XXXX {$card->last4}" : null,
                        'expires'        => $card ? "{$card->exp_month}/{$card->exp_year}" : null,
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