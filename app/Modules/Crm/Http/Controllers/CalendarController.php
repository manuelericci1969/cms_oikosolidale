<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Modules\Crm\Models\Appointment;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadActivity;
use App\Services\GoogleCalendarSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1; // TODO multi-tenant
    }

    protected function ensureCanAccessAppointment(Request $request, Appointment $appointment): void
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) return;

        if (!$user || (int)$appointment->user_id !== (int)$user->id) {
            abort(403, 'Non hai accesso a questo appuntamento');
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $routePrefix = $user && $user->isAdmin() ? 'admin.crm' : 'agent.crm';

        $users = $user && $user->isAdmin()
            ? User::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('crm::calendar.index', compact('routePrefix', 'users'));
    }

    public function events(Request $request)
    {
        $clientId = $this->clientId($request);
        $user     = $request->user();

        $start = Carbon::parse($request->query('start'));
        $end   = Carbon::parse($request->query('end'));

        // =========================================================
        // ✅ GOOGLE -> CRM “ON OPEN / ON VIEW CHANGE”
        // =========================================================
        $pullOnOpen = (bool) Setting::get('calendar.google.pull_on_calendar_open', false);

        // Per non ammazzare performance: se admin senza filtro, non synca tutti
        $syncUserId = null;
        if ($pullOnOpen && $user) {
            if ($user->isAdmin()) {
                // se vuoi syncare per un utente specifico in admin, passa ?sync_user_id=123
                $syncUserId = $request->query('sync_user_id') ? (int)$request->query('sync_user_id') : null;
            } else {
                $syncUserId = (int)$user->id;
            }
        }

        if ($syncUserId) {
            try {
                $acc = \App\Models\GoogleCalendarAccount::where('user_id', (int)$syncUserId)
                    ->where('enabled', 1)
                    ->first();

                if ($acc) {
                    app(GoogleCalendarSyncService::class)->syncAccountRange($acc, 'pull_only', $start, $end);
                }
            } catch (\Throwable $e) {
                Log::warning('[GoogleSync] pull on open failed', [
                    'user_id' => $syncUserId,
                    'err'     => $e->getMessage(),
                ]);
            }
        }


        // =====================================================================
        // 1) Appuntamenti manuali (CRM)
        // =====================================================================
        $aq = Appointment::query()
            ->with(['owner:id,name'])
            ->where('client_id', $clientId)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)
                    ->where(function ($q2) use ($start) {
                        $q2->whereNull('end_at')->orWhere('end_at', '>', $start);
                    });
            });

        if (!$user->isAdmin()) {
            $aq->where('user_id', $user->id);
        }

        $appointments = $aq->get();

        $appointmentEvents = $appointments->map(function (Appointment $a) {
            return [
                'id'       => 'appt-' . $a->id,
                'title'    => $a->title,
                'start'    => $a->start_at?->toIso8601String(),
                'end'      => $a->end_at?->toIso8601String(),
                'allDay'   => (bool)$a->all_day,
                'editable' => true,
                'classNames' => ['fc-appointment-event'],
                'extendedProps' => [
                    'kind'        => 'appointment',
                    'description' => $a->description,
                    'location'    => $a->location,
                    'type'        => $a->type,
                    'status'      => $a->status,
                    'user_id'     => $a->user_id,
                    'user_name'   => $a->owner?->name,
                    'lead_id'     => $a->lead_id,
                    'customer_id' => $a->customer_id,
                ],
            ];
        })->values()->toBase();

        // =====================================================================
        // 2) Lead next_action_at (read-only)
        // =====================================================================
        $lq = Lead::query()
            ->where('client_id', $clientId)
            ->whereNotNull('next_action_at')
            ->whereBetween('next_action_at', [$start, $end]);

        if (!$user->isAdmin()) {
            $lq->where('owner_id', $user->id);
        }

        $leads = $lq->get(['id', 'name', 'subject', 'next_action_at', 'owner_id']);

        $leadOwnerIds = $leads->pluck('owner_id')->filter()->unique()->values();
        $leadOwners = $leadOwnerIds->isNotEmpty()
            ? User::whereIn('id', $leadOwnerIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $leadEvents = $leads->map(function (Lead $lead) use ($user, $leadOwners) {
            $routePrefix = $user && $user->isAdmin() ? 'admin.crm' : 'agent.crm';

            $ownerId = $lead->owner_id;
            $ownerName = $ownerId ? ($leadOwners[$ownerId]->name ?? null) : null;

            return [
                'id'       => 'lead-' . $lead->id,
                'title'    => 'Follow-up: ' . ($lead->name ?: ('Lead #' . $lead->id)),
                'start'    => optional($lead->next_action_at)->toIso8601String(),
                'allDay'   => false,
                'editable' => false,
                'url'      => route($routePrefix . '.leads.edit', $lead->id),
                'classNames' => ['fc-lead-event'],
                'extendedProps' => [
                    'kind'      => 'lead_next_action',
                    'lead_id'   => $lead->id,
                    'subject'   => $lead->subject,
                    'user_id'   => $ownerId,
                    'user_name' => $ownerName,
                ],
            ];
        })->values()->toBase();

        // =====================================================================
        // 3) LeadActivity.contacted_at (read-only)
        // =====================================================================
        $actQ = LeadActivity::query()
            ->with(['lead'])
            ->whereNotNull('contacted_at')
            ->whereBetween('contacted_at', [$start, $end])
            ->whereHas('lead', function ($q) use ($clientId, $user) {
                $q->where('client_id', $clientId);
                if (!$user->isAdmin()) {
                    $q->where('owner_id', $user->id);
                }
            });

        $activities = $actQ->get();

        $actUserIds = $activities->pluck('user_id')->filter()->unique()->values();
        $actUsers = $actUserIds->isNotEmpty()
            ? User::whereIn('id', $actUserIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $activityEvents = $activities->map(function (LeadActivity $a) use ($user, $actUsers) {
            if (!$a->lead) return null;

            $routePrefix = $user && $user->isAdmin() ? 'admin.crm' : 'agent.crm';

            $labelType = match ($a->type) {
                'call'    => 'Telefonata',
                'email'   => 'Email',
                'meeting' => 'Incontro',
                'note'    => 'Nota',
                default   => ucfirst($a->type ?: 'Attività'),
            };

            $leadName = $a->lead->name ?: ('Lead #' . $a->lead_id);

            $actStart = $a->contacted_at ? $a->contacted_at->copy() : null;
            $actEnd   = $actStart ? $actStart->copy()->addMinutes(
                $a->type === 'meeting' ? 60 : ($a->type === 'call' ? 20 : 10)
            ) : null;

            $title = $labelType . ': ' . $leadName;
            if (!empty($a->subject)) $title .= ' — ' . $a->subject;

            $uid = $a->user_id ?? null;
            $uname = $uid ? ($actUsers[$uid]->name ?? null) : null;

            return [
                'id'       => 'act-' . $a->id,
                'title'    => $title,
                'start'    => $actStart?->toIso8601String(),
                'end'      => $actEnd?->toIso8601String(),
                'allDay'   => false,
                'editable' => false,
                'url'      => route($routePrefix . '.leads.edit', $a->lead_id) . '#activity-' . $a->id,
                'classNames' => ['fc-activity-event', 'fc-activity-' . ($a->type ?: 'other')],
                'extendedProps' => [
                    'kind'        => 'lead_activity',
                    'activity_id' => $a->id,
                    'lead_id'     => $a->lead_id,
                    'type'        => $a->type,
                    'subject'     => $a->subject,
                    'body'        => $a->body,
                    'outcome'     => $a->outcome,
                    'user_id'     => $uid,
                    'user_name'   => $uname,
                    'contacted_at'=> $a->contacted_at?->toIso8601String(),
                ],
            ];
        })->filter()->values()->toBase();

        return response()->json(
            $appointmentEvents->merge($leadEvents)->merge($activityEvents)->values()
        );
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId($request);
        $user     = $request->user();

        $data = $request->validate([
            'title'       => 'required|string|max:190',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:190',
            'type'        => 'nullable|string|max:50',
            'status'      => 'nullable|string|max:30',
            'start_at'    => 'required|date',
            'end_at'      => 'nullable|date',
            'all_day'     => 'nullable|boolean',
            'user_id'     => 'nullable|integer|exists:users,id',
            'lead_id'     => 'nullable|integer|exists:crm_leads,id',
            'customer_id' => 'nullable|integer|exists:crm_customers,id',
        ]);

        $allDay = (bool)($data['all_day'] ?? false);

        $start = Carbon::parse($data['start_at']);
        $end   = !empty($data['end_at']) ? Carbon::parse($data['end_at']) : null;

        if ($allDay) {
            $start = $start->startOfDay();
            $end   = ($end ?: $start->copy())->startOfDay()->addDay(); // end esclusivo
        } else {
            if (!$end) $end = $start->copy()->addMinutes(30);
            if ($end->lte($start)) {
                return response()->json(['message' => 'La fine deve essere successiva all’inizio.'], 422);
            }
        }

        $ownerId = $user->isAdmin()
            ? ($data['user_id'] ?? $user->id)
            : $user->id;

        $appointment = Appointment::create([
            'client_id'   => $clientId,
            'user_id'     => $ownerId,
            'lead_id'     => $data['lead_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'location'    => $data['location'] ?? null,
            'type'        => $data['type'] ?? null,
            'status'      => $data['status'] ?? 'planned',
            'start_at'    => $start,
            'end_at'      => $end,
            'all_day'     => $allDay,
            'created_by'  => $user?->id,
        ]);

        return response()->json(['id' => $appointment->id], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->ensureCanAccessAppointment($request, $appointment);
        $user = $request->user();

        $data = $request->validate([
            'title'       => 'nullable|string|max:190',
            'description' => 'nullable|string',
            'location'    => 'nullable|string|max:190',
            'type'        => 'nullable|string|max:50',
            'status'      => 'nullable|string|max:30',
            'start_at'    => 'nullable|date',
            'end_at'      => 'nullable|date',
            'all_day'     => 'nullable|boolean',
            'user_id'     => 'nullable|integer|exists:users,id',
            'lead_id'     => 'nullable|integer|exists:crm_leads,id',
            'customer_id' => 'nullable|integer|exists:crm_customers,id',
        ]);

        $allDay = array_key_exists('all_day', $data)
            ? (bool)$data['all_day']
            : (bool)$appointment->all_day;

        $start = array_key_exists('start_at', $data) ? Carbon::parse($data['start_at']) : $appointment->start_at;
        $end   = array_key_exists('end_at', $data)
            ? (!empty($data['end_at']) ? Carbon::parse($data['end_at']) : null)
            : $appointment->end_at;

        if ($allDay) {
            $start = $start->copy()->startOfDay();
            $end   = ($end ?: $start->copy())->startOfDay()->addDay(); // end esclusivo
        } else {
            if (!$end) $end = $start->copy()->addMinutes(30);
            if ($end->lte($start)) {
                return response()->json(['message' => 'La fine deve essere successiva all’inizio.'], 422);
            }
        }

        if (!$user->isAdmin()) unset($data['user_id']);

        $appointment->fill($data);
        $appointment->all_day  = $allDay;
        $appointment->start_at = $start;
        $appointment->end_at   = $end;
        $appointment->save();

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        $this->ensureCanAccessAppointment($request, $appointment);
        $appointment->delete();
        return response()->json(['ok' => true]);
    }
}
