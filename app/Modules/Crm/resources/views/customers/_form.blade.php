{{-- modules/Crm/resources/views/customers/_form.blade.php --}}

<div class="row">
    <div class="col-md-8">
        <div class="mb-3">
            <label class="form-label">Nome / Ragione sociale *</label>
            <input type="text"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $customer->name) }}"
                   required>
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email"
                       name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $customer->email) }}">
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Telefono</label>
                <input type="text"
                       name="phone"
                       class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $customer->phone) }}">
                @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Note interne</label>
            <textarea name="notes"
                      rows="4"
                      class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $customer->notes) }}</textarea>
            @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <h6 class="mb-3">Dati fiscali</h6>

        <div class="mb-3">
            <label class="form-label">Partita IVA</label>
            <input type="text"
                   name="vat_number"
                   class="form-control @error('vat_number') is-invalid @enderror"
                   value="{{ old('vat_number', $customer->vat_number) }}">
            @error('vat_number')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Codice fiscale</label>
            <input type="text"
                   name="tax_code"
                   class="form-control @error('tax_code') is-invalid @enderror"
                   value="{{ old('tax_code', $customer->tax_code) }}">
            @error('tax_code')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">PEC</label>
            <input type="email"
                   name="pec_email"
                   class="form-control @error('pec_email') is-invalid @enderror"
                   value="{{ old('pec_email', $customer->pec_email) }}">
            @error('pec_email')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Codice destinatario SDI</label>
            <input type="text"
                   name="sdi_code"
                   class="form-control @error('sdi_code') is-invalid @enderror"
                   value="{{ old('sdi_code', $customer->sdi_code) }}">
            <div class="form-text">Lascia vuoto se il cliente utilizza solo la PEC.</div>
            @error('sdi_code')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <h6 class="mt-4 mb-3">Indirizzo fatturazione</h6>

        <div class="mb-3">
            <label class="form-label">Indirizzo</label>
            <input type="text"
                   name="billing_address"
                   class="form-control @error('billing_address') is-invalid @enderror"
                   value="{{ old('billing_address', $customer->billing_address) }}">
            @error('billing_address')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-4 mb-3">
                <label class="form-label">CAP</label>
                <input type="text"
                       name="billing_zip"
                       class="form-control @error('billing_zip') is-invalid @enderror"
                       value="{{ old('billing_zip', $customer->billing_zip) }}">
                @error('billing_zip')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-8 mb-3">
                <label class="form-label">Città</label>
                <input type="text"
                       name="billing_city"
                       class="form-control @error('billing_city') is-invalid @enderror"
                       value="{{ old('billing_city', $customer->billing_city) }}">
                @error('billing_city')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label">Provincia</label>
                <input type="text"
                       name="billing_province"
                       class="form-control @error('billing_province') is-invalid @enderror"
                       value="{{ old('billing_province', $customer->billing_province) }}">
                @error('billing_province')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-6 mb-3">
                <label class="form-label">Nazione</label>
                <input type="text"
                       name="billing_country"
                       class="form-control @error('billing_country') is-invalid @enderror"
                       value="{{ old('billing_country', $customer->billing_country ?? 'IT') }}">
                @error('billing_country')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @isset($agents)
            <h6 class="mt-4 mb-3">Assegnazione</h6>

            <div class="mb-3">
                <label class="form-label">Assegnato a</label>
                <select name="owner_id"
                        class="form-select @error('owner_id') is-invalid @enderror">
                    <option value="">-- Nessun agente --</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}"
                            @selected(old('owner_id', $customer->owner_id) == $agent->id)>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
                @error('owner_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endisset


        <div class="form-check form-switch mb-3">
            <input class="form-check-input"
                   type="checkbox"
                   id="is_active"
                   name="is_active"
                   value="1"
                {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Cliente attivo</label>
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Salva
    </button>
    <a href="{{ route('admin.crm.customers.index') }}" class="btn btn-secondary">
        Annulla
    </a>
</div>
