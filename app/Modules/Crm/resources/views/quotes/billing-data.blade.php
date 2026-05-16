@extends('admin.layout')

@section('title', 'Modifica dati emittente preventivo '.$quote->number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Modifica dati emittente</h1>
            <div class="text-muted small">
                Preventivo {{ $quote->number }} · Cliente: {{ $quote->customer?->name }}
                @if($quote->status === 'accepted')
                    · <span class="badge bg-success">Accettato</span>
                @else
                    · <span class="badge bg-secondary">{{ ucfirst($quote->status) }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.crm.quotes.show', $quote) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna al preventivo
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-warning">
        Questa modifica aggiorna solo il soggetto emittente, P.IVA snapshot e coordinate bancarie del preventivo/contratto.
        Non modifica cliente, importi, righe, condizioni economiche o pagamenti.
    </div>

    <form method="POST" action="{{ route('admin.crm.quotes.billing-data.update', $quote) }}" class="card card-body">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Soggetto emittente / P.IVA</label>
            <select name="billing_profile_id" class="form-select" id="billing_profile_id">
                <option value="">Usa dati azienda globali</option>
                @foreach($billingProfiles as $profile)
                    <option value="{{ $profile->id }}"
                            data-bank-details="{{ e($profile->bank_details) }}"
                        {{ old('billing_profile_id', $quote->billing_profile_id) == $profile->id ? 'selected' : '' }}>
                        {{ $profile->legal_name ?: $profile->name }} @if($profile->vat) — P.IVA {{ $profile->vat }} @endif
                        @unless($profile->is_active) — DISATTIVATO @endunless
                    </option>
                @endforeach
            </select>
            <div class="form-text">
                Al salvataggio viene aggiornato lo snapshot fiscale del preventivo, così il PDF userà questi dati.
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Coordinate bancarie da usare nel contratto</label>
            <textarea name="bank_details" class="form-control" rows="5" id="bank_details">{{ old('bank_details', $quote->bank_details) }}</textarea>
            <div class="form-text">
                Puoi modificare il conto specifico usato da questo preventivo/contratto.
            </div>
        </div>

        @if($quote->billing_profile_snapshot)
            <div class="mb-3">
                <label class="form-label">Snapshot attualmente salvato</label>
                <pre class="bg-light border rounded p-3 small mb-0">{{ json_encode($quote->billing_profile_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        @if($quote->status === 'accepted')
            <div class="form-check form-switch mb-3">
                <input type="checkbox" class="form-check-input" id="regenerate_contract" name="regenerate_contract" value="1" checked>
                <label class="form-check-label" for="regenerate_contract">
                    Rigenera subito il contratto PDF con i nuovi dati
                </label>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.crm.quotes.show', $quote) }}" class="btn btn-outline-secondary">Annulla</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Salva dati emittente
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const profileSelect = document.querySelector('#billing_profile_id');
            const bankTextarea = document.querySelector('#bank_details');

            if (!profileSelect || !bankTextarea) return;

            profileSelect.addEventListener('change', function () {
                const option = profileSelect.options[profileSelect.selectedIndex];
                const bankDetails = option ? option.getAttribute('data-bank-details') : '';

                if (bankDetails) {
                    bankTextarea.value = bankDetails;
                }
            });
        })();
    </script>
@endpush
