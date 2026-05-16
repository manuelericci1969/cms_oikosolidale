@extends('admin.layout')

@section('title', 'Dettaglio campagna chiamate')

@section('content')
    @php
        $queueCollection = collect($queueItems->items());
        $logsCollection = collect($logs->items());

        $totalQueue = $queueTotalCount ?? $queueCollection->count();
        $pendingCount = $pendingQueueCount ?? 0;
        $retryCount = $retryQueueCount ?? 0;
        $callingCount = $callingQueueCount ?? 0;
        $completedCount = $completedQueueCount ?? 0;
        $failedCount = $failedQueueCount ?? 0;

        $listId = data_get($campaign->filters, 'list_id');
        $maxAttempts = data_get($campaign->settings, 'max_attempts', 3);
        $timeoutSecs = data_get($campaign->settings, 'timeout_secs', 30);

        $statusMap = [
            'draft' => ['label' => 'Bozza', 'class' => 'secondary'],
            'active' => ['label' => 'Attiva', 'class' => 'success'],
            'paused' => ['label' => 'In pausa', 'class' => 'warning'],
            'completed' => ['label' => 'Completata', 'class' => 'primary'],
            'archived' => ['label' => 'Archiviata', 'class' => 'dark'],
        ];

        $queueStatusMap = [
            'pending' => ['label' => 'Pending', 'class' => 'secondary'],
            'retry' => ['label' => 'Retry', 'class' => 'warning'],
            'calling' => ['label' => 'Calling', 'class' => 'info'],
            'completed' => ['label' => 'Completed', 'class' => 'success'],
            'failed' => ['label' => 'Failed', 'class' => 'danger'],
            'callback' => ['label' => 'Callback', 'class' => 'primary'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'dark'],
            'skipped' => ['label' => 'Skipped', 'class' => 'secondary'],
        ];

        $logStatusMap = [
            'initiated' => ['label' => 'Initiated', 'class' => 'secondary'],
            'ringing' => ['label' => 'Ringing', 'class' => 'info'],
            'answered' => ['label' => 'Answered', 'class' => 'primary'],
            'completed' => ['label' => 'Completed', 'class' => 'success'],
            'failed' => ['label' => 'Failed', 'class' => 'danger'],
            'busy' => ['label' => 'Busy', 'class' => 'warning'],
            'no_answer' => ['label' => 'No answer', 'class' => 'warning'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'dark'],
        ];

        $technicalOutcomeMap = [
            'completed' => ['label' => 'Completed', 'class' => 'success'],
            'busy' => ['label' => 'Busy', 'class' => 'warning'],
            'no_answer' => ['label' => 'No answer', 'class' => 'warning'],
            'voicemail' => ['label' => 'Voicemail', 'class' => 'info'],
            'failed' => ['label' => 'Failed', 'class' => 'danger'],
            'invalid_number' => ['label' => 'Invalid number', 'class' => 'danger'],
            'rejected' => ['label' => 'Rejected', 'class' => 'danger'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'dark'],
            'technical_error' => ['label' => 'Technical error', 'class' => 'danger'],
        ];

        $voiceSessionsByCallLog = \App\Modules\Crm\Models\CallVoiceSession::query()
            ->whereIn('call_log_id', $logsCollection->pluck('id')->filter()->all())
            ->orderByDesc('id')
            ->get()
            ->unique('call_log_id')
            ->keyBy('call_log_id');

        $statusData = $statusMap[$campaign->status] ?? ['label' => $campaign->status, 'class' => 'secondary'];
    @endphp

    <style>
        .call-show-card {
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 2px 12px rgba(0,0,0,.04);
        }

        .call-show-kpi-label {
            font-size: .82rem;
            color: #6c757d;
        }

        .call-show-kpi-value {
            font-size: 1.55rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .call-show-table td,
        .call-show-table th {
            vertical-align: middle;
        }

        .call-show-muted {
            color: #6c757d;
            font-size: .9rem;
        }

        .call-show-mini-box {
            border: 1px solid rgba(0,0,0,.08);
            border-radius: .5rem;
            padding: .75rem;
            background: #f8f9fa;
            height: 100%;
        }

        .call-show-id {
            font-family: monospace;
            font-size: .82rem;
            word-break: break-all;
        }

        .voice-actions a {
            text-decoration: none;
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h3 mb-1">Campagna chiamate: {{ $campaign->name }}</h1>
            <div class="small text-muted d-flex flex-wrap gap-2 align-items-center">
                <span><strong>Provider:</strong> {{ strtoupper($campaign->provider) }}</span>
                <span><strong>Lista:</strong> {{ $emailListName ?: ($listId ? 'Lista #' . $listId : '—') }}</span>
                <span><strong>Source mode:</strong> {{ $campaign->source_mode ?: '—' }}</span>

                <span class="badge bg-{{ $statusData['class'] }}">
                    {{ $statusData['label'] }}
                </span>

                @if($campaign->is_active)
                    <span class="badge bg-success">Attiva</span>
                @else
                    <span class="badge bg-secondary">Non attiva</span>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.call-campaigns.edit', $campaign) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil"></i> Modifica
            </a>

            <a href="{{ route('admin.crm.call-campaigns.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Torna alle campagne
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Queue totale</div>
                    <div class="call-show-kpi-value">{{ $totalQueue }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Pending</div>
                    <div class="call-show-kpi-value">{{ $pendingCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Retry</div>
                    <div class="call-show-kpi-value">{{ $retryCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Calling</div>
                    <div class="call-show-kpi-value">{{ $callingCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Completed</div>
                    <div class="call-show-kpi-value">{{ $completedCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Failed</div>
                    <div class="call-show-kpi-value">{{ $failedCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Log completed</div>
                    <div class="call-show-kpi-value">{{ $completedLogsCount ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">No answer</div>
                    <div class="call-show-kpi-value">{{ $noAnswerLogsCount ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Busy</div>
                    <div class="call-show-kpi-value">{{ $busyLogsCount ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Voicemail</div>
                    <div class="call-show-kpi-value">{{ $voicemailLogsCount ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 mb-3">
            <div class="card call-show-card h-100">
                <div class="card-body py-3">
                    <div class="call-show-kpi-label">Errori tecnici / rejected / invalid</div>
                    <div class="call-show-kpi-value">{{ $errorLogsCount ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card call-show-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Azioni campagna</span>
            <span class="small text-muted">
                Lista {{ $emailListName ?: ($listId ?: '—') }} · Max tentativi {{ $maxAttempts }} · Timeout {{ $timeoutSecs }}s
            </span>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.crm.call-campaigns.build_queue', $campaign) }}">
                    @csrf
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people"></i> Costruisci queue
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.crm.call-campaigns.rebuild_queue', $campaign) }}"
                      onsubmit="return confirm('Rigenerare la queue? Verranno eliminati gli elementi attuali della queue della campagna.');">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Rigenera queue
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.crm.call-campaigns.run_now', $campaign) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm">
                        <i class="bi bi-telephone-outbound"></i> Avvia una chiamata ora
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.crm.call-campaigns.activate', $campaign) }}">
                    @csrf
                    <button class="btn btn-outline-success btn-sm">
                        <i class="bi bi-play-circle"></i> Attiva
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.crm.call-campaigns.pause', $campaign) }}">
                    @csrf
                    <button class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-pause-circle"></i> Pausa
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.crm.call-campaigns.clear_queue', $campaign) }}"
                      onsubmit="return confirm('Svuotare tutta la queue della campagna? Verranno eliminati anche i log associati.');">
                    @csrf
                    <button class="btn btn-outline-dark btn-sm">
                        <i class="bi bi-trash3"></i> Svuota queue
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.crm.call-campaigns.reset_failed_items', $campaign) }}"
                      onsubmit="return confirm('Resettare failed/completed/retry per rifare i test? I log associati verranno rimossi.');">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Reset esiti
                    </button>
                </form>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <div class="call-show-mini-box">
                        <div class="small text-muted">Lista sorgente</div>
                        <div class="fw-semibold">
                            {{ $emailListName ?: ($listId ? 'Lista #' . $listId : '—') }}
                        </div>
                        <div class="small text-muted">ID {{ $listId ?: '—' }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="call-show-mini-box">
                        <div class="small text-muted">Max tentativi</div>
                        <div class="fw-semibold">{{ $maxAttempts }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="call-show-mini-box">
                        <div class="small text-muted">Timeout chiamata</div>
                        <div class="fw-semibold">{{ $timeoutSecs }}s</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="call-show-mini-box">
                        <div class="small text-muted">Log</div>
                        <div class="fw-semibold">{{ $logsTotalCount ?? $logs->total() }}</div>
                        <div class="small text-muted">Mostrati in pagina: {{ $logs->count() }}</div>
                    </div>
                </div>
            </div>

            @if($campaign->description)
                <div class="mt-3 call-show-muted">
                    {{ $campaign->description }}
                </div>
            @endif

            @if($campaign->script_prompt)
                <div class="mt-3">
                    <div class="small text-muted mb-1">Script prompt</div>
                    <div class="border rounded p-3 bg-light small">
                        {!! nl2br(e($campaign->script_prompt)) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card call-show-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Test Agente AI</span>
            <span class="small text-muted">Simulazione turni conversazionali su log esistenti</span>
        </div>
        <div class="card-body">
            <form id="ai-call-agent-form" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Queue item</label>
                    <select id="ai_queue_id" class="form-select" required>
                        <option value="">-- Seleziona queue item --</option>
                        @foreach($aiTestQueueItems as $item)
                            <option value="{{ $item->id }}">
                                #{{ $item->id }} · {{ $item->contact_name ?: 'Senza nome' }} · {{ $item->phone }} · {{ $item->status }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Call log</label>
                    <select id="ai_call_log_id" class="form-select" required>
                        <option value="">-- Seleziona call log --</option>
                        @foreach($aiTestLogs as $log)
                            <option value="{{ $log->id }}" data-queue-id="{{ $log->queue_id }}">
                                #{{ $log->id }} · Queue {{ $log->queue_id ?: '—' }} · {{ $log->phone }} · {{ $log->call_status ?: '—' }} · {{ $log->business_outcome ?: '—' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Usa preferibilmente un call log coerente con il queue item selezionato.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Campagna</label>
                    <input type="text" class="form-control" value="#{{ $campaign->id }} · {{ $campaign->name }}" disabled>
                </div>

                <div class="col-12">
                    <label class="form-label">Messaggio utente simulato</label>
                    <textarea
                        id="ai_message"
                        class="form-control"
                        rows="4"
                        placeholder="Esempio: Mi richiami domani mattina per favore"
                        required
                    ></textarea>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-cpu"></i> Invia al test Agente AI
                    </button>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="ai-fill-callback">
                        Callback
                    </button>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="ai-fill-interest">
                        Interessato
                    </button>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="ai-fill-nointerest">
                        Non interessato
                    </button>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="ai-fill-dnc">
                        Non chiamatemi più
                    </button>
                </div>
            </form>

            <hr>

            <div id="ai-test-result" class="d-none">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="call-show-mini-box">
                            <div class="small text-muted">Esito richiesta</div>
                            <div class="fw-semibold" id="ai_result_ok">—</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="call-show-mini-box">
                            <div class="small text-muted">Suggested outcome</div>
                            <div class="fw-semibold" id="ai_result_outcome">—</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="call-show-mini-box">
                            <div class="small text-muted">Queue status</div>
                            <div class="fw-semibold" id="ai_result_queue_status">—</div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="call-show-mini-box">
                            <div class="small text-muted">Business outcome</div>
                            <div class="fw-semibold" id="ai_result_business_outcome">—</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="small text-muted mb-1">Reply agente</div>
                    <div class="border rounded p-3 bg-light" id="ai_result_reply">—</div>
                </div>

                <div class="mt-3">
                    <div class="small text-muted mb-1">Messaggio sistema / errore</div>
                    <div class="border rounded p-3 bg-light" id="ai_result_message">—</div>
                </div>

                <div class="mt-3">
                    <div class="small text-muted mb-1">Storico conversazione</div>
                    <div class="border rounded p-3 bg-light" id="ai_result_history" style="max-height: 320px; overflow:auto;">
                        —
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card call-show-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Queue contatti</span>
            <span class="small text-muted">Totale: {{ $queueTotalCount ?? $queueItems->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table call-show-table table-sm mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Contatto</th>
                        <th>Origine</th>
                        <th>Telefono</th>
                        <th>Stato</th>
                        <th class="text-end">Tentativi</th>
                        <th>Ultimo esito</th>
                        <th>Prossimo tentativo</th>
                        <th>Completata</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($queueItems as $item)
                        @php
                            $queueStatus = $queueStatusMap[$item->status] ?? ['label' => $item->status, 'class' => 'secondary'];
                        @endphp
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>
                                <div>{{ $item->contact_name ?: '—' }}</div>
                                <div class="small text-muted">{{ $item->email ?: '' }}</div>
                            </td>
                            <td>
                                <div>{{ $item->source_type ?: '—' }}</div>
                                <div class="small text-muted">{{ $item->source_id ?: '' }}</div>
                            </td>
                            <td>{{ $item->phone }}</td>
                            <td>
                                <span class="badge bg-{{ $queueStatus['class'] }}">
                                    {{ $queueStatus['label'] }}
                                </span>
                            </td>
                            <td class="text-end">{{ $item->attempts }}/{{ $item->max_attempts }}</td>
                            <td>
                                <div>{{ $item->last_outcome ?: '—' }}</div>
                                @if($item->last_outcome_note)
                                    <div class="small text-muted">{{ $item->last_outcome_note }}</div>
                                @endif
                            </td>
                            <td>{{ $item->next_attempt_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td>{{ $item->completed_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="text-end">
                                <form method="POST"
                                      action="{{ route('admin.crm.call-campaigns.reset_queue_item', [$campaign, $item]) }}"
                                      onsubmit="return confirm('Resettare il queue item #{{ $item->id }}?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning" title="Reset queue item">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Nessun elemento in queue.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($queueItems->hasPages())
            <div class="card-footer">
                {{ $queueItems->links() }}
            </div>
        @endif
    </div>

    <div class="card call-show-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Log chiamate</span>
            <span class="small text-muted">Totale: {{ $logsTotalCount ?? $logs->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table call-show-table table-sm mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Queue</th>
                        <th>Provider Call ID</th>
                        <th>Telefono</th>
                        <th>Stato</th>
                        <th>Esito tecnico</th>
                        <th>Esito business</th>
                        <th>Nota</th>
                        <th>Voice / Transcript</th>
                        <th class="text-end">Durata</th>
                        <th>Answered</th>
                        <th>Ended</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>
                                <div>{{ $log->queue_id ?: '—' }}</div>
                                <div class="small text-muted">{{ $log->campaign_id ?: '' }}</div>
                            </td>
                            <td class="call-show-id">{{ $log->provider_call_id ?: '—' }}</td>
                            <td>{{ $log->phone }}</td>
                            <td>
                                @if($log->call_status)
                                    @php
                                        $logStatus = $logStatusMap[$log->call_status] ?? ['label' => $log->call_status, 'class' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $logStatus['class'] }}">
                                        {{ $logStatus['label'] }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($log->technical_outcome)
                                    @php
                                        $techStatus = $technicalOutcomeMap[$log->technical_outcome] ?? ['label' => $log->technical_outcome, 'class' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $techStatus['class'] }}">
                                        {{ $techStatus['label'] }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $log->business_outcome ?: '—' }}</td>
                            <td>
                                <div>{{ $log->operator_note ?: '—' }}</div>
                                @if($log->ai_summary)
                                    <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($log->ai_summary, 120) }}</div>
                                @endif
                            </td>

                            @php
                                $voiceSession = $voiceSessionsByCallLog->get($log->id);
                                $voiceMeta = is_array($voiceSession?->metadata) ? $voiceSession->metadata : [];
                                $transcriptText = $voiceMeta['last_transcript_text'] ?? null;
                                $streamId = $voiceMeta['last_stream_id'] ?? null;
                                $wavUrl = $voiceMeta['last_wav_url'] ?? null;
                                $transcriptUrl = $voiceMeta['last_transcript_url'] ?? null;
                                $audioUrl = $voiceMeta['last_audio_url'] ?? null;
                            @endphp
                            <td>
                                @if($voiceSession)
                                    <div class="small">
                                        <div><strong>Sessione:</strong> #{{ $voiceSession->id }}</div>
                                        <div><strong>Stream:</strong> <span class="call-show-id">{{ $streamId ?: '—' }}</span></div>

                                        @if($transcriptText)
                                            <div class="mt-1">
                                                <strong>Transcript:</strong><br>
                                                <span class="text-muted">{{ \Illuminate\Support\Str::limit(trim($transcriptText), 180) }}</span>
                                            </div>
                                        @else
                                            <div class="mt-1 text-muted">Transcript non disponibile</div>
                                        @endif

                                        <div class="mt-2 d-flex flex-wrap gap-2 voice-actions">
                                            @if($wavUrl)
                                                <a href="{{ $wavUrl }}" target="_blank" rel="noopener noreferrer"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-file-earmark-music"></i> WAV
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                                                    <i class="bi bi-file-earmark-music"></i> WAV
                                                </button>
                                            @endif

                                            @if($transcriptUrl)
                                                <a href="{{ $transcriptUrl }}" target="_blank" rel="noopener noreferrer"
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-file-earmark-text"></i> Transcript
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                                    <i class="bi bi-file-earmark-text"></i> Transcript
                                                </button>
                                            @endif

                                            @if($audioUrl)
                                                <a href="{{ $audioUrl }}" target="_blank" rel="noopener noreferrer"
                                                   class="btn btn-outline-dark btn-sm">
                                                    <i class="bi bi-file-earmark-binary"></i> RAW
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-outline-dark btn-sm" disabled>
                                                    <i class="bi bi-file-earmark-binary"></i> RAW
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="text-end">{{ $log->duration_seconds ?? 0 }}</td>
                            <td>{{ $log->answered_at?->format('d/m/Y H:i:s') ?: '—' }}</td>
                            <td>{{ $log->ended_at?->format('d/m/Y H:i:s') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                Nessun log chiamata disponibile.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('ai-call-agent-form');
            const messageEl = document.getElementById('ai_message');
            const queueEl = document.getElementById('ai_queue_id');
            const logEl = document.getElementById('ai_call_log_id');

            const resultBox = document.getElementById('ai-test-result');
            const resultOk = document.getElementById('ai_result_ok');
            const resultOutcome = document.getElementById('ai_result_outcome');
            const resultQueueStatus = document.getElementById('ai_result_queue_status');
            const resultBusinessOutcome = document.getElementById('ai_result_business_outcome');
            const resultReply = document.getElementById('ai_result_reply');
            const resultMessage = document.getElementById('ai_result_message');
            const resultHistory = document.getElementById('ai_result_history');

            document.getElementById('ai-fill-callback')?.addEventListener('click', () => {
                messageEl.value = 'Mi richiami domani mattina per favore';
            });

            document.getElementById('ai-fill-interest')?.addEventListener('click', () => {
                messageEl.value = 'Sì, mi interessa capire meglio cosa proponete';
            });

            document.getElementById('ai-fill-nointerest')?.addEventListener('click', () => {
                messageEl.value = 'Non mi interessa, grazie';
            });

            document.getElementById('ai-fill-dnc')?.addEventListener('click', () => {
                messageEl.value = 'Non chiamatemi più per favore';
            });

            form?.addEventListener('submit', async function (e) {
                e.preventDefault();

                const payload = {
                    campaign_id: {{ (int) $campaign->id }},
                    queue_id: parseInt(queueEl.value, 10),
                    call_log_id: parseInt(logEl.value, 10),
                    message: messageEl.value.trim(),
                };

                if (!payload.queue_id || !payload.call_log_id || !payload.message) {
                    alert('Compila queue item, call log e messaggio.');
                    return;
                }

                try {
                    const response = await fetch('{{ url('/api/ai/call-agent/reply') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    resultBox.classList.remove('d-none');

                    resultOk.textContent = data.ok ? 'OK' : 'KO';
                    resultOutcome.textContent = data.suggested_outcome ?? '—';
                    resultQueueStatus.textContent = data.call_log?.queue_item?.status ?? '—';
                    resultBusinessOutcome.textContent = data.call_log?.business_outcome ?? '—';
                    resultReply.textContent = data.reply ?? '—';
                    resultMessage.textContent = data.message ?? '—';

                    const history = data.call_log?.conversation_messages ?? [];
                    if (history.length) {
                        resultHistory.innerHTML = history.map(item => {
                            const role = item.role ?? '—';
                            const message = item.message ?? '';
                            return `<div class="mb-2"><strong>${role}</strong><br>${message}</div>`;
                        }).join('<hr class="my-2">');
                    } else {
                        resultHistory.textContent = 'Nessun messaggio disponibile.';
                    }
                } catch (error) {
                    resultBox.classList.remove('d-none');
                    resultOk.textContent = 'KO';
                    resultOutcome.textContent = '—';
                    resultQueueStatus.textContent = '—';
                    resultBusinessOutcome.textContent = '—';
                    resultReply.textContent = '—';
                    resultMessage.textContent = 'Errore di chiamata endpoint.';
                    resultHistory.textContent = '—';
                }
            });
        });
    </script>

@endsection
