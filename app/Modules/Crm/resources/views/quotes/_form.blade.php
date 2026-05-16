@csrf

@php
    $paymentType = old('payment_type', $quote->payment_type ?? 'free_text');
    $schedule = old('payment_schedule', $quote->payment_schedule ?? []);
    $deposit = data_get($schedule, 'deposit', []);
    $installments = data_get($schedule, 'installments', []);

    if (empty($installments)) {
        $installments = [
            ['label' => 'Rata 1', 'due_date' => '', 'amount' => ''],
        ];
    }
@endphp

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

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Soggetto emittente / P.IVA</label>
        <select name="billing_profile_id" class="form-select" id="billing_profile_id">
            <option value="">Usa dati azienda globali</option>
            @foreach(($billingProfiles ?? collect()) as $profile)
                <option value="{{ $profile->id }}"
                        data-bank-details="{{ e($profile->bank_details) }}"
                    {{ old('billing_profile_id', $quote->billing_profile_id) == $profile->id ? 'selected' : '' }}>
                    {{ $profile->legal_name ?: $profile->name }} @if($profile->vat) — P.IVA {{ $profile->vat }} @endif
                </option>
            @endforeach
        </select>
        <div class="form-text">
            Scegli se emettere il preventivo come consulente o come società. Il dato viene congelato sul preventivo/contratto.
        </div>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <a href="{{ route('admin.crm.billing-profiles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-buildings"></i> Gestisci profili di fatturazione
        </a>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Testo introduttivo</label>
    <textarea name="intro_text" class="form-control" rows="3">{{ old('intro_text', $quote->intro_text) }}</textarea>
    <div class="form-text">
        Verrà mostrato all’inizio del preventivo (pagina, email, PDF). Puoi modificarlo caso per caso.
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Forma di pagamento</strong>
        <span class="badge bg-light text-dark">Preventivo e PDF</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Modalità *</label>
                <select name="payment_type" id="payment_type" class="form-select">
                    <option value="free_text" {{ $paymentType === 'free_text' ? 'selected' : '' }}>Testo libero / standard</option>
                    <option value="structured" {{ $paymentType === 'structured' ? 'selected' : '' }}>Acconto alla firma + rate</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Condizioni / note di pagamento</label>
                <textarea name="payment_terms" class="form-control" rows="3">{{ old('payment_terms', $quote->payment_terms) }}</textarea>
                <div class="form-text">
                    Puoi usare questo campo come testo libero oppure come nota aggiuntiva al piano rateale.
                </div>
            </div>
        </div>

        <div id="payment_schedule_box" class="border rounded p-3 bg-light {{ $paymentType === 'structured' ? '' : 'd-none' }}">
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="payment_schedule[deposit][enabled]" value="0">
                <input class="form-check-input" type="checkbox" value="1" id="deposit_enabled"
                       name="payment_schedule[deposit][enabled]"
                    {{ old('payment_schedule.deposit.enabled', data_get($deposit, 'enabled')) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="deposit_enabled">Prevedi acconto alla firma</label>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Descrizione acconto</label>
                    <input type="text" class="form-control" name="payment_schedule[deposit][label]"
                           value="{{ old('payment_schedule.deposit.label', data_get($deposit, 'label', 'Acconto alla firma')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Scadenza acconto</label>
                    <input type="date" class="form-control" name="payment_schedule[deposit][due_date]"
                           value="{{ old('payment_schedule.deposit.due_date', data_get($deposit, 'due_date')) }}">
                    <div class="form-text">Lascia vuoto per indicare “alla firma”.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Importo acconto</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control js-payment-amount"
                               name="payment_schedule[deposit][amount]"
                               value="{{ old('payment_schedule.deposit.amount', data_get($deposit, 'amount')) }}">
                        <span class="input-group-text">€</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Rate</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-installment">
                    <i class="bi bi-plus-lg"></i> Aggiungi rata
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2" id="installments-table">
                    <thead>
                    <tr>
                        <th>Descrizione</th>
                        <th style="width: 22%">Scadenza</th>
                        <th style="width: 22%">Importo</th>
                        <th style="width: 5%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($installments as $idx => $installment)
                        <tr>
                            <td>
                                <input type="text" class="form-control"
                                       name="payment_schedule[installments][{{ $idx }}][label]"
                                       value="{{ data_get($installment, 'label', 'Rata '.($idx + 1)) }}">
                            </td>
                            <td>
                                <input type="date" class="form-control"
                                       name="payment_schedule[installments][{{ $idx }}][due_date]"
                                       value="{{ data_get($installment, 'due_date') }}">
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control js-payment-amount"
                                           name="payment_schedule[installments][{{ $idx }}][amount]"
                                           value="{{ data_get($installment, 'amount') }}">
                                    <span class="input-group-text">€</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-installment">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mb-0 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Totale piano pagamenti inserito</span>
                    <strong id="payment_schedule_total">0,00 €</strong>
                </div>
                <small class="d-block mt-1">
                    Il totale viene mostrato per controllo. Può coincidere con il totale preventivo o rappresentare solo il piano concordato.
                </small>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Coordinate bancarie da usare nel contratto</label>
    <textarea name="bank_details" class="form-control" rows="3" id="bank_details">{{ old('bank_details', $quote->bank_details) }}</textarea>
    <div class="form-text">
        Puoi inserire il conto corrente specifico per questo preventivo/contratto. Es: Intestato a, banca, IBAN, BIC/SWIFT. Questo dato resta salvato sul preventivo anche se in futuro cambi le impostazioni globali.
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
                    <input type="number" step="0.01" min="0" name="items[{{ $idx }}][discount_percent]"
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
                <td><input type="text" name="items[0][description]" class="form-control" required></td>
                <td><input type="number" step="0.01" name="items[0][quantity]" class="form-control" value="1"></td>
                <td><input type="text" name="items[0][unit]" class="form-control" value="pz"></td>
                <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control" value="0"></td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[0][discount_percent]" class="form-control js-discount-input" value="0">
                    <small class="text-muted d-block mt-1 js-discount-hint" style="display:none;"></small>
                </td>
                <td><input type="number" step="0.01" name="items[0][tax_rate]" class="form-control" value="22"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-x-lg"></i></button>
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
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Salva preventivo</button>
    <a href="{{ route('admin.crm.quotes.index') }}" class="btn btn-secondary">Annulla</a>
</div>

@php
    $productMap = collect($products)->mapWithKeys(function($p){
        return [
            $p->id => [
                'description'      => $p->description ?? $p->name,
                'unit'             => $p->unit ?? 'pz',
                'unit_price'       => $p->price ?? $p->unit_price ?? 0,
                'tax_rate'         => $p->tax_rate ?? 22,
                'max_discount'     => $p->max_discount,
                'is_promo'         => (bool) ($p->is_promo ?? false),
                'promo_expires_at' => optional($p->promo_expires_at)->format('Y-m-d'),
            ],
        ];
    });
@endphp

@push('scripts')
    <script>
        (function(){
            const tableBody = document.querySelector('#quote-items-table tbody');
            const btnAdd = document.querySelector('#btn-add-item');
            const PRODUCTS = @json($productMap);
            const billingProfileSelect = document.querySelector('#billing_profile_id');
            const bankDetailsTextarea = document.querySelector('#bank_details');
            const paymentType = document.querySelector('#payment_type');
            const paymentScheduleBox = document.querySelector('#payment_schedule_box');
            const installmentsBody = document.querySelector('#installments-table tbody');
            const btnAddInstallment = document.querySelector('#btn-add-installment');
            const paymentScheduleTotal = document.querySelector('#payment_schedule_total');

            if (billingProfileSelect && bankDetailsTextarea) {
                billingProfileSelect.addEventListener('change', function () {
                    const option = billingProfileSelect.options[billingProfileSelect.selectedIndex];
                    const bankDetails = option ? option.getAttribute('data-bank-details') : '';
                    if (bankDetails) bankDetailsTextarea.value = bankDetails;
                });
            }

            function parseIndexFromName(name) {
                const m = String(name || '').match(/^items\[(\d+)\]\[/);
                return m ? parseInt(m[1], 10) : null;
            }

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

            function getNextInstallmentIndex() {
                let max = -1;
                installmentsBody.querySelectorAll('input[name^="payment_schedule[installments]"]').forEach(input => {
                    const m = input.name.match(/^payment_schedule\[installments\]\[(\d+)\]/);
                    if (m) max = Math.max(max, parseInt(m[1], 10));
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
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            }

            function isPromoActive(data) {
                if (!data || !data.is_promo) return false;
                if (!data.promo_expires_at) return true;
                return data.promo_expires_at >= todayYmd();
            }

            function updateDiscountRules(row) {
                const selectEl = row.querySelector('.js-product-select');
                const discountEl = row.querySelector('.js-discount-input');
                const hintEl = row.querySelector('.js-discount-hint');
                if (!discountEl) return;

                const pid = selectEl ? selectEl.value : '';
                const data = pid ? PRODUCTS[pid] : null;
                discountEl.removeAttribute('max');

                if (hintEl) {
                    hintEl.style.display = 'none';
                    hintEl.textContent = '';
                }

                if (!data) return;

                const maxDisc = (data.max_discount === null || data.max_discount === undefined || data.max_discount === '') ? null : Number(data.max_discount);
                if (maxDisc !== null && !Number.isNaN(maxDisc)) {
                    discountEl.setAttribute('max', String(maxDisc));
                    const current = Number(discountEl.value || 0);
                    if (!Number.isNaN(current) && current > maxDisc) discountEl.value = maxDisc;
                }

                if (hintEl) {
                    const parts = [];
                    parts.push(maxDisc !== null && !Number.isNaN(maxDisc) ? `Max sconto: ${maxDisc}%` : 'Max sconto: —');

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
    <td><input type="text" name="items[${index}][description]" class="form-control" required></td>
    <td><input type="number" step="0.01" name="items[${index}][quantity]" class="form-control" value="1"></td>
    <td><input type="text" name="items[${index}][unit]" class="form-control" value="pz"></td>
    <td><input type="number" step="0.01" name="items[${index}][unit_price]" class="form-control" value="0"></td>
    <td>
        <input type="number" step="0.01" min="0" name="items[${index}][discount_percent]" class="form-control js-discount-input" value="0">
        <small class="text-muted d-block mt-1 js-discount-hint" style="display:none;"></small>
    </td>
    <td><input type="number" step="0.01" name="items[${index}][tax_rate]" class="form-control" value="22"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-x-lg"></i></button></td>
</tr>`;
                tableBody.insertAdjacentHTML('beforeend', tpl);
            }

            function autofillRowFromProduct(selectEl) {
                const productId = selectEl.value;
                if (!productId) return;
                const data = PRODUCTS[productId];
                if (!data) return;

                const row = selectEl.closest('tr');
                if (!row) return;

                const descInput = row.querySelector('input[name*="[description]"]');
                const unitInput = row.querySelector('input[name*="[unit]"]');
                const priceInput = row.querySelector('input[name*="[unit_price]"]');
                const taxInput = row.querySelector('input[name*="[tax_rate]"]');

                if (descInput && !descInput.value) descInput.value = data.description || '';
                if (unitInput && !unitInput.value) unitInput.value = data.unit || 'pz';
                if (priceInput && (!priceInput.value || Number(priceInput.value) === 0)) priceInput.value = data.unit_price ?? 0;
                if (taxInput && (!taxInput.value || Number(taxInput.value) === 0)) taxInput.value = data.tax_rate ?? 22;

                updateDiscountRules(row);
            }

            function togglePaymentSchedule() {
                if (!paymentType || !paymentScheduleBox) return;
                paymentScheduleBox.classList.toggle('d-none', paymentType.value !== 'structured');
            }

            function recalcPaymentScheduleTotal() {
                if (!paymentScheduleTotal) return;
                let total = 0;
                document.querySelectorAll('.js-payment-amount').forEach(input => {
                    const value = Number(String(input.value || '0').replace(',', '.'));
                    if (!Number.isNaN(value)) total += value;
                });
                paymentScheduleTotal.textContent = total.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            }

            function addInstallment() {
                const index = getNextInstallmentIndex();
                const tpl = `
<tr>
    <td><input type="text" class="form-control" name="payment_schedule[installments][${index}][label]" value="Rata ${index + 1}"></td>
    <td><input type="date" class="form-control" name="payment_schedule[installments][${index}][due_date]"></td>
    <td>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control js-payment-amount" name="payment_schedule[installments][${index}][amount]">
            <span class="input-group-text">€</span>
        </div>
    </td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-installment"><i class="bi bi-x-lg"></i></button></td>
</tr>`;
                installmentsBody.insertAdjacentHTML('beforeend', tpl);
            }

            if (btnAdd && tableBody) {
                btnAdd.addEventListener('click', onAddRow);
                tableBody.addEventListener('click', function(e) {
                    const btn = e.target.closest('.btn-remove-row');
                    if (!btn) return;
                    const row = btn.closest('tr');
                    if (row) row.remove();
                });
                tableBody.addEventListener('change', function(e) {
                    const selectEl = e.target.closest('.js-product-select');
                    if (selectEl) autofillRowFromProduct(selectEl);
                });
                tableBody.addEventListener('input', function(e) {
                    const disc = e.target.closest('.js-discount-input');
                    if (!disc) return;
                    const row = disc.closest('tr');
                    const selectEl = row ? row.querySelector('.js-product-select') : null;
                    const pid = selectEl ? selectEl.value : '';
                    const data = pid ? PRODUCTS[pid] : null;
                    if (!data) return;
                    const maxDisc = data.max_discount === null || data.max_discount === undefined || data.max_discount === '' ? null : Number(data.max_discount);
                    if (maxDisc !== null && !Number.isNaN(maxDisc)) {
                        const v = Number(disc.value || 0);
                        if (!Number.isNaN(v) && v > maxDisc) disc.value = maxDisc;
                    }
                });
            }

            if (paymentType) paymentType.addEventListener('change', togglePaymentSchedule);
            if (btnAddInstallment && installmentsBody) btnAddInstallment.addEventListener('click', addInstallment);
            if (installmentsBody) {
                installmentsBody.addEventListener('click', function(e) {
                    const btn = e.target.closest('.btn-remove-installment');
                    if (!btn) return;
                    const row = btn.closest('tr');
                    if (row) row.remove();
                    recalcPaymentScheduleTotal();
                });
            }
            document.addEventListener('input', function(e) {
                if (e.target.closest('.js-payment-amount')) recalcPaymentScheduleTotal();
            });

            tableBody.querySelectorAll('tr').forEach(tr => updateDiscountRules(tr));
            togglePaymentSchedule();
            recalcPaymentScheduleTotal();
        })();
    </script>
@endpush
