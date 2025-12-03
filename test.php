/**
 * Create a new Stripe customer.
 *
 * This endpoint registers a customer in your Stripe account using
 * the provided email, name, and optional description.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function createStripeCustomer(Request $request)
{
    // Validate required fields
    $request->validate([
        'email'       => 'required|email',
        'name'        => 'nullable|string',
        'description' => 'nullable|string',
    ]);

    // Initialize the Stripe API client
    $stripe = new StripeClient(config('services.stripe.secret'));

    // Create a new customer in Stripe
    $customer = $stripe->customers->create([
        'email'       => $request->email,                         // Customer email
        'name'        => $request->name,                          // Optional customer name
        'description' => $request->description ??                 // Optional custom description
                           'Customer created via API',            // Default fallback
    ]);

    // Return customer details as API response
    return response()->json([
        'customer_id' => $customer->id,
        'customer'    => $customer,
    ]);
}
