<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Services\OpenClawWhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $user     = $request->user();

        $search = $request->input('q');
        $status = $request->input('status');

        $query = Lead::with(['customer', 'owner'])
            ->where('client_id', $clientId);

        // limitazione per utenti non admin
        if (!$user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        // ricerca testuale
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('how_found', 'like', "%{$search}%")
                    ->orWhere('how_found_other', 'like', "%{$search}%");
            });
        }

        // filtro stato
        if ($status) {
            $query->where('status', $status);
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINAZIONE
        |--------------------------------------------------------------------------
        | ultimi lead sempre in alto
        | 10 risultati per pagina
        */

        $leads = $query
            ->orderByDesc('id')   // più veloce di created_at
            ->paginate(10)
            ->withQueryString();

        $statusOptions = Lead::STATUS_OPTIONS;

        return view('crm::leads.index', [
            'leads'         => $leads,
            'statusOptions' => $statusOptions,
            'search'        => $search,
            'status'        => $status
        ]);
    }

    public function create(Request $request)
    {
        $statusOptions = Lead::STATUS_OPTIONS;
        $customers     = Customer::orderBy('name')->get();
        $owners        = User::orderBy('name')->get(['id', 'name']);

        $defaultNextAction = now()->setDate(2026, 1, 7)->setTime(10, 0);

        return view('crm::leads.create', compact(
            'statusOptions',
            'customers',
            'owners',
            'defaultNextAction'
        ));
    }

    public function store(Request $request, OpenClawWhatsappService $whatsapp)
    {
        $clientId = $this->clientId($request);
        $user     = $request->user();

        $data = $request->validate([
            'name'            => 'required|string|max:190',
            'email'           => 'nullable|email|max:190',
            'phone'           => 'nullable|string|max:50',
            'subject'         => 'nullable|string|max:190',
            'message'         => 'nullable|string',
            'internal_notes'  => 'nullable|string',
            'status'          => 'nullable|string|in:' . implode(',', array_keys(Lead::STATUS_OPTIONS)),
            'customer_id'     => 'nullable|integer|exists:crm_customers,id',
            'owner_id'        => 'nullable|integer|exists:users,id',
            'next_action_at'  => 'nullable|date',
            'how_found'       => 'nullable|string|in:' . implode(',', array_keys(Lead::HOW_FOUND_OPTIONS)),
            'how_found_other' => 'nullable|string|max:190',
            'source'          => 'nullable|string|max:50',
        ]);

        $data['client_id'] = $clientId;
        $data['source']    = $data['source'] ?? 'manual';
        $data['status']    = $data['status'] ?? 'contacted';

        if (($data['how_found'] ?? null) !== 'altro') {
            $data['how_found_other'] = null;
        }

        if (!$user->isAdmin()) {
            $data['owner_id'] = $user->id;
        } else {
            $data['owner_id'] = $data['owner_id'] ?? $user->id;
        }

        $data['last_contact_at'] = now();

        $lead = Lead::create($data);

        $lead->activities()->create([
            'user_id'      => $user?->id,
            'type'         => 'note',
            'subject'      => 'Contatto a voce (da ricontattare)',
            'body'         => $data['message'] ?? $data['internal_notes'] ?? null,
            'contacted_at' => now(),
        ]);

        $lead = $lead->fresh(['owner']);

        $this->notifyLeadAssigned($lead, $whatsapp);
        $this->notifyAdminsNewLeadCreated($lead, $user, $whatsapp);

        return redirect()
            ->route('admin.crm.leads.edit', $lead)
            ->with('success', 'Lead inserito. Promemoria impostato per il follow-up.');
    }

    protected function ensureCanAccessLead(Request $request, Lead $lead): void
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            return;
        }

        if (!$user || $lead->owner_id !== $user->id) {
            abort(403, 'Non hai accesso a questo lead');
        }
    }

    public function edit(Lead $lead)
    {
        $lead->load(['customer', 'owner', 'activities.user']);

        $statusOptions = Lead::STATUS_OPTIONS;
        $customers     = Customer::orderBy('name')->get();
        $owners        = User::orderBy('name')->get(['id', 'name']);
        $howFoundOptions = Lead::HOW_FOUND_OPTIONS;

        return view('crm::leads.edit', compact(
            'lead',
            'statusOptions',
            'customers',
            'owners',
            'howFoundOptions'
        ));
    }

    public function update(Request $request, Lead $lead, OpenClawWhatsappService $whatsapp)
    {
        $oldOwnerId = $lead->owner_id;

        $data = $request->validate([
            'name'            => 'required|string|max:190',
            'email'           => 'nullable|email|max:190',
            'phone'           => 'nullable|string|max:50',
            'subject'         => 'nullable|string|max:190',
            'message'         => 'nullable|string',
            'status'          => 'required|string|in:' . implode(',', array_keys(Lead::STATUS_OPTIONS)),
            'customer_id'     => 'nullable|integer|exists:crm_customers,id',
            'internal_notes'  => 'nullable|string',
            'owner_id'        => 'nullable|integer|exists:users,id',
            'last_contact_at' => 'nullable|date',
            'next_action_at'  => 'nullable|date',
            'closed_at'       => 'nullable|date',
            'closed_reason'   => 'nullable|string|max:190',
            'how_found'       => 'nullable|string|in:' . implode(',', array_keys(Lead::HOW_FOUND_OPTIONS)),
            'how_found_other' => 'nullable|string|max:190',
            'source'          => 'nullable|string|max:50',
        ]);

        if (($data['how_found'] ?? null) !== 'altro') {
            $data['how_found_other'] = null;
        }

        $lead->update($data);

        if ((int) $oldOwnerId !== (int) $lead->owner_id) {
            $this->notifyLeadAssigned($lead->fresh(['owner']), $whatsapp);
        }

        return redirect()
            ->route('admin.crm.leads.edit', $lead)
            ->with('success', 'Lead aggiornato correttamente.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()
            ->route('admin.crm.leads.index')
            ->with('success', 'Lead eliminato.');
    }

    public function convertToCustomer(Lead $lead)
    {
        if ($lead->customer_id) {
            return redirect()
                ->route('admin.crm.customers.edit', $lead->customer_id)
                ->with('info', 'Il lead è già collegato a un cliente.');
        }

        $customer = Customer::create([
            'client_id' => $lead->client_id,
            'owner_id'  => $lead->owner_id,
            'name'      => $lead->name,
            'email'     => $lead->email,
            'phone'     => $lead->phone,
            'notes'     => "Creato da lead #{$lead->id}",
            'is_active' => true,
        ]);

        $lead->update([
            'customer_id'   => $customer->id,
            'status'        => 'won',
            'closed_at'     => now(),
            'closed_reason' => 'Convertito in cliente',
        ]);

        return redirect()
            ->route('admin.crm.customers.edit', $customer)
            ->with('success', 'Cliente creato a partire dal lead.');
    }

    public function storeActivity(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'type'           => 'nullable|string|max:50',
            'subject'        => 'nullable|string|max:190',
            'body'           => 'nullable|string',
            'outcome'        => 'nullable|string|max:190',
            'contacted_at'   => 'nullable|date',
            'next_action_at' => 'nullable|date',
        ]);

        if (empty($data['contacted_at'])) {
            $data['contacted_at'] = now();
        }

        $lead->activities()->create([
            'user_id'      => $request->user()?->id,
            'type'         => $data['type'] ?? 'note',
            'subject'      => $data['subject'] ?? null,
            'body'         => $data['body'] ?? null,
            'outcome'      => $data['outcome'] ?? null,
            'contacted_at' => $data['contacted_at'],
        ]);

        $lead->last_contact_at = $data['contacted_at'];

        if (!empty($data['next_action_at'])) {
            $lead->next_action_at = $data['next_action_at'];
        }

        $lead->save();

        return redirect()
            ->route('admin.crm.leads.edit', $lead)
            ->with('success', 'Attività registrata correttamente.');
    }

    protected function notifyLeadAssigned(Lead $lead, OpenClawWhatsappService $whatsapp): void
    {
        $lead->loadMissing('owner');

        $owner = $lead->owner;

        if (!$owner) {
            return;
        }

        if (!$owner->canReceiveWhatsapp()) {
            Log::info('Lead assignment WhatsApp skipped: owner cannot receive WhatsApp', [
                'lead_id' => $lead->id,
                'owner_id' => $owner->id,
            ]);
            return;
        }

        $message = $this->buildLeadAssignmentMessage($lead);
        $result  = $whatsapp->send($owner->whatsapp_phone, $message);

        Log::info('Lead assignment WhatsApp result', [
            'lead_id' => $lead->id,
            'owner_id' => $owner->id,
            'result' => $result,
        ]);
    }

    protected function buildLeadAssignmentMessage(Lead $lead): string
    {
        return implode("\n", [
            '📌 Nuovo lead assegnato',
            '',
            'Nome: ' . ($lead->name ?: '—'),
            'Telefono: ' . ($lead->phone ?: '—'),
            'Email: ' . ($lead->email ?: '—'),
            'Oggetto: ' . ($lead->subject ?: '—'),
            'Fonte: ' . ($lead->source_label ?: '—'),
            'Come ci ha trovato: ' . ($lead->how_found_full_label ?: '—'),
            '',
            'Messaggio:',
            $lead->message ?: '—',
            '',
            'Note interne:',
            $lead->internal_notes ?: '—',
        ]);
    }

    protected function notifyAdminsNewLeadCreated(Lead $lead, ?User $createdBy, OpenClawWhatsappService $whatsapp): void
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
            Log::info('Nuovo lead: nessun admin da notificare', [
                'lead_id' => $lead->id,
            ]);
            return;
        }

        $message = $this->buildAdminsNewLeadMessage($lead, $createdBy);

        foreach ($admins as $admin) {
            $number = $admin->whatsapp_phone;

            if (empty($number)) {
                Log::warning('Nuovo lead: admin senza numero valido', [
                    'lead_id' => $lead->id,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'phone' => $admin->phone,
                ]);
                continue;
            }

            $result = $whatsapp->send($number, $message);

            Log::info('Nuovo lead: WhatsApp inviato ad admin', [
                'lead_id' => $lead->id,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'result' => $result,
            ]);
        }
    }

    protected function buildAdminsNewLeadMessage(Lead $lead, ?User $createdBy): string
    {
        $createdByName = $createdBy?->name ?: 'Sistema';

        return implode("\n", [
            '🆕 Nuovo lead inserito',
            '',
            'Inserito da: ' . $createdByName,
            'Nome: ' . ($lead->name ?: '—'),
            'Telefono: ' . ($lead->phone ?: '—'),
            'Email: ' . ($lead->email ?: '—'),
            'Oggetto: ' . ($lead->subject ?: '—'),
            'Fonte: ' . ($lead->source_label ?: '—'),
            'Come ci ha trovato: ' . ($lead->how_found_full_label ?: '—'),
            '',
            'Messaggio:',
            $lead->message ?: '—',
            '',
            'Note interne:',
            $lead->internal_notes ?: '—',
        ]);
    }
}
