<?php

namespace App\Http\Controllers\API;

use Stripe\Exception\SignatureVerificationException;
use App\Events\StripePaymentIntentSucceeded;
use App\Events\StripeAccountOnboarded;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        if (!$sigHeader) {
            return response('Missing signature header', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        // Handle the webhook event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                StripePaymentIntentSucceeded::dispatch($event);
                break;

            case 'invoice.payment_failed':
                Log::info('Invoice payment failed:', ['event' => $event->toArray()]);
                break;

            case 'account.updated':
                StripeAccountOnboarded::dispatch($event);
                break;

            default:
        }

        return response('Webhook processed', Response::HTTP_OK);
    }
}