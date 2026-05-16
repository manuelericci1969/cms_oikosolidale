@csrf

<input type="hidden" name="unknown_question_id" value="{{ old('unknown_question_id', $unknownQuestionId ?? '') }}">

<div class="row">
    <div class="col-md-8">
        <div class="mb-3">
            <label class="form-label">Domanda / pattern *</label>
            <input
                type="text"
                name="question_pattern"
                class="form-control @error('question_pattern') is-invalid @enderror"
                value="{{ old('question_pattern', $faq->question_pattern) }}"
                required
                placeholder="Es. hmfluxus, prezzo hmfluxus, ble key"
            >
            @error('question_pattern')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
                Inserisci la frase principale che identifica questa FAQ.
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Parole chiave</label>
            <textarea
                name="keywords"
                rows="3"
                class="form-control @error('keywords') is-invalid @enderror"
                placeholder="Es. hmfluxus, flussimetro, monitoraggio acqua"
            >{{ old('keywords', $faq->keywords) }}</textarea>
            @error('keywords')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
                Separa le keyword con virgole, punto e virgola o una per riga.
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Risposta *</label>
            <textarea
                name="answer"
                rows="8"
                class="form-control @error('answer') is-invalid @enderror"
                required
                placeholder="Scrivi qui la risposta ufficiale che il chatbot deve usare"
            >{{ old('answer', $faq->answer) }}</textarea>
            @error('answer')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Intent</label>
            <input
                type="text"
                name="intent"
                class="form-control @error('intent') is-invalid @enderror"
                value="{{ old('intent', $faq->intent) }}"
                placeholder="Es. iot, pricing, crm"
            >
            @error('intent')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Prodotto collegato</label>
            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                <option value="">-- Nessuno --</option>
                @foreach($productOptions as $product)
                    <option value="{{ $product->id }}"
                        {{ (string) old('product_id', $faq->product_id) === (string) $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
            @error('product_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Priorità</label>
            <input
                type="number"
                name="priority"
                min="0"
                max="999999"
                class="form-control @error('priority') is-invalid @enderror"
                value="{{ old('priority', $faq->priority ?? 100) }}"
            >
            @error('priority')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
                Più basso = più importante.
            </div>
        </div>

        <div class="form-check form-switch mb-3">
            <input
                class="form-check-input"
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}
            >
            <label class="form-check-label" for="is_active">FAQ attiva</label>
        </div>

        @if(!empty($faq->id))
            <div class="card bg-light border-0">
                <div class="card-body py-3">
                    <div><strong>ID:</strong> {{ $faq->id }}</div>
                    <div><strong>Utilizzi:</strong> {{ (int) $faq->times_used }}</div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Salva
    </button>

    <a href="{{ route('admin.crm.chatbot-faqs.index') }}" class="btn btn-secondary">
        Annulla
    </a>
</div>
