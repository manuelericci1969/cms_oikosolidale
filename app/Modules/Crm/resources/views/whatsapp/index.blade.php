@extends('admin.layout')

@section('title', 'Messaggi WhatsApp CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Messaggi WhatsApp</h1>
        <a href="{{ route('admin.crm.whatsapp.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuovo messaggio
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="get" action="{{ route('admin.crm.whatsapp.index') }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-8">
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       class="form-control"
                       placeholder="Cerca per nome, numero o testo messaggio">
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Tutti gli stati --</option>
                    @foreach($statuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected(request('status') === $statusKey)>
                            {{ $statusLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width: 150px;">Data</th>
                        <th>Destinatario</th>
                        <th style="width: 170px;">Numero</th>
                        <th>Messaggio</th>
                        <th style="width: 180px;">Collegamento</th>
                        <th style="width: 120px;">Utente</th>
                        <th style="width: 120px;">Stato</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($messages as $row)
                        <tr>
                            <td>
                                <div>{{ $row->created_at?->format('d/m/Y') }}</div>
                                <div class="text-muted small">{{ $row->created_at?->format('H:i') }}</div>
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $row->recipient_name ?: '—' }}</div>

                                @if($row->sent_at)
                                    <div class="small text-muted">
                                        Inviato: {{ $row->sent_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="small">{{ $row->recipient_phone }}</span>

                                @if($row->normalized_phone && $row->normalized_phone !== $row->recipient_phone)
                                    <div class="small text-muted">{{ $row->normalized_phone }}</div>
                                @endif
                            </td>

                            <td style="max-width: 420px;">
                                <div>{{ \Illuminate\Support\Str::limit($row->message, 140) }}</div>

                                @if($row->error_message)
                                    <div class="small text-danger mt-1">
                                        {{ $row->error_message }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($row->lead)
                                    <a href="{{ route('admin.crm.leads.edit', $row->lead) }}"
                                       class="text-decoration-none">
                                        Lead #{{ $row->lead->id }}
                                    </a>
                                @elseif($row->customer)
                                    <a href="{{ route('admin.crm.customers.edit', $row->customer) }}"
                                       class="text-decoration-none">
                                        Cliente #{{ $row->customer->id }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                {{ $row->user?->name ?: '—' }}
                            </td>

                            <td>
                                <span class="badge {{ $row->status_badge_class }}">
                                    {{ $row->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nessun messaggio WhatsApp trovato.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($messages->hasPages())
            <div class="card-footer">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
@endsection
