<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Services\StripeServicePaymentService;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeServicePaymentService $stripePayments)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            report(new \RuntimeException('Stripe webhook secret non configurato.'));
            return response('Webhook secret missing', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        try {
            match ($event->type) {
                'checkout.session.completed' => $stripePayments->markPaidFromCheckoutSession($event->data->object),
                'checkout.session.expired' => $stripePayments->markExpiredFromCheckoutSession($event->data->object),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);
            return response('Webhook processing error', 500);
        }

        return response('OK', 200);
    }
}
