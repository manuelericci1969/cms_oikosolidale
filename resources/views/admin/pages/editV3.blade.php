@extends('admin.layout')
@section('no_vite', true)
@section('title', 'Modifica Pagina (V3 Visual Builder)')

@section('content')
    @php
        $meta = is_array($page->meta ?? null) ? $page->meta : [];

        $metaTitle = old('meta.title', $meta['title'] ?? '');
        $metaDescription = old('meta.description', $meta['description'] ?? '');
        $metaKeywords = old('meta.keywords', $meta['keywords'] ?? '');

        $showTitle = old('meta.show_title', array_key_exists('show_title', $meta) ? (int) !!$meta['show_title'] : 1);
        $showExcerpt = old('meta.show_excerpt', array_key_exists('show_excerpt', $meta) ? (int) !!$meta['show_excerpt'] : 0);
        $showPubdate = old('meta.show_pubdate', array_key_exists('show_pubdate', $meta) ? (int) !!$meta['show_pubdate'] : 1);
        $showAuthor = old('meta.show_author', array_key_exists('show_author', $meta) ? (int) !!$meta['show_author'] : 1);
        $showBreadcrumbs = old('meta.show_breadcrumbs', array_key_exists('show_breadcrumbs', $meta) ? (int) !!$meta['show_breadcrumbs'] : 1);

        $layout = is_array($meta['layout'] ?? null) ? $meta['layout'] : [];
        $layoutWidth = old('meta.layout.width', $layout['width'] ?? 'standard');
        $layoutGutter = old('meta.layout.gutter', $layout['gutter'] ?? 24);
        $layoutTop = old('meta.layout.top', $layout['top'] ?? 0);

        $pageBg = is_array($meta['page_bg'] ?? null) ? $meta['page_bg'] : [];
        $pageBgType = old('meta.page_bg.type', $pageBg['type'] ?? 'none');
        $pageBgColor = old('meta.page_bg.color', $pageBg['color'] ?? '#ffffff');

        $pageBgFrom = old(
            'meta.page_bg.from',
            $pageBg['from'] ?? (is_array($pageBg['gradient'] ?? null) ? ($pageBg['gradient']['from'] ?? '#0d6efd') : '#0d6efd')
        );

        $pageBgTo = old(
            'meta.page_bg.to',
            $pageBg['to'] ?? (is_array($pageBg['gradient'] ?? null) ? ($pageBg['gradient']['to'] ?? '#6610f2') : '#6610f2')
        );

        $pageBgAngle = old(
            'meta.page_bg.angle',
            $pageBg['angle'] ?? (is_array($pageBg['gradient'] ?? null) ? ($pageBg['gradient']['angle'] ?? 135) : 135)
        );

        $bgImage = is_array($pageBg['image'] ?? null) ? $pageBg['image'] : [];
        $pageBgImageSrc = old('meta.page_bg.image.src', $bgImage['src'] ?? '');
        $pageBgImageSize = old('meta.page_bg.image.size', $bgImage['size'] ?? 'cover');
        $pageBgImagePosition = old('meta.page_bg.image.position', $bgImage['position'] ?? 'center center');
        $pageBgImageRepeat = old('meta.page_bg.image.repeat', $bgImage['repeat'] ?? 'no-repeat');
        $pageBgImageAttachment = old('meta.page_bg.image.attachment', $bgImage['attachment'] ?? 'scroll');
        $pageBgImageParallax = old('meta.page_bg.image.parallax', !empty($bgImage['parallax']) ? 1 : 0);

        $bgOverlay = is_array($bgImage['overlay'] ?? null) ? $bgImage['overlay'] : [];
        $pageBgOverlayEnabled = old('meta.page_bg.image.overlay.enabled', !empty($bgOverlay['enabled']) ? 1 : 0);
        $pageBgOverlayColor = old('meta.page_bg.image.overlay.color', $bgOverlay['color'] ?? '#000000');
        $pageBgOverlayOpacity = old('meta.page_bg.image.overlay.opacity', $bgOverlay['opacity'] ?? 0.35);

        $visualJsonOld = old('visual_json');

        if (is_array($visualJsonOld)) {
            $visualJsonValue = json_encode($visualJsonOld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($visualJsonOld) && trim($visualJsonOld) !== '') {
            $visualJsonValue = $visualJsonOld;
        } elseif (is_array($page->visual_json ?? null)) {
            $visualJsonValue = json_encode($page->visual_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($page->visual_json ?? null) && trim((string) $page->visual_json) !== '') {
            $visualJsonValue = (string) $page->visual_json;
        } else {
            $visualJsonValue = '';
        }
    @endphp

    <div class="container-fluid v3-editor-page">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
                <h1 class="h4 mb-0">
                    <i class="bi bi-window-stack me-2"></i>
                    Modifica (V3 Visual): {{ $page->title }}
                </h1>

                @if($page->status)
                    <span class="badge text-bg-{{ $page->status === 'published' ? 'success' : ($page->status === 'draft' ? 'secondary' : 'warning') }}">
                        {{ ucfirst($page->status) }}
                    </span>
                @endif

                <span class="badge text-bg-dark">Visual Builder</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($page->slug)
                    <a href="{{ route('page.show', $page->slug) }}" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Anteprima pubblica
                    </a>
                @endif

                <a href="{{ route('admin.pages.edit_v2', $page) }}" class="btn btn-outline-primary">
                    <i class="bi bi-layout-text-window-reverse me-1"></i> Vai a V2
                </a>

                <a href="{{ route('admin.pages.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-1"></i> Torna all’elenco
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('ok'))
            <div class="alert alert-success mb-3">
                {{ session('ok') }}
            </div>
        @endif

        <form id="pageFormV3" method="POST" action="{{ route('admin.pages.update_v3', $page) }}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="editor_mode" value="visual">
            <input type="hidden" name="status" id="statusFieldV3" value="{{ old('status', $page->status ?? 'draft') }}">

            <textarea name="visual_html" id="visual_html" class="d-none">{{ old('visual_html', $page->visual_html ?? '') }}</textarea>
            <textarea name="visual_css" id="visual_css" class="d-none">{{ old('visual_css', $page->visual_css ?? '') }}</textarea>
            <textarea name="visual_json" id="visual_json" class="d-none">{{ old('visual_json', is_array($page->visual_json) ? json_encode($page->visual_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ($page->visual_json ?? '')) }}</textarea>

            <div class="v3-shell" id="v3Shell">
                <aside class="v3-settings-drawer" id="v3SettingsDrawer">
                    <div class="v3-settings-scroll">

                        <div class="card v3-side-card mb-3">
                            <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-gear me-2"></i> Impostazioni base</span>
                                <button type="button" class="btn btn-sm btn-light" id="v3CloseSettingsBtn">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Titolo *</label>
                                    <input type="text"
                                           name="title"
                                           class="form-control"
                                           value="{{ old('title', $page->title) }}"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text"
                                           name="slug"
                                           class="form-control"
                                           value="{{ old('slug', $page->slug) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Estratto</label>
                                    <textarea name="excerpt" class="form-control" rows="4">{{ old('excerpt', $page->excerpt) }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Data pubblicazione</label>
                                    <input type="datetime-local"
                                           name="published_at"
                                           class="form-control"
                                           value="{{ old('published_at', $page->published_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}">
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Homepage</label>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_homepage" value="0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="switchHomepage"
                                               name="is_homepage"
                                               value="1"
                                            @checked(old('is_homepage', $page->is_homepage))>
                                        <label class="form-check-label" for="switchHomepage">
                                            Imposta come homepage
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card v3-side-card mb-3">
                            <div class="card-header fw-semibold">
                                <i class="bi bi-search me-2"></i> SEO
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text"
                                           name="meta[title]"
                                           class="form-control"
                                           maxlength="60"
                                           value="{{ $metaTitle }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta[description]"
                                              class="form-control"
                                              rows="4"
                                              maxlength="160">{{ $metaDescription }}</textarea>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text"
                                           name="meta[keywords]"
                                           class="form-control"
                                           value="{{ $metaKeywords }}">
                                </div>
                            </div>
                        </div>

                        <div class="card v3-side-card mb-3">
                            <div class="card-header fw-semibold">
                                <i class="bi bi-eye me-2"></i> Visibilità frontend
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input type="hidden" name="meta[show_title]" value="0">
                                    <input class="form-check-input" type="checkbox" id="metaShowTitle" name="meta[show_title]" value="1" @checked((int)$showTitle === 1)>
                                    <label class="form-check-label" for="metaShowTitle">Mostra titolo</label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input type="hidden" name="meta[show_excerpt]" value="0">
                                    <input class="form-check-input" type="checkbox" id="metaShowExcerpt" name="meta[show_excerpt]" value="1" @checked((int)$showExcerpt === 1)>
                                    <label class="form-check-label" for="metaShowExcerpt">Mostra estratto</label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input type="hidden" name="meta[show_pubdate]" value="0">
                                    <input class="form-check-input" type="checkbox" id="metaShowPubdate" name="meta[show_pubdate]" value="1" @checked((int)$showPubdate === 1)>
                                    <label class="form-check-label" for="metaShowPubdate">Mostra data pubblicazione</label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input type="hidden" name="meta[show_author]" value="0">
                                    <input class="form-check-input" type="checkbox" id="metaShowAuthor" name="meta[show_author]" value="1" @checked((int)$showAuthor === 1)>
                                    <label class="form-check-label" for="metaShowAuthor">Mostra autore modifica</label>
                                </div>

                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="meta[show_breadcrumbs]" value="0">
                                    <input class="form-check-input" type="checkbox" id="metaShowBreadcrumbs" name="meta[show_breadcrumbs]" value="1" @checked((int)$showBreadcrumbs === 1)>
                                    <label class="form-check-label" for="metaShowBreadcrumbs">Mostra breadcrumbs</label>
                                </div>
                            </div>
                        </div>

                        <div class="card v3-side-card mb-3">
                            <div class="card-header fw-semibold">
                                <i class="bi bi-columns-gap me-2"></i> Layout pagina
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Larghezza contenitore</label>
                                    <select name="meta[layout][width]" class="form-select">
                                        <option value="standard" @selected($layoutWidth === 'standard')>Standard</option>
                                        <option value="boxed" @selected($layoutWidth === 'boxed')>Boxed</option>
                                        <option value="full" @selected($layoutWidth === 'full')>Full width</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Gutter laterale</label>
                                    <input type="number"
                                           name="meta[layout][gutter]"
                                           class="form-control"
                                           min="0"
                                           max="200"
                                           value="{{ $layoutGutter }}">
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Spazio superiore</label>
                                    <input type="number"
                                           name="meta[layout][top]"
                                           class="form-control"
                                           min="0"
                                           max="600"
                                           value="{{ $layoutTop }}">
                                </div>
                            </div>
                        </div>

                        <div class="card v3-side-card mb-3">
                            <div class="card-header fw-semibold">
                                <i class="bi bi-image me-2"></i> Sfondo pagina
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Tipo sfondo</label>
                                    <select name="meta[page_bg][type]" id="pageBgType" class="form-select">
                                        <option value="none" @selected($pageBgType === 'none')>Nessuno</option>
                                        <option value="color" @selected($pageBgType === 'color')>Colore</option>
                                        <option value="gradient" @selected($pageBgType === 'gradient')>Gradiente</option>
                                        <option value="image" @selected($pageBgType === 'image')>Immagine</option>
                                    </select>
                                </div>

                                <div id="pageBgColorFields" class="v3-bg-group {{ $pageBgType === 'color' ? '' : 'd-none' }}">
                                    <div class="mb-3">
                                        <label class="form-label">Colore sfondo</label>
                                        <input type="color" name="meta[page_bg][color]" class="form-control form-control-color" value="{{ $pageBgColor }}">
                                    </div>
                                </div>

                                <div id="pageBgGradientFields" class="v3-bg-group {{ $pageBgType === 'gradient' ? '' : 'd-none' }}">
                                    <div class="mb-3">
                                        <label class="form-label">Colore iniziale</label>
                                        <input type="color" name="meta[page_bg][from]" class="form-control form-control-color" value="{{ $pageBgFrom }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Colore finale</label>
                                        <input type="color" name="meta[page_bg][to]" class="form-control form-control-color" value="{{ $pageBgTo }}">
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">Angolo</label>
                                        <input type="number" name="meta[page_bg][angle]" class="form-control" min="0" max="360" value="{{ $pageBgAngle }}">
                                    </div>
                                </div>

                                <div id="pageBgImageFields" class="v3-bg-group {{ $pageBgType === 'image' ? '' : 'd-none' }}">
                                    <div class="mb-3">
                                        <label class="form-label">URL immagine</label>
                                        <div class="input-group">
                                            <input type="text"
                                                   name="meta[page_bg][image][src]"
                                                   id="pageBgImageSrc"
                                                   class="form-control"
                                                   value="{{ $pageBgImageSrc }}">
                                            <button type="button" class="btn btn-outline-secondary" id="pickPageBgImageBtn">
                                                Media
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Size</label>
                                        <select name="meta[page_bg][image][size]" class="form-select">
                                            <option value="cover" @selected($pageBgImageSize === 'cover')>cover</option>
                                            <option value="contain" @selected($pageBgImageSize === 'contain')>contain</option>
                                            <option value="auto" @selected($pageBgImageSize === 'auto')>auto</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Position</label>
                                        <input type="text"
                                               name="meta[page_bg][image][position]"
                                               class="form-control"
                                               value="{{ $pageBgImagePosition }}"
                                               placeholder="center center">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Repeat</label>
                                        <select name="meta[page_bg][image][repeat]" class="form-select">
                                            <option value="no-repeat" @selected($pageBgImageRepeat === 'no-repeat')>no-repeat</option>
                                            <option value="repeat" @selected($pageBgImageRepeat === 'repeat')>repeat</option>
                                            <option value="repeat-x" @selected($pageBgImageRepeat === 'repeat-x')>repeat-x</option>
                                            <option value="repeat-y" @selected($pageBgImageRepeat === 'repeat-y')>repeat-y</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <select name="meta[page_bg][image][attachment]" class="form-select">
                                            <option value="scroll" @selected($pageBgImageAttachment === 'scroll')>scroll</option>
                                            <option value="fixed" @selected($pageBgImageAttachment === 'fixed')>fixed</option>
                                        </select>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input type="hidden" name="meta[page_bg][image][parallax]" value="0">
                                        <input class="form-check-input" type="checkbox" id="pageBgImageParallax" name="meta[page_bg][image][parallax]" value="1" @checked((int)$pageBgImageParallax === 1)>
                                        <label class="form-check-label" for="pageBgImageParallax">Parallax</label>
                                    </div>

                                    <hr>

                                    <div class="form-check form-switch mb-3">
                                        <input type="hidden" name="meta[page_bg][image][overlay][enabled]" value="0">
                                        <input class="form-check-input" type="checkbox" id="pageBgOverlayEnabled" name="meta[page_bg][image][overlay][enabled]" value="1" @checked((int)$pageBgOverlayEnabled === 1)>
                                        <label class="form-check-label" for="pageBgOverlayEnabled">Overlay attivo</label>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Colore overlay</label>
                                        <input type="color" name="meta[page_bg][image][overlay][color]" class="form-control form-control-color" value="{{ $pageBgOverlayColor }}">
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">Opacità overlay</label>
                                        <input type="number"
                                               name="meta[page_bg][image][overlay][opacity]"
                                               class="form-control"
                                               step="0.05"
                                               min="0"
                                               max="0.9"
                                               value="{{ $pageBgOverlayOpacity }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </aside>

                <div class="v3-editor-main">
                    <div class="card v3-main-card">
                        <div class="card-header border-0 pb-0">
                            <div class="v3-topbar">
                                <div class="v3-topbar-left">
                                    <div class="v3-toolbar-group">
                                        <button type="button" class="btn btn-light btn-sm" id="v3OpenSettingsBtn">
                                            <i class="bi bi-sliders me-1"></i> Impostazioni
                                        </button>

                                        <button type="button" class="btn btn-light btn-sm active" id="v3ToggleLeftPanelBtn">
                                            <i class="bi bi-layout-sidebar me-1"></i> Blocchi
                                        </button>

                                        <button type="button" class="btn btn-light btn-sm active" id="v3ToggleRightPanelBtn">
                                            <i class="bi bi-layout-sidebar-inset-reverse me-1"></i> Stili
                                        </button>
                                    </div>

                                    <div class="v3-toolbar-group">
                                        <button type="button" class="btn btn-light btn-sm" id="gjs-undo-btn" title="Annulla">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" id="gjs-redo-btn" title="Ripeti">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>

                                    <div class="v3-toolbar-group">
                                        <button type="button" class="btn btn-light btn-sm active" id="gjs-device-desktop">
                                            <i class="bi bi-display me-1"></i> Desktop
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" id="gjs-device-tablet">
                                            <i class="bi bi-tablet me-1"></i> Tablet
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" id="gjs-device-mobile">
                                            <i class="bi bi-phone me-1"></i> Mobile
                                        </button>
                                    </div>

                                    <div class="v3-toolbar-group">
                                        <button type="button" class="btn btn-light btn-sm" id="gjs-preview-btn">
                                            <i class="bi bi-eye me-1"></i> Preview canvas
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" id="gjs-code-btn">
                                            <i class="bi bi-code-slash me-1"></i> HTML / CSS
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="gjs-clear-btn">
                                            <i class="bi bi-trash me-1"></i> Svuota
                                        </button>
                                    </div>
                                </div>

                                <div class="v3-topbar-right">
                                    <button type="submit"
                                            class="btn btn-outline-secondary btn-sm"
                                            data-v3-submit-status="draft">
                                        <i class="bi bi-floppy me-1"></i> Salva bozza
                                    </button>

                                    <button type="submit"
                                            class="btn btn-success btn-sm"
                                            data-v3-submit-status="published">
                                        <i class="bi bi-check2-circle me-1"></i> Pubblica
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="v3-editor-layout" id="v3EditorLayout">
                                <aside class="v3-panel v3-panel-left" id="v3LeftPanel">
                                    <div class="v3-panel-box">
                                        <div class="v3-panel-title">Blocchi</div>
                                        <div id="gjs-blocks"></div>
                                    </div>

                                    <div class="v3-panel-box">
                                        <div class="v3-panel-title">Layers</div>
                                        <div id="gjs-layers"></div>
                                    </div>
                                </aside>

                                <main class="v3-canvas-wrap">
                                    <div id="gjs"></div>
                                </main>

                                <aside class="v3-panel v3-panel-right" id="v3RightPanel">
                                    <div class="v3-panel-box">
                                        <div class="v3-panel-title">Stili</div>
                                        <div id="gjs-styles"></div>
                                    </div>

                                    <div class="v3-panel-box">
                                        <div class="v3-panel-title">Proprietà</div>
                                        <div id="gjs-traits"></div>
                                    </div>
                                </aside>
                            </div>

                            <div class="v3-code-editor d-none" id="v3CodeEditor">
                                <div class="row g-3">
                                    <div class="col-12 col-xl-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-filetype-html me-2"></i>HTML leggibile</span>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="v3SyncFromCanvasBtn">
                                                    Aggiorna dal canvas
                                                </button>
                                            </div>
                                            <div class="card-body p-0">
                                                <textarea id="v3HtmlEditor" class="v3-code-textarea" spellcheck="false"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-xl-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-white fw-semibold">
                                                <i class="bi bi-filetype-css me-2"></i>CSS leggibile
                                            </div>
                                            <div class="card-body p-0">
                                                <textarea id="v3CssEditor" class="v3-code-textarea" spellcheck="false"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            <button type="button" class="btn btn-outline-secondary" id="v3CloseCodeBtn">
                                                <i class="bi bi-layout-sidebar-inset me-1"></i> Torna al builder
                                            </button>
                                            <button type="button" class="btn btn-primary" id="v3ApplyCodeBtn">
                                                <i class="bi bi-check2-square me-1"></i> Applica al canvas
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>{{-- card-body --}}
                    </div>
                </div>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">

    <style>
        :root {
            --v3-white: #ffffff;
            --v3-bg: #f8fafc;
            --v3-bg-soft: #f1f5f9;
            --v3-bg-soft-2: #eef2f7;
            --v3-border: #dbe3ec;
            --v3-border-strong: #c7d2de;
            --v3-text: #1f2937;
            --v3-text-soft: #64748b;
            --v3-primary: #0e5bd3;
            --v3-primary-soft: #eaf3ff;
        }

        html,
        body {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            height: auto !important;
            min-height: 100% !important;
        }

        .v3-shell {
            position: relative;
        }

        .v3-editor-page,
        .v3-shell,
        .v3-editor-main,
        .v3-main-card,
        .v3-main-card > .card-body {
            overflow: visible !important;
            height: auto !important;
        }

        .v3-editor-main {
            width: 100%;
            min-width: 0;
        }

        .v3-settings-drawer {
            position: fixed;
            top: 0;
            right: 0;
            width: 380px;
            max-width: 94vw;
            height: 100vh;
            background: #f8fafc;
            border-left: 1px solid #e5e7eb;
            z-index: 2000;
            transform: translateX(100%);
            transition: transform .25s ease;
            box-shadow: -10px 0 30px rgba(15, 23, 42, 0.10);
            padding: 16px;
        }

        .v3-settings-drawer.is-open {
            transform: translateX(0);
        }

        .v3-settings-scroll {
            height: 100%;
            overflow: auto;
            padding-right: 4px;
        }

        .v3-editor-page .card {
            border-radius: 14px;
        }

        .v3-side-card .card-header,
        .v3-main-card .card-header {
            background: #fff;
        }

        .v3-main-card > .card-header {
            position: sticky;
            top: 0;
            z-index: 1050;
            background: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .v3-topbar {
            background: #ffffff;
        }

        .v3-topbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .v3-topbar-left,
        .v3-topbar-right {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .v3-toolbar-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            padding: 6px;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: #f8f9fa;
        }

        .v3-editor-layout {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr) 360px;
            column-gap: 14px;
            row-gap: 14px;
            min-height: 900px !important;
            height: auto !important;
            overflow: visible !important;
            align-items: stretch;
            transition: grid-template-columns .22s ease, column-gap .22s ease;
        }

        .v3-editor-layout.is-right-hidden {
            grid-template-columns: 220px minmax(0, 1fr) 0;
        }

        .v3-editor-layout.is-left-hidden {
            grid-template-columns: 0 minmax(0, 1fr) 360px;
        }

        .v3-editor-layout.is-left-hidden.is-right-hidden {
            grid-template-columns: 0 minmax(0, 1fr) 0;
            column-gap: 0;
        }

        .v3-editor-layout.is-right-hidden .v3-panel-right,
        .v3-editor-layout.is-left-hidden .v3-panel-left {
            width: 0 !important;
            min-width: 0 !important;
            max-width: 0 !important;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .v3-editor-layout.is-left-hidden {
            grid-template-columns: 0 minmax(0, 1fr) 360px;
        }

        .v3-editor-layout.is-right-hidden {
            grid-template-columns: 220px minmax(0, 1fr) 0;
        }

        .v3-editor-layout.is-left-hidden.is-right-hidden {
            grid-template-columns: 0 minmax(0, 1fr) 0;
            column-gap: 0;
        }

        .v3-editor-layout.is-left-hidden .v3-panel-left,
        .v3-editor-layout.is-right-hidden .v3-panel-right {
            width: 0 !important;
            min-width: 0 !important;
            max-width: 0 !important;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .v3-panel-left,
        .v3-panel-right {
            transition: opacity .18s ease;
        }

        #v3ToggleLeftPanelBtn:not(.active),
        #v3ToggleRightPanelBtn:not(.active) {
            opacity: .65;
            background: #eef2f7;
        }

        .v3-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .v3-panel-box {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            overflow: hidden;
            min-height: 200px;
        }

        .v3-panel-title {
            padding: 14px 16px;
            font-weight: 700;
            font-size: 15px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            color: var(--v3-text);
        }

        .v3-canvas-wrap {
            border: 1px solid #e9ecef;
            border-radius: 14px;
            overflow: hidden !important;
            background: #ffffff;
            min-width: 0;
            min-height: 900px !important;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
        }

        #gjs {
            height: 900px !important;
            width: 100%;
            border: 0;
            background: #ffffff;
            overflow: hidden !important;
        }

        .gjs-one-bg {
            background-color: #ffffff !important;
        }

        .gjs-two-color {
            color: var(--v3-text) !important;
        }

        .gjs-three-bg {
            background-color: var(--v3-primary-soft) !important;
            color: var(--v3-primary) !important;
        }

        .gjs-four-color,
        .gjs-four-color-h:hover {
            color: var(--v3-primary) !important;
        }

        #gjs .gjs-editor,
        #gjs .gjs-cv-canvas,
        #gjs .gjs-cv-canvas > iframe,
        #gjs iframe {
            width: 100% !important;
            min-width: 100% !important;
        }

        #gjs .gjs-editor {
            background: #ffffff !important;
            font-family: inherit;
            min-height: 900px !important;
        }

        #gjs .gjs-cv-canvas {
            background: #ffffff !important;
            overflow: auto !important;
            scroll-behavior: auto !important;
            min-height: 900px !important;
        }

        #gjs .gjs-toolbar,
        #gjs .gjs-toolbar-items,
        #gjs .gjs-resizer,
        #gjs .gjs-cv-canvas .gjs-toolbar {
            background: rgba(17, 24, 39, 0.96) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18) !important;
        }

        #gjs .gjs-toolbar {
            border-radius: 10px !important;
            overflow: hidden;
        }

        #gjs .gjs-toolbar-item,
        #gjs .gjs-toolbar-items .gjs-toolbar-item,
        #gjs .gjs-cv-canvas .gjs-toolbar-item {
            color: #ffffff !important;
            fill: #ffffff !important;
            border-right: 1px solid rgba(255,255,255,0.08) !important;
        }

        #gjs .gjs-toolbar-item:hover,
        #gjs .gjs-toolbar-items .gjs-toolbar-item:hover,
        #gjs .gjs-cv-canvas .gjs-toolbar-item:hover {
            background: rgba(59, 130, 246, 0.18) !important;
            color: #ffffff !important;
        }

        #gjs .gjs-toolbar-item svg,
        #gjs .gjs-toolbar svg,
        #gjs .gjs-badge svg {
            fill: currentColor !important;
        }

        #gjs .gjs-selected {
            outline: 2px solid #2563eb !important;
            outline-offset: 0 !important;
        }

        #gjs .gjs-comp-selected {
            box-shadow: inset 0 0 0 2px #2563eb !important;
        }

        #gjs .gjs-hovered {
            outline: 1px dashed #60a5fa !important;
        }

        #gjs .gjs-off-prv,
        #gjs .gjs-badge,
        #gjs .gjs-placeholder {
            background: rgba(17, 24, 39, 0.95) !important;
            color: #ffffff !important;
            border-color: rgba(255,255,255,0.08) !important;
        }

        #gjs .gjs-badge,
        #gjs .gjs-badge * {
            color: #fff !important;
            border-radius: 8px !important;
        }

        #gjs .gjs-resizer-h,
        #gjs .gjs-resizer-v,
        #gjs .gjs-resizer-hv {
            background: #2563eb !important;
            border: 1px solid #ffffff !important;
        }

        #gjs-blocks,
        #gjs-layers,
        #gjs-styles,
        #gjs-traits {
            min-height: 160px;
            max-height: 820px;
            overflow: auto;
            padding: 12px;
            background: var(--v3-white) !important;
            color: var(--v3-text) !important;
            font-size: 13px;
        }

        #gjs-blocks .gjs-block-category {
            background: var(--v3-bg) !important;
            border: 1px solid var(--v3-border) !important;
            border-radius: 12px !important;
            margin-bottom: 12px !important;
            overflow: hidden !important;
        }

        #gjs-blocks .gjs-title {
            background: var(--v3-bg) !important;
            color: var(--v3-text) !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            padding: 12px 14px !important;
            border-bottom: 1px solid var(--v3-border) !important;
        }

        #gjs-blocks .gjs-caret-icon,
        #gjs-blocks .gjs-title *,
        #gjs-blocks .gjs-block-category * {
            color: var(--v3-text) !important;
        }

        #gjs-blocks .gjs-blocks-c {
            padding: 10px !important;
            background: var(--v3-white) !important;
        }

        #gjs-blocks .gjs-block {
            width: 100% !important;
            min-height: auto !important;
            margin: 0 0 10px 0 !important;
            padding: 12px 14px !important;
            border: 1px solid var(--v3-border) !important;
            border-radius: 12px !important;
            background: var(--v3-white) !important;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04) !important;
            transition: all .18s ease !important;
        }

        #gjs-blocks .gjs-block:hover {
            border-color: var(--v3-primary) !important;
            background: var(--v3-primary-soft) !important;
            transform: translateY(-1px);
        }

        #gjs-blocks .gjs-block-label,
        #gjs-blocks .gjs-block .gjs-block-label {
            color: var(--v3-text) !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }

        #gjs-layers .gjs-layer {
            background: var(--v3-white) !important;
            color: var(--v3-text) !important;
            border-bottom: 1px solid var(--v3-bg-soft-2) !important;
        }

        #gjs-layers .gjs-layer:hover {
            background: var(--v3-bg) !important;
        }

        #gjs-layers .gjs-layer-title,
        #gjs-layers .gjs-layer-title-inn,
        #gjs-layers .gjs-layer-name,
        #gjs-layers .gjs-layer-vis,
        #gjs-layers .gjs-layer-count,
        #gjs-layers .gjs-layer-caret,
        #gjs-layers .gjs-layer * {
            color: var(--v3-text) !important;
        }

        #gjs-layers .gjs-selected,
        #gjs-layers .gjs-layer.gjs-selected,
        #gjs-layers .gjs-layer-title.gjs-selected {
            background: var(--v3-primary-soft) !important;
            color: var(--v3-primary) !important;
        }

        #gjs-layers .gjs-layer.gjs-selected *,
        #gjs-layers .gjs-selected * {
            color: var(--v3-primary) !important;
            font-weight: 600 !important;
        }

        #gjs-styles,
        #gjs-traits {
            background: #fcfdff !important;
        }

        #gjs-styles .gjs-sm-sector {
            border: 1px solid var(--v3-border) !important;
            border-radius: 12px !important;
            margin-bottom: 12px !important;
            overflow: hidden !important;
            background: var(--v3-white) !important;
        }

        #gjs-styles .gjs-sm-title {
            background: var(--v3-bg) !important;
            color: var(--v3-text) !important;
            font-weight: 700 !important;
            border-bottom: 1px solid var(--v3-border) !important;
            padding: 12px 14px !important;
        }

        #gjs-styles .gjs-sm-title:hover {
            background: var(--v3-primary-soft) !important;
            color: var(--v3-primary) !important;
        }

        #gjs-styles .gjs-sm-properties {
            background: var(--v3-white) !important;
            padding: 12px !important;
        }

        #gjs-styles .gjs-sm-property {
            margin-bottom: 12px !important;
        }

        #gjs-styles .gjs-sm-label,
        #gjs-styles label,
        #gjs-traits .gjs-label,
        #gjs-traits label {
            display: block !important;
            width: 100% !important;
            margin-bottom: 6px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            font-size: 13px !important;
            line-height: 1.35 !important;
        }

        #gjs-styles input,
        #gjs-styles select,
        #gjs-styles textarea,
        #gjs-traits input,
        #gjs-traits select,
        #gjs-traits textarea {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            background: #ffffff !important;
            color: var(--v3-text) !important;
            border: 1px solid var(--v3-border-strong) !important;
            border-radius: 8px !important;
            box-sizing: border-box !important;
        }

        #gjs-styles input:focus,
        #gjs-styles select:focus,
        #gjs-styles textarea:focus,
        #gjs-styles .gjs-field:focus-within,
        #gjs-traits input:focus,
        #gjs-traits select:focus,
        #gjs-traits textarea:focus {
            border-color: var(--v3-primary) !important;
            box-shadow: 0 0 0 3px rgba(14, 91, 211, 0.10) !important;
        }

        #gjs-traits .gjs-trt-trait {
            display: block !important;
            padding: 12px 0 !important;
            border-bottom: 1px solid var(--v3-bg-soft-2) !important;
        }

        #gjs iframe,
        #gjs .gjs-frame,
        #gjs .gjs-frame-wrapper {
            scroll-behavior: auto !important;
        }

        .v3-code-editor {
            margin-top: 12px;
        }

        .v3-code-textarea {
            width: 100%;
            min-height: 720px;
            border: 0;
            outline: 0;
            resize: vertical;
            padding: 18px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 13px;
            line-height: 1.6;
            background: #0f172a;
            color: #e2e8f0;
            white-space: pre;
            tab-size: 2;
        }

        @media (max-width: 1600px) {
            .v3-editor-layout {
                grid-template-columns: 200px minmax(0, 1fr) 320px;
            }

            .v3-editor-layout.is-left-hidden {
                grid-template-columns: 0 minmax(0, 1fr) 320px;
            }

            .v3-editor-layout.is-right-hidden {
                grid-template-columns: 200px minmax(0, 1fr) 0;
            }

            .v3-editor-layout.is-left-hidden.is-right-hidden {
                grid-template-columns: 0 minmax(0, 1fr) 0;
                column-gap: 0;
            }
        }

        @media (max-width: 1400px) {
            .v3-editor-layout {
                grid-template-columns: 180px minmax(0, 1fr) 280px;
            }

            .v3-editor-layout.is-left-hidden {
                grid-template-columns: 0 minmax(0, 1fr) 280px;
            }

            .v3-editor-layout.is-right-hidden {
                grid-template-columns: 180px minmax(0, 1fr) 0;
            }

            .v3-editor-layout.is-left-hidden.is-right-hidden {
                grid-template-columns: 0 minmax(0, 1fr) 0;
                column-gap: 0;
            }
        }

        @media (max-width: 1200px) {
            .v3-editor-layout {
                grid-template-columns: 1fr;
            }

            .v3-editor-layout.is-left-hidden,
            .v3-editor-layout.is-right-hidden,
            .v3-editor-layout.is-left-hidden.is-right-hidden {
                grid-template-columns: 1fr;
            }

            .v3-editor-layout.is-left-hidden .v3-panel-left,
            .v3-editor-layout.is-right-hidden .v3-panel-right {
                display: none !important;
            }

            #gjs {
                height: 700px !important;
            }

            .v3-canvas-wrap {
                min-height: 700px !important;
            }

            #gjs-blocks,
            #gjs-layers,
            #gjs-styles,
            #gjs-traits {
                max-height: 280px;
            }

            .v3-code-textarea {
                min-height: 420px;
            }
        }

        /* =========================================================
           V3 FIXED TOPBAR - non sovrapposta al contenuto
        ========================================================= */

        :root {
            --v3-toolbar-top: 0px;
            --v3-toolbar-left: 0px;
            --v3-toolbar-width: 100vw;
            --v3-toolbar-height: 72px;
        }

        .v3-editor-page {
            padding-top: calc(var(--v3-toolbar-height, 72px) + 16px) !important;
        }

        .v3-main-card > .card-header {
            position: fixed !important;
            top: var(--v3-toolbar-top, 0px) !important;
            left: var(--v3-toolbar-left, 0px) !important;
            width: var(--v3-toolbar-width, 100vw) !important;
            z-index: 5000 !important;
            background: #ffffff !important;
            border-bottom: 1px solid #e5e7eb !important;
            border-radius: 0 !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08) !important;
            padding: 10px 16px !important;
        }

        .v3-main-card > .card-body {
            padding-top: 18px !important;
        }

        .v3-topbar {
            background: #ffffff !important;
            margin: 0 !important;
            padding-bottom: 0 !important;
            border-bottom: 0 !important;
        }

        .v3-toolbar-group {
            margin: 0 !important;
        }

        @media (max-width: 768px) {
            .v3-editor-page {
                padding-top: calc(var(--v3-toolbar-height, 110px) + 16px) !important;
            }

            .v3-main-card > .card-header {
                padding: 8px 10px !important;
            }
        }

    </style>

    <script>
        window.PB_MEDIA_PICKER_URL = @json(url('/admin/media/browse'));
    </script>

    <script src="https://unpkg.com/grapesjs"></script>

    {{-- Per test puoi usare ?v={{ time() }}. Quando è stabile rimetti filemtime. --}}
    <script type="module" src="{{ asset('pb/v3/editor.js') }}?v={{ time() }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const STORAGE_KEY = 'r4_v3_editor_panels_visibility';

            const layout = document.getElementById('v3EditorLayout');
            const leftBtn = document.getElementById('v3ToggleLeftPanelBtn');
            const rightBtn = document.getElementById('v3ToggleRightPanelBtn');

            if (!layout || !leftBtn || !rightBtn) {
                return;
            }

            function getPageScroll() {
                return {
                    x: window.scrollX || window.pageXOffset || 0,
                    y: window.scrollY || window.pageYOffset || 0
                };
            }

            function restorePageScroll(pos) {
                if (!pos) return;

                try {
                    window.scrollTo(pos.x, pos.y);
                } catch (e) {
                    window.scrollTo(0, pos.y || 0);
                }
            }

            function preservePageScroll(callback) {
                const pos = getPageScroll();

                if (typeof callback === 'function') {
                    callback();
                }

                restorePageScroll(pos);

                requestAnimationFrame(function () {
                    restorePageScroll(pos);

                    requestAnimationFrame(function () {
                        restorePageScroll(pos);
                    });
                });

                setTimeout(function () {
                    restorePageScroll(pos);
                }, 60);

                setTimeout(function () {
                    restorePageScroll(pos);
                }, 160);

                setTimeout(function () {
                    restorePageScroll(pos);
                }, 300);
            }

            function loadState() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);

                    if (!raw) {
                        return {
                            left: true,
                            right: false
                        };
                    }

                    const parsed = JSON.parse(raw);

                    return {
                        left: parsed.left !== false,
                        right: parsed.right === true
                    };
                } catch (e) {
                    return {
                        left: true,
                        right: false
                    };
                }
            }

            function saveState(state) {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                } catch (e) {
                    console.warn('V3 panels: impossibile salvare stato pannelli', e);
                }
            }

            function refreshEditorSizePreservingScroll() {
                preservePageScroll(function () {
                    setTimeout(function () {
                        preservePageScroll(function () {
                            window.dispatchEvent(new Event('resize'));

                            if (typeof window.r4V3SyncToolbar === 'function') {
                                window.r4V3SyncToolbar();
                            }

                            if (window.r4V3Editor && typeof window.r4V3Editor.refresh === 'function') {
                                window.r4V3Editor.refresh();
                            }
                        });
                    }, 260);
                });
            }

            function applyState(state) {
                layout.classList.toggle('is-left-hidden', !state.left);
                layout.classList.toggle('is-right-hidden', !state.right);

                leftBtn.classList.toggle('active', state.left);
                rightBtn.classList.toggle('active', state.right);

                leftBtn.setAttribute('aria-pressed', state.left ? 'true' : 'false');
                rightBtn.setAttribute('aria-pressed', state.right ? 'true' : 'false');
            }

            let state = loadState();
            applyState(state);

            leftBtn.addEventListener('click', function () {
                preservePageScroll(function () {
                    state.left = !state.left;
                    applyState(state);
                    saveState(state);
                    refreshEditorSizePreservingScroll();
                });
            });

            rightBtn.addEventListener('click', function () {
                preservePageScroll(function () {
                    state.right = !state.right;
                    applyState(state);
                    saveState(state);
                    refreshEditorSizePreservingScroll();
                });
            });

            window.r4V3Panels = {
                openLeft() {
                    if (state.left === true) return;

                    preservePageScroll(function () {
                        state.left = true;
                        applyState(state);
                        saveState(state);
                        refreshEditorSizePreservingScroll();
                    });
                },

                closeLeft() {
                    if (state.left === false) return;

                    preservePageScroll(function () {
                        state.left = false;
                        applyState(state);
                        saveState(state);
                        refreshEditorSizePreservingScroll();
                    });
                },

                openRight() {
                    if (state.right === true) return;

                    preservePageScroll(function () {
                        state.right = true;
                        applyState(state);
                        saveState(state);
                        refreshEditorSizePreservingScroll();
                    });
                },

                closeRight() {
                    if (state.right === false) return;

                    preservePageScroll(function () {
                        state.right = false;
                        applyState(state);
                        saveState(state);
                        refreshEditorSizePreservingScroll();
                    });
                },

                toggleRight() {
                    preservePageScroll(function () {
                        state.right = !state.right;
                        applyState(state);
                        saveState(state);
                        refreshEditorSizePreservingScroll();
                    });
                },

                preservePageScroll,

                getState() {
                    return { ...state };
                }
            };
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.documentElement;
            const toolbar = document.querySelector('.v3-main-card > .card-header');

            if (!toolbar) {
                return;
            }

            function getTopFixedOffset() {
                let offset = 0;

                const elements = Array.from(document.body.querySelectorAll('*'));

                elements.forEach(function (el) {
                    if (el === toolbar || toolbar.contains(el)) {
                        return;
                    }

                    const style = window.getComputedStyle(el);

                    if (style.position !== 'fixed' && style.position !== 'sticky') {
                        return;
                    }

                    const rect = el.getBoundingClientRect();

                    if (rect.height <= 0 || rect.width <= 0) {
                        return;
                    }

                    /*
                     * Consideriamo solo barre realmente agganciate in alto.
                     * Evita di prendere pannelli laterali o elementi floating.
                     */
                    if (rect.top <= 5 && rect.bottom > offset && rect.width > window.innerWidth * 0.45) {
                        offset = Math.ceil(rect.bottom);
                    }
                });

                return offset;
            }

            function syncToolbarPosition() {
                const topOffset = getTopFixedOffset();

                root.style.setProperty('--v3-toolbar-top', `${topOffset}px`);
                root.style.setProperty('--v3-toolbar-left', `0px`);
                root.style.setProperty('--v3-toolbar-width', `${window.innerWidth}px`);

                requestAnimationFrame(function () {
                    const toolbarHeight = Math.ceil(toolbar.getBoundingClientRect().height || 72);
                    root.style.setProperty('--v3-toolbar-height', `${toolbarHeight}px`);
                });
            }

            syncToolbarPosition();

            window.addEventListener('resize', syncToolbarPosition);
            window.addEventListener('scroll', syncToolbarPosition, { passive: true });

            setTimeout(syncToolbarPosition, 150);
            setTimeout(syncToolbarPosition, 500);
            setTimeout(syncToolbarPosition, 1000);

            window.r4V3SyncToolbar = syncToolbarPosition;
        });
    </script>
@endsection
