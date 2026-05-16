<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\Service;
use App\Modules\Crm\Models\ServicePaymentLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeServicePaymentService
{
    public function createCheckoutLink(Service $service, ?float $amount = null, ?string $description = null): ServicePaymentLink
    {
        $secret = config('services.stripe.secret');
        if (!$secret) {
            throw new \RuntimeException('Stripe non configurato: STRIPE_SECRET mancante.');
        }

        $service->loadMissing('customer', 'product');

        $customer = $service->customer;
        if (!$customer) {
            throw new \RuntimeException('Cliente non collegato al servizio.');
        }

        $amount = $amount ?: (float) ($service->renew_price_gross ?? 0);
        if ($amount <= 0) {
            throw new \RuntimeException('Importo rinnovo non valido o non configurato.');
        }

        $currency = strtolower((string) config('services.stripe.currency', 'eur'));
        $description = $description ?: $this->defaultDescription($service);
        $expiresAt = $this->checkoutExpiresAt();

        Stripe::setApiKey($secret);

        $paymentLink = ServicePaymentLink::create([
            'client_id' => 1,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'amount' => round($amount, 2),
            'currency' => $currency,
            'description' => $description,
            'status' => ServicePaymentLink::STATUS_PENDING,
            'expires_at' => $expiresAt,
            'metadata' => [
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'service_name' => $service->name,
                'expires_at' => optional($service->expires_at)->toDateString(),
            ],
        ]);

        $session = Session::create([
            'mode' => 'payment',
            'client_reference_id' => (string) $paymentLink->id,
            'customer_email' => $customer->email ?: null,
            'expires_at' => $expiresAt->timestamp,
            'success_url' => route('crm.service-payments.success', $paymentLink) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('crm.service-payments.cancel', $paymentLink),
            'metadata' => [
                'service_payment_link_id' => (string) $paymentLink->id,
                'service_id' => (string) $service->id,
                'customer_id' => (string) $customer->id,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'service_payment_link_id' => (string) $paymentLink->id,
                    'service_id' => (string) $service->id,
                    'customer_id' => (string) $customer->id,
                ],
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $this->amountToStripeCents($amount),
                    'product_data' => [
                        'name' => Str::limit($description, 120, ''),
                        'description' => $this->productDescription($service),
                    ],
                ],
            ]],
        ]);

        $paymentLink->update([
            'stripe_checkout_session_id' => $session->id,
            'stripe_url' => $session->url,
        ]);

        return $paymentLink->fresh(['service.customer', 'service.product', 'customer']);
    }

    public function verifyAndMarkPaidBySessionId(ServicePaymentLink $paymentLink, ?string $sessionId): ServicePaymentLink
    {
        if (!$sessionId || !$paymentLink->stripe_checkout_session_id) {
            return $paymentLink;
        }

        if ($sessionId !== $paymentLink->stripe_checkout_session_id) {
            return $paymentLink;
        }

        $secret = config('services.stripe.secret');
        if (!$secret) {
            return $paymentLink;
        }

        Stripe::setApiKey($secret);

        $session = Session::retrieve($sessionId);

        if (($session->payment_status ?? null) === 'paid' || ($session->status ?? null) === 'complete') {
            return $this->finalizePaidPaymentLink($paymentLink, $session->payment_intent ?? null);
        }

        return $paymentLink->fresh(['service.customer', 'service.product', 'customer']);
    }

    public function markPaidFromCheckoutSession(array|object $session): ?ServicePaymentLink
    {
        $sessionId = data_get($session, 'id');
        if (!$sessionId) {
            return null;
        }

        $paymentLink = ServicePaymentLink::where('stripe_checkout_session_id', $sessionId)->first();
        if (!$paymentLink) {
            return null;
        }

        return $this->finalizePaidPaymentLink($paymentLink, data_get($session, 'payment_intent'));
    }

    public function markExpiredFromCheckoutSession(array|object $session): ?ServicePaymentLink
    {
        $sessionId = data_get($session, 'id');
        if (!$sessionId) {
            return null;
        }

        $paymentLink = ServicePaymentLink::where('stripe_checkout_session_id', $sessionId)->first();
        if (!$paymentLink || $paymentLink->status === ServicePaymentLink::STATUS_PAID) {
            return $paymentLink;
        }

        $paymentLink->status = ServicePaymentLink::STATUS_EXPIRED;
        $paymentLink->save();

        return $paymentLink;
    }

    protected function finalizePaidPaymentLink(ServicePaymentLink $paymentLink, ?string $paymentIntentId = null): ServicePaymentLink
    {
        return DB::transaction(function () use ($paymentLink, $paymentIntentId) {
            $paymentLink = ServicePaymentLink::whereKey($paymentLink->id)->lockForUpdate()->firstOrFail();
            $wasAlreadyPaid = $paymentLink->status === ServicePaymentLink::STATUS_PAID;

            $paymentLink->status = ServicePaymentLink::STATUS_PAID;
            $paymentLink->paid_at = $paymentLink->paid_at ?: now();
            $paymentLink->stripe_payment_intent_id = $paymentIntentId ?: $paymentLink->stripe_payment_intent_id;

            if (!$wasAlreadyPaid) {
                $this->renewServiceAfterPayment($paymentLink);
            }

            $paymentLink->save();

            return $paymentLink->fresh(['service.customer', 'service.product', 'customer']);
        });
    }

    protected function renewServiceAfterPayment(ServicePaymentLink $paymentLink): void
    {
        $service = Service::whereKey($paymentLink->service_id)->lockForUpdate()->first();
        if (!$service) {
            return;
        }

        $oldExpiresAt = $service->expires_at ? $service->expires_at->copy() : null;
        $baseDate = $oldExpiresAt && $oldExpiresAt->isFuture() ? $oldExpiresAt->copy() : now();
        $newExpiresAt = $this->calculateNextExpiration($baseDate, $service->renewal_vat_mode);

        $metadata = $paymentLink->metadata ?: [];
        $metadata['renewal'] = [
            'old_expires_at' => $oldExpiresAt?->toDateString(),
            'new_expires_at' => $newExpiresAt->toDateString(),
            'renewal_period' => $service->renewal_vat_mode ?: 'year',
            'renewed_at' => now()->toDateTimeString(),
        ];
        $paymentLink->metadata = $metadata;

        $service->expires_at = $newExpiresAt;
        $service->status = 'active';
        $service->save();
    }

    protected function calculateNextExpiration(Carbon $baseDate, ?string $period): Carbon
    {
        return match ($period) {
            'week' => $baseDate->copy()->addWeek(),
            'month' => $baseDate->copy()->addMonthNoOverflow(),
            default => $baseDate->copy()->addYearNoOverflow(),
        };
    }

    protected function defaultDescription(Service $service): string
    {
        $name = $service->name ?: optional($service->product)->name ?: 'Servizio';
        $date = $service->expires_at ? ' - scadenza ' . $service->expires_at->format('d/m/Y') : '';

        return 'Rinnovo ' . $name . $date;
    }

    protected function productDescription(Service $service): string
    {
        $parts = [];

        if ($service->provider_name) {
            $parts[] = 'Provider: ' . $service->provider_name;
        }

        if ($service->expires_at) {
            $parts[] = 'Scadenza: ' . $service->expires_at->format('d/m/Y');
        }

        return implode(' · ', $parts) ?: 'Rinnovo servizio';
    }

    protected function checkoutExpiresAt(): Carbon
    {
        $hours = (int) config('services.stripe.payment_link_ttl_hours', 24);
        $hours = max(1, min($hours, 23));

        return now()->addHours($hours);
    }

    protected function amountToStripeCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
