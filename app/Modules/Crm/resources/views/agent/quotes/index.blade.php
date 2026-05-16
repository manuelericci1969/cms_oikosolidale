@extends('admin.layout')

@section('title', 'I miei preventivi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">I miei preventivi</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- FILTRI --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('agent.crm.quotes.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Cerca</label>
                    <input type="text" name="q" class="form-control"
                           placeholder="Numero, oggetto…"
                           value="{{ $search ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Stato</label>
                    <input type="text" name="status" class="form-control"
                           placeholder="Es. draft, sent…"
                           value="{{ $status ?? '' }}">
                </div>

                <div class="col-md-2 text-md-end mt-2 mt-md-0">
                    <button class="btn btn-outline-secondary w-100 mb-1" title="Applica filtri">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('agent.crm.quotes.index') }}"
                       class="btn btn-outline-secondary w-100" title="Azzera filtri">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <a href="{{ route('agent.crm.quotes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Nuovo preventivo
    </a>


    {{-- TABELLA --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>Numero</th>
                        <th>Oggetto</th>
                        <th>Cliente</th>
                        <th>Lead</th>
                        <th>Stato</th>
                        <th>Creato il</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($quotes as $quote)
                        <tr>
                            <td>{{ $quote->number ?? $quote->id }}</td>
                            <td>{{ $quote->subject }}</td>
                            <td>{{ $quote->customer?->name ?? 'N/D' }}</td>
                            <td>{{ $quote->lead?->name ?? 'N/D' }}</td>
                            <td>{{ $quote->status }}</td>
                            <td>{{ $quote->created_at?->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('agent.crm.quotes.edit', $quote) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('agent.crm.quotes.pdf', $quote) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nessun preventivo trovato.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($quotes->hasPages())
            <div class="card-footer">
                {{ $quotes->links() }}
            </div>
        @endif
    </div>
@endsection
