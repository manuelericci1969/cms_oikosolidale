@extends('admin.layout')

@section('title', 'Campagne Chiamate')

@section('content')
    @php
        $totalCampaigns = $campaigns->count();
        $totalQueue = (int) $campaigns->sum('queue_items_count');
        $totalPending = (int) $campaigns->sum('pending_count');
        $totalRetry = (int) $campaigns->sum('retry_count');
        $totalCalling = (int) $campaigns->sum('calling_count');
        $totalCompleted = (int) $campaigns->sum('completed_count');
        $totalFailed = (int) $campaigns->sum('failed_count');

        $statusMap = [
            'draft' => ['label' => 'Bozza', 'class' => 'secondary'],
            'active' => ['label' => 'Attiva', 'class' => 'success'],
            'paused' => ['label' => 'In pausa', 'class' => 'warning'],
            'completed' => ['label' => 'Completata', 'class' => 'primary'],
            'archived' => ['label' => 'Archiviata', 'class' => 'dark'],
        ];
    @endphp

    <style>
        .call-campaign-card {
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }

        .call-kpi-label {
            font-size: .82rem;
            color: #6c757d;
        }

        .call-kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .call-campaign-name {
            font-weight: 600;
        }

        .call-campaign-meta {
            font-size: .84rem;
            color: #6c757d;
        }

        .call-campaign-table td,
        .call-campaign-table th {
            vertical-align: middle;
        }

        .call-index-mini {
            font-size: .82rem;
            color: #6c757d;
        }

        .call-index-provider {
            font-size: .85rem;
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h3 mb-1">Campagne chiamate</h1>
            <div class="text-muted small">
                Gestisci campagne Telnyx, queue contatti ed esiti chiamate.
            </div>
        </div>

        <a href="{{ route('admin.crm.call-campaigns.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nuova campagna chiamate
        </a>
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

    <div class="card call-campaign-card mb-4">
        <div class="card-header">Filtri</div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.crm.call-campaigns.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label">Cerca</label>
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Nome, descrizione, provider..."
                        >
                    </div>

                    <div class="col-lg-8 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.crm.call-campaigns.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>

                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel"></i> Applica filtri
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Campagne</div>
                    <div class="call-kpi-value">{{ $totalCampaigns }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Queue totale</div>
                    <div class="call-kpi-value">{{ $totalQueue }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Pending</div>
                    <div class="call-kpi-value">{{ $totalPending }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Retry</div>
                    <div class="call-kpi-value">{{ $totalRetry }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Calling</div>
                    <div class="call-kpi-value">{{ $totalCalling }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-1 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Done</div>
                    <div class="call-kpi-value">{{ $totalCompleted }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-1 col-sm-6 mb-3">
            <div class="card call-campaign-card h-100">
                <div class="card-body py-3">
                    <div class="call-kpi-label">Failed</div>
                    <div class="call-kpi-value">{{ $totalFailed }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card call-campaign-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table call-campaign-table mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th style="min-width:260px;">Campagna</th>
                        <th>Stato</th>
                        <th>Provider</th>
                        <th>Lista</th>
                        <th class="text-end">Queue</th>
                        <th class="text-end">Pending</th>
                        <th class="text-end">Retry</th>
                        <th class="text-end">Calling</th>
                        <th class="text-end">Completed</th>
                        <th class="text-end">Failed</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($campaigns as $campaign)
                        @php
                            $statusData = $statusMap[$campaign->status] ?? [
                                'label' => ucfirst((string) $campaign->status),
                                'class' => 'secondary',
                            ];

                            $listId = data_get($campaign->filters, 'list_id');
                            $maxAttempts = data_get($campaign->settings, 'max_attempts', 3);
                            $timeoutSecs = data_get($campaign->settings, 'timeout_secs', 30);
                        @endphp
                        <tr>
                            <td>
                                <div class="call-campaign-name">{{ $campaign->name }}</div>
                                <div class="call-campaign-meta">
                                    {{ $campaign->description ?: 'Nessuna descrizione' }}
                                </div>
                                <div class="call-index-mini mt-1">
                                    Max tentativi: {{ $maxAttempts }} · Timeout: {{ $timeoutSecs }}s
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-{{ $statusData['class'] }}">
                                    {{ $statusData['label'] }}
                                </span>
                                <div class="mt-1">
                                    @if($campaign->is_active)
                                        <span class="badge bg-success">Attiva</span>
                                    @else
                                        <span class="badge bg-secondary">Non attiva</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="call-index-provider">{{ strtoupper($campaign->provider ?: '—') }}</div>
                                <div class="call-index-mini">{{ $campaign->source_mode ?: '—' }}</div>
                            </td>

                            <td>
                                @if($listId)
                                    <div>{{ $emailListsMap[$listId] ?? ('Lista #' . $listId) }}</div>
                                    <div class="call-index-mini">ID {{ $listId }}</div>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="text-end">{{ $campaign->queue_items_count }}</td>
                            <td class="text-end">{{ $campaign->pending_count }}</td>
                            <td class="text-end">{{ $campaign->retry_count }}</td>
                            <td class="text-end">{{ $campaign->calling_count }}</td>
                            <td class="text-end">{{ $campaign->completed_count }}</td>
                            <td class="text-end">{{ $campaign->failed_count }}</td>

                            <td class="text-end">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                    <a href="{{ route('admin.crm.call-campaigns.show', $campaign) }}"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Apri dettaglio">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="{{ route('admin.crm.call-campaigns.edit', $campaign) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('admin.crm.call-campaigns.activate', $campaign) }}"
                                          method="POST"
                                          class="d-inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" title="Attiva">
                                            <i class="bi bi-play-circle"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.crm.call-campaigns.pause', $campaign) }}"
                                          method="POST"
                                          class="d-inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" title="Pausa">
                                            <i class="bi bi-pause-circle"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.crm.call-campaigns.destroy', $campaign) }}"
                                          method="POST"
                                          class="d-inline-block"
                                          onsubmit="return confirm('Eliminare questa campagna chiamate?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Elimina">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                Nessuna campagna chiamate trovata.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($campaigns->hasPages())
            <div class="card-footer">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
@endsection
