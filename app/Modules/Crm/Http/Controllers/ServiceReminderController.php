<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Mail\ServiceExpirationReminderMail;
use App\Modules\Crm\Models\Service;
use App\Modules\Crm\Models\ServiceReminderLog;
use App\Services\WhatsAppApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ServiceReminderController extends Controller
{
    /**
     * Invio manuale del promemoria email per un singolo servizio.
     * URL:  POST /admin/crm/services/{service}/send-reminder
     * Nome: admin.crm.services.send-reminder
     */
    public function send(Request $request, Service $service)
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $service->load('customer', 'product');

        $customer = $service->customer;
        if (!$customer || !$customer->email) {
            return back()
                ->with('error', 'Il cliente non ha un indirizzo email valido.')
                ->withInput();
        }

        // Limite: max 1 email al giorno per questo servizio
        $alreadyToday = ServiceReminderLog::where('service_id', $service->id)
            ->where('channel', 'email')
            ->whereDate('sent_at', now()->toDateString())
            ->whereIn('status', ['sent', 'opened'])
            ->exists();

        if ($alreadyToday) {
            return back()
                ->with('error', 'Per questo servizio è già stato inviato un promemoria email oggi.');
        }

        $serviceName = $service->name
            ?: optional($service->product)->name
                ?: 'servizio';

        $subject = 'Promemoria scadenza servizio: ' . $serviceName;

        $log = ServiceReminderLog::create([
            'service_id'    => $service->id,
            'customer_id'   => $customer->id,
            'channel'       => 'email',
            'to_email'      => $customer->email,
            'to_phone'      => null,
            'subject'       => $subject,
            'body'          => $data['message'],
            'status'        => 'pending',
            'tracking_hash' => Str::random(40),
        ]);

        try {
            Mail::to($customer->email)
                ->send(new ServiceExpirationReminderMail(
                    $service,
                    $log,
                    $data['message'],
                    $subject
                ));

            $log->status  = 'sent';
            $log->sent_at = now();
            $log->save();

            return back()->with('success', 'Promemoria di scadenza inviato correttamente.');
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error  = $e->getMessage();
            $log->save();

            return back()->with('error', 'Errore durante l\'invio del promemoria: ' . $e->getMessage());
        }
    }

    /**
     * Invio manuale promemoria WhatsApp.
     * URL: POST /admin/crm/services/{service}/send-whatsapp-reminder
     * Nome: admin.crm.services.send-whatsapp-reminder
     */
    public function sendWhatsapp(Request $request, Service $service, WhatsAppApiService $whatsAppApi)
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $service->load('customer', 'product');
        $customer = $service->customer;

        if (!$customer) {
            return back()->with('error', 'Cliente non trovato.');
        }

        $rawPhone = $customer->whatsapp
            ?? $customer->mobile
            ?? $customer->phone
            ?? null;

        $phone = $whatsAppApi->normalizePhone($rawPhone);

        if (!$phone) {
            return back()->with('error', 'Il cliente non ha un numero WhatsApp/cellulare valido.');
        }

        // Limite: max 1 WhatsApp al giorno per questo servizio
        $alreadyToday = ServiceReminderLog::where('service_id', $service->id)
            ->where('channel', 'whatsapp')
            ->whereDate('sent_at', now()->toDateString())
            ->whereIn('status', ['sent'])
            ->exists();

        if ($alreadyToday) {
            return back()->with('error', 'Per questo servizio è già stato inviato un promemoria WhatsApp oggi.');
        }

        $serviceName = $service->name
            ?: optional($service->product)->name
                ?: 'servizio';

        $subject = 'Promemoria WhatsApp scadenza servizio: ' . $serviceName;

        $log = ServiceReminderLog::create([
            'service_id'          => $service->id,
            'customer_id'         => $customer->id,
            'channel'             => 'whatsapp',
            'to_email'            => null,
            'to_phone'            => $phone,
            'subject'             => $subject,
            'body'                => $data['message'],
            'status'              => 'pending',
            'tracking_hash'       => null,
            'provider_message_id' => null,
        ]);

        try {
            $response = $whatsAppApi->send($phone, $data['message']);

            $log->status = 'sent';
            $log->sent_at = now();
            $log->provider_message_id =
                $response['message_id']
                ?? $response['id']
                ?? data_get($response, 'data.id')
                ?? null;
            $log->save();

            return back()->with('success', 'Promemoria WhatsApp inviato correttamente.');
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error  = $e->getMessage();
            $log->save();

            return back()->with('error', 'Errore durante l\'invio WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Pixel per tracciare l'apertura dell'email (lettura).
     * URL: GET /crm/service-reminders/open/{log}/{hash}
     * Nome: crm.service-reminders.open
     */
    public function trackOpen(ServiceReminderLog $log, string $hash)
    {
        if ($log->tracking_hash && hash_equals($log->tracking_hash, $hash)) {
            if (!$log->opened_at) {
                $log->opened_at = now();
            }
            $log->status = 'opened';
            $log->save();
        }

        $gif = base64_decode(
            'R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='
        );

        return response($gif, 200)
            ->header('Content-Type', 'image/gif');
    }
}
