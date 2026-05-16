<?php

namespace App\Modules\Crm\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadActivity;
use Illuminate\Http\Request;

class AgentLeadController extends Controller
{
    protected function clientId(Request $request): int
    {
        // stessa logica che usi negli altri controller CRM
        return 1;
    }

    /**
     * Lista lead assegnati all’agente.
     */
    public function index(Request $request)
    {
        $clientId = $this->clientId($request);
        $userId   = $request->user()->id;

        $query = Lead::with(['customer', 'owner'])
            ->where('client_id', $clientId)
            ->where('owner_id', $userId);   // 🔹 solo i lead dell’agente

        $search = $request->input('q');
        $status = $request->input('status');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $leads = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $statusOptions = Lead::STATUS_OPTIONS;

        // vista dedicata agenti (puoi tenerla com'è ora)
        return view('crm::agent.leads.index', compact('leads', 'statusOptions', 'search', 'status'));
    }

    /**
     * Edit lead (solo se assegnato all’agente).
     */
    public function edit(Request $request, Lead $lead)
    {
        $this->authorizeLead($request, $lead);

        // carico anche attività + utente che le ha inserite
        $lead->load(['customer', 'activities.user']);

        $statusOptions = Lead::STATUS_OPTIONS;

        return view('crm::agent.leads.edit', [
            'lead'          => $lead,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Update lead (solo i campi che l’agente può toccare).
     */
    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'name'           => 'required|string|max:190',
            'email'          => 'nullable|email|max:190',
            'phone'          => 'nullable|string|max:50',
            'subject'        => 'nullable|string|max:190',
            'message'        => 'nullable|string',
            'status'         => 'required|string|in:' . implode(',', array_keys(Lead::STATUS_OPTIONS)),
            'internal_notes' => 'nullable|string',

            'last_contact_at' => 'nullable|date',
            'next_action_at'  => 'nullable|date',
            'closed_at'       => 'nullable|date',
            'closed_reason'   => 'nullable|string|max:255',
        ]);

        $lead->update($data);

        return redirect()
            ->route('agent.crm.leads.edit', $lead)
            ->with('success', 'Lead aggiornato correttamente.');
    }

    /**
     * Salva una nuova attività / nota sul lead (cronologia contatti).
     */
    public function storeActivity(Request $request, Lead $lead)
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'type'          => 'required|string|in:call,email,meeting,note',
            'subject'       => 'nullable|string|max:190',
            'body'          => 'nullable|string',
            'outcome'       => 'nullable|string|max:190',
            'contacted_at'  => 'required|date',
            'next_action_at'=> 'nullable|date',
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = $request->user()->id;

        LeadActivity::create($data);

        // aggiorno il workflow del lead coerentemente con l’attività
        $lead->last_contact_at = $data['contacted_at'];

        if (!empty($data['next_action_at'])) {
            $lead->next_action_at = $data['next_action_at'];
        }

        // se vuoi, puoi anche forzare lo stato a "contacted" la prima volta
        if ($lead->status === 'new') {
            $lead->status = 'contacted';
        }

        $lead->save();

        return redirect()
            ->route('agent.crm.leads.edit', $lead)
            ->with('success', 'Attività registrata correttamente.');
    }

    /**
     * Sicurezza: l’agente può vedere/modificare solo i lead del proprio client & owner.
     */
    protected function authorizeLead(Request $request, Lead $lead): void
    {
        $clientId = $this->clientId($request);
        $userId   = $request->user()->id;

        // cast a int per evitare problemi '4' vs 4
        if ((int) $lead->client_id !== (int) $clientId || (int) $lead->owner_id !== (int) $userId) {
            abort(403, 'Non hai accesso a questo lead.');
        }
    }
}
