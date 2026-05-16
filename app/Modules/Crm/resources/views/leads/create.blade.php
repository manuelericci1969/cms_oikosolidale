{{-- modules/Crm/resources/views/leads/create.blade.php --}}
@extends('admin.layout')

@section('title', 'Nuovo Lead')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Nuovo lead</h1>

        <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Torna ai lead
        </a>
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

    <form method="POST" action="{{ route('admin.crm.leads.store') }}" id="lead-create-form">
        @csrf

        <div class="row">
            {{-- Colonna sinistra: dati lead --}}
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">Dettagli lead</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}">
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}">
                                @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Oggetto (es. Sito / App / Campagna social)</label>
                            <input type="text" name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}"
                                   placeholder="Es. Sito vetrina per farmacia, Campagna Instagram, App prenotazioni…">
                            @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Messaggio / richiesta</label>
                            <textarea name="message" rows="5"
                                      class="form-control @error('message') is-invalid @enderror"
                                      placeholder="Scrivi cosa ti ha detto a voce e cosa vorrebbe sviluppare…">{{ old('message') }}</textarea>
                            @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Note interne</label>
                            <textarea name="internal_notes" rows="3"
                                      class="form-control @error('internal_notes') is-invalid @enderror"
                                      placeholder="Dettagli extra, budget indicativo, urgenza, vincoli…">{{ old('internal_notes') }}</textarea>
                            @error('internal_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonna destra: workflow --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Stato e follow-up</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Stato lead</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old('status', 'contacted') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Consiglio: <strong>Contattato</strong> (perché vi siete già sentiti a voce).
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assegnato a</label>
                            <select name="owner_id" class="form-select @error('owner_id') is-invalid @enderror">
                                <option value="">-- Nessun assegnatario --</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}"
                                        @selected(old('owner_id') == $owner->id)>
                                        {{ $owner->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('owner_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cliente collegato (se già cliente)</label>
                            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">-- Nessun cliente --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="mb-0">
                            <label class="form-label">Prossima azione (richiamare)</label>
                            <input type="datetime-local"
                                   name="next_action_at"
                                   class="form-control @error('next_action_at') is-invalid @enderror"
                                   value="{{ old('next_action_at', optional($defaultNextAction)->format('Y-m-d\TH:i')) }}">
                            @error('next_action_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Imposta quando ricontattarli “dopo le feste”.
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-save"></i> Crea lead
                </button>
            </div>
        </div>
    </form>
@endsection
