@extends('admin.layout')
@section('title','Dashboard')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Dashboard</h1>

        <div class="text-muted small">
            <span class="me-2">Vista: </span>
            <span class="badge bg-dark">{{ $isAdmin ? 'Admin (tutto)' : 'Agente (solo assegnati)' }}</span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Richieste totali</div>
                            <div class="fs-3 fw-semibold">{{ $leadsTotal }}</div>
                            <div class="small mt-1">
                                <span class="badge bg-primary-subtle text-primary">Nuove: {{ $leadsNew }}</span>
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Aperte: {{ $leadsOpen }}</span>
                            </div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-primary-subtle text-primary fw-semibold">
                            CRM
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Assegnate a me</div>
                            <div class="fs-3 fw-semibold">{{ $assignedToMe }}</div>
                            <div class="small mt-1">
                                <span class="badge bg-warning-subtle text-warning">
                                    Azioni scadute: {{ $nextActionsOverdue }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-warning-subtle text-warning fw-semibold">
                            TODO
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isAdmin)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">Senza assegnazione</div>
                                <div class="fs-3 fw-semibold">{{ $leadsUnassigned }}</div>
                                <div class="text-muted small mt-1">Lead con owner_id nullo</div>
                            </div>
                            <div class="rounded-3 px-3 py-2 bg-danger-subtle text-danger fw-semibold">
                                Alert
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Appuntamenti (7 gg)</div>
                            <div class="fs-3 fw-semibold">{{ $appointmentsNext7 }}</div>
                            <div class="text-muted small mt-1">Prossimi 7 giorni</div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-info-subtle text-info fw-semibold">
                            CAL
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pages --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Pagine pubblicate</div>
                            <div class="fs-3 fw-semibold">{{ $pagesPublished }}</div>
                            <div class="small mt-1">
                                <span class="badge bg-secondary-subtle text-secondary">Bozze: {{ $pagesDraft }}</span>
                                <span class="badge bg-dark-subtle text-dark ms-1">Arch.: {{ $pagesArchived }}</span>
                            </div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-success-subtle text-success fw-semibold">
                            CMS
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Pagine totali</div>
                            <div class="fs-3 fw-semibold">{{ $pagesTotal }}</div>
                            <div class="text-muted small mt-1">Mie: {{ $pagesMine }}</div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-secondary-subtle text-secondary fw-semibold">
                            TOT
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chatbot --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Conversazioni chatbot</div>
                            <div class="fs-3 fw-semibold">{{ $chatbotTotal ?? 0 }}</div>
                            <div class="small mt-1">
                                <span class="badge bg-primary-subtle text-primary">Aperte: {{ $chatbotOpen ?? 0 }}</span>
                                <span class="badge bg-success-subtle text-success ms-1">Qualificate: {{ $chatbotQualified ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-primary-subtle text-primary fw-semibold">
                            BOT
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Convertite / Spam</div>
                            <div class="fs-3 fw-semibold">{{ ($chatbotConverted ?? 0) + ($chatbotSpam ?? 0) }}</div>
                            <div class="small mt-1">
                                <span class="badge bg-success-subtle text-success">Convertite: {{ $chatbotConverted ?? 0 }}</span>
                                <span class="badge bg-danger-subtle text-danger ms-1">Spam: {{ $chatbotSpam ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="rounded-3 px-3 py-2 bg-info-subtle text-info fw-semibold">
                            AI
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isAdmin)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">Chatbot non assegnate</div>
                                <div class="fs-3 fw-semibold">{{ $chatbotUnassigned ?? 0 }}</div>
                                <div class="text-muted small mt-1">Conversazioni senza owner</div>
                            </div>
                            <div class="rounded-3 px-3 py-2 bg-warning-subtle text-warning fw-semibold">
                                BOT
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Andamento (ultimi 14 giorni)</div>
                        <div class="text-muted small">Lead vs Pagine</div>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="chartTrend"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Distribuzione Lead</div>
                        <div class="text-muted small">per stato</div>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="chartStatus"></canvas>
                    </div>

                    <div class="small text-muted mt-2">
                        <span class="badge bg-success-subtle text-success">Vinti: {{ $leadsWon }}</span>
                        <span class="badge bg-danger-subtle text-danger ms-1">Persi: {{ $leadsLost }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest lists --}}
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Ultime richieste</div>
                        <a class="small" href="{{ route('admin.crm.leads.index') }}">Vai ai lead</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr class="text-muted">
                                <th>Nome</th>
                                <th>Stato</th>
                                <th>Owner</th>
                                <th class="text-end">Data</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($latestLeads as $lead)
                                <tr>
                                    <td class="fw-medium">
                                        <a href="{{ route('admin.crm.leads.edit', $lead) }}">
                                            {{ $lead->name ?? ('Lead #' . $lead->id) }}
                                        </a>
                                        <div class="text-muted small">{{ $lead->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $lead->status_badge_class ?? 'bg-light text-muted' }}">
                                            {{ $lead->status_label ?? $lead->status }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ $lead->owner->name ?? '—' }}
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ optional($lead->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">Nessuna richiesta.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Ultime pagine</div>
                        <a class="small" href="{{ route('admin.pages.index') }}">Vai alle pagine</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr class="text-muted">
                                <th>Titolo</th>
                                <th>Stato</th>
                                <th class="text-end">Data</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($latestPages as $page)
                                <tr>
                                    <td class="fw-medium">
                                        <a href="{{ route('admin.pages.edit_v2', $page) }}">
                                            {{ $page->title ?? ('Pagina #' . $page->id) }}
                                        </a>
                                        <div class="text-muted small">{{ $page->slug }}</div>
                                    </td>
                                    <td>
                                        <span class="badge
                                            @if($page->status === 'published') bg-success-subtle text-success
                                            @elseif($page->status === 'draft') bg-secondary-subtle text-secondary
                                            @elseif($page->status === 'archived') bg-dark-subtle text-dark
                                            @else bg-light text-muted
                                            @endif
                                        ">
                                            {{ $page->status }}
                                        </span>
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ optional($page->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Nessuna pagina.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        {{-- Chatbot latest --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Ultime conversazioni chatbot</div>
                        <a class="small" href="{{ route('admin.crm.chatbot-conversations.index') }}">Vai alle conversazioni</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr class="text-muted">
                                <th>Visitatore</th>
                                <th>Intento</th>
                                <th>Stato</th>
                                <th>Score</th>
                                <th>Owner</th>
                                <th class="text-end">Ultimo messaggio</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse(($latestChatbotConversations ?? collect()) as $conversation)
                                <tr>
                                    <td class="fw-medium">
                                        <a href="{{ route('admin.crm.chatbot-conversations.show', $conversation) }}">
                                            {{ $conversation->visitor_name ?: ('Conversazione #' . $conversation->id) }}
                                        </a>
                                        <div class="text-muted small">
                                            {{ $conversation->visitor_email ?: ($conversation->visitor_phone ?: '—') }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $conversation->intent_label ?? $conversation->intent ?? '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        @php
                                            $chatbotStatusClass = match($conversation->status ?? null) {
                                                'open' => 'bg-primary-subtle text-primary',
                                                'qualified' => 'bg-success-subtle text-success',
                                                'converted' => 'bg-success text-white',
                                                'closed' => 'bg-secondary-subtle text-secondary',
                                                'spam' => 'bg-danger-subtle text-danger',
                                                default => 'bg-light text-muted',
                                            };
                                        @endphp
                                        <span class="badge {{ $chatbotStatusClass }}">
                                            {{ $conversation->status_label ?? $conversation->status ?? '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-dark-subtle text-dark">
                                            {{ (int) ($conversation->score ?? 0) }}
                                        </span>
                                    </td>

                                    <td class="text-muted small">
                                        {{ $conversation->owner->name ?? '—' }}
                                    </td>

                                    <td class="text-end text-muted small">
                                        {{ optional($conversation->last_message_at)->format('d/m/Y H:i') ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">Nessuna conversazione chatbot.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const labels = @json($labels);
            const leadDaily = @json($leadDaily);
            const pagesDaily = @json($pagesDaily);

            const byStatus = @json($leadsByStatus);
            const statusOrder = ['new','contacted','qualified','proposal','won','lost','archived'];

            const statusLabelsMap = {
                new: 'Nuovo',
                contacted: 'Contattato',
                qualified: 'Qualificato',
                proposal: 'Preventivo',
                won: 'Convertito',
                lost: 'Perso',
                archived: 'Archiviato'
            };

            const statusLabels = statusOrder.filter(s => (byStatus[s] ?? 0) > 0).map(s => statusLabelsMap[s] ?? s);
            const statusValues = statusOrder.filter(s => (byStatus[s] ?? 0) > 0).map(s => byStatus[s] ?? 0);

            const ctxTrend = document.getElementById('chartTrend');
            if (ctxTrend) {
                new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Lead', data: leadDaily, tension: 0.35 },
                            { label: 'Pagine', data: pagesDaily, tension: 0.35 },
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true },
                            tooltip: { enabled: true }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            const ctxStatus = document.getElementById('chartStatus');
            if (ctxStatus) {
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{ data: statusValues }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } }
                    }
                });
            }
        })();
    </script>
@endsection
