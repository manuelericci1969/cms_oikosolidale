@extends('admin.layout')

@section('title', 'Campagne CRM')

@section('content')
    @php
        $pageTotalCampaigns = $campaigns->count();
        $pageTotalRecipients = (int) $campaigns->sum(fn($c) => (int) ($c->total_recipients ?? 0));
        $pageTotalSent = (int) $campaigns->sum(fn($c) => (int) ($c->sent_count ?? 0));
        $pageTotalOpen = (int) $campaigns->sum(fn($c) => (int) ($c->open_count ?? 0));
        $pageTotalClick = (int) $campaigns->sum(fn($c) => (int) ($c->click_count ?? 0));

        $pageOpenRate = $pageTotalSent > 0 ? round(($pageTotalOpen / $pageTotalSent) * 100, 1) : null;
        $pageClickRate = $pageTotalSent > 0 ? round(($pageTotalClick / $pageTotalSent) * 100, 1) : null;

        $statusMap = [
            'draft' => ['label' => 'Bozza', 'class' => 'secondary'],
            'scheduled' => ['label' => 'Programmato', 'class' => 'info'],
            'sending' => ['label' => 'In invio', 'class' => 'primary'],
            'paused' => ['label' => 'In pausa', 'class' => 'warning'],
            'completed' => ['label' => 'Completato', 'class' => 'success'],
            'cancelled' => ['label' => 'Annullato', 'class' => 'dark'],
            'sent' => ['label' => 'Inviata', 'class' => 'success'],
        ];
    @endphp

    <style>
        .campaign-index-card {
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }

        .campaign-kpi-label {
            font-size: .82rem;
            color: #6c757d;
        }

        .campaign-kpi-value {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .campaign-table td,
        .campaign-table th {
            vertical-align: middle;
        }

        .campaign-name {
            font-weight: 600;
        }

        .campaign-subject {
            color: #6c757d;
            font-size: .92rem;
        }

        .campaign-rate {
            font-weight: 600;
            white-space: nowrap;
        }

        .campaign-meta {
            font-size: .83rem;
            color: #6c757d;
        }

        .campaign-empty {
            padding: 3rem 1rem;
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h3 mb-1">Campagne newsletter</h1>
            <div class="text-muted small">
                Gestisci elenco, stato e performance delle campagne email.
            </div>
        </div>

        <a href="{{ route('admin.crm.campaigns.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nuova campagna
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card campaign-index-card mb-4">
        <div class="card-header">Filtri</div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.crm.campaigns.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label">Cerca</label>
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Nome campagna, oggetto, preheader..."
                        >
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Stato</label>
                        <select name="status" class="form-select">
                            <option value="">-- Tutti --</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Dal</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Al</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Ordina per</label>
                        <select name="sort" class="form-select">
                            <option value="created_desc" {{ ($filters['sort'] ?? '') === 'created_desc' ? 'selected' : '' }}>Più recenti</option>
                            <option value="created_asc" {{ ($filters['sort'] ?? '') === 'created_asc' ? 'selected' : '' }}>Meno recenti</option>
                            <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Nome A-Z</option>
                            <option value="name_desc" {{ ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' }}>Nome Z-A</option>
                            <option value="recipients_desc" {{ ($filters['sort'] ?? '') === 'recipients_desc' ? 'selected' : '' }}>Più destinatari</option>
                            <option value="sent_desc" {{ ($filters['sort'] ?? '') === 'sent_desc' ? 'selected' : '' }}>Più inviate</option>
                            <option value="open_desc" {{ ($filters['sort'] ?? '') === 'open_desc' ? 'selected' : '' }}>Più aperture</option>
                            <option value="click_desc" {{ ($filters['sort'] ?? '') === 'click_desc' ? 'selected' : '' }}>Più click</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('admin.crm.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">
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
            <div class="card campaign-index-card h-100">
                <div class="card-body py-3">
                    <div class="campaign-kpi-label">Campagne trovate</div>
                    <div class="campaign-kpi-value">{{ $pageTotalCampaigns }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-index-card h-100">
                <div class="card-body py-3">
                    <div class="campaign-kpi-label">Destinatari</div>
                    <div class="campaign-kpi-value">{{ $pageTotalRecipients }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-index-card h-100">
                <div class="card-body py-3">
                    <div class="campaign-kpi-label">Inviate</div>
                    <div class="campaign-kpi-value">{{ $pageTotalSent }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-index-card h-100">
                <div class="card-body py-3">
                    <div class="campaign-kpi-label">Aperture</div>
                    <div class="campaign-kpi-value">{{ $pageTotalOpen }}</div>
                    <div class="small text-muted mt-1">
                        {{ $pageOpenRate !== null ? number_format($pageOpenRate, 1, ',', '.') . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-index-card h-100">
                <div class="card-body py-3">
                    <div class="campaign-kpi-label">Click</div>
                    <div class="campaign-kpi-value">{{ $pageTotalClick }}</div>
                    <div class="small text-muted mt-1">
                        {{ $pageClickRate !== null ? number_format($pageClickRate, 1, ',', '.') . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-index-card h-100">
                <div class="card-body py-3">
                    <div class="campaign-kpi-label">Vista</div>
                    <div class="small text-muted">
                        I KPI sono calcolati sui risultati filtrati presenti in questa pagina.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card campaign-index-card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-2 align-items-center small">
                <span class="text-muted">Legenda stati:</span>
                <span class="badge bg-secondary">Bozza</span>
                <span class="badge bg-info text-dark">Programmato</span>
                <span class="badge bg-primary">In invio</span>
                <span class="badge bg-warning text-dark">In pausa</span>
                <span class="badge bg-success">Completato / Inviata</span>
                <span class="badge bg-dark">Annullato</span>
            </div>
        </div>
    </div>

    <div class="card campaign-index-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table campaign-table mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th style="min-width: 260px;">Campagna</th>
                        <th>Stato</th>
                        <th class="text-end">Destinatari</th>
                        <th class="text-end">Inviate</th>
                        <th class="text-end">Aperture</th>
                        <th class="text-end">Open rate</th>
                        <th class="text-end">Click</th>
                        <th class="text-end">Click rate</th>
                        <th>Creata il</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($campaigns as $campaign)
                        @php
                            $sent = (int) ($campaign->sent_count ?? 0);
                            $open = (int) ($campaign->open_count ?? 0);
                            $click = (int) ($campaign->click_count ?? 0);
                            $totalRecipients = (int) ($campaign->total_recipients ?? 0);

                            $openRate = $sent > 0 ? round(($open / $sent) * 100, 1) : null;
                            $clickRate = $sent > 0 ? round(($click / $sent) * 100, 1) : null;

                            $statusData = $statusMap[$campaign->status] ?? [
                                'label' => \App\Modules\Crm\Models\Campaign::STATUS_OPTIONS[$campaign->status] ?? $campaign->status,
                                'class' => 'secondary',
                            ];
                        @endphp

                        <tr>
                            <td>
                                <div class="campaign-name">{{ $campaign->name }}</div>
                                <div class="campaign-subject">{{ $campaign->subject ?: '—' }}</div>
                            </td>

                            <td>
                                <span class="badge bg-{{ $statusData['class'] }}">
                                    {{ $statusData['label'] }}
                                </span>
                            </td>

                            <td class="text-end">
                                <span class="fw-semibold">{{ $totalRecipients }}</span>
                            </td>

                            <td class="text-end">
                                <span class="fw-semibold">{{ $sent }}</span>
                            </td>

                            <td class="text-end">
                                <span class="fw-semibold">{{ $open }}</span>
                            </td>

                            <td class="text-end">
                                @if($openRate === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="campaign-rate">{{ number_format($openRate, 1, ',', '.') }}%</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <span class="fw-semibold">{{ $click }}</span>
                            </td>

                            <td class="text-end">
                                @if($clickRate === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="campaign-rate">{{ number_format($clickRate, 1, ',', '.') }}%</span>
                                @endif
                            </td>

                            <td>
                                <div>{{ $campaign->created_at?->format('d/m/Y') }}</div>
                                <div class="campaign-meta">{{ $campaign->created_at?->format('H:i') }}</div>
                            </td>

                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.crm.campaigns.edit', $campaign) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Apri campagna">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('admin.crm.campaigns.destroy', $campaign) }}"
                                          method="POST"
                                          class="d-inline-block"
                                          onsubmit="return confirm('Eliminare definitivamente questa campagna?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Elimina campagna">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted campaign-empty">
                                <div class="mb-2">
                                    <i class="bi bi-envelope-paper fs-2"></i>
                                </div>
                                <div class="fw-semibold">Nessuna campagna trovata</div>
                                <div class="small mt-1">Prova a cambiare i filtri oppure crea una nuova campagna.</div>
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
