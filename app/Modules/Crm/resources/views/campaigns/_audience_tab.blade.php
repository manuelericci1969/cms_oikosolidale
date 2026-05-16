<div class="row">
    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header">Selezione destinatari</div>
            <div class="card-body">
                <form id="recipients-form" method="POST" action="{{ route('admin.crm.campaigns.recipients.update', $campaign) }}">
                    @csrf

                    <input type="hidden" name="mode" id="recipients-mode" value="{{ old('mode', 'mixed') }}">

                    <div id="estimateBox" class="alert alert-info d-none"></div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_leads"
                                       name="include_leads" value="1" {{ old('include_leads', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="include_leads">
                                    Includi leads (consenso marketing)
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_customers"
                                       name="include_customers" value="1" {{ old('include_customers', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="include_customers">
                                    Includi clienti
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Stati lead da includere</label>
                            <select name="lead_status[]" class="form-select" multiple>
                                @foreach($leadStatusOptions as $value => $label)
                                    <option value="{{ $value }}" selected>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">CTRL/CMD per selezione multipla.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Solo leads assegnati a</label>
                            <select name="owner_id" class="form-select">
                                <option value="">-- Tutti gli assegnatari --</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="match_leads_to_categories"
                                   name="match_leads_to_categories" value="1"
                                {{ old('match_leads_to_categories') ? 'checked' : '' }}>
                            <label class="form-check-label" for="match_leads_to_categories">
                                Limita i lead alle categorie selezionate (Categoria + Stato lead, match via email)
                            </label>
                        </div>
                        <div class="form-text">
                            Se attivo, i lead verranno presi solo se la loro email è presente tra i contatti delle categorie selezionate.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Liste email da includere</label>
                        <select name="list_ids[]" class="form-select" multiple>
                            @foreach($emailLists as $list)
                                <option value="{{ $list->id }}">
                                    {{ $list->name }} ({{ $list->contacts_count }} contatti)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categorie contatti (liste)</label>
                        <select name="category_ids[]" class="form-select" multiple>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Se selezioni categorie, verranno inclusi solo i contatti appartenenti a quelle categorie.<br>
                            Per “Campagna solo per categoria” devi selezionare almeno una categoria.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-info btn-sm" id="btn-estimate">
                            <i class="bi bi-calculator"></i> Stima destinatari
                        </button>

                        <button type="submit" class="btn btn-outline-primary btn-sm" data-mode="mixed" id="btn-regenerate-mixed">
                            <i class="bi bi-people-fill"></i> Rigenera (MIX)
                        </button>

                        <button type="submit" class="btn btn-outline-dark btn-sm" data-mode="category_only" id="btn-regenerate-category">
                            <i class="bi bi-tags"></i> Rigenera SOLO per categoria
                        </button>
                    </div>

                    <div class="mt-3 small text-muted">
                        MIX = lead + clienti + liste/categorie.<br>
                        SOLO per categoria = usa solo i contatti delle categorie selezionate.
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Destinatari (prime 100 righe)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Segmento</th>
                            <th>Inviata</th>
                            <th>Consegnata</th>
                            <th>Aperture</th>
                            <th>Click</th>
                            <th>Bounce / Spam</th>
                            <th>Stato</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($campaign->recipients()->limit(100)->get() as $r)
                            <tr>
                                <td>{{ $r->email }}</td>
                                <td>{{ ucfirst($r->contact_type) }}</td>
                                <td>{{ $r->segment }}</td>
                                <td>{{ $r->sent_at ? $r->sent_at->format('d/m/Y H:i') : '' }}</td>
                                <td>{{ $r->delivered_at ? $r->delivered_at->format('d/m/Y H:i') : '' }}</td>
                                <td>{{ $r->open_count ?: '-' }}</td>
                                <td>{{ $r->click_count ?: '-' }}</td>
                                <td>
                                    @if ($r->bounced_at)
                                        <span class="badge bg-danger">Bounce</span>
                                    @elseif ($r->complained_at)
                                        <span class="badge bg-warning text-dark">Spam</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $r->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Nessun destinatario presente.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header">Importa destinatari da CSV</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.crm.campaigns.import_csv', $campaign) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">File CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nome lista / segmento</label>
                        <input type="text" name="segment" class="form-control" value="{{ old('segment') }}"
                               placeholder="Es. Lista Natale 2025">
                    </div>

                    <button class="btn btn-outline-success w-100">
                        <i class="bi bi-file-earmark-arrow-up"></i> Importa CSV
                    </button>
                </form>

                <hr>

                <div class="small text-muted">
                    Header consigliato: <code>EMAIL</code>, <code>NOME</code>, <code>COGNOME</code>.
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Azioni lista destinatari</div>
            <div class="card-body">
                <div class="small text-muted mb-3">
                    Se vuoi ripartire da zero puoi svuotare tutti i destinatari della campagna.
                </div>

                <form method="POST" action="{{ route('admin.crm.campaigns.recipients.clear', $campaign) }}"
                      onsubmit="return confirm('Vuoi davvero cancellare tutti i destinatari della campagna?');">
                    @csrf
                    <button class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Svuota destinatari
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Riepilogo pubblico</div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="small text-muted">Totale destinatari</div>
                    <div class="h4 mb-0">{{ $campaign->total_recipients }}</div>
                </div>

                <div class="small text-muted">
                    Questa sezione serve per costruire o aggiornare il pubblico della campagna prima dell’invio.
                </div>
            </div>
        </div>
    </div>
</div>
