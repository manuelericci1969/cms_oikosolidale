@extends('admin.layout')

@section('title', 'Modifica contatto')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Modifica contatto</h1>

        <a href="{{ route('admin.crm.email-lists.edit', $list) }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    <div class="card">
        <div class="card-header">Dati contatto</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.crm.email-lists.contacts.update', [$list, $contact]) }}">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $contact->email) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $contact->name) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Segmento</label>
                        <input type="text" name="segment" class="form-control"
                               value="{{ old('segment', $contact->segment) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Città</label>
                        <input type="text" name="city" class="form-control"
                               value="{{ old('city', $contact->city) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Provincia</label>
                        <input type="text" name="province" class="form-control"
                               value="{{ old('province', $contact->province) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Regione</label>
                        <input type="text" name="region" class="form-control"
                               value="{{ old('region', $contact->region) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">CAP</label>
                        <input type="text" name="postal_code" class="form-control"
                               value="{{ old('postal_code', $contact->postal_code) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Paese</label>
                        <input type="text" name="country" class="form-control"
                               value="{{ old('country', $contact->country) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $contact->phone) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control"
                               value="{{ old('whatsapp', $contact->whatsapp) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Sito web</label>
                        <input type="url" name="website_url" class="form-control"
                               value="{{ old('website_url', $contact->website_url) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Pagina contatti</label>
                        <input type="url" name="contact_page_url" class="form-control"
                               value="{{ old('contact_page_url', $contact->contact_page_url) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Indirizzo</label>
                        <input type="text" name="address" class="form-control"
                               value="{{ old('address', $contact->address) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo attività</label>
                        <input type="text" name="business_type" class="form-control"
                               value="{{ old('business_type', $contact->business_type) }}">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Stelle</label>
                        <input type="number" min="1" max="5" name="stars" class="form-control"
                               value="{{ old('stars', $contact->stars) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Ruolo contatto</label>
                        <input type="text" name="contact_role" class="form-control"
                               value="{{ old('contact_role', $contact->contact_role) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">P.IVA</label>
                        <input type="text" name="vat_number" class="form-control"
                               value="{{ old('vat_number', $contact->vat_number) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">CIN</label>
                        <input type="text" name="cin_code" class="form-control"
                               value="{{ old('cin_code', $contact->cin_code) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Stato email</label>
                        <input type="text" name="email_status" class="form-control"
                               value="{{ old('email_status', $contact->email_status) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Tipo fonte</label>
                        <input type="text" name="source_type" class="form-control"
                               value="{{ old('source_type', $contact->source_type) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Valutazione sito</label>
                        <input type="text" name="site_rating" class="form-control"
                               value="{{ old('site_rating', $contact->site_rating) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Potenziale</label>
                        <input type="text" name="commercial_potential" class="form-control"
                               value="{{ old('commercial_potential', $contact->commercial_potential) }}">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">SEO</label>
                        <input type="number" step="0.01" min="0" max="100" name="seo_score" class="form-control"
                               value="{{ old('seo_score', $contact->seo_score) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ultima verifica</label>
                        <input type="datetime-local"
                               name="last_verified_at"
                               class="form-control"
                               value="{{ old('last_verified_at', optional($contact->last_verified_at)->format('Y-m-d\\TH:i')) }}">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Fonte URL</label>
                        <input type="url" name="source_url" class="form-control"
                               value="{{ old('source_url', $contact->source_url) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $contact->notes) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Categorie</label>
                        <select name="category_ids[]" class="form-select" multiple>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    @selected(in_array($cat->id, old('category_ids', $selectedCategoryIds ?? [])))>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="marketing_consense"
                                   name="marketing_consense"
                                   value="1"
                                @checked(old('marketing_consense', $contact->marketing_consense))>
                            <label class="form-check-label" for="marketing_consense">
                                Consenso marketing
                            </label>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
