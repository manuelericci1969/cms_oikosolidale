@php
    $selectedListId = old('list_id', data_get($campaign->filters ?? [], 'list_id'));
    $selectedMaxAttempts = old('max_attempts', data_get($campaign->settings ?? [], 'max_attempts', 3));
    $selectedTimeoutSecs = old('timeout_secs', data_get($campaign->settings ?? [], 'timeout_secs', 30));

    $selectedProvider = old('provider', $campaign->provider ?: 'telnyx');
    $selectedStatus = old('status', $campaign->status ?: 'draft');
    $isActive = old('is_active', $campaign->is_active);

    $statusHelp = match ($selectedStatus) {
        'draft' => 'La campagna è in bozza e non verrà eseguita automaticamente.',
        'active' => 'La campagna è pronta per essere eseguita dai job automatici.',
        'paused' => 'La campagna è temporaneamente sospesa.',
        'completed' => 'La campagna è considerata conclusa.',
        'archived' => 'La campagna è archiviata e conservata solo come storico.',
        default => 'Configura lo stato operativo della campagna.',
    };
@endphp

<style>
    .call-form-card {
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
    }

    .call-form-help {
        font-size: .85rem;
        color: #6c757d;
    }

    .call-form-aside-box {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: .5rem;
        padding: .85rem;
        background: #f8f9fa;
    }

    .call-form-mini-label {
        font-size: .8rem;
        color: #6c757d;
        margin-bottom: .2rem;
    }

    .call-form-mini-value {
        font-weight: 600;
    }
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card call-form-card mb-3">
            <div class="card-header">Dettagli campagna chiamate</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nome campagna *</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name', $campaign->name) }}"
                        required
                    >
                    <div class="form-text">
                        Usa un nome interno chiaro, ad esempio: <em>Test interni Lista 2</em>.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrizione</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="4"
                        placeholder="Descrizione interna, obiettivo o note operative della campagna..."
                    >{{ old('description', $campaign->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Lista email di origine *</label>
                    <select name="list_id" class="form-select" required>
                        <option value="">-- Seleziona lista --</option>
                        @foreach($emailLists as $list)
                            <option value="{{ $list->id }}" {{ (string) $selectedListId === (string) $list->id ? 'selected' : '' }}>
                                {{ $list->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        I numeri telefonici verranno presi dai contatti della lista selezionata, se presenti e validi.
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label">Script prompt</label>
                    <textarea
                        name="script_prompt"
                        class="form-control"
                        rows="7"
                        placeholder="Prompt o istruzioni operative per la campagna..."
                    >{{ old('script_prompt', $campaign->script_prompt) }}</textarea>
                    <div class="form-text">
                        Campo utile per definire contesto, tono, obiettivo della chiamata e future integrazioni AI/OpenClaw.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card call-form-card mb-3">
            <div class="card-header">Configurazione</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Provider *</label>
                    <select name="provider" class="form-select" required>
                        <option value="telnyx" {{ $selectedProvider === 'telnyx' ? 'selected' : '' }}>
                            Telnyx
                        </option>
                    </select>
                </div>

                <input type="hidden" name="source_mode" value="email_list_contacts">

                <div class="mb-3">
                    <label class="form-label">Max tentativi *</label>
                    <input
                        type="number"
                        name="max_attempts"
                        class="form-control"
                        min="1"
                        max="10"
                        value="{{ $selectedMaxAttempts }}"
                        required
                    >
                    <div class="form-text">
                        Numero massimo di tentativi per ciascun contatto.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Timeout chiamata (secondi) *</label>
                    <input
                        type="number"
                        name="timeout_secs"
                        class="form-control"
                        min="10"
                        max="120"
                        value="{{ $selectedTimeoutSecs }}"
                        required
                    >
                    <div class="form-text">
                        Durata massima attesa prima che la chiamata venga considerata senza risposta.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Stato *</label>
                    <select name="status" class="form-select" required>
                        <option value="draft" {{ $selectedStatus === 'draft' ? 'selected' : '' }}>Bozza</option>
                        <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Attiva</option>
                        <option value="paused" {{ $selectedStatus === 'paused' ? 'selected' : '' }}>In pausa</option>
                        <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completata</option>
                        <option value="archived" {{ $selectedStatus === 'archived' ? 'selected' : '' }}>Archiviata</option>
                    </select>
                    <div class="form-text">
                        {{ $statusHelp }}
                    </div>
                </div>

                <div class="form-check mb-0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_active"
                        value="1"
                        id="is_active"
                        {{ $isActive ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="is_active">
                        Campagna attiva
                    </label>
                </div>
            </div>
        </div>

        <div class="card call-form-card mb-3">
            <div class="card-header">Riepilogo rapido</div>
            <div class="card-body">
                <div class="call-form-aside-box mb-3">
                    <div class="call-form-mini-label">Sorgente</div>
                    <div class="call-form-mini-value">Contatti da liste email</div>
                </div>

                <div class="call-form-aside-box mb-3">
                    <div class="call-form-mini-label">Lista selezionata</div>
                    <div class="call-form-mini-value">
                        @if($selectedListId)
                            {{ collect($emailLists)->firstWhere('id', (int) $selectedListId)?->name ?? ('Lista #' . $selectedListId) }}
                        @else
                            Nessuna lista selezionata
                        @endif
                    </div>
                </div>

                <div class="call-form-aside-box mb-3">
                    <div class="call-form-mini-label">Provider</div>
                    <div class="call-form-mini-value">{{ strtoupper($selectedProvider) }}</div>
                </div>

                <div class="call-form-aside-box">
                    <div class="call-form-mini-label">Configurazione attuale</div>
                    <div class="small">
                        <div><strong>Tentativi:</strong> {{ $selectedMaxAttempts }}</div>
                        <div><strong>Timeout:</strong> {{ $selectedTimeoutSecs }} secondi</div>
                        <div><strong>Stato:</strong> {{ ucfirst($selectedStatus) }}</div>
                        <div><strong>Attiva:</strong> {{ $isActive ? 'Sì' : 'No' }}</div>
                    </div>
                </div>

                <div class="call-form-help mt-3">
                    Per far partire automaticamente le chiamate, la campagna deve essere coerente con:
                    <strong>stato attivo</strong> e <strong>flag campagna attiva</strong>.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-end">
    <button class="btn btn-primary">
        <i class="bi bi-save"></i> Salva campagna chiamate
    </button>
</div>
