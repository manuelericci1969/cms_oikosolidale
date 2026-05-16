@csrf

<div class="row mb-3">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Cliente *</label>
            <select name="customer_id" class="form-select" required>
                <option value="">-- Seleziona cliente --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                        {{ old('customer_id', $quote->customer_id) == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <label class="form-label">Data *</label>
            <input type="date" name="date" class="form-control"
                   value="{{ old('date', optional($quote->date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <label class="form-label">Valido fino al</label>
            <input type="date" name="valid_until" class="form-control"
                   value="{{ old('valid_until', optional($quote->valid_until)->format('Y-m-d')) }}">
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Testo introduttivo</label>
    <textarea name="intro_text" class="form-control" rows="3">{{ old('intro_text', $quote->intro_text) }}</textarea>
    <div class="form-text">
        Verrà mostrato all’inizio del preventivo (pagina, email, PDF). Puoi modificarlo caso per caso.
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Condizioni di pagamento</label>
    <textarea name="payment_terms" class="form-control" rows="3">{{ old('payment_terms', $quote->payment_terms) }}</textarea>
    <div class="form-text">
        Es: "30% all’ordine, 40% ad avanzamento lavori, 30% a saldo a 30gg. Pagamento tramite bonifico".
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Note interne</label>
    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $quote->notes) }}</textarea>
</div>

<hr>

<h5 class="mb-3">Righe preventivo</h5>

<div class="table-responsive mb-2">
    <table class="table align-middle" id="quote-items-table">
        <thead>
        <tr>
            <th style="width: 25%">Prodotto</th>
            <th>Descrizione</th>
            <th style="width: 8%">Q.tà</th>
            <th style="width: 10%">Unità</th>
            <th style="width: 12%">Prezzo</th>
            <th style="width: 12%">Sconto %</th>
            <th style="width: 10%">IVA %</th>
            <th style="width: 5%"></th>
        </tr>
        </thead>
        <tbody>
        @php
            $oldItems = old('items', $quote->items?->toArray() ?? []);
        @endphp

        @forelse($oldItems as $idx => $item)
            <tr>
                <td>
                    <select name="items[{{ $idx }}][product_id]" class="form-select js-product-select">
                        <option value="">-- libero --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                {{ ($item['product_id'] ?? null) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item['id'] ?? '' }}">
                    <input type="text" name="items[{{ $idx }}][description]" class="form-control"
                           value="{{ $item['description'] ?? '' }}" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[{{ $idx }}][quantity]" class="form-control"
                           value="{{ $item['quantity'] ?? 1 }}">
                </td>
                <td>
                    <input type="text" name="items[{{ $idx }}][unit]" class="form-control"
                           value="{{ $item['unit'] ?? 'pz' }}">
                </td>
                <td>
                    <input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" class="form-control"
                           value="{{ $item['unit_price'] ?? 0 }}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0"
                           name="items[{{ $idx }}][discount_percent]"
                           class="form-control js-discount-input"
                           value="{{ $item['discount_percent'] ?? 0 }}">
                    <small class="text-muted d-block mt-1 js-discount-hint" style="display:none;"></small>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[{{ $idx }}][tax_rate]" class="form-control"
                           value="{{ $item['tax_rate'] ?? 22 }}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td>
                    <select name="items[0][product_id]" class="form-select js-product-select">
                        <option value="">-- libero --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="items[0][description]" class="form-control" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[0][quantity]" class="form-control" value="1">
                </td>
                <td>
                    <input type="text" name="items[0][unit]" class="form-control" value="pz">
                </td>
                <td>
                    <input type="number" step="0.01" name="items[0][unit_price]" class="form-control" value="0">
                </td>
                <td>
                    <input type="number" step="0.01" min="0"
                           name="items[0][discount_percent]"
                           class="form-control js-discount-input"
                           value="0">
                    <small class="text-muted d-block mt-1 js-discount-hint" style="display:none;"></small>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[0][tax_rate]" class="form-control" value="22">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="btn-add-item">
    <i class="bi bi-plus-lg"></i> Aggiungi riga
</button>

<div class="text-end">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Salva preventivo
    </button>
    <a href="{{ route('agent.crm.quotes.index') }}" class="btn btn-secondary">
        Annulla
    </a>
</div>

{{-- ====== Mappa prodotti per JS (include max_discount e promo) ====== --}}
@php
    $productMap = collect($products)->mapWithKeys(function($p){
        return [
            $p->id => [
                'description'      => $p->description ?? $p->name,
                'unit'             => $p->unit ?? 'pz',
                'unit_price'       => $p->price ?? $p->unit_price ?? 0,
                'tax_rate'         => $p->tax_rate ?? 22,
                'max_discount'     => $p->max_discount, // float|null
                'is_promo'         => (bool) ($p->is_promo ?? false),
                'promo_expires_at' => optional($p->promo_expires_at)->format('Y-m-d'), // string|null
            ],
        ];
    });
@endphp

@push('scripts')
    <script>
        (function(){
            const tableBody = document.querySelector('#quote-items-table tbody');
            const btnAdd    = document.querySelector('#btn-add-item');
            const PRODUCTS  = @json($productMap);

            function parseIndexFromName(name) {
                const m = String(name || '').match(/^items\[(\d+)\]\[/);
                return m ? parseInt(m[1], 10) : null;
            }

            // Evita indici duplicati se elimini righe e poi ne aggiungi
            function getNextIndex() {
                let max = -1;
                tableBody.querySelectorAll('tr').forEach(tr => {
                    const any = tr.querySelector('select[name^="items["], input[name^="items["]');
                    if (!any) return;
                    const idx = parseIndexFromName(any.name);
                    if (idx !== null && !Number.isNaN(idx)) max = Math.max(max, idx);
                });
                return max + 1;
            }

            function formatDateIt(ymd) {
                if (!ymd) return '';
                const parts = ymd.split('-');
                if (parts.length !== 3) return ymd;
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }

            function todayYmd() {
                const d = new Date();
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            function isPromoActive(data) {
                if (!data || !data.is_promo) return false;
                if (!data.promo_expires_at) return true; // promo senza scadenza => attiva
                return data.promo_expires_at >= todayYmd();
            }

            function updateDiscountRules(row) {
                const selectEl   = row.querySelector('.js-product-select');
                const discountEl = row.querySelector('.js-discount-input');
                const hintEl     = row.querySelector('.js-discount-hint');
                if (!discountEl) return;

                const pid  = selectEl ? selectEl.value : '';
                const data = pid ? PRODUCTS[pid] : null;

                // reset
                discountEl.removeAttribute('max');
                if (hintEl) {
                    hintEl.style.display = 'none';
                    hintEl.textContent = '';
                }

                if (!data) return;

                const maxDisc = (data.max_discount === null || data.max_discount === undefined || data.max_discount === '')
                    ? null
                    : Number(data.max_discount);

                if (maxDisc !== null && !Number.isNaN(maxDisc)) {
                    discountEl.setAttribute('max', String(maxDisc));
                    const current = Number(discountEl.value || 0);
                    if (!Number.isNaN(current) && current > maxDisc) {
                        discountEl.value = maxDisc; // clamp
                    }
                }

                if (hintEl) {
                    const parts = [];
                    if (maxDisc !== null && !Number.isNaN(maxDisc)) parts.push(`Max sconto: ${maxDisc}%`);
                    else parts.push(`Max sconto: —`);

                    if (isPromoActive(data)) {
                        parts.push(data.promo_expires_at ? `Promo fino al ${formatDateIt(data.promo_expires_at)}` : 'Promo attiva');
                    } else if (data.is_promo && data.promo_expires_at) {
                        parts.push(`Promo scaduta (${formatDateIt(data.promo_expires_at)})`);
                    }

                    hintEl.textContent = parts.join(' · ');
                    hintEl.style.display = '';
                }
            }

            function onAddRow() {
                const index = getNextIndex();
                const tpl = `
<tr>
    <td>
        <select name="items[${index}][product_id]" class="form-select js-product-select">
            <option value="">-- libero --</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
                </select>
            </td>
            <td>
                <input type="text" name="items[${index}][description]" class="form-control" required>
    </td>
    <td>
        <input type="number" step="0.01" name="items[${index}][quantity]" class="form-control" value="1">
    </td>
    <td>
        <input type="text" name="items[${index}][unit]" class="form-control" value="pz">
    </td>
    <td>
        <input type="number" step="0.01" name="items[${index}][unit_price]" class="form-control" value="0">
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="items[${index}][discount_percent]" class="form-control js-discount-input" value="0">
        <small class="text-muted d-block mt-1 js-discount-hint" style="display:none;"></small>
    </td>
    <td>
        <input type="number" step="0.01" name="items[${index}][tax_rate]" class="form-control" value="22">
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
            <i class="bi bi-x-lg"></i>
        </button>
    </td>
</tr>
`;
                tableBody.insertAdjacentHTML('beforeend', tpl);
            }

            function onRemoveRow(e) {
                if (!e.target.closest('.btn-remove-row')) return;
                const row = e.target.closest('tr');
                if (row) row.remove();
            }

            function autofillRowFromProduct(selectEl) {
                const productId = selectEl.value;
                if (!productId) return;

                const data = PRODUCTS[productId];
                if (!data) return;

                const row = selectEl.closest('tr');
                if (!row) return;

                const descInput  = row.querySelector('input[name*="[description]"]');
                const unitInput  = row.querySelector('input[name*="[unit]"]');
                const priceInput = row.querySelector('input[name*="[unit_price]"]');
                const taxInput   = row.querySelector('input[name*="[tax_rate]"]');

                if (descInput && !descInput.value) descInput.value = data.description || '';
                if (unitInput && !unitInput.value) unitInput.value = data.unit || 'pz';
                if (priceInput && (!priceInput.value || Number(priceInput.value) === 0)) priceInput.value = data.unit_price ?? 0;
                if (taxInput && (!taxInput.value || Number(taxInput.value) === 0)) taxInput.value = data.tax_rate ?? 22;

                updateDiscountRules(row);
            }

            function onChange(e) {
                const selectEl = e.target.closest('.js-product-select');
                if (selectEl) autofillRowFromProduct(selectEl);
            }

            // Enforce max anche mentre scrivi/incolli
            function onInput(e) {
                const disc = e.target.closest('.js-discount-input');
                if (!disc) return;

                const row = disc.closest('tr');
                if (!row) return;

                const selectEl = row.querySelector('.js-product-select');
                const pid  = selectEl ? selectEl.value : '';
                const data = pid ? PRODUCTS[pid] : null;
                if (!data) return;

                const maxDisc = (data.max_discount === null || data.max_discount === undefined || data.max_discount === '')
                    ? null
                    : Number(data.max_discount);

                if (maxDisc !== null && !Number.isNaN(maxDisc)) {
                    const v = Number(disc.value || 0);
                    if (!Number.isNaN(v) && v > maxDisc) disc.value = maxDisc;
                }
            }

            if (btnAdd && tableBody) {
                btnAdd.addEventListener('click', onAddRow);
                tableBody.addEventListener('click', onRemoveRow);
                tableBody.addEventListener('change', onChange);
                tableBody.addEventListener('input', onInput);
            }

            // Applica regole al load
            tableBody.querySelectorAll('tr').forEach(tr => updateDiscountRules(tr));
        })();
    </script>
@endpush
