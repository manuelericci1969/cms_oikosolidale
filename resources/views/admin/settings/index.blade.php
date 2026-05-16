@php use App\Models\Media; @endphp
@extends('admin.layout')
@section('title','Impostazioni')

@section('content')
    <style>
        :root{
            --pb-bg:#f6f8fb;
            --pb-card:#ffffff;
            --pb-muted:#6c757d;
            --pb-primary:#0d6efd;
            --pb-soft:#e9f1ff;
            --pb-border:#e5e7eb;
        }
        body{ background: var(--pb-bg); }
        .page-topbar{ position: sticky; top: -1px; z-index: 20; background: linear-gradient(180deg,#ffffff 0%, #ffffffef 70%, #ffffff00 100%); backdrop-filter: blur(6px); border-bottom: 1px solid var(--pb-border); }
        .card-soft{ background:var(--pb-card); border:1px solid var(--pb-border); border-radius:14px; box-shadow: 0 1px 2px rgba(16,24,40,.06); }
        .media-preview{ border:1px dashed var(--pb-border); border-radius:10px; background:#fafbff; display:flex; align-items:center; justify-content:center; min-height:84px; padding:6px; }
        .w-64{ width:64px; height:64px; object-fit:contain; border-radius:8px; }
        .w-180{ max-height:60px; object-fit:contain; }
        .nav-tabs .nav-link{ display:flex; align-items:center; gap:.5rem; }
        .form-hint{ font-size:.875rem; color:var(--pb-muted); }
        .mp-thumb{ width:100%; height:120px; object-fit:cover; border-radius:8px; border:1px solid var(--pb-border); }
        .theme-preview{ border:1px solid var(--pb-border); border-radius:12px; overflow:hidden; background:#fff; }
        .theme-preview .tp-header{ background: var(--preview-primary,#0d6efd); color:#fff; padding:10px 12px; display:flex; align-items:center; gap:8px; }
        .theme-preview .tp-body{ padding:12px; }
        .theme-preview .tp-btn{ border:1px solid var(--preview-primary,#0d6efd); color:var(--preview-primary,#0d6efd); background:#fff; border-radius:999px; padding:.35rem .8rem; font-size:.9rem; }
    </style>

    <div class="page-topbar mb-3">
        <div class="container-fluid py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-sliders2-vertical fs-5 text-primary"></i>
                    <h1 class="h4 mb-0">Impostazioni</h1>
                </div>
                @if(session('ok'))
                    <div class="alert alert-success py-1 px-2 mb-0"><i class="bi bi-check2-circle me-1"></i>{{ session('ok') }}</div>
                @endif

                @if(session('sync_errors'))
                    <div class="alert alert-warning mt-2">
                        <div class="fw-semibold mb-1">Dettaglio errori sync ({{ count(session('sync_errors')) }})</div>
                        <ul class="mb-0">
                            @foreach(session('sync_errors') as $e)
                                <li style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                                    {{ $e }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            @if ($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-0 mt-2">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>
        </div>
    </div>

    @php $active = session('tab', request('tab','branding')); @endphp

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $active==='branding'?'active':'' }}" href="?tab=branding"><i class="bi bi-palette2"></i> Branding</a></li>
        <li class="nav-item"><a class="nav-link {{ $active==='company'?'active':'' }}" href="?tab=company"><i class="bi bi-buildings"></i> Azienda</a></li>
        <li class="nav-item">
            <a class="nav-link {{ $active==='crm'?'active':'' }}" href="?tab=crm">
                <i class="bi bi-file-earmark-text"></i> CRM / Preventivi
            </a>
        </li>
        <li class="nav-item"><a class="nav-link {{ $active==='seo'?'active':'' }}" href="?tab=seo"><i class="bi bi-search"></i> SEO</a></li>
        <li class="nav-item"><a class="nav-link {{ $active==='analytics'?'active':'' }}" href="?tab=analytics"><i class="bi bi-graph-up"></i> Analytics</a></li>
        <li class="nav-item"><a class="nav-link {{ $active==='typography'?'active':'' }}" href="?tab=typography"><i class="bi bi-type"></i> Tipografia</a></li>
        <li class="nav-item">
            <a class="nav-link {{ $active==='legal'?'active':'' }}" href="?tab=legal">
                <i class="bi bi-shield-lock"></i> Privacy / Cookie
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $active==='calendar'?'active':'' }}" href="?tab=calendar">
                <i class="bi bi-calendar3"></i> Calendario
            </a>
        </li>
    </ul>

    {{-- ================= PRIVACY / COOKIE ================= --}}
    @if($active==='legal')
        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="legal">

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="cookie_enabled"
                               name="cookie_enabled" value="1"
                            @checked(!empty($legal['cookie_enabled']))>
                        <label class="form-check-label" for="cookie_enabled">
                            Mostra banner cookie
                        </label>
                    </div>
                    <div class="form-hint mt-1">
                        Se disattivi, il banner non verrà più mostrato (non consigliato in produzione).
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">URL pagina Privacy</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ url('/') }}</span>
                        <input type="text" class="form-control" name="privacy_url"
                               value="{{ $legal['privacy_url'] ?? '/privacy-policy' }}"
                               placeholder="/privacy-policy">
                    </div>
                    <div class="form-hint mt-1">
                        Se esiste la route <code>policy.privacy</code> useremo quella; altrimenti questo URL.
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Testo banner cookie</label>
                    <textarea name="cookie_message" rows="3" class="form-control">{{ $legal['cookie_message'] ?? '' }}</textarea>
                    <div class="form-hint mt-1">
                        Breve informativa che appare nel banner (ricorda di citare Google Analytics/terze parti se presenti).
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Testo pulsante accetta</label>
                    <input type="text" name="cookie_button" class="form-control"
                           value="{{ $legal['cookie_button'] ?? 'Accetta' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Testo link all’informativa</label>
                    <input type="text" name="cookie_link_label" class="form-control"
                           value="{{ $legal['cookie_link_label'] ?? 'Leggi l’informativa' }}">
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=legal" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Annulla
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-save2 me-1"></i> Salva impostazioni Privacy / Cookie
                </button>
            </div>
        </form>
    @endif


    {{-- ================= BRANDING ================= --}}
    @if($active==='branding')
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="branding">
            <input type="hidden" name="logo_id" id="logo_id" value="{{ $branding['logo_id'] ?? '' }}">
            <input type="hidden" name="logo_dark_id" id="logo_dark_id" value="{{ $branding['logo_dark_id'] ?? '' }}">
            <input type="hidden" name="favicon_id" id="favicon_id" value="{{ $branding['favicon_id'] ?? '' }}">

            <div class="row g-4">
                {{-- Logo chiaro --}}
                <div class="col-md-4">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*" data-preview="#prev_logo">
                    <div class="row g-2 mt-2">
                        <div class="col-7">
                            @php $lf = $branding['logo_fit'] ?? 'contain'; @endphp
                            <select class="form-select" name="logo_fit">
                                <option value="contain" @selected($lf==='contain')>Adatta (non tagliare)</option>
                                <option value="cover" @selected($lf==='cover')>Ritaglia (riempi area)</option>
                            </select>
                            <div class="form-hint mt-1"><i class="bi bi-arrows-angle-expand"></i> Mantieni rapporto o riempi tagliando.</div>
                        </div>
                        <div class="col-5 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" data-mp-open data-mp-target-input="logo_id" data-mp-target-preview="#prev_logo" title="Seleziona da archivio">
                                <i class="bi bi-images"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-mp-clear="logo_id" data-mp-clear-preview="#prev_logo" title="Rimuovi">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="media-preview mt-2">
                        @php $m = !empty($branding['logo_id']) ? Media::find((int)$branding['logo_id']) : null; @endphp
                        <img id="prev_logo" src="{{ $m?->variantUrl('thumb') ?: $m?->url }}" class="img-fluid w-180" alt="">
                    </div>
                    <div class="form-hint mt-1"><i class="bi bi-info-circle"></i> PNG/SVG con trasparenza consigliato.</div>
                </div>

                {{-- Logo scuro --}}
                <div class="col-md-4">
                    <label class="form-label">Logo (tema scuro)</label>
                    <input type="file" name="logo_dark" class="form-control" accept="image/*" data-preview="#prev_logo_dark">
                    <div class="row g-2 mt-2">
                        <div class="col-7">
                            @php $ldf = $branding['logo_dark_fit'] ?? 'contain'; @endphp
                            <select class="form-select" name="logo_dark_fit">
                                <option value="contain" @selected($ldf==='contain')>Adatta (non tagliare)</option>
                                <option value="cover" @selected($ldf==='cover')>Ritaglia (riempi area)</option>
                            </select>
                            <div class="form-hint mt-1"><i class="bi bi-arrows-angle-expand"></i> Come adattare il logo scuro.</div>
                        </div>
                        <div class="col-5 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" data-mp-open data-mp-target-input="logo_dark_id" data-mp-target-preview="#prev_logo_dark" title="Seleziona da archivio">
                                <i class="bi bi-images"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-mp-clear="logo_dark_id" data-mp-clear-preview="#prev_logo_dark" title="Rimuovi">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="media-preview mt-2" style="background:#0b1220">
                        @php $m = !empty($branding['logo_dark_id']) ? Media::find((int)$branding['logo_dark_id']) : null; @endphp
                        <img id="prev_logo_dark" src="{{ $m?->variantUrl('thumb') ?: $m?->url }}" class="img-fluid w-180" alt="">
                    </div>
                </div>

                {{-- Favicon --}}
                <div class="col-md-4">
                    <label class="form-label">Favicon</label>
                    <input type="file" name="favicon" class="form-control" accept="image/*" data-preview="#prev_favicon">
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" data-mp-open data-mp-target-input="favicon_id" data-mp-target-preview="#prev_favicon" title="Seleziona da archivio">
                            <i class="bi bi-images"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-mp-clear="favicon_id" data-mp-clear-preview="#prev_favicon" title="Rimuovi">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="media-preview mt-2" style="min-height:72px">
                        @php $m = !empty($branding['favicon_id']) ? Media::find((int)$branding['favicon_id']) : null; @endphp
                        <img id="prev_favicon" src="{{ $m?->variantUrl('thumb') ?: $m?->url }}" class="img-thumbnail w-64" alt="Favicon">
                    </div>
                    <div class="form-hint mt-1"><i class="bi bi-aspect-ratio"></i> 64×64 o 512×512 (PNG).</div>
                </div>

                {{-- Colore tema + anteprima --}}
                <div class="col-md-5">
                    <label class="form-label">Colore tema</label>
                    @php $tc = $branding['theme_color'] ?? '#0d6efd'; @endphp
                    <div class="d-flex gap-2">
                        <input type="color" class="form-control form-control-color" id="themeColorPicker" value="{{ $tc }}">
                        <input type="text" name="theme_color" id="themeColorHex" class="form-control" value="{{ $tc }}" placeholder="#0d6efd">
                    </div>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Anteprima tema</label>
                    <div class="theme-preview" id="themePreview">
                        <div class="tp-header"><i class="bi bi-brush"></i> Header di esempio</div>
                        <div class="tp-body">
                            <div class="mb-2 text-muted">Questo blocco mostra come appare il colore primario.</div>
                            <button type="button" class="tp-btn"><i class="bi bi-lightning-charge"></i> Azione primaria</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=branding" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva Branding</button>
            </div>
        </form>
    @endif

    {{-- ================= COMPANY ================= --}}
    @if($active==='company')
        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="company">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ragione sociale</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                        <input class="form-control" name="name" value="{{ $company['name'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">P.IVA</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input class="form-control" name="vat" value="{{ $company['vat'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" value="{{ $company['email'] }}">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Indirizzo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input class="form-control" name="address" value="{{ $company['address'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Città</label>
                    <input class="form-control" name="city" value="{{ $company['city'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">CAP</label>
                    <input class="form-control" name="zip" value="{{ $company['zip'] }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Prov.</label>
                    <input class="form-control" name="province" value="{{ $company['province'] }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Telefono</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input class="form-control" name="phone" value="{{ $company['phone'] }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nazione</label>
                    <input class="form-control" name="country" value="{{ $company['country'] }}">
                </div>

                {{-- 🔹 Dati bancari / fiscali --}}
                <div class="col-md-6">
                    <label class="form-label">Banca</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-bank"></i></span>
                        <input class="form-control" name="bank" value="{{ $company['bank'] }}">
                    </div>
                    <div class="form-hint mt-1">Es: Intesa Sanpaolo – Filiale Olbia.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">IBAN</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-credit-card-2-front"></i></span>
                        <input class="form-control" name="iban" value="{{ $company['iban'] }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">BIC / SWIFT</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input class="form-control" name="bic" value="{{ $company['bic'] }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">PEC</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-check"></i></span>
                        <input type="email" class="form-control" name="pec" value="{{ $company['pec'] }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Codice SDI</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc"></i></span>
                        <input class="form-control" name="sdi" value="{{ $company['sdi'] }}">
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=company" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Annulla
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-save2 me-1"></i> Salva Azienda
                </button>
            </div>
        </form>
    @endif

    {{-- ================= CRM / PREVENTIVI ================= --}}
    @if($active==='crm')
        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="crm">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Testo introduttivo predefinito</label>
                    <textarea name="quote_intro_default" class="form-control" rows="6">{{ $crm['quote_intro_default'] }}</textarea>
                    <div class="form-hint mt-1">
                        Questo testo verrà proposto automaticamente nei nuovi preventivi, ma potrai modificarlo caso per caso.
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Condizioni di pagamento predefinite</label>
                    <textarea name="quote_payment_terms_default" class="form-control" rows="4">{{ $crm['quote_payment_terms_default'] }}</textarea>
                    <div class="form-hint mt-1">
                        Es: <code>30% all'ordine, 40% ad avanzamento lavori, saldo a 30 giorni data fattura. Pagamento tramite bonifico bancario.</code>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Termini di vendita (contratto)</label>
                    <textarea name="contract_terms" class="form-control" rows="10">{{ $crm['contract_terms'] ?? '' }}</textarea>
                    <div class="form-hint mt-1">
                        Testo delle condizioni generali di vendita che vuoi compaia nel contratto PDF
                        (se vuoto, useremo il testo standard di default).
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Informativa Privacy / GDPR (contratto)</label>
                    <textarea name="contract_privacy" class="form-control" rows="8">{{ $crm['contract_privacy'] ?? '' }}</textarea>
                    <div class="form-hint mt-1">
                        Informativa sul trattamento dei dati da usare nel contratto (se vuota, useremo il testo standard di default).
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Coordinate bancarie per il pagamento</label>
                    <textarea name="bank_details" class="form-control" rows="4">{{ $crm['bank_details'] ?? '' }}</textarea>
                    <div class="form-hint mt-1">
                        Es.: <code>Intestato a: XYZ S.r.l. – IBAN: IT00A0000000000000000000000 – Banca ABC, Filiale di ...</code><br>
                        Questo testo verrà stampato nella sezione “Coordinate bancarie per il pagamento” del contratto PDF.
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=crm" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Annulla
                </a>
                <button class="btn btn-primary">
                    <i class="bi bi-save2 me-1"></i> Salva impostazioni CRM
                </button>
            </div>
        </form>
    @endif

    {{-- ================= SEO ================= --}}
    @if($active==='seo')
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="seo">
            <input type="hidden" name="og_image_id" id="og_image_id" value="{{ $seo['og_image_id'] ?? '' }}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta Title (default)</label>
                    <input class="form-control" name="meta_title" value="{{ $seo['meta_title'] }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Robots</label>
                    <input class="form-control" name="robots" list="robotsList" value="{{ $seo['robots'] }}" placeholder="es. index, follow">
                    <datalist id="robotsList">
                        <option value="index, follow"></option>
                        <option value="noindex, follow"></option>
                        <option value="index, nofollow"></option>
                        <option value="noindex, nofollow"></option>
                    </datalist>
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Description (default)</label>
                    <textarea class="form-control" name="meta_description" rows="2">{{ $seo['meta_description'] }}</textarea>
                    <div class="form-hint mt-1"><i class="bi bi-type"></i> Consigliati 140–160 caratteri.</div>
                </div>
                {{-- ROBOT--}}
                <div class="col-12 mt-3">
                    <label class="form-label">Regole aggiuntive robots.txt</label>
                    <textarea class="form-control" name="robots_extra" rows="3">{{ $seo['robots_extra'] ?? '' }}</textarea>
                    <div class="form-hint mt-1">
                        Una direttiva per riga, es. <code>Disallow: /cart</code>
                    </div>
                </div>


                <div class="col-md-6">
                    <label class="form-label">OpenGraph Image</label>
                    <input type="file" name="og_image" class="form-control" accept="image/*" data-preview="#prev_og">
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" data-mp-open data-mp-target-input="og_image_id" data-mp-target-preview="#prev_og" title="Seleziona da archivio">
                            <i class="bi bi-images"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-mp-clear="og_image_id" data-mp-clear-preview="#prev_og" title="Rimuovi">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="media-preview mt-2">
                        @php $m = !empty($seo['og_image_id']) ? Media::find((int)$seo['og_image_id']) : null; @endphp
                        <img id="prev_og" src="{{ $m?->variantUrl('thumb') ?? $m?->url }}" class="img-fluid w-180" alt="">
                    </div>
                    <div class="form-hint mt-1"><i class="bi bi-image"></i> 1200×630 consigliato (JPG/PNG).</div>
                </div>
            </div>
            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=seo" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Annulla</a>
                <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva SEO</button>
            </div>
        </form>
    @endif

    {{-- ================= ANALYTICS ================= --}}
    @if($active==='analytics')
        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="analytics">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">GA4 Measurement ID</label>
                    <input class="form-control" name="ga4_id" placeholder="G-XXXXXXXX" value="{{ $analytics['ga4_id'] }}">
                    <div class="form-hint mt-1"><i class="bi bi-google"></i> Formato: <code>G-XXXXXXX</code></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Google Tag Manager ID</label>
                    <input class="form-control" name="gtm_id" placeholder="GTM-XXXXXX" value="{{ $analytics['gtm_id'] }}">
                    <div class="form-hint mt-1"><i class="bi bi-box-seam"></i> Formato: <code>GTM-XXXXXX</code></div>
                </div>
            </div>
            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=analytics" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Annulla</a>
                <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva Analytics</button>
            </div>
        </form>
    @endif

    {{-- ================= CALENDAR ================= --}}
    @if($active==='calendar')

        @if(session('sync_errors') && is_array(session('sync_errors')) && count(session('sync_errors')))
            <div class="alert alert-warning mt-3">
                <strong>Dettaglio errori Sync (prime {{ count(session('sync_errors')) }})</strong>
                <pre class="mb-0" style="white-space:pre-wrap;">{{ implode("\n\n", session('sync_errors')) }}</pre>
                <div class="small text-muted mt-2">
                    Tutti gli errori completi sono anche in <code>storage/logs/laravel.log</code>.
                </div>
            </div>
        @endif


        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="calendar">
            <input type="hidden" name="sync_direction" value="two_way">
            <input type="hidden" name="sync_past_days" value="30">
            <input type="hidden" name="sync_future_days" value="180">
            <input type="hidden" name="sync_interval_minutes" value="5">
            <input type="hidden" name="timezone" value="Europe/Rome">

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="google_enabled"
                               name="google_enabled" value="1" @checked(!empty($calendar['google_enabled']))>
                        <label class="form-check-label" for="google_enabled">
                            Sincronizza con Google Calendar
                        </label>
                    </div>
                    <div class="form-hint mt-1">
                        Sync bidirezionale appuntamenti ↔ Google Calendar.
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Redirect URI (da inserire in Google Cloud)</label>
                    <input class="form-control" value="{{ $calendar['google_redirect_uri'] ?? route('admin.settings.google.callback') }}" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Google OAuth Client ID</label>
                    <input class="form-control" name="google_client_id"
                           value="{{ $calendar['google_client_id'] ?? '' }}"
                           placeholder="xxxxx.apps.googleusercontent.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Google OAuth Client Secret</label>
                    <input class="form-control" name="google_client_secret" value=""
                           placeholder="{{ !empty($calendar['google_secret_set']) ? '•••••• (già impostato)' : 'Inserisci secret' }}">
                    <div class="form-hint mt-1">
                        Per sicurezza non viene mostrato. Lascia vuoto per non modificarlo.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Calendar ID</label>
                    <input class="form-control" name="google_calendar_id"
                           value="{{ $calendar['google_calendar_id'] ?? 'primary' }}"
                           placeholder="primary oppure ID calendario">
                    <div class="form-hint mt-1">Di default <code>primary</code>.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Client ID fallback (import da Google → CRM)</label>
                    <input class="form-control" name="default_client_id" type="number" min="1"
                           value="{{ $calendar['default_client_id'] ?? 1 }}">
                    <div class="form-hint mt-1">
                        Serve perché <code>crm_appointments.client_id</code> è NOT NULL: quando importi eventi creati su Google
                        il CRM deve assegnarli a un cliente.
                    </div>
                </div>


                <div class="col-md-6">
                    <label class="form-label">Stato connessione</label>
                    <div class="p-2 border rounded bg-light">
                        @if(!empty($calendar['connected']))
                            <div><i class="bi bi-check2-circle text-success"></i>
                                Connesso: <strong>{{ $calendar['connected_email'] ?? 'Google account' }}</strong>
                            </div>
                            <div class="small text-muted">
                                Ultima sync:
                                {{ !empty($calendar['last_synced_at']) ? $calendar['last_synced_at']->format('d/m/Y H:i') : '—' }}
                            </div>
                        @else
                            <div><i class="bi bi-x-circle text-danger"></i> Non connesso</div>
                        @endif
                    </div>

                    {{-- ✅ NESSUN FORM ANNIDATO: uso formaction --}}
                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <a class="btn btn-outline-primary" href="{{ route('admin.settings.google.connect') }}">
                            <i class="bi bi-google"></i> Connetti Google
                        </a>

                        <button class="btn btn-outline-danger" type="submit"
                                formaction="{{ route('admin.settings.google.disconnect') }}"
                                formmethod="POST"
                            @disabled(empty($calendar['connected']))>
                            <i class="bi bi-unlink"></i> Disconnetti
                        </button>

                        <button class="btn btn-outline-secondary" type="submit"
                                formaction="{{ route('admin.settings.google.sync') }}"
                                formmethod="POST"
                            @disabled(empty($calendar['connected']))>
                            <i class="bi bi-arrow-repeat"></i> Sync adesso
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=calendar" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Annulla
                </a>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save2 me-1"></i> Salva Calendario
                </button>
            </div>
        </form>
    @endif



    {{-- ================= TYPOGRAPHY ================= --}}
    @if($active==='typography')
        <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-soft p-3">
            @csrf
            <input type="hidden" name="tab" value="typography">

            @php
                $fontOptions = [
                    'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins',
                    'Playfair Display','Merriweather','Source Sans 3','Raleway',
                    'Nunito','Oswald','PT Serif','Work Sans','Rubik',
                    'Arial','Verdana','Times New Roman','Georgia','Tahoma','Trebuchet MS','Courier New',
                ];
                $printFont = fn($f) => $f;
            @endphp

            <div class="row g-4">
                {{-- BODY --}}
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6 class="mb-3"><i class="bi bi-paragraph me-1"></i> Testo (body)</h6>
                        <label class="form-label">Font</label>
                        <select name="body_family" class="form-select font-picker" data-preview="#prev_body">
                            <option value="">— Default —</option>
                            @foreach($fontOptions as $f)
                                <option value="{{ $f }}" @selected(($typography['body_family'] ?? '') === $f)>{{ $printFont($f) }}</option>
                            @endforeach
                        </select>

                        <div class="row mt-2">
                            <div class="col-6">
                                <label class="form-label">Peso</label>
                                <select name="body_weight" class="form-select weight-picker" data-preview="#prev_body">
                                    @foreach(['400'=>'Normal','500'=>'Medium','600'=>'SemiBold','700'=>'Bold'] as $w=>$lbl)
                                        <option value="{{ $w }}" @selected(($typography['body_weight'] ?? '400') === $w)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input italic-picker" type="checkbox" name="body_italic" value="1"
                                           data-preview="#prev_body" @checked(!empty($typography['body_italic']))>
                                    <label class="form-check-label">Italic</label>
                                </div>
                            </div>
                        </div>

                        <label class="form-label mt-3">Dimensione body</label>
                        <input class="form-control" name="body_size" value="{{ $typography['body_size'] ?? '1rem' }}" placeholder="es. 1rem, 16px">

                        <label class="form-label mt-2">Dimensione lead</label>
                        <input class="form-control" name="lead_size" value="{{ $typography['lead_size'] ?? '1.25rem' }}">

                        <div id="prev_body" class="mt-3 p-2 border rounded bg-light">The quick brown fox…</div>
                    </div>
                </div>

                {{-- HEADINGS --}}
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6 class="mb-3"><i class="bi bi-type-bold me-1"></i> Titoli (h2–h6)</h6>
                        <label class="form-label">Font</label>
                        <select name="heading_family" class="form-select font-picker" data-preview="#prev_headings">
                            <option value="">— Default —</option>
                            @foreach($fontOptions as $f)
                                <option value="{{ $f }}" @selected(($typography['heading_family'] ?? '') === $f)>{{ $printFont($f) }}</option>
                            @endforeach
                        </select>

                        <div class="row mt-2">
                            <div class="col-6">
                                <label class="form-label">Peso</label>
                                <select name="heading_weight" class="form-select weight-picker" data-preview="#prev_headings">
                                    @foreach(['400'=>'Normal','500'=>'Medium','600'=>'SemiBold','700'=>'Bold'] as $w=>$lbl)
                                        <option value="{{ $w }}" @selected(($typography['heading_weight'] ?? '700') === $w)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input italic-picker" type="checkbox" name="heading_italic" value="1"
                                           data-preview="#prev_headings" @checked(!empty($typography['heading_italic']))>
                                    <label class="form-check-label">Italic</label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            @foreach(['h2_size'=>'H2','h3_size'=>'H3','h4_size'=>'H4','h5_size'=>'H5','h6_size'=>'H6'] as $key=>$lbl)
                                <div class="col-6">
                                    <label class="form-label">{{ $lbl }}</label>
                                    <input class="form-control" name="{{ $key }}" value="{{ $typography[$key] ?? '' }}">
                                </div>
                            @endforeach
                        </div>

                        <div id="prev_headings" class="mt-3 p-2 border rounded bg-light">
                            <div class="h2 mb-1">Titolo H2</div>
                            <div class="h5 text-muted">Sottotitolo H5</div>
                        </div>
                    </div>
                </div>

                {{-- TITLE (H1) --}}
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6 class="mb-3"><i class="bi bi-type-h1 me-1"></i> Titolo principale (H1)</h6>
                        <label class="form-label">Font</label>
                        <select name="title_family" class="form-select font-picker" data-preview="#prev_title">
                            <option value="">— Usa font “Titoli” —</option>
                            @foreach($fontOptions as $f)
                                <option value="{{ $f }}" @selected(($typography['title_family'] ?? '') === $f)>{{ $printFont($f) }}</option>
                            @endforeach
                        </select>

                        <div class="row mt-2">
                            <div class="col-6">
                                <label class="form-label">Peso</label>
                                <select name="title_weight" class="form-select weight-picker" data-preview="#prev_title">
                                    @foreach(['400'=>'Normal','500'=>'Medium','600'=>'SemiBold','700'=>'Bold'] as $w=>$lbl)
                                        <option value="{{ $w }}" @selected(($typography['title_weight'] ?? '700') === $w)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input italic-picker" type="checkbox" name="title_italic" value="1"
                                           data-preview="#prev_title" @checked(!empty($typography['title_italic']))>
                                    <label class="form-check-label">Italic</label>
                                </div>
                            </div>
                        </div>

                        <label class="form-label mt-2">Dimensione H1</label>
                        <input class="form-control" name="h1_size" value="{{ $typography['h1_size'] ?? '' }}">

                        <div id="prev_title" class="mt-3 p-2 border rounded bg-light display-6">Titolo H1 di esempio</div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="?tab=typography" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Annulla</a>
                <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva Tipografia</button>
            </div>
        </form>

        {{-- Script anteprima tipografia --}}
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                function stackFor(font){
                    const map = {
                        'Arial': 'Arial, Helvetica, sans-serif',
                        'Verdana': 'Verdana, Geneva, sans-serif',
                        'Times New Roman': '\'Times New Roman\', Times, serif',
                        'Georgia': 'Georgia, \'Times New Roman\', Times, serif',
                        'Tahoma': 'Tahoma, Geneva, sans-serif',
                        'Trebuchet MS': '\'Trebuchet MS\', Helvetica, sans-serif',
                        'Courier New': '\'Courier New\', Courier, monospace'
                    };
                    if (!font) return 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans"';
                    return (map[font] ? map[font] : `'${font}', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans"`);
                }
                function applyPreview(sel){
                    const previewSel = sel.getAttribute('data-preview');
                    const preview = document.querySelector(previewSel);
                    if (!preview) return;
                    const wrapper = sel.closest('.border.rounded');
                    const weightSel = wrapper.querySelector('.weight-picker[data-preview="'+previewSel+'"]');
                    const italicChk = wrapper.querySelector('.italic-picker[data-preview="'+previewSel+'"]');
                    const family = sel.value;
                    const weight = weightSel ? weightSel.value : '400';
                    const italic = italicChk ? italicChk.checked : false;
                    preview.style.fontFamily = stackFor(family);
                    preview.style.fontWeight = weight;
                    preview.style.fontStyle  = italic ? 'italic' : 'normal';
                }
                document.querySelectorAll('.font-picker').forEach(s => { s.addEventListener('change', () => applyPreview(s)); applyPreview(s); });
                document.querySelectorAll('.weight-picker').forEach(s => { s.addEventListener('change', () => applyPreview(s)); applyPreview(s); });
                document.querySelectorAll('.italic-picker').forEach(c => {
                    c.addEventListener('change', () => {
                        const wrapper = c.closest('.border.rounded');
                        const fp = wrapper.querySelector('.font-picker');
                        if (fp) applyPreview(fp);
                    });
                });
            });
        </script>
    @endif

    {{-- ============ MEDIA PICKER (modal + JS) ============ --}}
    <div class="modal fade" id="mediaPicker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-images me-2"></i>Seleziona media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <input type="search" class="form-control" id="mpSearch" placeholder="Cerca per nome o titolo…">
                        </div>
                        <div class="col-md-3">
                            <select id="mpPerPage" class="form-select">
                                <option value="24">24 per pagina</option>
                                <option value="48">48 per pagina</option>
                                <option value="96">96 per pagina</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <div id="mpCounter" class="text-muted small"></div>
                        </div>
                    </div>
                    <div id="mpGrid" class="row g-3"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" id="mpPrev" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Prec</button>
                        <button type="button" id="mpNext" class="btn btn-outline-secondary btn-sm">Succ <i class="bi bi-chevron-right"></i></button>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Script comuni (preview file + color sync + MediaPicker) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            // ===== File preview
            document.querySelectorAll('input[type="file"][data-preview]').forEach(inp=>{
                inp.addEventListener('change', ()=>{
                    const target = document.querySelector(inp.dataset.preview);
                    const file = inp.files && inp.files[0];
                    if (!file || !target) return;
                    const reader = new FileReader();
                    reader.onload = e => target.src = e.target.result;
                    reader.readAsDataURL(file);
                });
            });

            // ===== Color picker sync + theme preview
            const picker = document.getElementById('themeColorPicker');
            const hex    = document.getElementById('themeColorHex');
            const tp     = document.getElementById('themePreview');
            function applyPreviewColor(val){ if (tp) tp.style.setProperty('--preview-primary', (val||'').trim()); }
            function normalizeHex(v){ v=(v||'').trim(); if(!v) return ''; if(!v.startsWith('#')) v='#'+v; if(v.length===4){ v='#'+v.substring(1).split('').map(c=>c+c).join(''); } return v; }
            if (picker && hex){
                const syncFromPicker = ()=>{ hex.value = picker.value; applyPreviewColor(picker.value); };
                const syncFromHex = ()=>{ const v = normalizeHex(hex.value); hex.value = v; if (/^#([0-9a-f]{6})$/i.test(v)) picker.value = v; applyPreviewColor(v); };
                picker.addEventListener('input', syncFromPicker);
                hex.addEventListener('input', syncFromHex);
                applyPreviewColor(picker ? picker.value : hex?.value);
            }

            // ===== Media Picker
            const modalEl = document.getElementById('mediaPicker');
            const mp = {
                modal: (window.bootstrap && bootstrap.Modal) ? new bootstrap.Modal(modalEl) : null,
                targetInputId: null,
                targetPreviewSel: null,
                page: 1,
                lastPage: 1,
                perPage: 24,
                q: '',
            };

            function renderGrid(items){
                const grid = document.getElementById('mpGrid');
                grid.innerHTML = '';
                if (!items || !items.length) {
                    grid.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox"></i> Nessun media trovato</div>';
                    return;
                }
                items.forEach(it=>{
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3 col-xl-2';
                    col.innerHTML = `
                        <div class="card h-100 border-0">
                            ${String(it.mime||'').startsWith('image/')
                        ? `<img src="${it.thumb||it.url}" alt="${(it.alt||'')}" class="mp-thumb">`
                        : `<div class="mp-thumb d-flex align-items-center justify-content-center">📎</div>`
                    }
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-pick="${it.id}"><i class="bi bi-check2-circle me-1"></i> Scegli</button>
                            <div class="small text-truncate mt-1" title="${it.title||it.original_name||''}">${it.title||''}</div>
                        </div>`;
                    grid.appendChild(col);
                });
            }

            function updatePager(meta){
                mp.lastPage = meta.last_page || 1;
                document.getElementById('mpPrev').disabled = (mp.page<=1);
                document.getElementById('mpNext').disabled = (mp.page>=mp.lastPage);
                const counter = document.getElementById('mpCounter');
                const showingFrom = (mp.page-1)*mp.perPage + 1;
                const showingTo   = Math.min(mp.page*mp.perPage, meta.total||0);
                counter.textContent = meta.total ? `Mostrati ${showingFrom}-${showingTo} di ${meta.total}` : '';
            }

            async function loadPage(){
                const params = new URLSearchParams({ ajax:'1', page:String(mp.page), per_page:String(mp.perPage) });
                if (mp.q) params.set('q', mp.q);
                const res = await fetch(`{{ route('admin.media.index') }}?`+params.toString(), { headers: { 'Accept':'application/json' } });
                if (!res.ok) { console.error('Media ajax error'); return; }
                const json = await res.json();
                renderGrid(json.data||[]);
                updatePager(json.meta||{});
            }

            // open picker
            document.querySelectorAll('[data-mp-open]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    mp.targetInputId    = btn.getAttribute('data-mp-target-input');
                    mp.targetPreviewSel = btn.getAttribute('data-mp-target-preview');
                    mp.page=1; mp.q=''; mp.perPage = parseInt(document.getElementById('mpPerPage').value,10)||24;
                    document.getElementById('mpSearch').value = '';
                    loadPage();
                    mp.modal && mp.modal.show();
                });
            });

            // clear selection
            document.querySelectorAll('[data-mp-clear]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const id = btn.getAttribute('data-mp-clear');
                    const previewSel = btn.getAttribute('data-mp-clear-preview');
                    const inp = document.getElementById(id);
                    if (inp) inp.value = '';
                    if (previewSel) {
                        const img = document.querySelector(previewSel);
                        if (img) img.src = '';
                    }
                });
            });

            // search & per-page
            document.getElementById('mpSearch').addEventListener('input', (e)=>{
                mp.q = e.target.value.trim();
                mp.page = 1;
                loadPage();
            });
            document.getElementById('mpPerPage').addEventListener('change', (e)=>{
                mp.perPage = parseInt(e.target.value,10)||24;
                mp.page = 1;
                loadPage();
            });

            // pagination
            document.getElementById('mpPrev').addEventListener('click', ()=>{ if (mp.page>1){ mp.page--; loadPage(); }});
            document.getElementById('mpNext').addEventListener('click', ()=>{ if (mp.page<mp.lastPage){ mp.page++; loadPage(); }});

            // pick handler (event delegation)
            document.getElementById('mpGrid').addEventListener('click', (e)=>{
                const btn = e.target.closest('[data-pick]');
                if (!btn) return;
                const id = btn.getAttribute('data-pick');
                // recupera info card per anteprima
                const card = btn.closest('.card');
                const img  = card ? card.querySelector('img.mp-thumb') : null;
                const previewUrl = img ? img.getAttribute('src') : null;

                const hidden = document.getElementById(mp.targetInputId);
                if (hidden) hidden.value = id;
                if (mp.targetPreviewSel && previewUrl) {
                    const prev = document.querySelector(mp.targetPreviewSel);
                    if (prev) prev.src = previewUrl;
                }
                mp.modal && mp.modal.hide();
            });
        });
    </script>
@endsection
