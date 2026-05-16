@extends('admin.layout')

@section('title', 'Leads assegnati a me')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">I miei lead</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    {{-- FILTRI --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('agent.crm.leads.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Cerca</label>
                    <input type="text" name="q" class="form-control"
                           placeholder="Nome, email, telefono, oggetto…"
                           value="{{ $search ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Stato lead</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($status ?? null) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 text-md-end mt-2 mt-md-0">
                    <button class="btn btn-outline-secondary w-100 mb-1" title="Applica filtri">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('agent.crm.leads.index') }}"
                       class="btn btn-outline-secondary w-100" title="Azzera filtri">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABELLA LEADS (solo quelli dell’agente) --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 26%;">Lead</th>
                        <th style="width: 22%;">Contatti</th>
                        <th style="width: 26%;">Workflow</th>
                        <th style="width: 16%;">Cliente</th>
                        <th style="width: 10%;">Creato il</th>
                        <th class="text-end" style="width: 10%;">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                        @php
                            $status        = $lead->status ?? 'new';
                            $statusLabel   = $lead->status_label ?? ucfirst($status);
                            $statusClass   = $lead->status_badge_class
                                ?? match($status) {
                                    'new'        => 'bg-primary-subtle text-primary',
                                    'contacted'  => 'bg-info-subtle text-info',
                                    'qualified'  => 'bg-success-subtle text-success',
                                    'proposal'   => 'bg-warning-subtle text-warning',
                                    'won'        => 'bg-success',
                                    'lost'       => 'bg-danger-subtle text-danger',
                                    'archived'   => 'bg-secondary',
                                    default      => 'bg-light text-muted',
                                };

                            $owner         = $lead->owner?->name ?? null;
                            $lastContact   = $lead->last_contact_at;
                            $nextAction    = $lead->next_action_at;
                            $closedAt      = $lead->closed_at;
                            $closedReason  = $lead->closed_reason;

                            $nextActionText  = null;
                            $nextActionClass = 'text-muted';

                            if ($nextAction) {
                                $daysDiff = now()->startOfDay()->diffInDays(
                                    $nextAction->copy()->startOfDay(),
                                    false
                                );

                                if ($daysDiff < 0) {
                                    $nextActionText  = 'In ritardo di ' . abs($daysDiff) . ' gg';
                                    $nextActionClass = 'text-danger fw-semibold';
                                } elseif ($daysDiff === 0) {
                                    $nextActionText  = 'Oggi';
                                    $nextActionClass = 'text-warning fw-semibold';
                                } elseif ($daysDiff === 1) {
                                    $nextActionText  = 'Domani';
                                    $nextActionClass = 'text-primary';
                                } else {
                                    $nextActionText  = 'Tra ' . $daysDiff . ' gg';
                                    $nextActionClass = 'text-success';
                                }
                            }
                        @endphp

                        <tr>
                            {{-- Lead --}}
                            <td>
                                <div class="fw-semibold">
                                    {{ $lead->name ?: 'Senza nome' }}
                                </div>

                                @if($lead->subject)
                                    <div class="small text-muted">
                                        <i class="bi bi-chat-left-text"></i>
                                        {{ $lead->subject }}
                                    </div>
                                @endif

                                @if($lead->source)
                                    <div class="small text-muted">
                                        <i class="bi bi-megaphone"></i>
                                        Fonte: {{ $lead->source_label ?? ucfirst($lead->source) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Contatti --}}
                            <td>
                                @if($lead->email)
                                    <div class="mb-1">
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                                    </div>
                                @endif

                                @if($lead->phone)
                                    <div>
                                        <i class="bi bi-telephone"></i>
                                        {{ $lead->phone }}
                                    </div>
                                @endif

                                @if(!$lead->email && !$lead->phone)
                                    <span class="text-muted small">Nessun recapito</span>
                                @endif
                            </td>

                            {{-- Workflow --}}
                            <td>
                                {{-- Owner --}}
                                <div class="mb-1">
                                    <span class="small text-muted d-block">Assegnato a</span>
                                    @if($owner)
                                        <i class="bi bi-person-badge"></i>
                                        {{ $owner }}
                                    @else
                                        <span class="text-muted small">
                                            <i class="bi bi-person-dash"></i> Non assegnato
                                        </span>
                                    @endif
                                </div>

                                {{-- Ultimo contatto --}}
                                <div class="mb-1">
                                    <span class="small text-muted d-block">Ultimo contatto</span>
                                    @if($lastContact)
                                        <i class="bi bi-clock-history"></i>
                                        {{ $lastContact->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted small">Nessun contatto registrato</span>
                                    @endif
                                </div>

                                {{-- Prossima azione --}}
                                <div class="mb-1">
                                    <span class="small text-muted d-block">Prossima azione</span>
                                    @if($nextAction)
                                        <div class="{{ $nextActionClass }}">
                                            <i class="bi bi-flag"></i>
                                            {{ $nextAction->format('d/m/Y H:i') }}
                                            @if($nextActionText)
                                                <span class="small">({{ $nextActionText }})</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Non pianificata</span>
                                    @endif
                                </div>

                                {{-- Chiusura lead --}}
                                @if($closedAt)
                                    <div class="mt-1 small text-muted">
                                        <i class="bi bi-check2-circle"></i>
                                        Chiuso il {{ $closedAt->format('d/m/Y H:i') }}
                                        @if($closedReason)
                                            <span>– {{ $closedReason }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Cliente associato (solo lettura) --}}
                            <td>
                                @if($lead->customer)
                                    <div>{{ $lead->customer->name }}</div>
                                    @if($lead->customer->vat_number)
                                        <div class="small text-muted">
                                            P.IVA {{ $lead->customer->vat_number }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted small">Nessun cliente</span>
                                @endif
                            </td>

                            {{-- Creato il --}}
                            <td>
                                <div>{{ $lead->created_at?->format('d/m/Y') }}</div>
                                <div class="small text-muted">
                                    {{ $lead->created_at?->format('H:i') }}
                                </div>
                            </td>

                            {{-- Azioni --}}
                            <td class="text-end">
                                <a href="{{ route('agent.crm.leads.edit', $lead) }}"
                                   class="btn btn-sm btn-outline-primary mb-1" title="Gestisci lead">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                {{-- niente elimina per gli agenti --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nessun lead assegnato a te.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($leads->hasPages())
            <div class="card-footer">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
@endsection
