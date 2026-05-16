{{-- app/Modules/Crm/resources/views/campaigns/edit.blade.php --}}
@extends('admin.layout')

@section('title', 'Campagna: ' . $campaign->name)

@section('content')
    <link rel="stylesheet" href="{{ asset('pb/pb.css') }}">

    <style>
        #campaignRichtextEditor img { max-width: 100%; height: auto; }
        .pb-img-selected { outline: 2px solid #0d6efd; outline-offset: 2px; }
        .pb-img-panel { position: absolute; z-index: 1080; border-radius: 0.5rem; background: #fff; }
        .pb-img-panel.pb-img-panel--visible { display: block; }
        .pb-img-panel:not(.pb-img-panel--visible) { display: none; }

        .campaign-section {
            scroll-margin-top: 90px;
        }

        .campaign-kpi .card {
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }

        .campaign-kpi .kpi-label {
            font-size: .82rem;
            color: #6c757d;
        }

        .campaign-kpi .kpi-value {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .campaign-sticky-actions {
            position: sticky;
            top: 70px;
            z-index: 20;
        }

        .campaign-anchor-nav a {
            text-decoration: none;
        }

        .campaign-anchor-nav .btn {
            text-align: left;
        }

        .campaign-card {
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 2px 12px rgba(0,0,0,.04);
        }

        .campaign-card .card-header {
            font-weight: 600;
        }

        .campaign-muted-box {
            border: 1px dashed rgba(0,0,0,.12);
            border-radius: .75rem;
            padding: 1rem;
            background: #fafbfc;
        }
    </style>

    @php
        $errorCount = $campaign->recipients()->where('status', 'failed')->count();
        $recipientRows = $campaign->recipients()->limit(100)->get();
        $totalRecipients = (int) ($campaign->total_recipients ?: 0);
        $sentCount = (int) ($campaign->sent_count ?: 0);
        $openCount = (int) ($campaign->open_count ?: 0);
        $clickCount = (int) ($campaign->click_count ?: 0);
        $bounceCount = (int) ($campaign->bounce_count ?: 0);
        $unsubscribeCount = (int) ($campaign->unsubscribe_count ?: 0);
        $openRate = $sentCount > 0 ? round(($openCount / $sentCount) * 100, 1) : null;
        $clickRate = $sentCount > 0 ? round(($clickCount / $sentCount) * 100, 1) : null;
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h3 mb-1">Campagna: {{ $campaign->name }}</h1>
            <div class="text-muted small">
                Stato:
                <strong>{{ \App\Modules\Crm\Models\Campaign::STATUS_OPTIONS[$campaign->status] ?? $campaign->status }}</strong>
                · Creata il {{ $campaign->created_at?->format('d/m/Y H:i') }}
                @if($campaign->sent_at)
                    · Ultimo invio {{ $campaign->sent_at->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.campaigns.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Torna alle campagne
            </a>

            <a href="#section-content" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Contenuto
            </a>

            <a href="#section-audience" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people"></i> Pubblico
            </a>

            <a href="#section-report" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-bar-chart"></i> Report
            </a>

            <button type="button" class="btn btn-primary btn-sm" id="btn-send-now">
                <i class="bi bi-send"></i> Avvia invio
            </button>
        </div>
    </div>

    {{-- Modal progress invio --}}
    <div class="modal fade" id="sendProgressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-send me-1"></i> Invio campagna in corso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Non chiudere questa finestra fino al completamento dell'invio.</p>
                    <div class="progress mb-2">
                        <div class="progress-bar" id="send-progress-bar" role="progressbar"
                             style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="small text-muted" id="send-progress-text">Inizializzazione…</div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Attenzione:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errorCount > 0)
        <div class="alert alert-warning">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong>Attenzione:</strong> ci sono {{ $errorCount }} destinatari in errore.
                </div>
                <form method="POST" action="{{ route('admin.crm.campaigns.retry_errors', $campaign) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-arrow-repeat"></i> Rimetti in coda i contatti in errore
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- KPI --}}
    <div class="row campaign-kpi mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-label">Destinatari totali</div>
                    <div class="kpi-value">{{ $totalRecipients }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-label">Inviate</div>
                    <div class="kpi-value">{{ $sentCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-label">Aperture</div>
                    <div class="kpi-value">{{ $openCount }}</div>
                    <div class="small text-muted mt-1">
                        {{ $openRate !== null ? number_format($openRate, 1, ',', '.') . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-label">Click</div>
                    <div class="kpi-value">{{ $clickCount }}</div>
                    <div class="small text-muted mt-1">
                        {{ $clickRate !== null ? number_format($clickRate, 1, ',', '.') . '%' : '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-label">Bounce</div>
                    <div class="kpi-value">{{ $bounceCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card campaign-card h-100">
                <div class="card-body py-3">
                    <div class="kpi-label">Cancellati</div>
                    <div class="kpi-value">{{ $unsubscribeCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- COLONNA PRINCIPALE --}}
        <div class="col-xl-9">
            {{-- CONTENUTO --}}
            <div id="section-content" class="card campaign-card mb-4 campaign-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pencil-square me-2"></i>Contenuto campagna</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis">Step 1</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.campaigns.update', $campaign) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label">Nome interno *</label>
                                    <input type="text" name="name" class="form-control"
                                           value="{{ old('name', $campaign->name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Oggetto *</label>
                                    <input type="text" name="subject" class="form-control"
                                           value="{{ old('subject', $campaign->subject) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Preheader</label>
                                    <input type="text" name="preheader" class="form-control"
                                           value="{{ old('preheader', $campaign->preheader) }}">
                                    <div class="form-text">
                                        Testo breve che molti client mostrano dopo l’oggetto.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Contenuto HTML *</label>

                                    <textarea name="html_body" id="campaignHtmlTextarea" class="d-none" rows="10" required>{{ old('html_body', $campaign->html_body) }}</textarea>

                                    <div id="campaignRichtextToolbar" class="pb-toolbar pb-richtext-toolbar mb-2"></div>

                                    <div id="campaignRichtextEditor" class="pb-richtext-editor form-control"
                                         contenteditable="true" style="min-height: 300px;"></div>

                                    <textarea id="campaignHtmlSource"
                                              class="pb-richtext-html form-control form-control-sm mt-2 d-none"
                                              style="font-family: monospace; min-height: 160px;"></textarea>

                                    <div class="form-text">
                                        Placeholder:
                                        <code>&lbrace;&lbrace;name&rbrace;&rbrace;</code> /
                                        <code>&lbrace;&lbrace;email&rbrace;&rbrace;</code>.
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Contenuto testuale (opzionale)</label>
                                    <textarea name="text_body" rows="5" class="form-control">{{ old('text_body', $campaign->text_body) }}</textarea>
                                    <div class="form-text">
                                        Usato come versione "solo testo" in alcuni client email.
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="campaign-muted-box mb-3">
                                    <div class="fw-semibold mb-3">Mittente</div>

                                    <div class="mb-3">
                                        <label class="form-label">Da (nome)</label>
                                        <input type="text" name="from_name" class="form-control"
                                               value="{{ old('from_name', $campaign->from_name) }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Da (email)</label>
                                        <input type="email" name="from_email" class="form-control"
                                               value="{{ old('from_email', $campaign->from_email) }}">
                                        <div class="form-text">
                                            Se vuoto, verranno usati i dati predefiniti di sistema.
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">Reply-to</label>
                                        <input type="email" name="reply_to_email" class="form-control"
                                               value="{{ old('reply_to_email', $campaign->reply_to_email) }}">
                                    </div>
                                </div>

                                <div class="campaign-muted-box">
                                    <div class="fw-semibold mb-2">Riepilogo rapido</div>
                                    <div class="small">
                                        <div><strong>Stato:</strong> {{ \App\Modules\Crm\Models\Campaign::STATUS_OPTIONS[$campaign->status] ?? $campaign->status }}</div>
                                        <div><strong>Destinatari:</strong> {{ $totalRecipients }}</div>
                                        <div><strong>Inviate:</strong> {{ $sentCount }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button class="btn btn-primary">
                                <i class="bi bi-save"></i> Salva campagna
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- PUBBLICO --}}
            <div id="section-audience" class="card campaign-card mb-4 campaign-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-2"></i>Pubblico e sorgenti destinatari</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis">Step 2</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <form id="recipients-form" method="POST" action="{{ route('admin.crm.campaigns.recipients.update', $campaign) }}">
                                @csrf

                                <input type="hidden" name="mode" id="recipients-mode" value="{{ old('mode', 'mixed') }}">

                                <div id="estimateBox" class="alert alert-info d-none"></div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="include_leads"
                                                   name="include_leads" value="1" {{ old('include_leads', 1) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="include_leads">
                                                Includi leads (consenso marketing)
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
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
                                    MIX = lead + clienti + liste/categorie (se selezionate).<br>
                                    SOLO per categoria = usa solo i contatti delle categorie selezionate (ignora lead/clienti).
                                </div>
                            </form>
                        </div>

                        <div class="col-lg-4">
                            <div class="campaign-muted-box mb-3">
                                <div class="fw-semibold mb-3">Importa destinatari da CSV</div>

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

                                <div class="form-text mt-3">
                                    Header consigliato: <code>EMAIL</code>, <code>NOME</code>, <code>COGNOME</code>.
                                </div>
                            </div>

                            <div class="campaign-muted-box">
                                <div class="fw-semibold mb-3">Azioni lista</div>

                                <form method="POST"
                                      action="{{ route('admin.crm.campaigns.recipients.clear', $campaign) }}"
                                      onsubmit="return confirm('Vuoi davvero cancellare tutti i destinatari della campagna?');">
                                    @csrf
                                    <button class="btn btn-outline-danger w-100">
                                        <i class="bi bi-trash"></i> Svuota destinatari
                                    </button>
                                </form>

                                <div class="small text-muted mt-3">
                                    Totale attuale: <strong>{{ $totalRecipients }}</strong> destinatari.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DESTINATARI --}}
            <div id="section-recipients" class="card campaign-card mb-4 campaign-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table me-2"></i>Destinatari generati</span>
                    <span class="badge bg-secondary">{{ $recipientRows->count() }} / max 100</span>
                </div>
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
                            @forelse ($recipientRows as $r)
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

            {{-- REPORT --}}
            <div id="section-report" class="card campaign-card mb-4 campaign-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bar-chart me-2"></i>Report campagna</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis">Step 3</span>
                </div>
                <div class="card-body">
                    <div class="accordion" id="campaignClickAccordion">

                        {{-- 1) Link più cliccati --}}
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingLinks">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseLinks"
                                        aria-expanded="false" aria-controls="collapseLinks">
                                    <i class="bi bi-link-45deg me-2"></i>
                                    Link più cliccati
                                    <span class="ms-2 badge bg-secondary">{{ $linkStats->count() }}</span>
                                </button>
                            </h2>

                            <div id="collapseLinks" class="accordion-collapse collapse"
                                 aria-labelledby="headingLinks" data-bs-parent="#campaignClickAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead class="table-light">
                                            <tr>
                                                <th>URL</th>
                                                <th class="text-end">Click</th>
                                                <th class="text-end">Utenti</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($linkStats as $ls)
                                                <tr>
                                                    <td class="small">
                                                        <a href="{{ $ls->url }}" target="_blank" rel="noopener">
                                                            {{ \Illuminate\Support\Str::limit($ls->url, 80) }}
                                                        </a>
                                                    </td>
                                                    <td class="text-end">{{ (int) $ls->clicks }}</td>
                                                    <td class="text-end">{{ (int) $ls->unique_recipients }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">Nessun click registrato.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2) Utenti più attivi --}}
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingUsers">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                                        aria-expanded="false" aria-controls="collapseUsers">
                                    <i class="bi bi-person-lines-fill me-2"></i>
                                    Utenti più attivi
                                    <span class="ms-2 badge bg-secondary">{{ $topRecipients->count() }}</span>
                                </button>
                            </h2>

                            <div id="collapseUsers" class="accordion-collapse collapse"
                                 aria-labelledby="headingUsers" data-bs-parent="#campaignClickAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Destinatario</th>
                                                <th class="text-end">Click</th>
                                                <th class="text-end">Ultimo click</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($topRecipients as $tr)
                                                <tr>
                                                    <td class="small">
                                                        <div class="fw-semibold">{{ $tr->email }}</div>
                                                        @if(!empty($tr->name))
                                                            <div class="text-muted">{{ $tr->name }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ (int) $tr->clicks }}</td>
                                                    <td class="text-end small text-muted">
                                                        {{ $tr->last_clicked_at ? \Carbon\Carbon::parse($tr->last_clicked_at)->format('d/m/Y H:i') : '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">Nessun click registrato.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3) Dettaglio click --}}
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingDetails">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseDetails"
                                        aria-expanded="false" aria-controls="collapseDetails">
                                    <i class="bi bi-mouse2 me-2"></i>
                                    Dettaglio click (destinatario + link)
                                    <span class="ms-2 badge bg-secondary">{{ $clickRows->count() }}</span>
                                </button>
                            </h2>

                            <div id="collapseDetails" class="accordion-collapse collapse"
                                 aria-labelledby="headingDetails" data-bs-parent="#campaignClickAccordion">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Destinatario</th>
                                                <th>Link</th>
                                                <th class="text-end">Click</th>
                                                <th class="text-end">Primo</th>
                                                <th class="text-end">Ultimo</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($clickRows as $c)
                                                <tr>
                                                    <td class="small">
                                                        <div class="fw-semibold">{{ $c->recipient->email ?? '—' }}</div>
                                                        @if(!empty($c->recipient?->name))
                                                            <div class="text-muted">{{ $c->recipient->name }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="small">
                                                        <a href="{{ $c->url }}" target="_blank" rel="noopener">
                                                            {{ \Illuminate\Support\Str::limit($c->url, 90) }}
                                                        </a>
                                                    </td>
                                                    <td class="text-end">{{ (int) $c->click_count }}</td>
                                                    <td class="text-end small text-muted">
                                                        {{ $c->first_clicked_at ? $c->first_clicked_at->format('d/m/Y H:i') : '-' }}
                                                    </td>
                                                    <td class="text-end small text-muted">
                                                        {{ $c->last_clicked_at ? $c->last_clicked_at->format('d/m/Y H:i') : '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">Nessun click registrato.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="p-3 small text-muted">
                                        Nota: mostra gli ultimi 200 record ordinati per “ultimo click”.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- INVIO --}}
            <div id="section-send" class="card campaign-card mb-4 campaign-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-send-check me-2"></i>Invio campagna</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis">Step 4</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-md-4">
                            <div class="campaign-muted-box h-100">
                                <div class="small text-muted">Stato campagna</div>
                                <div class="fw-semibold mt-1">
                                    {{ \App\Modules\Crm\Models\Campaign::STATUS_OPTIONS[$campaign->status] ?? $campaign->status }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="campaign-muted-box h-100">
                                <div class="small text-muted">Destinatari pronti</div>
                                <div class="fw-semibold mt-1">{{ $totalRecipients }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="campaign-muted-box h-100">
                                <div class="small text-muted">Destinatari in errore</div>
                                <div class="fw-semibold mt-1">{{ $errorCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-primary" id="btn-send-now-bottom">
                            <i class="bi bi-send"></i> Avvia invio progressivo
                        </button>

                        <form method="POST" action="{{ route('admin.crm.campaigns.queue', $campaign) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-primary">
                                <i class="bi bi-clock-history"></i> Metti in coda
                            </button>
                        </form>

                        @if($errorCount > 0)
                            <form method="POST" action="{{ route('admin.crm.campaigns.retry_errors', $campaign) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-warning">
                                    <i class="bi bi-arrow-repeat"></i> Ripeti errori
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="small text-muted mt-3">
                        Prima dell’invio verifica contenuto, pubblico e statistiche.
                    </div>
                </div>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="col-xl-3">
            <div class="campaign-sticky-actions">
                <div class="card campaign-card mb-3">
                    <div class="card-header">Navigazione rapida</div>
                    <div class="card-body campaign-anchor-nav d-grid gap-2">
                        <a href="#section-content" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil-square me-1"></i> Contenuto
                        </a>
                        <a href="#section-audience" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-people me-1"></i> Pubblico
                        </a>
                        <a href="#section-recipients" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-table me-1"></i> Destinatari
                        </a>
                        <a href="#section-report" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-bar-chart me-1"></i> Report
                        </a>
                        <a href="#section-send" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-send-check me-1"></i> Invio
                        </a>
                    </div>
                </div>

                <div class="card campaign-card">
                    <div class="card-header">Checklist</div>
                    <div class="card-body small">
                        <div class="mb-2">
                            @if(!empty($campaign->subject))
                                <span class="badge bg-success me-2">OK</span> Oggetto compilato
                            @else
                                <span class="badge bg-danger me-2">NO</span> Oggetto mancante
                            @endif
                        </div>

                        <div class="mb-2">
                            @if(!empty($campaign->html_body))
                                <span class="badge bg-success me-2">OK</span> Contenuto HTML presente
                            @else
                                <span class="badge bg-danger me-2">NO</span> Contenuto HTML mancante
                            @endif
                        </div>

                        <div class="mb-2">
                            @if($totalRecipients > 0)
                                <span class="badge bg-success me-2">OK</span> Destinatari presenti
                            @else
                                <span class="badge bg-danger me-2">NO</span> Nessun destinatario
                            @endif
                        </div>

                        <div class="mb-0">
                            @if($errorCount > 0)
                                <span class="badge bg-warning text-dark me-2">ATT</span> {{ $errorCount }} destinatari in errore
                            @else
                                <span class="badge bg-success me-2">OK</span> Nessun errore pendente
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.PB_MEDIA_PICKER_URL = '{{ url('/admin/media/picker') }}';
        window.PB_FONTS = [
            'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Playfair Display','Merriweather',
            'Source Sans 3','Raleway','Nunito','Oswald','PT Serif','Work Sans','Rubik','Arial','Verdana',
            'Times New Roman','Georgia','Tahoma','Trebuchet MS','Courier New'
        ];
    </script>

    <script type="module" src="{{ asset('pb/campaignEditor.js') }}"></script>

    {{-- Stima destinatari + gestione mode --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('recipients-form');
            const modeInput = document.getElementById('recipients-mode');
            const estimateBox = document.getElementById('estimateBox');
            const btnEstimate = document.getElementById('btn-estimate');

            document.querySelectorAll('button[data-mode]').forEach(btn => {
                btn.addEventListener('click', function () {
                    modeInput.value = this.getAttribute('data-mode') || 'mixed';
                });
            });

            btnEstimate?.addEventListener('click', async function () {
                if (!form) return;

                estimateBox.classList.add('d-none');
                estimateBox.classList.remove('alert-danger');
                estimateBox.classList.add('alert-info');
                estimateBox.innerHTML = 'Calcolo in corso…';
                estimateBox.classList.remove('d-none');

                const url = "{{ route('admin.crm.campaigns.recipients.estimate', $campaign) }}";
                const token = "{{ csrf_token() }}";

                const fd = new FormData(form);
                fd.set('mode', modeInput.value || 'mixed');

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                        credentials: 'same-origin',
                        cache: 'no-store',
                    });

                    const ct = res.headers.get('content-type') || '';
                    let data;

                    if (ct.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        throw new Error(`Risposta non JSON (HTTP ${res.status}): ${text.slice(0, 300)}`);
                    }

                    if (!res.ok || !data.ok) {
                        estimateBox.classList.remove('alert-info');
                        estimateBox.classList.add('alert-danger');
                        estimateBox.innerHTML = data?.message || 'Errore nel calcolo.';
                        return;
                    }

                    const b = data.breakdown || {};
                    estimateBox.innerHTML =
                        `<strong>Stima destinatari (unici):</strong> ${data.total_unique}<br>` +
                        `<span class="text-muted">Lead:</span> ${b.leads_unique ?? 0} — ` +
                        `<span class="text-muted">Clienti:</span> ${b.customers_unique ?? 0} — ` +
                        `<span class="text-muted">Liste/Categorie:</span> ${b.list_contacts_unique ?? 0}<br>` +
                        `<span class="text-muted">Modalità:</span> ${data.mode}`;

                } catch (e) {
                    console.error(e);
                    estimateBox.classList.remove('alert-info');
                    estimateBox.classList.add('alert-danger');
                    estimateBox.innerHTML = 'Errore durante la richiesta di stima.<br><small class="text-muted">'
                        + (e?.message ? e.message : '') + '</small>';
                }
            });
        });
    </script>

    {{-- Script invio progressivo campagne --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnTop   = document.getElementById('btn-send-now');
            const btnBottom = document.getElementById('btn-send-now-bottom');
            const modalEl = document.getElementById('sendProgressModal');
            if (!btnTop || !modalEl) return;

            const modal = new bootstrap.Modal(modalEl);
            const bar   = document.getElementById('send-progress-bar');
            const text  = document.getElementById('send-progress-text');

            const totalInitial = {{ (int) ($campaign->total_recipients ?: $campaign->recipients()->count()) }};

            async function sendBatch() {
                const url   = "{{ route('admin.crm.campaigns.send_now', $campaign) }}";
                const token = "{{ csrf_token() }}";

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                if (!res.ok) throw new Error('Errore HTTP ' + res.status);
                return await res.json();
            }

            async function runSending() {
                modal.show();
                btnTop.disabled = true;
                if (btnBottom) btnBottom.disabled = true;

                try {
                    let done  = false;
                    let sent  = 0;
                    let total = totalInitial || 0;

                    while (!done) {
                        const data = await sendBatch();

                        sent  = data.sent  ?? sent;
                        total = data.total ?? total;

                        const perc = total > 0 ? Math.round(sent / total * 100) : 0;

                        if (bar) {
                            bar.style.width = perc + '%';
                            bar.setAttribute('aria-valuenow', String(perc));
                            bar.textContent = perc + '%';
                        }

                        if (text) {
                            text.textContent = sent + ' / ' + total + ' email inviate';
                        }

                        done = !!data.done;

                        if (!done) await new Promise(r => setTimeout(r, 400));
                    }

                    setTimeout(function () {
                        window.location.reload();
                    }, 800);

                } catch (e) {
                    console.error(e);
                    alert('Errore durante l\'invio della campagna.');
                    btnTop.disabled = false;
                    if (btnBottom) btnBottom.disabled = false;
                    modal.hide();
                }
            }

            btnTop.addEventListener('click', function (e) {
                e.preventDefault();
                runSending();
            });

            if (btnBottom) {
                btnBottom.addEventListener('click', function (e) {
                    e.preventDefault();
                    runSending();
                });
            }
        });
    </script>
@endpush
