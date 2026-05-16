<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadActivity;
use App\Models\User;
use Illuminate\Http\Request;

class AdminLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with(['owner', 'customer'])
            ->orderByDesc('created_at');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($ownerId = $request->input('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        $leads = $query->paginate(25)->withQueryString();

        $statusOptions = Lead::STATUS_OPTIONS;
        $owners        = User::orderBy('name')->get(['id', 'name']);

        return view('crm::leads.index', compact(
            'leads',
            'statusOptions',
            'owners'
        ));
    }

    public function show(Lead $lead)
    {
        $lead->load(['owner', 'customer', 'activities.user']);

        $statusOptions = Lead::STATUS_OPTIONS;
        $owners        = User::orderBy('name')->get(['id', 'name']);
        $customers     = Customer::orderBy('name')->get(['id', 'name']);

        return view('crm::leads.show', compact(
            'lead',
            'statusOptions',
            'owners',
            'customers'
        ));
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status'         => 'required|string|in:' . implode(',', array_keys(Lead::STATUS_OPTIONS)),
            'owner_id'       => 'nullable|integer|exists:users,id',
            'customer_id'    => 'nullable|integer|exists:crm_customers,id',
            'next_action_at' => 'nullable|date',
            'closed_reason'  => 'nullable|string|max:190',
        ]);

        // se status diventa won/lost e non aveva closed_at → lo mettiamo ora
        if (in_array($data['status'], ['won', 'lost']) && !$lead->closed_at) {
            $data['closed_at'] = now();
        }

        $lead->update($data);

        return redirect()
            ->route('admin.crm.leads.show', $lead)
            ->with('success', 'Lead aggiornato correttamente.');
    }

    public function storeActivity(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'type'         => 'required|string|max:50',
            'subject'      => 'nullable|string|max:190',
            'body'         => 'nullable|string',
            'outcome'      => 'nullable|string|max:50',
            'contacted_at' => 'nullable|date',
            'new_status'   => 'nullable|string|in:' . implode(',', array_keys(Lead::STATUS_OPTIONS)),
            'next_action_at' => 'nullable|date',
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = auth()->id();
        $data['contacted_at'] = $data['contacted_at'] ?? now();

        LeadActivity::create($data);

        // aggiorno lead (ultima attività + eventuale prossimo step + stato)
        $lead->last_contact_at = $data['contacted_at'];

        if (!empty($data['next_action_at'])) {
            $lead->next_action_at = $data['next_action_at'];
        }

        if (!empty($data['new_status'])) {
            $lead->status = $data['new_status'];
            if (in_array($lead->status, ['won', 'lost']) && !$lead->closed_at) {
                $lead->closed_at = now();
            }
        }

        $lead->save();

        return redirect()
            ->route('admin.crm.leads.show', $lead)
            ->with('success', 'Attività aggiunta al lead.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()
            ->route('admin.crm.leads.index')
            ->with('success', 'Lead eliminato.');
    }
}
