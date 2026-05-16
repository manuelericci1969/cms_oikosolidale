@extends('admin.layout')

@section('title', 'Lead: ' . $lead->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Lead: {{ $lead->name }}</h1>

        <div class="d-flex gap-2">
            @if(!$lead->customer_id)
                <form action="{{ route('admin.crm.leads.convert', $lead) }}"
                      method="POST"
                      onsubmit="return confirm('Creare un nuovo cliente da questo lead?');">
                    @csrf
                    <button class="btn btn-success btn-sm">
                        <i class="bi bi-person-plus"></i> Converti in cliente
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Torna ai lead
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Attenzione:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.crm.leads.update', $lead) }}" id="lead-form">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">Dettagli lead</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $lead->name) }}"
                                   required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email"
                                       name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $lead->email) }}">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text"
                                       name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $lead->phone) }}">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Oggetto</label>
                            <input type="text"
                                   name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject', $lead->subject) }}">
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Messaggio</label>
                            <textarea name="message"
                                      rows="5"
                                      class="form-control @error('message') is-invalid @enderror">{{ old('message', $lead->message) }}</textarea>
                            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note interne</label>
                            <textarea name="internal_notes"
                                      rows="4"
                                      class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $lead->internal_notes) }}</textarea>
                            @error('internal_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Origine e consensi</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fonte</label>
                                <input type="text"
                                       name="source"
                                       class="form-control @error('source') is-invalid @enderror"
                                       value="{{ old('source', $lead->source) }}">
                                @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Come ci ha trovato</label>
                                <select name="how_found"
                                        id="how_found"
                                        class="form-select @error('how_found') is-invalid @enderror">
                                    <option value="">-- Seleziona --</option>
                                    @foreach($howFoundOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('how_found', $lead->how_found) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('how_found') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3" id="how_found_other_wrapper" style="{{ old('how_found', $lead->how_found) === 'altro' ? '' : 'display:none;' }}">
                            <label class="form-label">Altro (specifica)</label>
                            <input type="text"
                                   name="how_found_other"
                                   class="form-control @error('how_found_other') is-invalid @enderror"
                                   value="{{ old('how_found_other', $lead->how_found_other) }}">
                            @error('how_found_other') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" disabled {{ $lead->gdpr_consense ? 'checked' : '' }}>
                                    <label class="form-check-label">Consenso privacy</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" disabled {{ $lead->marketing_consense ? 'checked' : '' }}>
                                    <label class="form-check-label">Consenso marketing</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Stato e workflow</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Stato lead</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $lead->status) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assegnato a</label>
                            <select name="owner_id" class="form-select @error('owner_id') is-invalid @enderror">
                                <option value="">-- Nessun assegnatario --</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected(old('owner_id', $lead->owner_id) == $owner->id)>
                                        {{ $owner->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('owner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cliente collegato</label>
                            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">-- Nessun cliente --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $lead->customer_id) == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Ultimo contatto</label>
                            <input type="datetime-local"
                                   name="last_contact_at"
                                   class="form-control @error('last_contact_at') is-invalid @enderror"
                                   value="{{ old('last_contact_at', optional($lead->last_contact_at)->format('Y-m-d\TH:i')) }}">
                            @error('last_contact_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Prossima azione</label>
                            <input type="datetime-local"
                                   name="next_action_at"
                                   class="form-control @error('next_action_at') is-invalid @enderror"
                                   value="{{ old('next_action_at', optional($lead->next_action_at)->format('Y-m-d\TH:i')) }}">
                            @error('next_action_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data chiusura</label>
                            <input type="datetime-local"
                                   name="closed_at"
                                   class="form-control @error('closed_at') is-invalid @enderror"
                                   value="{{ old('closed_at', optional($lead->closed_at)->format('Y-m-d\TH:i')) }}">
                            @error('closed_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Motivo chiusura</label>
                            <input type="text"
                                   name="closed_reason"
                                   class="form-control @error('closed_reason') is-invalid @enderror"
                                   value="{{ old('closed_reason', $lead->closed_reason) }}">
                            @error('closed_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr>

                        <p class="mb-1">
                            <strong>Creato il:</strong><br>
                            {{ $lead->created_at?->format('d/m/Y H:i') }}
                        </p>

                        @if($lead->updated_at)
                            <p class="mb-0">
                                <strong>Ultimo aggiornamento:</strong><br>
                                {{ $lead->updated_at->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Cronologia contatti</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.leads.activities.store', $lead) }}" class="mb-4">
                        @csrf

                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="call">Telefonata</option>
                                    <option value="email">Email</option>
                                    <option value="meeting">Incontro</option>
                                    <option value="note">Nota</option>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Oggetto</label>
                                <input type="text"
                                       name="subject"
                                       class="form-control form-control-sm"
                                       placeholder="Es. Prima chiamata, follow-up, preventivo inviato…">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Data contatto</label>
                                <input type="datetime-local"
                                       name="contacted_at"
                                       class="form-control form-control-sm"
                                       value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-md-8">
                                <label class="form-label">Dettagli</label>
                                <textarea name="body"
                                          rows="2"
                                          class="form-control form-control-sm"
                                          placeholder="Cosa si sono detti, obiezioni, interessi…"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Esito</label>
                                <input type="text"
                                       name="outcome"
                                       class="form-control form-control-sm"
                                       placeholder="Es. Interessato, da richiamare, non interessato…">

                                <label class="form-label mt-2">Prossima azione</label>
                                <input type="datetime-local"
                                       name="next_action_at"
                                       class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i> Aggiungi attività
                            </button>
                        </div>
                    </form>

                    <h6 class="mb-2">Storico</h6>

                    <ul class="list-group list-group-flush small">
                        @forelse($lead->activities as $activity)
                            <li class="list-group-item" id="activity-{{ $activity->id }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fw-semibold">
                                            @php
                                                $labelType = match($activity->type) {
                                                    'call'    => 'Telefonata',
                                                    'email'   => 'Email',
                                                    'meeting' => 'Incontro',
                                                    'note'    => 'Nota',
                                                    default   => ucfirst($activity->type ?? 'Attività'),
                                                };
                                            @endphp
                                            {{ $labelType }}
                                            @if($activity->subject)
                                                — {{ $activity->subject }}
                                            @endif
                                        </div>

                                        @if($activity->body)
                                            <div class="text-muted mt-1">
                                                {!! nl2br(e($activity->body)) !!}
                                            </div>
                                        @endif

                                        @if($activity->outcome)
                                            <div class="mt-1">
                                                <span class="badge bg-light text-muted border">
                                                    Esito: {{ $activity->outcome }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="text-end">
                                        <div>{{ $activity->contacted_at?->format('d/m/Y H:i') }}</div>
                                        @if($activity->user)
                                            <div class="small text-muted">di {{ $activity->user->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted small">
                                Nessuna attività registrata per questo lead.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <form action="{{ route('admin.crm.leads.destroy', $lead) }}"
              method="POST"
              onsubmit="return confirm('Eliminare definitivamente questo lead?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger">
                <i class="bi bi-trash"></i> Elimina
            </button>
        </form>

        <button type="submit" form="lead-form" class="btn btn-primary">
            <i class="bi bi-save"></i> Salva modifiche
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const howFound = document.getElementById('how_found');
            const wrapper = document.getElementById('how_found_other_wrapper');

            function toggleOtherField() {
                if (!howFound || !wrapper) return;
                wrapper.style.display = howFound.value === 'altro' ? '' : 'none';
            }

            if (howFound) {
                howFound.addEventListener('change', toggleOtherField);
                toggleOtherField();
            }
        });
    </script>
@endsection
