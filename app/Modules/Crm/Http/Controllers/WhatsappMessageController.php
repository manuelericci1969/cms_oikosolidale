<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\WhatsappMessage;
use App\Services\WhatsAppApiService;
use Illuminate\Http\Request;

class WhatsappMessageController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        $query = WhatsappMessage::with(['user', 'lead', 'customer'])
            ->where('client_id', $clientId)
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('normalized_phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $messages = $query->paginate(20)->withQueryString();

        return view('crm::whatsapp.index', [
            'messages' => $messages,
            'search'   => $search ?? null,
            'status'   => $status ?? null,
            'statuses' => WhatsappMessage::STATUS_OPTIONS,
        ]);
    }

    public function create(Request $request)
    {
        $lead = null;
        $customer = null;

        if ($request->filled('lead_id')) {
            $lead = Lead::find($request->integer('lead_id'));
        }

        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->integer('customer_id'));
        }

        $recipientName  = old('recipient_name');
        $recipientPhone = old('recipient_phone');
        $messageText    = old('message');

        if ($lead) {
            $recipientName  = $recipientName ?: $lead->name;
            $recipientPhone = $recipientPhone ?: $lead->phone;
        }

        if ($customer) {
            $recipientName  = $recipientName ?: $customer->name;
            $recipientPhone = $recipientPhone ?: $customer->phone;
        }

        return view('crm::whatsapp.create', [
            'lead'           => $lead,
            'customer'       => $customer,
            'recipientName'  => $recipientName,
            'recipientPhone' => $recipientPhone,
            'messageText'    => $messageText,
        ]);
    }

    public function store(Request $request, WhatsAppApiService $whatsapp)
    {
        $clientId = $this->clientId($request);

        $data = $request->validate([
            'lead_id'         => 'nullable|integer|exists:crm_leads,id',
            'customer_id'     => 'nullable|integer|exists:crm_customers,id',
            'recipient_name'  => 'nullable|string|max:255',
            'recipient_phone' => 'required|string|max:50',
            'message'         => 'required|string|max:5000',
        ]);

        $lead = !empty($data['lead_id']) ? Lead::find($data['lead_id']) : null;
        $customer = !empty($data['customer_id']) ? Customer::find($data['customer_id']) : null;

        $recipientName = $data['recipient_name']
            ?: $lead?->name
                ?: $customer?->name;

        $normalizedPhone = $whatsapp->normalizePhone($data['recipient_phone']);

        $log = WhatsappMessage::create([
            'client_id'        => $clientId,
            'user_id'          => auth()->id(),
            'lead_id'          => $lead?->id,
            'customer_id'      => $customer?->id,
            'recipient_name'   => $recipientName,
            'recipient_phone'  => $data['recipient_phone'],
            'normalized_phone' => $normalizedPhone,
            'message'          => trim($data['message']),
            'status'           => WhatsappMessage::STATUS_PENDING,
        ]);

        try {
            $result = $whatsapp->send($data['recipient_phone'], $data['message']);

            $log->update([
                'status'       => WhatsappMessage::STATUS_SENT,
                'api_response' => $result,
                'sent_at'      => now(),
            ]);

            if ($lead) {
                $lead->activities()->create([
                    'user_id'      => auth()->id(),
                    'type'         => 'whatsapp',
                    'subject'      => 'Messaggio WhatsApp inviato',
                    'body'         => trim($data['message']),
                    'outcome'      => 'sent',
                    'contacted_at' => now(),
                ]);

                $lead->last_contact_at = now();
                $lead->save();
            }

            return redirect()
                ->route('admin.crm.whatsapp.index')
                ->with('success', 'Messaggio WhatsApp inviato correttamente.');
        } catch (\Throwable $e) {
            $log->update([
                'status'        => WhatsappMessage::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            if ($lead) {
                $lead->activities()->create([
                    'user_id'      => auth()->id(),
                    'type'         => 'whatsapp',
                    'subject'      => 'Tentativo invio WhatsApp fallito',
                    'body'         => trim($data['message']),
                    'outcome'      => 'failed',
                    'contacted_at' => now(),
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Invio fallito: ' . $e->getMessage());
        }
    }
}
