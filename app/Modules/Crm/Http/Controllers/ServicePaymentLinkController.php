<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Mail\ServicePaymentLinkMail;
use App\Modules\Crm\Models\Service;
use App\Modules\Crm\Models\ServicePaymentLink;
use App\Modules\Crm\Services\StripeServicePaymentService;
use App\Services\WhatsAppApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ServicePaymentLinkController extends Controller
{
    public function index(Service $service)
    {
        $service->load('customer', 'product');

        $paymentLinks = ServicePaymentLink::with('customer')
            ->where('service_id', $service->id)
            ->latest()
            ->paginate(20);

        return view('crm::service_payment_links.index', compact('service', 'paymentLinks'));
    }

    public function store(Request $request, Service $service, StripeServicePaymentService $stripePayments)
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $paymentLink = $stripePayments->createCheckoutLink(
                $service,
                isset($data['amount']) ? (float) $data['amount'] : null,
                $data['description'] ?? null
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore creazione link Stripe: ' . $e->getMessage())->withInput();
        }

        return redirect()
            ->route('admin.crm.services.payment-links.index', $service)
            ->with('success', 'Link pagamento creato correttamente.')
            ->with('payment_url', $paymentLink->stripe_url);
    }

    public function refresh(Service $service, ServicePaymentLink $paymentLink, StripeServicePaymentService $stripePayments)
    {
        $this->ensurePaymentLinkBelongsToService($service, $paymentLink);

        try {
            $stripePayments->verifyAndMarkPaidBySessionId($paymentLink, $paymentLink->stripe_checkout_session_id);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore verifica stato Stripe: ' . $e->getMessage());
        }

        return back()->with('success', 'Stato pagamento aggiornato da Stripe.');
    }

    public function sendEmail(Service $service, ServicePaymentLink $paymentLink)
    {
        $this->ensurePaymentLinkBelongsToService($service, $paymentLink);

        $paymentLink->loadMissing('service.customer', 'service.product', 'customer');
        $customer = $paymentLink->customer ?: $service->customer;

        if (!$customer || !$customer->email) {
            return back()->with('error', 'Il cliente non ha un indirizzo email valido.');
        }

        try {
            Mail::to($customer->email)->send(new ServicePaymentLinkMail($paymentLink));
            $paymentLink->sent_email_at = now();
            $paymentLink->save();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore durante invio email pagamento: ' . $e->getMessage());
        }

        return back()->with('success', 'Link pagamento inviato via email.');
    }

    public function sendWhatsapp(Service $service, ServicePaymentLink $paymentLink, WhatsAppApiService $whatsAppApi)
    {
        $this->ensurePaymentLinkBelongsToService($service, $paymentLink);

        $paymentLink->loadMissing('service.customer', 'service.product', 'customer');
        $customer = $paymentLink->customer ?: $service->customer;

        $rawPhone = $customer->whatsapp
            ?? $customer->mobile
            ?? $customer->phone
            ?? null;

        $phone = $whatsAppApi->normalizePhone($rawPhone);

        if (!$phone) {
            return back()->with('error', 'Il cliente non ha un numero WhatsApp/cellulare valido.');
        }

        try {
            $whatsAppApi->send($phone, $this->whatsappMessage($paymentLink));
            $paymentLink->sent_whatsapp_at = now();
            $paymentLink->save();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore invio WhatsApp pagamento: ' . $e->getMessage());
        }

        return back()->with('success', 'Link pagamento inviato via WhatsApp.');
    }

    public function success(Request $request, ServicePaymentLink $paymentLink, StripeServicePaymentService $stripePayments)
    {
        try {
            $paymentLink = $stripePayments->verifyAndMarkPaidBySessionId(
                $paymentLink,
                $request->query('session_id')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return view('crm::service_payment_links.public-success', compact('paymentLink'));
    }

    public function cancel(ServicePaymentLink $paymentLink)
    {
        return view('crm::service_payment_links.public-cancel', compact('paymentLink'));
    }

    protected function ensurePaymentLinkBelongsToService(Service $service, ServicePaymentLink $paymentLink): void
    {
        if ((int) $paymentLink->service_id !== (int) $service->id) {
            abort(404);
        }
    }

    protected function whatsappMessage(ServicePaymentLink $paymentLink): string
    {
        $service = $paymentLink->service;
        $customer = $paymentLink->customer ?: $service->customer;
        $serviceName = $service->name ?: optional($service->product)->name ?: 'servizio';
        $expires = $service->expires_at ? $service->expires_at->format('d/m/Y') : 'non indicata';
        $amount = number_format((float) $paymentLink->amount, 2, ',', '.') . ' ' . strtoupper($paymentLink->currency);

        return "Gentile {$customer?->name},\n\n"
            . "le inviamo il link sicuro per il pagamento del rinnovo del servizio {$serviceName}.\n"
            . "Scadenza servizio: {$expires}\n"
            . "Importo: {$amount}\n\n"
            . "Può pagare con carta da questo link:\n{$paymentLink->stripe_url}\n\n"
            . "Cordiali saluti.";
    }
}
