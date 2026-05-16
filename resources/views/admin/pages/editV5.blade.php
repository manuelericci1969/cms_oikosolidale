@extends('admin.layout')

@section('no_vite', true)
@section('title', 'Modifica Pagina (Editor V5)')

@section('content')
    @php
        $visualJsonOld = old('visual_json');
        $visualJsonValue = '';
        $editorV5AssetVersion = '20260512-v5-animations-panel-fix';
        $meta = is_array($page->meta ?? null) ? $page->meta : [];
        $layoutValue = data_get($meta, 'layout');
        $layout = is_array($layoutValue) ? $layoutValue : [];
        $layoutWidth = old('meta.layout.width', data_get($layout, 'width', 'standard'));
        $layoutGutter = (int) old('meta.layout.gutter', data_get($layout, 'gutter', 24));
        $layoutTop = (int) old('meta.layout.top', data_get($layout, 'top', 0));

        $pageBgValue = data_get($meta, 'page_bg');
        $pageBg = is_array($pageBgValue) ? $pageBgValue : [];
        $pageBgType = old('meta.page_bg.type', data_get($pageBg, 'type', 'none'));
        $pageBgColor = old('meta.page_bg.color', data_get($pageBg, 'color', '#ffffff'));
        $pageBgFrom = old('meta.page_bg.from', data_get($pageBg, 'from', '#0d6efd'));
        $pageBgTo = old('meta.page_bg.to', data_get($pageBg, 'to', '#ffffff'));
        $pageBgAngle = (int) old('meta.page_bg.angle', data_get($pageBg, 'angle', 135));
        $pageBgImage = is_array(data_get($pageBg, 'image')) ? data_get($pageBg, 'image') : [];
        $pageBgImageSrc = old('meta.page_bg.image.src', data_get($pageBgImage, 'src', ''));
        $pageBgImageSize = old('meta.page_bg.image.size', data_get($pageBgImage, 'size', 'cover'));
        $pageBgImagePosition = old('meta.page_bg.image.position', data_get($pageBgImage, 'position', 'center center'));
        $pageBgImageRepeat = old('meta.page_bg.image.repeat', data_get($pageBgImage, 'repeat', 'no-repeat'));
        $pageBgImageAttachment = old('meta.page_bg.image.attachment', data_get($pageBgImage, 'attachment', 'scroll'));
        $pageBgOverlay = is_array(data_get($pageBgImage, 'overlay')) ? data_get($pageBgImage, 'overlay') : [];
        $pageBgOverlayEnabled = (bool) old('meta.page_bg.image.overlay.enabled', data_get($pageBgOverlay, 'enabled', false));
        $pageBgOverlayColor = old('meta.page_bg.image.overlay.color', data_get($pageBgOverlay, 'color', '#000000'));
        $pageBgOverlayOpacity = old('meta.page_bg.image.overlay.opacity', data_get($pageBgOverlay, 'opacity', 0.35));

        if (is_array($visualJsonOld)) {
            $visualJsonValue = json_encode($visualJsonOld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($visualJsonOld) && trim($visualJsonOld) !== '') {
            $visualJsonValue = $visualJsonOld;
        } elseif (is_array($page->visual_json ?? null)) {
            $visualJsonValue = json_encode($page->visual_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($page->visual_json ?? null) && trim((string) $page->visual_json) !== '') {
            $visualJsonValue = (string) $page->visual_json;
        }
    @endphp

    <form id="r4v5PageForm" method="POST" action="{{ route('admin.pages.update_v5', $page) }}">
        @csrf
        @method('PATCH')

        <input type="hidden" name="editor_mode" value="visual">
        <input type="hidden" name="status" id="r4v5StatusField" value="{{ old('status', $page->status ?? 'draft') }}">
        <input type="hidden" name="title" id="r4v5TitleField" value="{{ old('title', $page->title) }}">
        <input type="hidden" name="slug" value="{{ old('slug', $page->slug) }}">
        <input type="hidden" name="excerpt" value="{{ old('excerpt', $page->excerpt) }}">
        <input type="hidden" name="published_at" value="{{ old('published_at', $page->published_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}">
        <input type="hidden" name="is_homepage" value="0">

        <textarea name="visual_html" id="r4v5VisualHtml" class="d-none">{{ old('visual_html', $page->visual_html ?? '') }}</textarea>
        <textarea name="visual_css" id="r4v5VisualCss" class="d-none">{{ old('visual_css', $page->visual_css ?? '') }}</textarea>
        <textarea name="visual_json" id="r4v5VisualJson" class="d-none">{{ $visualJsonValue }}</textarea>

        <div class="r4v5-editor" id="r4EditorV5">
            <header class="r4v5-topbar">
                <div class="r4v5-brand"><span class="r4v5-logo">R5</span><div><div class="r4v5-title">Editor V5</div><div class="r4v5-subtitle">{{ $page->title }}</div></div></div>
                <div class="r4v5-actions">
                    <a href="{{ route('admin.dashboard') }}" class="r4v5-btn r4v5-btn-light">Dashboard</a>
                    <a href="{{ route('admin.pages.index') }}" class="r4v5-btn r4v5-btn-light">Esci / Pagine</a>
                    <a href="{{ route('admin.pages.edit_v4', $page) }}" class="r4v5-btn r4v5-btn-light">V4 fallback</a>
                    <a href="{{ route('admin.pages.preview_v5', $page) }}" class="r4v5-btn r4v5-btn-light" target="_blank">Anteprima</a>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-command="media">Media</button>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-command="undo">Annulla</button>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-command="redo">Ripeti</button>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-device="Desktop">Desktop</button>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-device="Tablet">Tablet</button>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-device="Mobile">Mobile</button>
                    <button type="button" class="r4v5-btn r4v5-btn-light" data-r4v5-toggle-right>Avanzato</button>
                    <button type="submit" class="r4v5-btn r4v5-btn-secondary" data-r4v5-submit-status="draft">Salva bozza</button>
                    <button type="submit" class="r4v5-btn r4v5-btn-primary" data-r4v5-submit-status="published">Pubblica</button>
                </div>
            </header>

            @if ($errors->any())<div class="r4v5-alert r4v5-alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if(session('ok'))<div class="r4v5-alert r4v5-alert-success">{{ session('ok') }}</div>@endif

            <main class="r4v5-workspace">
                <aside class="r4v5-sidebar r4v5-sidebar-left">
                    <div class="r4v5-left-head">
                        <div class="r4v5-left-title">Elementi</div>
                        <div class="r4v5-left-tabs">
                            <button type="button" class="r4v5-left-tab is-active" data-r4v5-left-tab="widgets">Widget</button>
                            <button type="button" class="r4v5-left-tab" data-r4v5-left-tab="inspector">Inspector</button>
                            <button type="button" class="r4v5-left-tab" data-r4v5-left-tab="page">Pagina</button>
                            <button type="button" class="r4v5-left-tab" data-r4v5-left-tab="seo">SEO</button>
                        </div>
                    </div>

                    <div class="r4v5-left-panel" data-r4v5-left-panel="widgets"><input type="search" class="r4v5-search" placeholder="Cerca widget..."><div id="r4v5Blocks"></div></div>

                    <div class="r4v5-left-panel" data-r4v5-left-panel="inspector" hidden>
                        <div class="r4v5-panel-title">Inspector</div>
                        <div class="r4v5-inspector-tabs" role="tablist" aria-label="Inspector Editor V5">
                            <button type="button" class="r4v5-inspector-tab is-active" data-r4v5-inspector-tab="base">Base</button>
                            <button type="button" class="r4v5-inspector-tab" data-r4v5-inspector-tab="animations">Animazioni</button>
                            <button type="button" class="r4v5-inspector-tab" data-r4v5-inspector-tab="style">Stile</button>
                            <button type="button" class="r4v5-inspector-tab" data-r4v5-inspector-tab="props">Proprietà</button>
                        </div>
                        <div class="r4v5-inspector-panel is-active" data-r4v5-inspector-panel="base"><div id="r4v5Controls"></div></div>
                        <div class="r4v5-inspector-panel" data-r4v5-inspector-panel="animations" hidden><div id="r4v5AnimationsSlot"></div></div>
                        <div class="r4v5-inspector-panel" data-r4v5-inspector-panel="style" hidden><div id="r4v5Styles"></div></div>
                        <div class="r4v5-inspector-panel" data-r4v5-inspector-panel="props" hidden><div id="r4v5Traits"></div></div>
                    </div>

                    <div class="r4v5-left-panel" data-r4v5-left-panel="page" hidden>
                        <div class="r4v5-panel-title">Impostazioni pagina</div>
                        <div class="r4v5-page-box">
                            <label>Titolo visuale<input type="text" name="meta[page_title]" value="{{ old('meta.page_title', data_get($meta, 'page_title', $page->title)) }}"></label>
                            <label>Excerpt / descrizione breve<textarea name="meta[page_excerpt]">{{ old('meta.page_excerpt', data_get($meta, 'page_excerpt', $page->excerpt)) }}</textarea></label>
                            <div class="r4v5-panel-title">Home page</div><label class="r4v5-check"><input type="checkbox" name="is_homepage" value="1" @checked(old('is_homepage', $page->is_homepage ? 1 : 0))> Imposta questa pagina come Home page</label>
                            <div class="r4v5-panel-title">Layout pagina</div>
                            <label>Larghezza pagina<select name="meta[layout][width]"><option value="standard" @selected($layoutWidth === 'standard')>Standard</option><option value="boxed" @selected($layoutWidth === 'boxed')>Boxed</option><option value="full" @selected($layoutWidth === 'full')>Full width</option></select></label>
                            <label>Gutter laterale px<input type="number" min="0" max="120" name="meta[layout][gutter]" value="{{ $layoutGutter }}"></label>
                            <label>Distanza top px<input type="number" min="0" max="240" name="meta[layout][top]" value="{{ $layoutTop }}"></label>
                            <div class="r4v5-panel-title">Sfondo pagina</div>
                            <label>Tipo sfondo<select name="meta[page_bg][type]"><option value="none" @selected($pageBgType === 'none')>Nessuno</option><option value="color" @selected($pageBgType === 'color')>Colore</option><option value="gradient" @selected($pageBgType === 'gradient')>Gradiente</option><option value="image" @selected($pageBgType === 'image')>Immagine</option></select></label>
                            <label>Colore sfondo<input type="color" name="meta[page_bg][color]" value="{{ $pageBgColor }}"></label>
                            <div class="r4v5-field-row"><label>Gradiente da<input type="color" name="meta[page_bg][from]" value="{{ $pageBgFrom }}"></label><label>Gradiente a<input type="color" name="meta[page_bg][to]" value="{{ $pageBgTo }}"></label></div>
                            <label>Angolo gradiente<input type="number" min="0" max="360" name="meta[page_bg][angle]" value="{{ $pageBgAngle }}"></label>
                            <label>URL immagine sfondo<input type="text" name="meta[page_bg][image][src]" value="{{ $pageBgImageSrc }}" placeholder="/storage/media/immagine.jpg"></label>
                            <div class="r4v5-field-row"><label>Size<select name="meta[page_bg][image][size]"><option value="cover" @selected($pageBgImageSize === 'cover')>Cover</option><option value="contain" @selected($pageBgImageSize === 'contain')>Contain</option><option value="auto" @selected($pageBgImageSize === 'auto')>Auto</option></select></label><label>Repeat<select name="meta[page_bg][image][repeat]"><option value="no-repeat" @selected($pageBgImageRepeat === 'no-repeat')>No repeat</option><option value="repeat" @selected($pageBgImageRepeat === 'repeat')>Repeat</option><option value="repeat-x" @selected($pageBgImageRepeat === 'repeat-x')>Repeat X</option><option value="repeat-y" @selected($pageBgImageRepeat === 'repeat-y')>Repeat Y</option></select></label></div>
                            <label>Position<select name="meta[page_bg][image][position]"><option value="center center" @selected($pageBgImagePosition === 'center center')>Centro</option><option value="top center" @selected($pageBgImagePosition === 'top center')>Alto centro</option><option value="bottom center" @selected($pageBgImagePosition === 'bottom center')>Basso centro</option><option value="center left" @selected($pageBgImagePosition === 'center left')>Centro sinistra</option><option value="center right" @selected($pageBgImagePosition === 'center right')>Centro destra</option></select></label>
                            <label>Attachment<select name="meta[page_bg][image][attachment]"><option value="scroll" @selected($pageBgImageAttachment === 'scroll')>Scroll</option><option value="fixed" @selected($pageBgImageAttachment === 'fixed')>Fixed / Parallax semplice</option></select></label>
                            <input type="hidden" name="meta[page_bg][image][overlay][enabled]" value="0"><label class="r4v5-check"><input type="checkbox" name="meta[page_bg][image][overlay][enabled]" value="1" @checked($pageBgOverlayEnabled)> Overlay immagine</label>
                            <div class="r4v5-field-row"><label>Colore overlay<input type="color" name="meta[page_bg][image][overlay][color]" value="{{ $pageBgOverlayColor }}"></label><label>Opacità<input type="number" min="0" max="0.9" step="0.05" name="meta[page_bg][image][overlay][opacity]" value="{{ $pageBgOverlayOpacity }}"></label></div>
                            <div class="r4v5-panel-title">Visibilità</div>
                            <input type="hidden" name="meta[show_title]" value="0"><input type="hidden" name="meta[show_excerpt]" value="0"><input type="hidden" name="meta[show_pubdate]" value="0"><input type="hidden" name="meta[show_author]" value="0"><input type="hidden" name="meta[show_breadcrumbs]" value="0">
                            <label class="r4v5-check"><input type="checkbox" name="meta[show_title]" value="1" @checked(old('meta.show_title', data_get($meta, 'show_title', true)))> Mostra titolo</label>
                            <label class="r4v5-check"><input type="checkbox" name="meta[show_excerpt]" value="1" @checked(old('meta.show_excerpt', data_get($meta, 'show_excerpt', false)))> Mostra excerpt</label>
                            <label class="r4v5-check"><input type="checkbox" name="meta[show_pubdate]" value="1" @checked(old('meta.show_pubdate', data_get($meta, 'show_pubdate', true)))> Mostra data pubblicazione</label>
                            <label class="r4v5-check"><input type="checkbox" name="meta[show_author]" value="1" @checked(old('meta.show_author', data_get($meta, 'show_author', true)))> Mostra autore</label>
                            <label class="r4v5-check"><input type="checkbox" name="meta[show_breadcrumbs]" value="1" @checked(old('meta.show_breadcrumbs', data_get($meta, 'show_breadcrumbs', true)))> Mostra breadcrumb</label>
                        </div>
                    </div>

                    <div class="r4v5-left-panel" data-r4v5-left-panel="seo" hidden><div class="r4v5-panel-title">SEO</div><div class="r4v5-page-box"><label>SEO title<input type="text" name="meta[seo_title]" value="{{ old('meta.seo_title', data_get($meta, 'seo_title', data_get($meta, 'title'))) }}"></label><label>Meta description<textarea name="meta[seo_description]">{{ old('meta.seo_description', data_get($meta, 'seo_description', data_get($meta, 'description'))) }}</textarea></label><label>Keywords<input type="text" name="meta[seo_keywords]" value="{{ old('meta.seo_keywords', data_get($meta, 'seo_keywords', data_get($meta, 'keywords'))) }}"></label></div></div>
                </aside>
                <section class="r4v5-canvas-area"><div id="r4v5Canvas"></div></section>
                <aside class="r4v5-sidebar r4v5-sidebar-right"><div class="r4v5-panel"><div class="r4v5-panel-title">Avanzato</div><p style="font-size:12px;line-height:1.6;color:#94a3b8;margin:0;">I controlli stile e proprietà sono ora integrati nella tab Inspector.</p></div></aside>
            </main>
        </div>
    </form>

    <div class="r4v5-media-modal" id="r4v5MediaModal" hidden><div class="r4v5-media-backdrop" data-r4v5-media-close></div><div class="r4v5-media-dialog"><div class="r4v5-media-header"><div><strong>Libreria Media V5</strong><span>Seleziona una o più immagini dalla libreria o caricane una nuova.</span></div><button type="button" class="r4v5-media-close" data-r4v5-media-close>×</button></div><div class="r4v5-media-toolbar"><input type="search" id="r4v5MediaSearch" placeholder="Cerca immagine..."><span id="r4v5MediaSelectionInfo" class="r4v5-media-selection-info">0 immagini selezionate</span><form id="r4v5MediaUploadForm" enctype="multipart/form-data"><input type="file" name="file" id="r4v5MediaUploadFile" accept="image/jpeg,image/png,image/webp"><button type="submit" class="r4v5-media-btn r4v5-media-btn-primary">Upload</button></form></div><div class="r4v5-media-body"><div class="r4v5MediaGrid" id="r4v5MediaGrid"></div></div><div class="r4v5-media-footer"><button type="button" class="r4v5-media-btn" id="r4v5MediaClearSelection">Deseleziona</button><button type="button" class="r4v5-media-btn r4v5-media-btn-danger" id="r4v5MediaDeleteSelected">Elimina selezionato</button><button type="button" class="r4v5-media-btn" data-r4v5-media-close>Chiudi</button><button type="button" class="r4v5-media-btn r4v5-media-btn-primary" id="r4v5MediaInsertImage">Immagine</button><button type="button" class="r4v5-media-btn r4v5-media-btn-primary" id="r4v5MediaInsertGallery">Gallery</button><button type="button" class="r4v5-media-btn r4v5-media-btn-primary" id="r4v5MediaInsertSlider">Slider</button><button type="button" class="r4v5-media-btn r4v5-media-btn-primary" id="r4v5MediaInsertLogoGrid">Loghi/Lavori</button></div></div></div>

    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css"><link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v5/editor.css') }}?v={{ $editorV5AssetVersion }}"><link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v5/media/media.css') }}?v={{ $editorV5AssetVersion }}"><link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v5/panels/panels.css') }}?v={{ $editorV5AssetVersion }}"><link rel="stylesheet" href="{{ asset('assets/editor-v5/runtime/widgets-pro.css') }}?v={{ $editorV5AssetVersion }}">
    <script src="https://unpkg.com/grapesjs"></script>
    <script>window.R4EditorV5Config = {htmlFieldId:'r4v5VisualHtml',cssFieldId:'r4v5VisualCss',jsonFieldId:'r4v5VisualJson',statusFieldId:'r4v5StatusField',titleFieldId:'r4v5TitleField',formId:'r4v5PageForm',canvasId:'r4v5Canvas',blocksId:'r4v5Blocks',stylesId:'r4v5Styles',traitsId:'r4v5Traits',controlsId:'r4v5Controls',animationsSlotId:'r4v5AnimationsSlot',mediaPickerUrl:@json(route('admin.media.picker')),mediaUploadUrl:@json(route('admin.media.store')),mediaDeleteBaseUrl:@json(url('/admin/media')),csrfToken:@json(csrf_token())};</script>
    <script src="{{ asset('assets/admin/visual-editor-v5/core/registry.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/widgets/base.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/widgets/layout.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/widgets/static.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/widgets/sections-extra.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/widgets/landing-presets.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/core/editor.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/core/code-editor-readable.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/background/background-manager.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/media/media.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/panels/panels.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/panels/text-editor.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/panels/background-media.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/panels/background-cleaner.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/panels/animations.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/runtime/background-slider-editor-bridge.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/runtime/editor-code-runtime-bridge.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/ui/sidebar.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/ui/left-sidebar.js') }}?v={{ $editorV5AssetVersion }}"></script><script src="{{ asset('assets/admin/visual-editor-v5/ui/layers.js') }}?v={{ $editorV5AssetVersion }}"></script>
@endsection
