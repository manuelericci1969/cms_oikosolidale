<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use App\Modules\Crm\Models\CallVoiceSession;
use App\Modules\Crm\Models\EmailList;
use App\Modules\Crm\Services\CallExecutionService;
use App\Modules\Crm\Services\CallQueueBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class CallCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $campaigns = CallCampaign::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('provider', 'like', "%{$q}%");
                });
            })
            ->withCount([
                'queueItems',
                'queueItems as pending_count' => fn ($q) => $q->where('status', CallQueue::STATUS_PENDING),
                'queueItems as retry_count' => fn ($q) => $q->where('status', CallQueue::STATUS_RETRY),
                'queueItems as calling_count' => fn ($q) => $q->where('status', CallQueue::STATUS_CALLING),
                'queueItems as completed_count' => fn ($q) => $q->where('status', CallQueue::STATUS_COMPLETED),
                'queueItems as failed_count' => fn ($q) => $q->where('status', CallQueue::STATUS_FAILED),
            ])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $listIds = $campaigns->getCollection()
            ->pluck('filters.list_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $emailListsMap = EmailList::query()
            ->whereIn('id', $listIds)
            ->pluck('name', 'id')
            ->all();

        return view('crm::call-campaigns.index', [
            'campaigns' => $campaigns,
            'filters' => ['q' => $q],
            'emailListsMap' => $emailListsMap,
        ]);
    }

    public function create(): View
    {
        return view('crm::call-campaigns.create', [
            'campaign' => new CallCampaign(),
            'emailLists' => EmailList::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $campaign = CallCampaign::create($data);

        return redirect()
            ->route('admin.crm.call-campaigns.edit', $campaign)
            ->with('success', 'Campagna chiamate creata correttamente.');
    }

    public function show(Request $request, CallCampaign $callCampaign): View
    {
        $listId = (int) data_get($callCampaign->filters, 'list_id');
        $emailListName = $listId > 0
            ? EmailList::query()->where('id', $listId)->value('name')
            : null;

        $queueItems = $callCampaign->queueItems()
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'queue_page')
            ->withQueryString();

        $logs = $callCampaign->logs()
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'logs_page')
            ->withQueryString();

        $queueQuery = $callCampaign->queueItems();
        $logsQuery = $callCampaign->logs();

        return view('crm::call-campaigns.show', [
            'campaign' => $callCampaign,
            'emailListName' => $emailListName,
            'queueItems' => $queueItems,
            'logs' => $logs,

            'queueTotalCount' => (clone $queueQuery)->count(),
            'pendingQueueCount' => (clone $queueQuery)->where('status', CallQueue::STATUS_PENDING)->count(),
            'retryQueueCount' => (clone $queueQuery)->where('status', CallQueue::STATUS_RETRY)->count(),
            'callingQueueCount' => (clone $queueQuery)->where('status', CallQueue::STATUS_CALLING)->count(),
            'completedQueueCount' => (clone $queueQuery)->where('status', CallQueue::STATUS_COMPLETED)->count(),
            'failedQueueCount' => (clone $queueQuery)->where('status', CallQueue::STATUS_FAILED)->count(),

            'logsTotalCount' => (clone $logsQuery)->count(),
            'completedLogsCount' => (clone $logsQuery)->where('technical_outcome', CallLog::TECH_COMPLETED)->count(),
            'noAnswerLogsCount' => (clone $logsQuery)->where('technical_outcome', CallLog::TECH_NO_ANSWER)->count(),
            'busyLogsCount' => (clone $logsQuery)->where('technical_outcome', CallLog::TECH_BUSY)->count(),
            'voicemailLogsCount' => (clone $logsQuery)->where('technical_outcome', CallLog::TECH_VOICEMAIL)->count(),
            'errorLogsCount' => (clone $logsQuery)->whereIn('technical_outcome', [
                CallLog::TECH_ERROR,
                CallLog::TECH_FAILED,
                CallLog::TECH_REJECTED,
                CallLog::TECH_INVALID_NUMBER,
                CallLog::TECH_CANCELLED,
            ])->count(),

            'aiTestQueueItems' => $callCampaign->queueItems()
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'contact_name', 'phone', 'status']),

            'aiTestLogs' => $callCampaign->logs()
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'queue_id', 'phone', 'call_status', 'business_outcome']),
        ]);
    }

    public function edit(CallCampaign $callCampaign): View
    {
        return view('crm::call-campaigns.edit', [
            'campaign' => $callCampaign,
            'emailLists' => EmailList::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, CallCampaign $callCampaign): RedirectResponse
    {
        $data = $this->validateData($request);

        $callCampaign->update($data);

        return redirect()
            ->route('admin.crm.call-campaigns.edit', $callCampaign)
            ->with('success', 'Campagna chiamate aggiornata correttamente.');
    }

    public function destroy(CallCampaign $callCampaign): RedirectResponse
    {
        $callCampaign->delete();

        return redirect()
            ->route('admin.crm.call-campaigns.index')
            ->with('success', 'Campagna chiamate eliminata.');
    }

    public function buildQueue(CallCampaign $callCampaign, CallQueueBuilderService $builder): RedirectResponse
    {
        if ($callCampaign->source_mode !== CallCampaign::SOURCE_MODE_EMAIL_LIST_CONTACTS) {
            return back()->with('error', 'La build queue è supportata solo per sorgente email_list_contacts.');
        }

        $inserted = $builder->buildFromEmailListContacts($callCampaign);

        return back()->with('success', "Queue generata. Nuovi contatti inseriti: {$inserted}.");
    }

    public function rebuildQueue(CallCampaign $callCampaign, CallQueueBuilderService $builder): RedirectResponse
    {
        if ($callCampaign->source_mode !== CallCampaign::SOURCE_MODE_EMAIL_LIST_CONTACTS) {
            return back()->with('error', 'La rebuild queue è supportata solo per sorgente email_list_contacts.');
        }

        $inserted = 0;

        DB::transaction(function () use ($callCampaign, $builder, &$inserted) {
            $queueIds = $callCampaign->queueItems()->pluck('id');

            if ($queueIds->isNotEmpty()) {
                $callCampaign->logs()->whereIn('queue_id', $queueIds)->delete();
            }

            $callCampaign->queueItems()->delete();

            $inserted = $builder->buildFromEmailListContacts($callCampaign);
        });

        return back()->with('success', "Queue rigenerata. Contatti inseriti: {$inserted}.");
    }

    public function clearQueue(CallCampaign $callCampaign): RedirectResponse
    {
        DB::transaction(function () use ($callCampaign) {
            $queueIds = $callCampaign->queueItems()->pluck('id');

            if ($queueIds->isNotEmpty()) {
                $callCampaign->logs()->whereIn('queue_id', $queueIds)->delete();
            }

            $callCampaign->queueItems()->delete();
        });

        return back()->with('success', 'Queue svuotata correttamente.');
    }

    public function resetQueueItem(CallCampaign $callCampaign, CallQueue $queueItem): RedirectResponse
    {
        if ((int) $queueItem->campaign_id !== (int) $callCampaign->id) {
            abort(404);
        }

        DB::transaction(function () use ($queueItem) {
            $queueItem->update([
                'status' => CallQueue::STATUS_RETRY,
                'attempts' => 0,
                'last_attempt_at' => null,
                'next_attempt_at' => null,
                'completed_at' => null,
                'last_outcome' => null,
                'last_outcome_note' => null,
            ]);

            $queueItem->logs()->delete();
        });

        return back()->with('success', "Queue item #{$queueItem->id} resettato.");
    }

    public function resetFailedItems(CallCampaign $callCampaign): RedirectResponse
    {
        $items = $callCampaign->queueItems()
            ->whereIn('status', [
                CallQueue::STATUS_FAILED,
                CallQueue::STATUS_COMPLETED,
                CallQueue::STATUS_RETRY,
            ])
            ->get();

        $count = 0;

        DB::transaction(function () use ($items, &$count) {
            foreach ($items as $item) {
                $item->update([
                    'status' => CallQueue::STATUS_RETRY,
                    'attempts' => 0,
                    'last_attempt_at' => null,
                    'next_attempt_at' => null,
                    'completed_at' => null,
                    'last_outcome' => null,
                    'last_outcome_note' => null,
                ]);

                $item->logs()->delete();
                $count++;
            }
        });

        return back()->with('success', "Elementi resettati: {$count}.");
    }

    public function activate(CallCampaign $callCampaign): RedirectResponse
    {
        $callCampaign->update([
            'status' => CallCampaign::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        return back()->with('success', 'Campagna attivata.');
    }

    public function pause(CallCampaign $callCampaign): RedirectResponse
    {
        $callCampaign->update([
            'status' => CallCampaign::STATUS_PAUSED,
            'is_active' => false,
        ]);

        return back()->with('success', 'Campagna messa in pausa.');
    }

    public function runNow(CallCampaign $callCampaign, CallExecutionService $executor): RedirectResponse
    {
        try {
            $result = $executor->executeNextForCampaign($callCampaign);

            if (!$result) {
                return back()->with('info', 'Nessun contatto disponibile da chiamare.');
            }

            $providerCallId = data_get($result, 'provider_result.call_control_id')
                ?? data_get($result, 'provider_result.data.call_control_id')
                ?? data_get($result, 'provider_result.data.id');

            return back()->with(
                'success',
                'Chiamata avviata correttamente.' . ($providerCallId ? " ProviderCallId: {$providerCallId}" : '')
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore avvio chiamata: ' . $e->getMessage());
        }
    }

    public function downloadVoiceWav(CallVoiceSession $voiceSession)
    {
        $path = data_get($voiceSession->metadata, 'last_wav_file');

        return $this->downloadVoiceFile($voiceSession, $path, 'audio/wav');
    }

    public function downloadVoiceTranscript(CallVoiceSession $voiceSession)
    {
        $path = data_get($voiceSession->metadata, 'last_transcript_file');

        return $this->downloadVoiceFile($voiceSession, $path, 'text/plain; charset=UTF-8');
    }

    public function downloadVoiceRaw(CallVoiceSession $voiceSession)
    {
        $path = data_get($voiceSession->metadata, 'last_audio_file');

        return $this->downloadVoiceFile($voiceSession, $path, 'application/octet-stream');
    }

    protected function downloadVoiceFile(CallVoiceSession $voiceSession, ?string $path, string $contentType)
    {
        if (!$path || !is_string($path)) {
            abort(404, 'File non disponibile.');
        }

        $realPath = realpath($path);
        $allowedBase = realpath('/home/ubuntu/telnyx-voice-bridge/sessions');

        if (!$realPath || !$allowedBase || !str_starts_with($realPath, $allowedBase)) {
            abort(403, 'Percorso file non consentito.');
        }

        if (!is_file($realPath) || !is_readable($realPath)) {
            abort(404, 'File non trovato.');
        }

        return Response::download(
            $realPath,
            basename($realPath),
            ['Content-Type' => $contentType]
        );
    }

    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'provider' => ['required', 'string', 'max:50'],
            'source_mode' => ['required', 'string', 'max:50'],
            'script_prompt' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'list_id' => ['required_if:source_mode,email_list_contacts', 'nullable', 'integer', 'exists:crm_email_lists,id'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'timeout_secs' => ['nullable', 'integer', 'min:10', 'max:120'],
        ]);

        $user = auth()->user();
        $listId = (int) ($validated['list_id'] ?? 0);
        $maxAttempts = (int) ($validated['max_attempts'] ?? 3);
        $timeoutSecs = (int) ($validated['timeout_secs'] ?? 30);

        return [
            'client_id' => $user->client_id ?? 1,
            'owner_id' => $user?->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'provider' => $validated['provider'],
            'source_mode' => $validated['source_mode'],
            'script_prompt' => $validated['script_prompt'] ?? null,
            'status' => $validated['status'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'filters' => [
                'list_id' => $listId,
            ],
            'settings' => [
                'max_attempts' => $maxAttempts,
                'timeout_secs' => $timeoutSecs,
            ],
        ];
    }
}
