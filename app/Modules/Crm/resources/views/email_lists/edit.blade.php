@extends('admin.layout')

@section('title', $list->exists ? 'Lista: '.$list->name : 'Nuova lista email')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            {{ $list->exists ? 'Lista: '.$list->name : 'Nuova lista email' }}
        </h1>

        <a href="{{ route('admin.crm.email-lists.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Torna alle liste
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

    <form method="POST"
          action="{{ $list->exists ? route('admin.crm.email-lists.update', $list) : route('admin.crm.email-lists.store') }}">
        @csrf
        @if($list->exists)
            @method('PUT')
        @endif

        <div class="card mb-3">
            <div class="card-header">Dettagli lista</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name', $list->name) }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description"
                              rows="3"
                              class="form-control">{{ old('description', $list->description) }}</textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-primary">
                    <i class="bi bi-save"></i> Salva lista
                </button>
            </div>
        </div>
    </form>

    @if($list->exists)
        <div class="card mb-3">
            <div class="card-header">Categorie contatti</div>
            <div class="card-body">
                <form class="row g-2" method="POST" action="{{ route('admin.crm.email-lists.categories.store') }}">
                    @csrf
                    <div class="col-md-6">
                        <input type="text" name="name" class="form-control"
                               placeholder="Es. Prospect, Clienti, Fornitori" required>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-sm btn-success">
                            <i class="bi bi-plus-lg"></i> Crea categoria
                        </button>
                    </div>
                </form>

                @if($categories->count())
                    <hr>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($categories as $cat)
                            <form method="POST" action="{{ route('admin.crm.email-lists.categories.destroy', $cat) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Eliminare la categoria?');">
                                    {{ $cat->name }} <i class="bi bi-x"></i>
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">Aggiorna lista da Lead/Clienti</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.crm.email-lists.sync_from_crm', $list) }}">
                            @csrf

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="include_leads" name="include_leads" value="1" checked>
                                    <label class="form-check-label" for="include_leads">
                                        Includi leads (con consenso marketing)
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="include_customers" name="include_customers" value="1" checked>
                                    <label class="form-check-label" for="include_customers">
                                        Includi clienti
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stati lead da includere</label>
                                <select name="lead_status[]" class="form-select" multiple>
                                    @foreach($leadStatusOptions as $value => $label)
                                        <option value="{{ $value }}" selected>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Tieni premuto CTRL (o CMD) per selezionare più stati.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Solo leads assegnati a</label>
                                <select name="owner_id" class="form-select">
                                    <option value="">-- Tutti gli assegnatari --</option>
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="text-end">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-people-fill"></i> Aggiorna lista da CRM
                                </button>
                            </div>

                            <div class="mt-2 small text-muted">
                                Questa azione sovrascrive lead/clienti nella lista, ma non tocca i contatti importati via CSV.
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">Importa contatti da CSV</div>
                    <div class="card-body">
                        <form method="POST"
                              action="{{ route('admin.crm.email-lists.import_csv', $list) }}"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">File CSV *</label>
                                <input type="file"
                                       name="file"
                                       class="form-control"
                                       accept=".csv,text/csv"
                                       required>
                                <div class="form-text">
                                    Header minimo: <code>EMAIL;NOME;COGNOME</code>. Supportati anche campi extra come CITY, PHONE, WEBSITE_URL, SOURCE_TYPE, ecc.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nome segmento (opzionale)</label>
                                <input type="text"
                                       name="segment"
                                       class="form-control"
                                       value="{{ old('segment') }}"
                                       placeholder="Es. Lead - Hotel - Olbia">
                            </div>

                            <div class="text-end">
                                <button class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-file-earmark-arrow-up"></i> Importa CSV
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header">Aggiungi contatto manualmente</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.crm.email-lists.contacts.store', $list) }}">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Nome</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Segmento</label>
                                    <input type="text" name="segment" class="form-control" value="{{ old('segment') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Categorie</label>
                                    <select name="category_ids[]" class="form-select" multiple>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" @selected(collect(old('category_ids', []))->contains($cat->id))>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Città</label>
                                    <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Provincia</label>
                                    <input type="text" name="province" class="form-control" value="{{ old('province') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Regione</label>
                                    <input type="text" name="region" class="form-control" value="{{ old('region') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">CAP</label>
                                    <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Paese</label>
                                    <input type="text" name="country" class="form-control" value="{{ old('country') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Telefono</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Sito web</label>
                                    <input type="url" name="website_url" class="form-control" value="{{ old('website_url') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Pagina contatti</label>
                                    <input type="url" name="contact_page_url" class="form-control" value="{{ old('contact_page_url') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Indirizzo</label>
                                    <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Tipo attività</label>
                                    <input type="text" name="business_type" class="form-control" value="{{ old('business_type') }}" placeholder="Es. Hotel">
                                </div>

                                <div class="col-md-1">
                                    <label class="form-label">Stelle</label>
                                    <input type="number" min="1" max="5" name="stars" class="form-control" value="{{ old('stars') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Ruolo contatto</label>
                                    <input type="text" name="contact_role" class="form-control" value="{{ old('contact_role') }}" placeholder="booking">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">P.IVA</label>
                                    <input type="text" name="vat_number" class="form-control" value="{{ old('vat_number') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">CIN</label>
                                    <input type="text" name="cin_code" class="form-control" value="{{ old('cin_code') }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Stato email</label>
                                    <input type="text" name="email_status" class="form-control" value="{{ old('email_status') }}" placeholder="verified">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Tipo fonte</label>
                                    <input type="text" name="source_type" class="form-control" value="{{ old('source_type') }}" placeholder="official_site">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Valutazione sito</label>
                                    <input type="text" name="site_rating" class="form-control" value="{{ old('site_rating') }}" placeholder="buono">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Potenziale</label>
                                    <input type="text" name="commercial_potential" class="form-control" value="{{ old('commercial_potential') }}" placeholder="alto">
                                </div>

                                <div class="col-md-1">
                                    <label class="form-label">SEO</label>
                                    <input type="number" step="0.01" min="0" max="100" name="seo_score" class="form-control" value="{{ old('seo_score') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Ultima verifica</label>
                                    <input type="datetime-local"
                                           name="last_verified_at"
                                           class="form-control"
                                           value="{{ old('last_verified_at') }}">
                                </div>

                                <div class="col-md-9">
                                    <label class="form-label">Fonte URL</label>
                                    <input type="url" name="source_url" class="form-control" value="{{ old('source_url') }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Note</label>
                                    <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <button class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Aggiungi contatto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Contatti della lista (prime 100 righe)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Email</th>
                            <th>Nome</th>
                            <th>Azienda</th>
                            <th>Località</th>
                            <th>Telefono</th>
                            <th>Fonte</th>
                            <th>Categorie</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($contacts as $c)
                            <tr>
                                <td>
                                    <div>{{ $c->email }}</div>
                                    <div class="small text-muted">{{ $c->contact_role ?: '—' }}</div>
                                </td>
                                <td>{{ $c->name ?: '—' }}</td>
                                <td>
                                    <div>{{ $c->business_type ?: '—' }}</div>
                                    <div class="small text-muted">
                                        @if($c->website_url)
                                            <a href="{{ $c->website_url }}" target="_blank" rel="noopener">Sito</a>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $locationParts = array_filter([$c->city, $c->province, $c->region, $c->postal_code, $c->country]);
                                    @endphp
                                    {{ count($locationParts) ? implode(', ', $locationParts) : '—' }}
                                </td>
                                <td>
                                    <div>{{ $c->phone ?: '—' }}</div>
                                    <div class="small text-muted">{{ $c->whatsapp ?: '' }}</div>
                                </td>
                                <td>
                                    <div>{{ $c->source_type ?: '—' }}</div>
                                    <div class="small text-muted">{{ $c->email_status ?: '—' }}</div>
                                </td>
                                <td>
                                    @forelse($c->categories as $cat)
                                        <span class="badge bg-info text-dark">{{ $cat->name }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.crm.email-lists.contacts.edit', [$list, $c]) }}"
                                       class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="POST"
                                          class="d-inline-block"
                                          action="{{ route('admin.crm.email-lists.contacts.destroy', [$list, $c]) }}"
                                          onsubmit="return confirm('Rimuovere questo contatto dalla lista?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    Nessun contatto in questa lista.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
