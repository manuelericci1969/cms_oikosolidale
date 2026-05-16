{{-- resources/views/admin/pages/editV2.blade.php --}}
@extends('admin.layout')
@section('no_vite', true)
@section('title', 'Modifica Pagina (V2)')

@section('content')
    @php
        /**
         * Contenuto del Page Builder (V2)
         *
         * Supporta:
         * - contenuto già in formato "builder" (array o JSON di array)
         * - vecchio contenuto HTML (lo incapsula in una sezione + blocco text)
         */

        $rawContent = $page->content;
        $pbContent  = [];

        if (is_array($rawContent)) {
            $pbContent = $rawContent;
        } elseif (is_string($rawContent)) {
            $decoded = json_decode($rawContent, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $pbContent = $decoded;
            } else {
                $trimmed = trim($rawContent);
                if ($trimmed !== '') {
                    $pbContent = [
                        [
                            'id'     => 'legacy-sec-1',
                            'blocks' => [
                                [
                                    'id'      => 'legacy-block-1',
                                    'columns' => 12,
                                    'type'    => 'text',
                                    'content' => $rawContent,
                                    'style'   => (object)[],
                                ],
                            ],
                        ],
                    ];
                }
            }
        }

        if (!is_array($pbContent)) $pbContent = [];

        // Lista font da usare anche nel Rich Text
        $pbFonts = [
            'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins',
            'Playfair Display','Merriweather','Source Sans 3','Raleway',
            'Nunito','Oswald','PT Serif','Work Sans','Rubik',
            'Arial','Verdana','Times New Roman','Georgia','Tahoma','Trebuchet MS','Courier New',
        ];
    @endphp

    {{-- CSS del builder (Bootstrap è già incluso nel layout admin) --}}
    <link rel="stylesheet" href="{{ asset('pb/pb.css') }}">

    <style>
        .pb-richtext-editor img{ max-width:100%; height:auto; }

        .pb-img-selected{
            outline:2px solid #0d6efd;
            outline-offset:2px;
        }

        .pb-img-panel{
            position:fixed;
            z-index:1080;
            border-radius:0.5rem;
        }
        .pb-img-panel.pb-img-panel--visible{ display:block; }
        .pb-img-panel:not(.pb-img-panel--visible){ display:none; }

        .page-editor-layout { align-items:flex-start; }

        .settings-col-toggle {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:.5rem;
        }
        .settings-col-toggle-title { font-weight:600; font-size:.9rem; }

        .page-editor-layout--settings-collapsed #settingsContent { display:none; }

        .page-editor-layout--settings-collapsed #builderCol {
            flex:0 0 100%;
            max-width:100%;
            width:100%;
        }
        .page-editor-layout--settings-collapsed #settingsCol {
            flex:0 0 auto;
            max-width:3rem;
            padding-right:0;
        }

        @media (max-width: 991.98px) {
            .page-editor-layout--settings-collapsed #settingsCol,
            .page-editor-layout--settings-collapsed #builderCol { max-width:100%; }
        }

        .page-editor-footer {
            position:sticky;
            bottom:0;
            z-index:1010;
            background:#fff;
            border-top:1px solid #dee2e6;
            margin-top:1rem;
            padding:.5rem 0;
        }
        .page-editor-footer-inner {
            display:flex;
            justify-content:flex-end;
            gap:.5rem;
            align-items:center;
        }
        .page-editor-footer-inner .status-label {
            margin-right:auto;
            font-size:.8rem;
            color:#6c757d;
        }
    </style>

    <script>
        window.__PB_CONTENT__ = @json($pbContent);
        window.PB_FONTS = @json($pbFonts);
        window.PB_MEDIA_PICKER_URL = @json(url('/admin/media/browse'));
        window.PB_COMPONENTS_URL = @json(route('admin.api.page-components.index'));
        window.PB_COMPONENTS_STORE_URL = @json(route('admin.api.page-components.store'));
    </script>

    <div class="container-fluid">
        {{-- Topbar --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
                <h1 class="h4 mb-0">
                    <i class="bi bi-pencil-square me-2"></i>
                    Modifica (V2): {{ $page->title }}
                </h1>

                @if($page->status)
                    <span class="badge text-bg-{{ $page->status === 'published' ? 'success' : ($page->status === 'draft' ? 'secondary' : 'warning') }}">
                        <i class="bi bi-{{ $page->status === 'published' ? 'check2-circle' : ($page->status === 'draft' ? 'file-earmark' : 'exclamation-triangle') }}"></i>
                        {{ ucfirst($page->status) }}
                    </span>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($page->slug)
                    <a href="{{ route('page.show', $page->slug) }}" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Anteprima
                    </a>
                @endif

                    <a href="{{ route('admin.pages.edit_v3', $page) }}" class="btn btn-outline-dark">
                        <i class="bi bi-window-stack me-1"></i> Vai a V3
                    </a>

                <a href="{{ route('admin.pages.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-1"></i> Torna all’elenco
                </a>
            </div>
        </div>

        {{-- Form --}}
        <form id="pageForm" method="post" action="{{ route('admin.pages.update', $page) }}">
            @csrf
            @method('PATCH')

            {{-- JSON serializzato del builder --}}
            <input type="hidden" id="contentJson" name="content">
            <input type="hidden" name="status" id="statusField" value="{{ old('status', $page->status) }}">

            {{-- per gestire "slug vuoto = mantieni invariato" --}}
            <input type="hidden" id="currentSlug" value="{{ $page->slug }}">

            {{-- DI DEFAULT: impostazioni & SEO già chiusi --}}
            <div class="row g-3 page-editor-layout page-editor-layout--settings-collapsed" id="pageEditorLayout">
                {{-- Sidebar Impostazioni / SEO --}}
                <div class="col-12 col-lg-3 settings-col" id="settingsCol">
                    <div class="settings-col-toggle">
                        <button type="button"
                                class="btn btn-light btn-sm border"
                                id="settingsToggle"
                                title="Mostra impostazioni">
                            <i class="bi bi-layout-sidebar"></i>
                        </button>
                        <span class="settings-col-toggle-title d-none d-lg-inline">Impostazioni &amp; SEO</span>
                    </div>

                    <div id="settingsContent">
                        {{-- Impostazioni principali --}}
                        <div class="card mb-3">
                            <div class="card-header fw-semibold">
                                <i class="bi bi-gear me-2"></i> Impostazioni
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Titolo *</label>
                                    <input type="text" name="title" class="form-control"
                                           value="{{ old('title', $page->title) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slugInput" class="form-control"
                                           value="{{ old('slug', $page->slug) }}" autocomplete="off">
                                    <div class="form-text">Se lo lasci vuoto, viene mantenuto lo slug attuale.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Data pubblicazione</label>
                                    <input type="datetime-local" name="published_at" class="form-control"
                                           value="{{ old('published_at', $page->published_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Homepage</label>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_homepage" value="0">
                                        <input class="form-check-input" type="checkbox" id="switchHomepage"
                                               name="is_homepage" value="1"
                                            @checked(old('is_homepage', $page->is_homepage))>
                                        <label class="form-check-label" for="switchHomepage">Imposta come homepage</label>
                                    </div>
                                </div>

                                @php
                                    // ===== Layout pagina (meta.layout) =====
                                    $layoutMeta = is_array($page->meta['layout'] ?? null) ? $page->meta['layout'] : [];
                                    $storedW = $layoutMeta['width'] ?? 'standard';

                                    // in DB può esserci "standard" (controller) oppure "container" (vecchio)
                                    $storedWForSelect = in_array($storedW, ['standard','container'], true) ? 'container' : $storedW;

                                    $layoutWidth  = old('meta.layout.width', $storedWForSelect); // container|boxed|full
                                    $layoutGutter = old('meta.layout.gutter', $layoutMeta['gutter'] ?? 24);

                                    // distanza dall’alto
                                    $layoutTop    = old('meta.layout.top', $layoutMeta['top'] ?? 0);

                                    // ===== Sfondo pagina (meta.page_bg) =====
                                    $bgMeta = is_array($page->meta['page_bg'] ?? null) ? $page->meta['page_bg'] : [];
                                    $bgType = old('meta.page_bg.type', $bgMeta['type'] ?? 'none');

                                    // Color
                                    $bgColor = old('meta.page_bg.color', $bgMeta['color'] ?? '#ffffff');

                                    // Gradient: supporto sia nuovo formato (from/to/angle) sia vecchio (gradient[from|to|angle])
                                    $legacyGrad = is_array($bgMeta['gradient'] ?? null) ? $bgMeta['gradient'] : [];
                                    $gFrom   = old('meta.page_bg.from',  $bgMeta['from']  ?? ($legacyGrad['from'] ?? '#0d6efd'));
                                    $gTo     = old('meta.page_bg.to',    $bgMeta['to']    ?? ($legacyGrad['to']   ?? '#6610f2'));
                                    $gAngle  = old('meta.page_bg.angle', $bgMeta['angle'] ?? ($legacyGrad['angle']?? 135));

                                    // Image
                                    $bgImg   = is_array($bgMeta['image'] ?? null) ? $bgMeta['image'] : [];
                                    $iSrc    = old('meta.page_bg.image.src', $bgImg['src'] ?? '');
                                    $iPos    = old('meta.page_bg.image.position', $bgImg['position'] ?? 'center center');
                                    $iSize   = old('meta.page_bg.image.size', $bgImg['size'] ?? 'cover');
                                    $iRep    = old('meta.page_bg.image.repeat', $bgImg['repeat'] ?? 'no-repeat');
                                    $iAtt    = old('meta.page_bg.image.attachment', $bgImg['attachment'] ?? 'scroll');
                                    $iPar    = (bool) old('meta.page_bg.image.parallax', $bgImg['parallax'] ?? false);

                                    $ov      = is_array($bgImg['overlay'] ?? null) ? $bgImg['overlay'] : [];
                                    $ovEn    = (bool) old('meta.page_bg.image.overlay.enabled', $ov['enabled'] ?? false);
                                    $ovCol   = old('meta.page_bg.image.overlay.color', $ov['color'] ?? '#000000');
                                    $ovOp    = old('meta.page_bg.image.overlay.opacity', $ov['opacity'] ?? 0.35);
                                @endphp

                                <div class="mb-3">
                                    <label class="form-label">Larghezza pagina</label>
                                    <select name="meta[layout][width]" class="form-select">
                                        <option value="container" @selected($layoutWidth === 'container')>
                                            Standard (container centrato)
                                        </option>
                                        <option value="boxed" @selected($layoutWidth === 'boxed')>
                                            Boxed (max 1200px)
                                        </option>
                                        <option value="full" @selected($layoutWidth === 'full')>
                                            A tutta larghezza (monitor intero)
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        - <strong>Standard</strong>: come il container Bootstrap<br>
                                        - <strong>Boxed</strong>: max 1200px centrato<br>
                                        - <strong>Full</strong>: a tutta larghezza
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Distanza orizzontale dai bordi (px)</label>
                                    <input type="number"
                                           name="meta[layout][gutter]"
                                           class="form-control"
                                           min="0"
                                           max="120"
                                           value="{{ (int)$layoutGutter }}">
                                    <div class="form-text">
                                        Padding sinistra/destra interno alla pagina (es. 24).
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Distanza dall’alto (menu/header) (px)</label>
                                    <input type="number"
                                           name="meta[layout][top]"
                                           class="form-control"
                                           min="0"
                                           max="600"
                                           value="{{ (int)$layoutTop }}">
                                    <div class="form-text">
                                        Spazio sopra al contenuto della pagina (utile se il menu è fixed/sticky).
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="mb-2 fw-semibold">
                                    <i class="bi bi-palette me-2"></i> Sfondo pagina
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tipo sfondo</label>
                                    <select class="form-select" name="meta[page_bg][type]" id="pageBgType">
                                        <option value="none"     @selected($bgType==='none')>Nessuno</option>
                                        <option value="color"    @selected($bgType==='color')>Colore</option>
                                        <option value="gradient" @selected($bgType==='gradient')>Gradiente</option>
                                        <option value="image"    @selected($bgType==='image')>Immagine</option>
                                    </select>
                                </div>

                                {{-- COLOR --}}
                                <div class="mb-3" data-bg-panel="color">
                                    <label class="form-label">Colore</label>
                                    <input type="color"
                                           class="form-control form-control-color"
                                           name="meta[page_bg][color]"
                                           value="{{ $bgColor }}">
                                </div>

                                {{-- GRADIENT --}}
                                <div class="mb-3" data-bg-panel="gradient">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">Da</label>
                                            <input type="color"
                                                   class="form-control form-control-color"
                                                   name="meta[page_bg][from]"
                                                   value="{{ $gFrom }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">A</label>
                                            <input type="color"
                                                   class="form-control form-control-color"
                                                   name="meta[page_bg][to]"
                                                   value="{{ $gTo }}">
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <label class="form-label">Angolo (0–360)</label>
                                        <input type="number"
                                               class="form-control"
                                               name="meta[page_bg][angle]"
                                               min="0" max="360"
                                               value="{{ (int)$gAngle }}">
                                    </div>
                                </div>

                                {{-- IMAGE --}}
                                <div class="mb-3" data-bg-panel="image">
                                    <input type="hidden" name="meta[page_bg][image][src]" id="pageBgImageSrc" value="{{ $iSrc }}">
                                    <input type="hidden" name="meta[page_bg][image][parallax]" id="pageBgParallax" value="{{ $iPar ? 1 : 0 }}">

                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnPickPageBg">
                                            <i class="bi bi-image me-1"></i> Scegli immagine
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnClearPageBg">
                                            <i class="bi bi-x-lg me-1"></i> Rimuovi
                                        </button>
                                    </div>

                                    <div class="border rounded p-2 mb-3" style="background:#f8f9fa">
                                        <div class="small text-muted mb-1">Anteprima</div>
                                        <div id="pageBgPreview"
                                             class="rounded"
                                             style="height:90px;background-size:cover;background-position:center;background-repeat:no-repeat;display:flex;align-items:center;justify-content:center;color:#6c757d;font-size:.85rem;">
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Posizione</label>
                                            <select class="form-select" name="meta[page_bg][image][position]">
                                                <option value="center center" @selected($iPos==='center center')>Centro</option>
                                                <option value="top center"    @selected($iPos==='top center')>Alto</option>
                                                <option value="bottom center" @selected($iPos==='bottom center')>Basso</option>
                                                <option value="center left"   @selected($iPos==='center left')>Sinistra</option>
                                                <option value="center right"  @selected($iPos==='center right')>Destra</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Dimensione</label>
                                            <select class="form-select" name="meta[page_bg][image][size]">
                                                <option value="cover"   @selected($iSize==='cover')>Cover</option>
                                                <option value="contain" @selected($iSize==='contain')>Contain</option>
                                                <option value="auto"    @selected($iSize==='auto')>Auto</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Ripetizione</label>
                                            <select class="form-select" name="meta[page_bg][image][repeat]">
                                                <option value="no-repeat" @selected($iRep==='no-repeat')>No</option>
                                                <option value="repeat"    @selected($iRep==='repeat')>Sì</option>
                                                <option value="repeat-x"  @selected($iRep==='repeat-x')>Solo X</option>
                                                <option value="repeat-y"  @selected($iRep==='repeat-y')>Solo Y</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Scorrimento</label>
                                            <select class="form-select" name="meta[page_bg][image][attachment]" id="pageBgAttachment">
                                                <option value="scroll" @selected($iAtt==='scroll')>Normale</option>
                                                <option value="fixed"  @selected($iAtt==='fixed')>Parallax (fixed)</option>
                                            </select>
                                            <div class="form-text">Se scegli “fixed”, imposto anche <code>parallax=1</code>.</div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="meta[page_bg][image][overlay][enabled]" value="0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="pageBgOverlayEnabled"
                                               name="meta[page_bg][image][overlay][enabled]"
                                               value="1"
                                            @checked($ovEn)>
                                        <label class="form-check-label" for="pageBgOverlayEnabled">
                                            Overlay (scurisce l’immagine)
                                        </label>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">Colore overlay</label>
                                            <input type="color"
                                                   class="form-control form-control-color"
                                                   name="meta[page_bg][image][overlay][color]"
                                                   value="{{ $ovCol }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Opacità (0–0.9)</label>
                                            <input type="number"
                                                   class="form-control"
                                                   name="meta[page_bg][image][overlay][opacity]"
                                                   min="0" max="0.9" step="0.05"
                                                   value="{{ (float)$ovOp }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-text">
                                    Questo sfondo viene applicato al <code>&lt;body&gt;</code> del frontend.
                                </div>

                                <hr class="my-3">

                                <div class="mb-3">
                                    <label class="form-label">Visibilità meta</label>

                                    <input type="hidden" name="meta[show_title]" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switchShowTitle"
                                               name="meta[show_title]" value="1"
                                            @checked(old('meta.show_title', $page->meta['show_title'] ?? true))>
                                        <label class="form-check-label" for="switchShowTitle">Mostra il titolo</label>
                                    </div>

                                    <input type="hidden" name="meta[show_excerpt]" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switchShowExcerpt"
                                               name="meta[show_excerpt]" value="1"
                                            @checked(old('meta.show_excerpt', $page->meta['show_excerpt'] ?? false))>
                                        <label class="form-check-label" for="switchShowExcerpt">Mostra l’estratto</label>
                                    </div>

                                    <input type="hidden" name="meta[show_author]" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switchShowAuthor"
                                               name="meta[show_author]" value="1"
                                            @checked(old('meta.show_author', $page->meta['show_author'] ?? true))>
                                        <label class="form-check-label" for="switchShowAuthor">Mostra autore/ultimo editor</label>
                                    </div>

                                    <input type="hidden" name="meta[show_pubdate]" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switchShowPubdate"
                                               name="meta[show_pubdate]" value="1"
                                            @checked(old('meta.show_pubdate', $page->meta['show_pubdate'] ?? true))>
                                        <label class="form-check-label" for="switchShowPubdate">Mostra data pubblicazione</label>
                                    </div>

                                    <input type="hidden" name="meta[show_breadcrumbs]" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switchShowBreadcrumbs"
                                               name="meta[show_breadcrumbs]" value="1"
                                            @checked(old('meta.show_breadcrumbs', $page->meta['show_breadcrumbs'] ?? true))>
                                        <label class="form-check-label" for="switchShowBreadcrumbs">Mostra breadcrumb</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- SEO --}}
                        <div class="card mb-3">
                            <div class="card-header fw-semibold">
                                <i class="bi bi-search me-2"></i> SEO
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Meta Title <span id="mtCounter" class="text-muted small"></span>
                                    </label>
                                    <input type="text" name="meta[title]" class="form-control"
                                           value="{{ old('meta.title', $page->meta['title'] ?? '') }}"
                                           maxlength="60" id="metaTitleInput">
                                    <div class="form-text">Consigliato: max 60 caratteri.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Meta Description <span id="mdCounter" class="text-muted small"></span>
                                    </label>
                                    <textarea name="meta[description]" class="form-control" rows="3"
                                              maxlength="160" id="metaDescInput">{{ old('meta.description', $page->meta['description'] ?? '') }}</textarea>
                                    <div class="form-text">Consigliato: max 160 caratteri.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text"
                                           name="meta[keywords]"
                                           class="form-control"
                                           value="{{ old('meta.keywords', $page->meta['keywords'] ?? '') }}">
                                    <div class="form-text">
                                        Parole chiave separate da virgola (opzionale).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Builder --}}
                <div class="col-12 col-lg-9" id="builderCol">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-layers"></i>
                                <span class="fw-semibold">Page Builder</span>
                                <span class="text-muted small">Trascina le sezioni per riordinarle</span>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        id="pbTogglePreview">
                                    <i class="bi bi-eye me-1"></i> Anteprima
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-primary" id="pbAddSectionBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Aggiungi sezione
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-dark" id="pbAddComponentBtn">
                                    <i class="bi bi-box me-1"></i> Aggiungi componente
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-submit-status="draft"
                                        id="btnSaveDraftHeader">
                                    <i class="bi bi-floppy me-1"></i> Salva bozza
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-success"
                                        data-submit-status="published"
                                        id="btnPublishHeader">
                                    <i class="bi bi-check2-circle me-1"></i> Pubblica
                                </button>
                            </div>
                        </div>

                        <div class="card-body" id="builderContainer">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Caricamento builder…
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FOOTER STICKY --}}
            <div class="page-editor-footer">
                <div class="page-editor-footer-inner">
                    <span class="status-label">
                        Stato attuale: <strong>{{ ucfirst($page->status ?? 'draft') }}</strong>
                    </span>

                    <button type="button"
                            class="btn btn-outline-secondary btn-sm"
                            data-submit-status="draft"
                            id="btnSaveDraftFooter">
                        <i class="bi bi-floppy me-1"></i> Salva bozza
                    </button>

                    <button type="button"
                            class="btn btn-success btn-sm"
                            data-submit-status="published"
                            id="btnPublishFooter">
                        <i class="bi bi-check2-circle me-1"></i> Pubblica
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="pbComponentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box me-2"></i>Libreria componenti
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cerca componente</label>
                            <input type="text" class="form-control" id="pbComponentSearch" placeholder="Es. Hero, FAQ, CTA, Testimonials...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Categoria</label>
                            <input type="text" class="form-control" id="pbComponentCategory" placeholder="Es. hero, faq, cta">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" id="pbComponentSearchBtn">
                                <i class="bi bi-search me-1"></i> Cerca
                            </button>
                        </div>
                    </div>

                    <div id="pbComponentsList" class="row g-3">
                        <div class="col-12 text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Caricamento componenti…
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Entrypoint no-Vite --}}
    <script type="module" src="{{ asset('pb/index.js') }}?v={{ @filemtime(public_path('pb/index.js')) ?: time() }}"></script>

    <script>
        (function(){
            const form   = document.getElementById('pageForm');
            const hidden = document.getElementById('contentJson');
            const status = document.getElementById('statusField');

            const slugInput   = document.getElementById('slugInput');
            const currentSlug = document.getElementById('currentSlug');

            function ensureSlugNotEmpty(){
                if (!slugInput || !currentSlug) return;
                if ((slugInput.value || '').trim() === '') {
                    slugInput.value = currentSlug.value || '';
                }
            }

            function serialize(){
                ensureSlugNotEmpty();
                if (window.__PB_STATE__ && hidden) {
                    try {
                        hidden.value = JSON.stringify(window.__PB_STATE__.get());
                    } catch (e) {
                        console.error('PB serialize error', e);
                    }
                }
            }

            form && form.addEventListener('submit', function(){
                serialize();
            });

            document.querySelectorAll('[data-submit-status]').forEach(btn=>{
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    if (status) status.value = this.getAttribute('data-submit-status') || '';
                    serialize();
                    if (form) form.submit();
                });
            });
        })();
    </script>

    <script>
        // Counter SEO
        (function(){
            const mt = document.getElementById('metaTitleInput');
            const md = document.getElementById('metaDescInput');
            const mtC = document.getElementById('mtCounter');
            const mdC = document.getElementById('mdCounter');

            function bind(input, out, max){
                if(!input || !out) return;
                const tick = () => out.textContent = `(${input.value.length}/${max})`;
                input.addEventListener('input', tick);
                tick();
            }

            bind(mt, mtC, 60);
            bind(md, mdC, 160);
        })();
    </script>

    <script>
        // Toggle colonna Impostazioni/SEO
        (function(){
            const layout = document.getElementById('pageEditorLayout');
            const toggle = document.getElementById('settingsToggle');
            const icon   = toggle ? toggle.querySelector('i') : null;

            if (!layout || !toggle) return;

            function updateToggle() {
                const collapsed = layout.classList.contains('page-editor-layout--settings-collapsed');
                toggle.title = collapsed ? 'Mostra impostazioni' : 'Nascondi impostazioni';
                if (!icon) return;

                if (collapsed) {
                    icon.classList.remove('bi-layout-sidebar-inset-reverse');
                    icon.classList.add('bi-layout-sidebar');
                } else {
                    icon.classList.remove('bi-layout-sidebar');
                    icon.classList.add('bi-layout-sidebar-inset-reverse');
                }
            }

            toggle.addEventListener('click', function () {
                layout.classList.toggle('page-editor-layout--settings-collapsed');
                updateToggle();
            });

            updateToggle();
        })();
    </script>

    <script>
        // Resize + bordo immagini nel Rich Text
        (function(){
            const builder = document.getElementById('builderContainer');
            if (!builder) return;

            let panel   = null;
            let range   = null;
            let input   = null;
            let presets = null;
            let btnReset= null;
            let borderColorInp  = null;
            let borderWidthInp  = null;
            let borderRadiusInp = null;

            let currentImg      = null;
            let currentEditable = null;

            function clamp(value, min, max) {
                value = parseInt(value, 10);
                if (isNaN(value)) return 100;
                return Math.max(min, Math.min(max, value));
            }

            function syncEditable() {
                if (!currentEditable) return;
                currentEditable.dispatchEvent(new Event('input', { bubbles:true }));
            }

            function ensurePanel() {
                if (panel) return;

                panel = document.createElement('div');
                panel.className = 'pb-img-panel bg-white border shadow-sm p-2';

                panel.innerHTML = `
                    <div class="small text-muted mb-1">Larghezza immagine (10–100%)</div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="range" min="10" max="100" value="100" data-role="pb-img-range" class="form-range" />
                        <div class="d-flex align-items-center gap-1">
                            <input type="number" min="10" max="100" value="100" class="form-control form-control-sm" style="width:70px" data-role="pb-img-input">
                            <span>%</span>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-auto"><label class="form-label form-label-sm mb-0">Colore bordo</label></div>
                        <div class="col-auto"><input type="color" class="form-control form-control-color form-control-sm" data-role="pb-img-border-color"></div>
                        <div class="col-auto"><label class="form-label form-label-sm mb-0">Spessore</label></div>
                        <div class="col-auto"><input type="number" min="0" max="50" step="1" class="form-control form-control-sm" style="width:70px" data-role="pb-img-border-width"></div>
                        <div class="col-auto"><label class="form-label form-label-sm mb-0">Raggio</label></div>
                        <div class="col-auto"><input type="number" min="0" max="200" step="1" class="form-control form-control-sm" style="width:70px" data-role="pb-img-border-radius"></div>
                    </div>

                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-preset="25">25%</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-preset="50">50%</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-preset="75">75%</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-preset="100">100%</button>
                        <button type="button" class="btn btn-link btn-sm text-danger ms-auto" data-role="pb-img-reset">Reset</button>
                    </div>
                `;

                document.body.appendChild(panel);

                range          = panel.querySelector('[data-role="pb-img-range"]');
                input          = panel.querySelector('[data-role="pb-img-input"]');
                presets        = panel.querySelectorAll('[data-preset]');
                btnReset       = panel.querySelector('[data-role="pb-img-reset"]');
                borderColorInp = panel.querySelector('[data-role="pb-img-border-color"]');
                borderWidthInp = panel.querySelector('[data-role="pb-img-border-width"]');
                borderRadiusInp= panel.querySelector('[data-role="pb-img-border-radius"]');

                if (borderColorInp && !borderColorInp.value) borderColorInp.value = '#000000';

                function applyWidth(pct) {
                    if (!currentImg) return;
                    const v = clamp(pct, 10, 100);
                    currentImg.style.width  = v + '%';
                    currentImg.style.height = 'auto';
                    currentImg.dataset.pbSize = 'custom';

                    range.value = v;
                    input.value = v;

                    syncEditable();
                }

                function applyBorderFromInputs() {
                    if (!currentImg) return;

                    if (borderColorInp && borderColorInp.value) {
                        currentImg.style.borderColor = borderColorInp.value;
                    }

                    if (borderWidthInp) {
                        const bw = parseInt(borderWidthInp.value || '0', 10);
                        if (!isNaN(bw) && bw > 0) {
                            currentImg.style.borderStyle = 'solid';
                            currentImg.style.borderWidth = bw + 'px';
                        } else {
                            currentImg.style.borderWidth = '';
                            currentImg.style.borderStyle = '';
                        }
                    }

                    if (borderRadiusInp) {
                        const br = parseInt(borderRadiusInp.value || '0', 10);
                        if (!isNaN(br) && br >= 0) {
                            currentImg.style.borderRadius = br + 'px';
                        } else {
                            currentImg.style.borderRadius = '';
                        }
                    }

                    syncEditable();
                }

                range.addEventListener('input', () => applyWidth(range.value));
                input.addEventListener('change', () => applyWidth(input.value));

                presets.forEach(btn => btn.addEventListener('click', () => applyWidth(btn.dataset.preset)));

                btnReset.addEventListener('click', () => {
                    if (!currentImg) return;
                    currentImg.style.width  = '';
                    currentImg.style.height = '';
                    delete currentImg.dataset.pbSize;

                    if (borderColorInp) borderColorInp.value = '#000000';
                    if (borderWidthInp) borderWidthInp.value = '0';
                    if (borderRadiusInp) borderRadiusInp.value = '0';

                    currentImg.style.borderWidth  = '';
                    currentImg.style.borderStyle  = '';
                    currentImg.style.borderColor  = '';
                    currentImg.style.borderRadius = '';

                    range.value = 100;
                    input.value = 100;

                    syncEditable();
                });

                borderColorInp && borderColorInp.addEventListener('input', applyBorderFromInputs);
                borderWidthInp && borderWidthInp.addEventListener('input', applyBorderFromInputs);
                borderRadiusInp && borderRadiusInp.addEventListener('input', applyBorderFromInputs);
            }

            function showPanel(img, editable) {
                ensurePanel();

                if (currentImg && currentImg !== img) currentImg.classList.remove('pb-img-selected');

                currentImg      = img;
                currentEditable = editable || null;
                currentImg.classList.add('pb-img-selected');

                let initial = 100;
                if (img.style.width && img.style.width.endsWith('%')) {
                    initial = clamp(parseInt(img.style.width, 10), 10, 100);
                }
                range.value = initial;
                input.value = initial;

                // Sync border inputs
                if (borderColorInp) borderColorInp.value = (img.style.borderColor || '#000000');
                if (borderWidthInp) borderWidthInp.value = (parseInt(img.style.borderWidth || '0', 10) || 0);
                if (borderRadiusInp) borderRadiusInp.value = (parseInt(img.style.borderRadius || '0', 10) || 0);

                panel.classList.add('pb-img-panel--visible');

                const rect      = img.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();

                let top = rect.top - panelRect.height - 8;
                if (top < 8) top = rect.bottom + 8;

                let left = rect.left;
                const maxLeft = window.innerWidth - panelRect.width - 8;
                if (left > maxLeft) left = maxLeft;

                panel.style.top  = `${top}px`;
                panel.style.left = `${left}px`;
            }

            function hidePanel() {
                if (currentImg) {
                    currentImg.classList.remove('pb-img-selected');
                    currentImg = null;
                }
                currentEditable = null;
                if (panel) panel.classList.remove('pb-img-panel--visible');
            }

            builder.addEventListener('click', function(e){
                const editable = e.target.closest('[contenteditable="true"]');
                const img = e.target.closest('img');

                if (!editable || !img || !builder.contains(editable)) {
                    hidePanel();
                    return;
                }
                showPanel(img, editable);
            });

            document.addEventListener('click', function(e){
                if (!panel) return;
                if (panel.contains(e.target)) return;
                if (builder.contains(e.target)) return;
                hidePanel();
            });
        })();
    </script>

    <script type="module">
        import { openImagePicker, getMediaUrlByQuality } from "{{ asset('pb/mediaPicker.js') }}?v={{ @filemtime(public_path('pb/mediaPicker.js')) ?: time() }}";

        // Esposte globalmente per il builder component.js
        window.openImagePicker = openImagePicker;
        window.getMediaUrlByQuality = getMediaUrlByQuality;

        (function(){
            const sel      = document.getElementById('pageBgType');
            const panels   = Array.from(document.querySelectorAll('[data-bg-panel]'));

            const srcInput = document.getElementById('pageBgImageSrc');
            const preview  = document.getElementById('pageBgPreview');
            const btnPick  = document.getElementById('btnPickPageBg');
            const btnClear = document.getElementById('btnClearPageBg');

            const attSel    = document.getElementById('pageBgAttachment');
            const parHidden = document.getElementById('pageBgParallax');

            const endpoint =
                window.R4ADMIN?.mediaBrowseUrl ||
                window.PB_MEDIA_PICKER_URL ||
                window.R4ADMIN?.mediaPickerUrl ||
                '/admin/media/browse';

            function updatePanels(){
                const v = sel ? sel.value : 'none';
                panels.forEach(p => {
                    p.style.display = (p.getAttribute('data-bg-panel') === v) ? '' : 'none';
                });
            }

            function setPreview(url){
                if (!preview) return;
                if (url) {
                    const safe = String(url).replace(/"/g, '%22');
                    preview.style.backgroundImage = `url("${safe}")`;
                    preview.textContent = '';
                } else {
                    preview.style.backgroundImage = 'none';
                    preview.textContent = 'Nessuna immagine selezionata';
                }
            }

            function syncParallaxFromAttachment(){
                if (!attSel || !parHidden) return;
                parHidden.value = (attSel.value === 'fixed') ? '1' : '0';
            }

            // init
            setPreview(srcInput?.value || '');
            updatePanels();
            syncParallaxFromAttachment();

            sel && sel.addEventListener('change', updatePanels);
            attSel && attSel.addEventListener('change', syncParallaxFromAttachment);

            btnPick && btnPick.addEventListener('click', async (e) => {
                e.preventDefault();

                const picked = await openImagePicker({
                    pickerUrl: endpoint,
                    mode: 'image',
                    quality: 'full',
                });

                if (!picked) return;

                const fallback =
                    picked.url || picked.src || picked.full || picked.original || picked.original_url || picked.thumb || '';

                const url =
                    (typeof getMediaUrlByQuality === 'function'
                            ? getMediaUrlByQuality(picked, 'full', fallback)
                            : fallback
                    ) || '';

                if (!url) {
                    if (preview) preview.textContent = 'Errore: URL immagine non trovato';
                    return;
                }

                if (srcInput) srcInput.value = url;

                if (sel && sel.value !== 'image') {
                    sel.value = 'image';
                    updatePanels();
                }

                setPreview(url);
            });

            btnClear && btnClear.addEventListener('click', (e) => {
                e.preventDefault();
                if (srcInput) srcInput.value = '';
                setPreview('');
            });
        })();
    </script>
@endsection
