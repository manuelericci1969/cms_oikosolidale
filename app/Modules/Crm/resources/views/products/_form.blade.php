@csrf

<div class="row">
    <div class="col-md-8">
        <div class="mb-3">
            <label class="form-label">Nome *</label>
            <input
                type="text"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $product->name) }}"
                required
            >
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Descrizione</label>
            <textarea
                name="description"
                rows="4"
                class="form-control @error('description') is-invalid @enderror"
            >{{ old('description', $product->description) }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">URL pagina prodotto</label>
            <input
                type="url"
                name="website_url"
                class="form-control @error('website_url') is-invalid @enderror"
                value="{{ old('website_url', $product->website_url) }}"
                placeholder="https://www.r4software.it/prodotto"
            >
            <div class="form-text">
                Inserisci il link completo della pagina prodotto sul sito.
            </div>
            @error('website_url')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Codice (SKU)</label>
            <input
                type="text"
                name="sku"
                class="form-control @error('sku') is-invalid @enderror"
                value="{{ old('sku', $product->sku) }}"
            >
            @error('sku')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Unità</label>
            <input
                type="text"
                name="unit"
                class="form-control @error('unit') is-invalid @enderror"
                value="{{ old('unit', $product->unit ?? 'pz') }}"
            >
            @error('unit')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Prezzo</label>
            <div class="input-group">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    class="form-control @error('price') is-invalid @enderror"
                    value="{{ old('price', $product->price ?? 0) }}"
                    required
                >
                <span class="input-group-text">€</span>
                @error('price')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">IVA %</label>
            <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="tax_rate"
                class="form-control @error('tax_rate') is-invalid @enderror"
                value="{{ old('tax_rate', $product->tax_rate ?? 22) }}"
                required
            >
            @error('tax_rate')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Sconto massimo (%)</label>
            <div class="input-group">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    name="max_discount"
                    class="form-control @error('max_discount') is-invalid @enderror"
                    value="{{ old('max_discount', $product->max_discount) }}"
                >
                <span class="input-group-text">%</span>
                @error('max_discount')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-check form-switch mb-2">
            <input
                class="form-check-input"
                type="checkbox"
                id="is_promo"
                name="is_promo"
                value="1"
                {{ old('is_promo', $product->is_promo ?? false) ? 'checked' : '' }}
            >
            <label class="form-check-label" for="is_promo">Prodotto in promozione</label>
        </div>

        <div class="mb-3" id="promoExpiresWrap" style="display:none;">
            <label class="form-label">Scadenza promozione</label>
            <input
                type="date"
                name="promo_expires_at"
                class="form-control @error('promo_expires_at') is-invalid @enderror"
                value="{{ old('promo_expires_at', optional($product->promo_expires_at)->format('Y-m-d')) }}"
            >
            <div class="form-text">Obbligatoria se il prodotto è in promozione.</div>
            @error('promo_expires_at')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <script>
            (function () {
                const promo = document.getElementById('is_promo');
                const wrap  = document.getElementById('promoExpiresWrap');
                if (!promo || !wrap) return;

                const sync = () => {
                    wrap.style.display = promo.checked ? '' : 'none';
                    if (!promo.checked) {
                        const input = wrap.querySelector('input[name="promo_expires_at"]');
                        if (input) input.value = '';
                    }
                };

                promo.addEventListener('change', sync);
                sync();
            })();
        </script>

        <div class="form-check form-switch mb-3">
            <input
                class="form-check-input"
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}
            >
            <label class="form-check-label" for="is_active">Prodotto attivo</label>
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Salva
    </button>
    <a href="{{ route('admin.crm.products.index') }}" class="btn btn-secondary">
        Annulla
    </a>
</div>
