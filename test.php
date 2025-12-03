use Stripe\StripeClient;

public function createOrGetCustomer(Request $request)
{
    try {
        $stripe = new StripeClient(env('STRIPE_SECRET'));

        $connectedAccountId = $request->account_id; // e.g. acct_123
        $email = $request->email;                   // customer email

        // 1️⃣ Check if customer already exists in this connected account
        $existing = $stripe->customers->all(
            ['email' => $email],
            ['stripe_account' => $connectedAccountId]
        );

        if (!empty($existing->data)) {
            // Customer found → return existing customer
            return response()->json([
                'status' => 'exists',
                'customer' => $existing->data[0],
            ]);
        }

        // 2️⃣ Create new customer in connected account
        $customer = $stripe->customers->create(
            [
                'email' => $email,
                'name'  => $request->name,
                'metadata' => [
                    'source' => 'my-app',
                ],
            ],
            ['stripe_account' => $connectedAccountId]
        );

        return response()->json([
            'status' => 'created',
            'customer' => $customer,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 400);
    }
}
