@php use Illuminate\Support\Facades\Route; @endphp
@extends('admin.layout')

@section('title', 'Leads CRM')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h1 class="h3 mb-0">
            Leads
            <small class="text-muted">({{ $leads->total() }})</small>
        </h1>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.crm.whatsapp.index') }}" class="btn btn-outline-success">
                <i class="bi bi-whatsapp"></i> Messaggi WhatsApp
            </a>

            <a href="{{ route('admin.crm.leads.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuovo lead
            </a>
        </div>

    </div>


    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif


    <div class="card mb-3">
        <div class="card-body">

            <form method="GET" action="{{ route('admin.crm.leads.index') }}" class="row g-2 align-items-end">

                <div class="col-md-5">
                    <label class="form-label mb-1">Cerca</label>

                    <input type="text"
                           name="q"
                           class="form-control"
                           placeholder="Nome, email, telefono..."
                           value="{{ $search ?? '' }}">
                </div>


                <div class="col-md-3">

                    <label class="form-label mb-1">Stato lead</label>

                    <select name="status" class="form-select">

                        <option value="">Tutti</option>

                        @foreach($statusOptions as $value => $label)

                            <option value="{{ $value }}"
                                @selected(($status ?? '') === $value)>
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-2 text-md-end">

                    <button class="btn btn-outline-secondary w-100 mb-1">
                        <i class="bi bi-search"></i>
                    </button>

                    <a href="{{ route('admin.crm.leads.index') }}"
                       class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg"></i>
                    </a>

                </div>

            </form>

        </div>
    </div>


    <div class="card">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table mb-0 align-middle">

                    <thead class="table-light">

                    <tr>

                        <th>Lead</th>

                        <th>Contatti</th>

                        <th>Origine</th>

                        <th>Workflow</th>

                        <th>Creato</th>

                        <th class="text-end">Azioni</th>

                    </tr>

                    </thead>


                    <tbody>

                    @forelse($leads as $lead)

                        <tr>

                            <td>

                                <div class="fw-semibold">

                                    {{ $lead->name ?: 'Senza nome' }}

                                </div>

                                <span class="badge {{ $lead->status_badge_class ?? 'bg-secondary' }}">
                                    {{ $lead->status_label ?? $lead->status }}
                                </span>

                                @if($lead->subject)

                                    <div class="small text-muted">
                                        {{ $lead->subject }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                @if($lead->email)

                                    <div>
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:{{ $lead->email }}">
                                            {{ $lead->email }}
                                        </a>
                                    </div>

                                @endif


                                @if($lead->phone)

                                    <div>
                                        <i class="bi bi-telephone"></i>
                                        {{ $lead->phone }}
                                    </div>
                                @endif

                            </td>


                            <td>

                                <div>

                                    <span class="small text-muted">Fonte</span>

                                    <div>

                                        {{ $lead->source_label ?: '—' }}

                                    </div>

                                </div>


                                <div class="mt-1">

                                    <span class="small text-muted">
                                        Come ci ha trovato
                                    </span>

                                    <div>

                                        {{ $lead->how_found_full_label ?: '—' }}

                                    </div>

                                </div>

                            </td>


                            <td>

                                <div>

                                    <span class="small text-muted">
                                        Assegnato
                                    </span>

                                    <div>

                                        {{ $lead->owner?->name ?? 'Non assegnato' }}

                                    </div>

                                </div>


                                <div class="mt-1">

                                    <span class="small text-muted">
                                        Prossima azione
                                    </span>

                                    <div>

                                        {{ $lead->next_action_at?->format('d/m/Y H:i') ?? '—' }}

                                    </div>

                                </div>

                            </td>


                            <td>

                                <div>

                                    {{ $lead->created_at?->format('d/m/Y') }}

                                </div>

                                <div class="small text-muted">

                                    {{ $lead->created_at?->format('H:i') }}

                                </div>

                            </td>


                            <td class="text-end">
                                @if($lead->phone)
                                    <a href="{{ route('admin.crm.whatsapp.create', ['lead_id' => $lead->id]) }}"
                                       class="btn btn-sm btn-outline-success"
                                       title="Invia WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                @endif

                                <a href="{{ route('admin.crm.leads.edit', $lead) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('admin.crm.leads.destroy', $lead) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Eliminare questo lead?');">

                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="text-center text-muted py-4">

                                Nessun lead registrato

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
