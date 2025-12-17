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