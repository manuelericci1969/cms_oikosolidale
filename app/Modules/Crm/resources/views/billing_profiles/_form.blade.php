@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nome profilo *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $profile->name) }}" required>
        <div class="form-text">Es: Manuele Ricci - Consulente, R4Software s.r.l.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Ragione sociale / intestazione fiscale</label>
        <input type="text" name="legal_name" class="form-control" value="{{ old('legal_name', $profile->legal_name) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">P.IVA</label>
        <input type="text" name="vat" class="form-control" value="{{ old('vat', $profile->vat) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Codice fiscale</label>
        <input type="text" name="tax_code" class="form-control" value="{{ old('tax_code', $profile->tax_code) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $profile->email) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Telefono</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $profile->phone) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Indirizzo</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $profile->address) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Città</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $profile->city) }}">
    </div>

    <div class="col-md-1">
        <label class="form-label">CAP</label>
        <input type="text" name="zip" class="form-control" value="{{ old('zip', $profile->zip) }}">
    </div>

    <div class="col-md-1">
        <label class="form-label">Prov.</label>
        <input type="text" name="province" class="form-control" value="{{ old('province', $profile->province) }}">
    </div>

    <div class="col-md-1">
        <label class="form-label">Paese</label>
        <input type="text" name="country" maxlength="2" class="form-control" value="{{ old('country', $profile->country ?: 'IT') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">PEC</label>
        <input type="email" name="pec" class="form-control" value="{{ old('pec', $profile->pec) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Codice SDI</label>
        <input type="text" name="sdi" class="form-control" value="{{ old('sdi', $profile->sdi) }}">
    </div>

    <div class="col-md-4 d-flex align-items-end gap-4">
        <div class="form-check form-switch mb-2">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $profile->exists ? $profile->is_active : true))>
            <label class="form-check-label" for="is_active">Attivo</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" @checked(old('is_default', $profile->is_default))>
            <label class="form-check-label" for="is_default">Predefinito</label>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Coordinate bancarie</label>
        <textarea name="bank_details" class="form-control" rows="4">{{ old('bank_details', $profile->bank_details) }}</textarea>
        <div class="form-text">Puoi indicare conto, intestatario, banca, IBAN, BIC/SWIFT. Questo testo verrà usato come base nei preventivi.</div>
    </div>

    <div class="col-12">
        <label class="form-label">Note interne</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $profile->notes) }}</textarea>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('admin.crm.billing-profiles.index') }}" class="btn btn-outline-secondary">Annulla</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> Salva profilo
    </button>
</div>
