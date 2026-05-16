<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Crm\Models\Lead;
use App\Services\OpenClawWhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicLeadController extends Controller
{
    protected function clientId(Request $request): int
    {
        // TODO: logica multi-tenant (per ora fisso 1)
        return 1;
    }

    /**
     * Mostra il form contatti pubblico (web)
     */
    public function create(Request $request)
    {
        return view('crm::public.leads.contact_form');
    }

    /**
     * Mostra il form contatti pubblico (social / landing)
     */
    public function create_social(Request $request)
    {
        return view('crm::public.leads.contact_form_social');
    }

    /**
     * Salva il lead da form pubblico
     */
    public function store(Request $request, OpenClawWhatsappService $whatsapp)
    {
        $clientId = $this->clientId($request);

        // =========================
        // 1) ANTI-SPAM: honeypot
        // =========================
        if ($request->filled('website')) {
            return redirect()->route('crm.leads.thankyou');
        }

        // =========================
        // 2) ANTI-SPAM: tempo minimo
        // =========================
        $formTs = (int) $request->input('form_ts', 0);

        if (!$formTs) {
            return redirect()->route('crm.leads.thankyou');
        }

        $diffSeconds = now()->timestamp - $formTs;

        if ($diffSeconds < 3) {
            return redirect()->route('crm.leads.thankyou');
        }

        // =========================
        // 3) VALIDAZIONE
        // =========================
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:190'],
            'email'           => ['nullable', 'email', 'max:190'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'subject'         => ['nullable', 'string', 'max:190'],
            'message'         => ['nullable', 'string'],

            'how_found'       => ['required', 'string', 'in:web,social,passaparola,altro'],
            'how_found_other' => ['nullable', 'string', 'max:190', 'required_if:how_found,altro'],

            'gdpr'            => ['accepted'],
            'marketing'       => ['nullable', 'boolean'],
        ], [
            'how_found.required' => 'Seleziona come ci hai trovato.',
            'how_found.in' => 'Valore non valido per "Come ci hai trovato".',
            'how_found_other.required_if' => 'Specifica come ci hai trovato (Altro).',
        ]);

        // Normalizzazione: se non è "altro", svuoto how_found_other
        $howFound = $data['how_found'] ?? null;
        $howFoundOther = ($howFound === 'altro') ? ($data['how_found_other'] ?? null) : null;

        // =========================
        // 4) SOURCE
        // =========================
        $formSource = $request->input('form_source');
        $source = ($formSource === 'social') ? 'contact_form_social' : 'contact_form';

        // =========================
        // 5) SALVATAGGIO
        // =========================
        $lead = Lead::create([
            'client_id'          => $clientId,
            'customer_id'        => null,

            'name'               => $data['name'],
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'subject'            => $data['subject'] ?? null,
            'message'            => $data['message'] ?? null,

            'source'             => $source,
            'how_found'          => $howFound,
            'how_found_other'    => $howFoundOther,

            'status'             => 'new',
            'owner_id'           => null,
            'last_contact_at'    => null,
            'next_action_at'     => null,
            'closed_at'          => null,
            'closed_reason'      => null,

            'gdpr_consense'      => true,
            'marketing_consense' => !empty($data['marketing']),
        ]);

        // =========================
        // 6) NOTIFICA WHATSAPP A TUTTI GLI ADMIN
        // =========================
        $this->notifyAdminsNewPublicLead($lead, $whatsapp);

        return redirect()->route('crm.leads.thankyou');
    }

    public function thankyou()
    {
        return view('crm::public.leads.thankyou');
    }

    protected function notifyAdminsNewPublicLead(Lead $lead, OpenClawWhatsappService $whatsapp): void
    {
        $admins = User::query()
            ->whereIn('role', [
                Role::Admin->value,
                Role::SuperAdmin->value,
            ])
            ->whereNotNull('phone')
            ->orderBy('name')
            ->get();

        if ($admins->isEmpty()) {
            Log::info('Public lead WhatsApp: nessun admin trovato', [
                'lead_id' => $lead->id,
            ]);
            return;
        }

        $message = $this->buildAdminsNewPublicLeadMessage($lead);

        foreach ($admins as $admin) {
            $number = $admin->whatsapp_phone ?: $this->normalizePhoneForWhatsapp($admin->phone);

            if (empty($number)) {
                Log::warning('Public lead WhatsApp: admin senza numero valido', [
                    'lead_id' => $lead->id,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'phone' => $admin->phone,
                ]);
                continue;
            }

            $result = $whatsapp->send($number, $message);

            Log::info('Public lead WhatsApp: risultato invio ad admin', [
                'lead_id' => $lead->id,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'result' => $result,
            ]);
        }
    }

    protected function buildAdminsNewPublicLeadMessage(Lead $lead): string
    {
        $howFound = Lead::HOW_FOUND_OPTIONS[$lead->how_found] ?? ($lead->how_found ?: '—');

        if ($lead->how_found === 'altro' && !empty($lead->how_found_other)) {
            $howFound .= ' - ' . $lead->how_found_other;
        }

        $sourceLabel = match ($lead->source) {
            'contact_form'        => 'Form contatti',
            'contact_form_social' => 'Form contatti social',
            default               => $lead->source ?: '—',
        };

        $lines = [
            '🆕 Nuova richiesta dal form contatti',
            '',
            'Nome: ' . ($lead->name ?: '—'),
            'Telefono: ' . ($lead->phone ?: '—'),
            'Email: ' . ($lead->email ?: '—'),
            'Oggetto: ' . ($lead->subject ?: '—'),
            'Origine: ' . $sourceLabel,
            'Come ci ha trovato: ' . $howFound,
            '',
            'Messaggio:',
            $lead->message ?: '—',
        ];

        return implode("\n", $lines);
    }

    protected function normalizePhoneForWhatsapp(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $number = preg_replace('/\D+/', '', trim($phone));

        if ($number === '') {
            return null;
        }

        if (!str_starts_with($number, '39')) {
            $number = '39' . ltrim($number, '0');
        }

        return $number;
    }
}
