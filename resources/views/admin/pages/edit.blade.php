{{-- resources/views/admin/pages/edit.blade.php --}}
@extends('admin.layout')
@section('title', 'Modifica Pagina')

@section('content')
    <style>
        :root{
            --pb-bg:#f6f8fb; --pb-card:#ffffff; --pb-muted:#6c757d; --pb-primary:#0d6efd;
            --pb-soft:#e9f1ff; --pb-border:#e5e7eb; --pb-ok:#198754; --pb-warn:#ffc107;

            /* utili per spacing/controls */
            --space-1:6px; --space-2:10px; --space-3:14px; --space-4:18px;
            --radius:14px; --control-h:40px; --control-h-sm:34px;
        }
        body{ background: var(--pb-bg); }

        /* Header sticky */
        .editor-topbar{
            position: sticky; top: 0; z-index: 100;
            background: linear-gradient(180deg,#ffffff 0%, #ffffffef 70%, #ffffff00 100%);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid var(--pb-border);
        }

        .sidebar-sticky{ position: sticky; top: 76px; }
        .btn-soft{ background:var(--pb-soft); border-color:#cfe1ff; color:#0b5ed7; }
        .btn-soft:hover{ background:#dfeaff; border-color:#bdd3ff; color:#0846a6; }
        .btn-ghost{ background:#fff; border:1px solid var(--pb-border); color:#111827; }
        .btn-ghost:hover{ background:#f8fafc; }
        .btn-pill{ border-radius:999px; }
        .btn-glow{ box-shadow:0 6px 16px rgba(13,110,253,.18); }
        .btn-primary.btn-glow:hover{ box-shadow:0 8px 20px rgba(13,110,253,.28); }

        .pb-section{ background:var(--pb-card); border:1px solid var(--pb-border); border-radius:14px; margin-bottom:18px; box-shadow: 0 1px 2px rgba(16,24,40,.06); }
        .pb-head{ padding:12px 14px; border-bottom:1px solid var(--pb-border); display:flex; align-items:center; justify-content:space-between; gap:12px; border-top-left-radius:14px; border-top-right-radius:14px; }
        .pb-title{ display:flex; align-items:center; gap:10px; font-weight:600; }
        .drag-handle{ cursor:grab; user-select:none; font-size:18px; opacity:.6; }
        .pb-actions .btn{ margin-left:6px; }
        .pb-body{ padding:16px; }
        .pb-drag-over{ outline:3px solid #0d6efd33; }

        .pb-block{ background:#fff; border:1px solid var(--pb-border); border-radius:12px; padding:12px; height:100%; box-shadow: 0 1px 2px rgba(16,24,40,.04); }
        /* Evidenzia blocco selezionato (pannello Stile) */
        .pb-block.selected{
            outline: 2px solid rgba(13,110,253,.35);
            box-shadow: 0 0 0 4px rgba(13,110,253,.08);
        }
        /* === Background video & overlay per qualsiasi blocco === */
        .block-preview .pb-bgvideo{
            position:absolute; inset:0; width:100%; height:100%;
            object-fit: var(--pb-bgvideo-fit, cover);
            z-index:0;
        }
        .block-preview .pb-overlay{
            position:absolute; inset:0; pointer-events:none; z-index:1;
        }
        .block-preview .pb-content{
            position:relative; z-index:2; /* sopra video/overlay */
        }

        .pb-toolbar{ display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; flex-wrap:wrap; }
        .small-muted{ font-size:.85rem; color:var(--pb-muted); }

        .block-preview{ width:100%; }
        .block-preview img{ max-width:100%; height:auto; }
        .thumb{ width:88px; height:88px; object-fit:cover; border-radius:8px; border:1px solid var(--pb-border); }

        .palette{ display:flex; gap:8px; flex-wrap:wrap; }
        .palette .btn{ border-radius:999px; padding:.38rem .9rem; white-space:nowrap; }

        .pb-fab{ position: fixed; right: 20px; bottom: 20px; z-index: 40; }
        .caret{ transition: transform .2s ease; display:inline-block; }
        .collapsed .caret{ transform: rotate(-90deg); }

        .settings-col{ transition: flex-basis .25s ease, max-width .25s ease; }
        .settings-content{ transition: opacity .2s ease; }
        .settings-rail{ position: sticky; top:76px; display:flex; flex-direction:column; align-items:center; gap:.5rem; padding:.5rem 0; }
        .rail-btn{ width:36px; height:36px; border-radius:999px; display:flex; align-items:center; justify-content:center; }
        .builder-col{ transition: flex-basis .25s ease, max-width .25s ease; }

        @media (min-width: 992px){
            .settings-col.collapsed{ flex:0 0 56px !important; max-width:56px !important; }
            .settings-col.collapsed .settings-content{ opacity:0; visibility:hidden; pointer-events:none; }
            .settings-col .settings-content{ opacity:1; visibility:visible; }
            .settings-col .settings-rail{ display:flex; }
        }
        @media (max-width: 991.98px){
            .settings-col{ flex:0 0 0; max-width:100%; }
            .settings-content{ display:none; }
            .pb-gear-fab{ position:fixed; right:16px; bottom:16px; z-index:1080; border-radius:999px; }
            .settings-offcanvas{
                position:fixed; right:0; top:0; bottom:0; width:min(92vw, 420px);
                transform:translateX(100%); transition:transform .25s ease;
                background:#fff; border-left:1px solid var(--pb-border); z-index:1085; overflow:auto;
                box-shadow: -10px 0 30px rgba(0,0,0,.1);
            }
            .settings-offcanvas.show{ transform:translateX(0); }
            .settings-backdrop{ position:fixed; inset:0; background:rgba(33,37,41,.35); z-index:1080; display:none; }
            .settings-backdrop.show{ display:block; }
        }

        .fieldset{ border:1px dashed var(--pb-border); border-radius:10px; padding:10px; }
        .fieldset legend{ font-size:.9rem; color:#495057; padding:0 6px; width:auto; }

        /* Collassabili */
        .fieldset.collapsible summary{ cursor:pointer; list-style:none; }
        .fieldset.collapsible summary::-webkit-details-marker{ display:none; }
        .fieldset.collapsible{ border-style: solid; }
        .fieldset.collapsible[open]{ border-style: dashed; }
        .fieldset.collapsible > summary{ font-weight:600; }

        .upload-overlay{ position: fixed; inset: 0; background: rgba(255,255,255,.75); backdrop-filter: blur(3px); display: none; align-items: center; justify-content: center; z-index: 1080; }
        .upload-overlay.show{ display: flex; }
        .upload-card{ background:#fff; border:1px solid var(--pb-border); border-radius:12px; padding:16px 18px; min-width: min(90vw, 420px); box-shadow: 0 10px 30px rgba(0,0,0,.12); }
        .upload-card .progress{ height: 8px; }
        .upload-card .msg{ color:#495057; }
        .upload-toasts{ position: fixed; right: 18px; bottom: 18px; display: flex; flex-direction: column; gap: 8px; z-index: 1090; }
        .upload-toast{ background: #111827; color:#fff; border-radius: 10px; padding: 10px 12px; font-size: 0.9rem; box-shadow: 0 10px 20px rgba(0,0,0,.18); }
        .upload-toast.success{ background:#14532d; }
        .upload-toast.error{ background:#7f1d1d; }

        .r4-editor{ border:1px solid var(--pb-border); border-radius:10px; background:#fff; }
        .r4-editor .r4-toolbar{ display:flex; flex-wrap:wrap; gap:6px; padding:6px; border-bottom:1px solid var(--pb-border); background:#f8fafc; position:sticky; top:0; z-index:1; }
        .r4-editor .r4-toolbar button{ border:1px solid var(--pb-border); background:#fff; border-radius:8px; padding:6px 8px; line-height:1; }
        .r4-editor .r4-toolbar button:active{ transform: translateY(1px); }
        .r4-editor select.r4-block{ border:1px solid var(--pb-border); border-radius:8px; padding:4px 8px; }
        .r4-editor .r4-editable{ min-height:220px; padding:12px; border-radius:0 0 10px 10px; }
        .r4-editor .r4-editable:focus{ outline: none; }
        .r4-editor .r4-editable p{ margin: 0 0 .6rem; }
        .r4-editor .r4-editable img{ max-width:100%; height:auto; }
        .editor-overlay, .mask, .drag-ghost{ pointer-events:none; }
        .ql-container{ position:relative; z-index:1; }

        /* === R4 Rich Text extra === */
        .r4-editor .r4-toolbar .group{ display:flex; gap:6px; align-items:center; padding-right:6px; border-right:1px solid var(--pb-border); }
        .r4-editor .r4-toolbar .group:last-child{ border-right:none; padding-right:0; }

        .r4-editor .r4-toolbar input[type="color"]{ width:34px; height:34px; padding:0; border-radius:8px; border:1px solid var(--pb-border); }
        .r4-editor .r4-toolbar select.r4-font,
        .r4-editor .r4-toolbar select.r4-size{ border:1px solid var(--pb-border); border-radius:8px; padding:4px 8px; }

        .r4-editor .r4-editable img{ max-width:100%; height:auto; }
        .r4-editor .r4-editable img.align-left{ float:left; margin:.25rem .75rem .5rem 0; max-width:50%; }
        .r4-editor .r4-editable img.align-right{ float:right; margin:.25rem 0 .5rem .75rem; max-width:50%; }
        .r4-editor .r4-editable img.align-center{ display:block; margin:.5rem auto; float:none; }
        .r4-editor .r4-editable::after{ content:""; display:block; clear:both; }

        .r4-editor .r4-editable blockquote{ border-left:3px solid #e5e7eb; margin:.75rem 0; padding:.5rem .75rem; color:#495057; background:#f8fafc; border-radius:8px; }
        .r4-editor .r4-editable pre{ background:#0f172a; color:#e2e8f0; padding:.6rem .8rem; border-radius:8px; overflow:auto; font-size:.9rem; }


        /* Media Picker */
        .mp-item { position:relative; border:1px solid var(--pb-border,#e5e7eb); border-radius:10px; overflow:hidden; cursor:pointer; background:#fff; }
        .mp-item img { width:100%; height:140px; object-fit:cover; display:block; }
        .mp-item .mp-caption { padding:.4rem .5rem; font-size:.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .mp-item.active { outline:3px solid rgba(13,110,253,.35); box-shadow:0 0 0 3px rgba(13,110,253,.15) inset; }
        .mp-check { position:absolute; top:6px; left:6px; background:#0d6efd; color:#fff; width:24px; height:24px; border-radius:999px; display:none; align-items:center; justify-content:center; font-size:.9rem; }
        .mp-item.active .mp-check { display:flex; }

        /* Modalità compatta (persistente via JS) */
        [data-density="compact"] .card-header,
        [data-density="compact"] .pb-head{ padding: var(--space-1) var(--space-2); }
        [data-density="compact"] .pb-body{ padding: var(--space-2); }
        [data-density="compact"] .pb-toolbar{ gap:6px; margin-bottom:8px; }
        [data-density="compact"] .palette .btn{ padding:.3rem .6rem; }
        [data-density="compact"] .form-control,
        [data-density="compact"] .form-select,
        [data-density="compact"] .form-check-input{
            height: var(--control-h-sm);
            padding-top:.25rem; padding-bottom:.25rem;
        }
        [data-density="compact"] .form-control.form-control-sm,
        [data-density="compact"] .form-select.form-select-sm{
            height: calc(var(--control-h-sm) - 4px);
        }

        /* Griglia impostazioni */
        .form-grid{ display:grid; grid-template-columns:1fr; gap: var(--space-2); }
        @media (min-width: 1200px){
            .form-grid.two{ grid-template-columns: 1fr 1fr; }
            .form-grid .col-span-2{ grid-column: 1 / -1; }
        }
        .field > label{ display:block; margin-bottom:.35rem; font-weight:500; }
        .small-help{ font-size:.825rem; color: var(--pb-muted); }
        .counter{ font-size:.8rem; color: var(--pb-muted); float:right; }

        .list-switch{ display:grid; gap:.25rem; }
        .list-switch .form-check{ margin:0; padding:.25rem 0; display:flex; align-items:center; justify-content:space-between; }

        .card-header .subtle{ font-weight:400; color:var(--pb-muted); font-size:.9rem; }
        .card-body > .mb-3:last-child{ margin-bottom:0 !important; }
        .pb-section{ margin-bottom: var(--space-3); }
        .row.g-3{ --bs-gutter-y:1rem; --bs-gutter-x:1rem; }

        /* Tile per elementi media (galleria/carosello) */
        .thumb-tile{ position:relative; border:1px solid var(--pb-border); border-radius:10px; overflow:hidden; background:#fff; }
        .thumb-tile img{ display:block; width:100%; height:100px; object-fit:cover; }
        .thumb-tile .tile-body{ padding:.45rem; }
        .thumb-tile .tile-actions{ position:absolute; top:6px; right:6px; display:flex; gap:6px; }
        .btn-icon{ width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:8px; }

        /* Editor testo: placeholder e conta */
        .r4-editor .r4-editable:empty:before{
            content: attr(data-placeholder);
            color: var(--pb-muted);
        }
        .r4-editor .txt-count{ margin-left:auto; font-size:.8rem; color:var(--pb-muted); }

        /* === R4 Rich Text: stato toolbar & piccoli fix === */
        .r4-editor .r4-toolbar button.active{
            outline:2px solid var(--pb-primary);
            border-color: var(--pb-primary);
        }
        .r4-editor .r4-toolbar button[disabled]{ opacity:.5; pointer-events:none; }
        .r4-editor .r4-toolbar .note{ font-size:.8rem; color:var(--pb-muted); margin-left:6px; }


        /* === IMMAGINI editor: non tagliare === */
        #builderContainer img.edit-safe-img{
            width:100%;
            max-width:100%;
            height:auto !important;
            object-fit: contain !important;
            object-position: center center !important;
            background:#f8fafc;
            max-height: 70vh;
            border-radius: inherit;
        }
        #builderContainer img.edit-safe-img.preview-frontend{
            height: var(--img-h, auto) !important;
            object-fit: var(--img-fit, cover) !important;
            object-position: var(--img-pos, center center) !important;
        }

        /* Troncamento URL */
        .text-truncate-inline{ max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block; vertical-align:bottom; }

        /* --- FIX overflow immagine nel blocco --- */
        .pb-block .block-preview{
            position: relative;
            overflow: hidden;
            border-radius: inherit;
        }

        /* --- Modal impostazioni (custom, NON bootstrap) --- */
        #pbSettingsModal{
            position: fixed;
            top: 80px; left: 50%;
            transform: translateX(-50%);
            width: min(92vw, 780px);
            z-index: 3000;
            background:#fff;
            border:1px solid var(--pb-border);
            border-radius: 14px;
            box-shadow: 0 18px 50px rgba(0,0,0,.18);
            display: none;
        }
        #pbSettingsModal.show{ display:block; }
        #pbSettingsModal .pfm-header{
            display:flex; align-items:center; justify-content:space-between; gap:.5rem;
            padding:.6rem .8rem; border-bottom:1px solid var(--pb-border);
            cursor: move; user-select: none;
        }
        #pbSettingsModal .pfm-body{ padding:.8rem; max-height: calc(100vh - 180px); overflow:auto; }
        #pbSettingsModal .pfm-footer{ padding:.6rem .8rem; border-top:1px solid var(--pb-border); display:flex; gap:.5rem; justify-content:flex-end; }
        #pbSettingsModal .pfm-tabs{ display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:.6rem; }
        #pbSettingsModal .pfm-tabs .btn{ border-radius:999px; padding:.28rem .8rem; }


        /* === Wrapper immagine (editor) — coerente col frontend === */
        .pb-imgwrap{ position:relative; overflow:hidden; width:100%; }
        .pb-imgwrap > a{ display:block; width:100%; }
        .pb-imgwrap.is-fixed{ height: var(--pb-ch, 450px); }
        .pb-imgwrap.is-fixed > a{ height:100%; }
        .pb-imgwrap.is-ratio{ aspect-ratio: var(--pb-ar, 16 / 9); }
        .pb-imgwrap.is-ratio > a{ height:100%; }
        .pb-imgwrap img{
            width:100%;
            height:100%;
            display:block;
            object-fit: var(--pb-of, cover);
            object-position: var(--pb-op, center center);
        }

        /* Toggle “Anteprima editor” (mostra immagine non tagliata) */
        .pb-imgwrap.editor-safe{
            height:auto !important;
            aspect-ratio:auto !important;
        }
        .pb-imgwrap.editor-safe > a{ height:auto !important; }
        .pb-imgwrap.editor-safe img{
            height:auto !important;
            object-fit: contain !important;
            object-position: center center !important;
        }


        /* === Compact UI per pannello impostazioni (sidebar + modal) === */
        #pbStylePanel,
        #pbSettingsModal,
        .settings-content {
            --panel-fs: .875rem;           /* testo controlli */
            --panel-label-fs: .80rem;      /* etichette */
            --panel-help-fs: .75rem;       /* testi di aiuto */
            --panel-control-h: 34px;       /* altezza base */
        }

        #pbStylePanel .card-body,
        #pbSettingsModal .pfm-body,
        .settings-content .card-body { font-size: var(--panel-fs); }

        /* controlli compatti */
        #pbStylePanel .form-control,
        #pbStylePanel .form-select,
        #pbSettingsModal .form-control,
        #pbSettingsModal .form-select,
        .settings-content .form-control,
        .settings-content .form-select {
            height: var(--panel-control-h);
            padding: .28rem .55rem;
            font-size: var(--panel-fs);
        }

        /* floating: nascondo i placeholder (la label fa da placeholder) e riduco label */
        #pbStylePanel .form-floating>.form-control::placeholder,
        #pbStylePanel .form-floating>.form-select::placeholder,
        #pbSettingsModal .form-floating>.form-control::placeholder,
        #pbSettingsModal .form-floating>.form-select::placeholder { color: transparent; }

        #pbStylePanel .form-floating>.form-control,
        #pbStylePanel .form-floating>.form-select,
        #pbSettingsModal .form-floating>.form-control,
        #pbSettingsModal .form-floating>.form-select {
            height: calc(var(--panel-control-h) + 12px);
            padding-top: .6rem; padding-bottom: .25rem;
            font-size: var(--panel-fs);
        }

        #pbStylePanel .form-floating>label,
        #pbSettingsModal .form-floating>label {
            font-size: var(--panel-label-fs);
            padding: .25rem .45rem;
            opacity: .9;
        }

        /* testo “small-help” più piccolo */
        #pbStylePanel .small-help,
        #pbSettingsModal .small-help,
        .settings-content .small-help { font-size: var(--panel-help-fs); }

        /* badge info “i” + tooltip semplice (senza JS esterno) */
        .info-badge {
            position: relative;
            display: inline-flex; align-items:center; justify-content:center;
            width: 18px; height: 18px; margin-left: 6px;
            border-radius: 999px; border: 1px solid var(--pb-border);
            font-size: .72rem; line-height: 1; cursor: help;
            background: #f8fafc; color:#0b5ed7;
        }
        .with-help { position: relative; }
        .with-help .info-badge { position: absolute; right: 6px; top: 6px; }

        /* Tooltip CSS-only */
        .info-badge[data-help]:hover::after,
        .info-badge[data-help]:focus::after {
            content: attr(data-help);
            position: absolute; z-index: 10;
            left: 50%; transform: translateX(-50%);
            bottom: calc(100% + 8px);
            max-width: 280px;
            background: #111827; color: #fff;
            padding: .45rem .55rem; border-radius: 8px;
            font-size: .75rem; box-shadow: 0 8px 20px rgba(0,0,0,.18);
            white-space: normal;
        }
        .info-badge[data-help]:hover::before,
        .info-badge[data-help]:focus::before {
            content: ""; position: absolute; bottom: calc(100% + 4px); left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent; border-top-color:#111827;
        }

    </style>

    <script>window.__PLUGIN_BLOCKS__ = @json($pluginBlocksRegistry ?? []);</script>

    {{-- FORM --}}
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" id="pageForm" novalidate>
        @csrf
        @method('PATCH')

        {{-- Topbar --}}
        <div class="editor-topbar mb-3">
            <div class="container-fluid py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <h1 class="h4 mb-0"><i class="bi bi-pencil-square me-2"></i> Modifica: {{ $page->title }}</h1>
                        @if($page->status)
                            <span class="badge text-bg-{{ $page->status === 'published' ? 'success' : ($page->status === 'draft' ? 'secondary' : 'warning') }}">
                                <i class="bi bi-{{ $page->status === 'published' ? 'check2-circle' : ($page->status === 'draft' ? 'file-earmark' : 'exclamation-triangle') }}"></i>
                                {{ ucfirst($page->status) }}
                            </span>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        @if($page->slug)
                            <a href="{{ route('page.show', $page->slug) }}" class="btn btn-ghost btn-pill" target="_blank">
                                <i class="bi bi-eye me-1"></i> Anteprima
                            </a>
                        @endif

                        <button type="button" class="btn btn-ghost btn-pill" id="densityToggle" title="Densità interfaccia">
                            <i class="bi bi-arrows-collapse"></i> Compatta
                        </button>

                        <button type="submit" form="pageForm"
                                formaction="{{ route('admin.pages.update', $page) }}" formmethod="post"
                                name="status" value="draft"
                                class="btn btn-outline-secondary btn-pill">
                            <i class="bi bi-floppy me-1"></i> Salva Bozza
                        </button>

                        <button type="submit" form="pageForm"
                                formaction="{{ route('admin.pages.update', $page) }}" formmethod="post"
                                name="status" value="published"
                                class="btn btn-primary btn-pill btn-glow">
                            <i class="bi bi-check2-circle me-1"></i> Pubblica
                        </button>

                        <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost btn-pill">
                            <i class="bi bi-arrow-left me-1"></i> Chiudi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Sidebar Settings --}}
            <div class="col-lg-3 settings-col" id="settingsCol">
                <div class="sidebar-sticky">
                    <div class="settings-rail d-none d-lg-flex">
                        <button type="button" class="btn btn-light border rail-btn" id="settingsToggle" title="Mostra/Nascondi impostazioni">
                            <i class="bi bi-gear"></i>
                        </button>
                    </div>

                    <div class="settings-content-host">
                        <div id="settingsContent" class="settings-content">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <i class="bi bi-gear me-1"></i> Impostazioni
                                </div>
                                <div class="card-body">
                                    {{-- GRID 2 COLONNE --}}
                                    <div class="form-grid two">
                                        <div class="field">
                                            <label class="form-label">Titolo *</label>
                                            <input type="text" name="title" class="form-control" value="{{ old('title', $page->title) }}" required>
                                        </div>

                                        <div class="field">
                                            <label class="form-label">Data Pubblicazione</label>
                                            <input type="datetime-local" name="published_at" class="form-control"
                                                   value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}">
                                            <div class="small-help">Vuoto = pubblica subito.</div>
                                        </div>

                                        <div class="field col-span-2">
                                            <label class="form-label">Slug</label>
                                            <div class="input-group">
                                                <span class="input-group-text d-none d-xl-inline">/</span>
                                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug) }}">
                                                @if($page->slug)
                                                    <a class="btn btn-outline-secondary" href="{{ route('page.show', $page->slug) }}" target="_blank" title="Apri">
                                                        <i class="bi bi-box-arrow-up-right"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="small-help">URL finale: /{{ old('slug', $page->slug) }}</div>
                                        </div>

                                        <div class="field col-span-2">
                                            <label class="form-label">Estratto</label>
                                            <textarea name="excerpt" class="form-control" rows="2" autogrow>{{ old('excerpt', $page->excerpt) }}</textarea>
                                        </div>

                                        <div class="field col-span-2">
                                            <label class="form-label d-block mb-1">Visibilità contenuti</label>
                                            <div class="list-switch">
                                                <input type="hidden" name="meta[show_title]" value="0">
                                                <div class="form-check form-switch">
                                                    <label class="form-check-label" for="switchShowTitle">Mostra il titolo</label>
                                                    <input class="form-check-input" type="checkbox" id="switchShowTitle" name="meta[show_title]" value="1"
                                                        @checked(old('meta.show_title', $page->meta['show_title'] ?? true))>
                                                </div>

                                                <input type="hidden" name="meta[show_excerpt]" value="0">
                                                <div class="form-check form-switch">
                                                    <label class="form-check-label" for="switchShowExcerpt">Mostra l’estratto</label>
                                                    <input class="form-check-input" type="checkbox" id="switchShowExcerpt" name="meta[show_excerpt]" value="1"
                                                        @checked(old('meta.show_excerpt', $page->meta['show_excerpt'] ?? false))>
                                                </div>

                                                <input type="hidden" name="meta[show_pubdate]" value="0">
                                                <div class="form-check form-switch">
                                                    <label class="form-check-label" for="switchShowPubdate">Mostra la data di pubblicazione</label>
                                                    <input class="form-check-input" type="checkbox" id="switchShowPubdate" name="meta[show_pubdate]" value="1"
                                                        @checked(old('meta.show_pubdate', $page->meta['show_pubdate'] ?? true))>
                                                </div>

                                                <input type="hidden" name="meta[show_author]" value="0">
                                                <div class="form-check form-switch">
                                                    <label class="form-check-label" for="switchShowAuthor">Mostra autore/ultimo editor</label>
                                                    <input class="form-check-input" type="checkbox" id="switchShowAuthor" name="meta[show_author]" value="1"
                                                        @checked(old('meta.show_author', $page->meta['show_author'] ?? true))>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="field col-span-2">
                                            <h6 class="mb-2"><i class="bi bi-search me-1"></i> SEO <span class="subtle">(facoltativo)</span></h6>
                                            <div class="mb-2">
                                                <label class="form-label small">Meta Title <span class="counter" id="mtCounter"></span></label>
                                                <input type="text" name="meta[title]" class="form-control form-control-sm" maxlength="60"
                                                       value="{{ old('meta.title', $page->meta['title'] ?? '') }}">
                                            </div>
                                            <div>
                                                <label class="form-label small">Meta Description <span class="counter" id="mdCounter"></span></label>
                                                <textarea name="meta[description]" class="form-control form-control-sm" rows="2" maxlength="160">{{ old('meta.description', $page->meta['description'] ?? '') }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Extra sidebar (homepage + menu) --}}
                                        @include('admin.pages._sidebar', ['page' => $page])
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header"><i class="bi bi-tools me-1"></i> Azioni Builder</div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="btnAddSection" class="btn btn-soft btn-pill">
                                            <i class="bi bi-plus-lg me-1"></i> Aggiungi Blocco
                                        </button>
                                        <button type="button" id="btnCollapseAll" class="btn btn-ghost btn-pill">
                                            <i class="bi bi-chevron-bar-contract me-1"></i> Comprimi tutti
                                        </button>
                                        <button type="button" id="btnExpandAll" class="btn btn-ghost btn-pill">
                                            <i class="bi bi-chevron-bar-expand me-1"></i> Espandi tutti
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- === PANNELLO STILE BLOCCO (sidebar) ================================== -->
                            <div id="pbStylePanel" class="card mb-3 d-none">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-sliders me-2"></i>Stile blocco</span>
                                    <small class="text-muted" id="pbStyleBlockId"></small>
                                </div>
                                <div class="card-body">
                                    <!-- Dimensioni -->
                                    <div class="mb-3">
                                        <label class="form-label">Dimensioni</label>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="form-floating">
                                                    <input type="number" class="form-control" data-style="maxWidth" placeholder="maxWidth">
                                                    <label>maxWidth (px)</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-floating">
                                                    <input type="number" class="form-control" data-style="minHeight" placeholder="minHeight">
                                                    <label>minHeight (px)</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-floating">
                                                    <input type="number" class="form-control" data-style="height" placeholder="height">
                                                    <label>height (px)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Margini -->
                                    <div class="mb-3">
                                        <label class="form-label">Margin (top/right/bottom/left)</label>
                                        <div class="row g-2">
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="margin.t" placeholder="top"><label>top</label></div></div>
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="margin.r" placeholder="right"><label>right</label></div></div>
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="margin.b" placeholder="bottom"><label>bottom</label></div></div>
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="margin.l" placeholder="left"><label>left</label></div></div>
                                        </div>
                                    </div>

                                    <!-- Padding -->
                                    <div class="mb-3">
                                        <label class="form-label">Padding (top/right/bottom/left)</label>
                                        <div class="row g-2">
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="padding.t" placeholder="top"><label>top</label></div></div>
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="padding.r" placeholder="right"><label>right</label></div></div>
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="padding.b" placeholder="bottom"><label>bottom</label></div></div>
                                            <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="padding.l" placeholder="left"><label>left</label></div></div>
                                        </div>
                                    </div>

                                    <!-- Allineamento -->
                                    <div class="mb-3">
                                        <label class="form-label">Allineamento contenuto</label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="align">
                                                        <option value="">(auto)</option>
                                                        <option value="left">left</option>
                                                        <option value="center">center</option>
                                                        <option value="right">right</option>
                                                        <option value="justify">justify</option>
                                                    </select>
                                                    <label>Text align (blocchi "text")</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="hAlign">
                                                        <option value="">(auto)</option>
                                                        <option value="start">start</option>
                                                        <option value="center">center</option>
                                                        <option value="end">end</option>
                                                    </select>
                                                    <label>Horizontal align (altri tipi)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sfondo base (colore/gradiente) -->
                                    <div class="mb-3">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="bgType" id="bgTypeSel">
                                                        <option value="none">(nessuno)</option>
                                                        <option value="color">Colore</option>
                                                        <option value="gradient">Gradiente</option>
                                                    </select>
                                                    <label>Tipo sfondo</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" data-style="bg" placeholder="fallback CSS (opz.)">
                                                    <label>Bg libero (opz.)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3" id="bgColorRow">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" data-style="bg1" placeholder="#RRGGBB">
                                                    <label>bg1 (colore primario)</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" data-style="bg2" placeholder="#RRGGBB">
                                                    <label>bg2 (secondario, per gradiente)</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="number" class="form-control" data-style="bgAngle" placeholder="0..360">
                                                    <label>Angolo gradiente (deg)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sfondo immagine -->
                                    <div class="mb-2">
                                        <label class="form-label d-flex align-items-center">
                                            <span class="me-2">Immagine di sfondo</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="bgImagePick">
                                                <i class="bi bi-images me-1"></i>Media
                                            </button>
                                        </label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="bi bi-card-image"></i></span>
                                            <input type="text" class="form-control" data-style="bgImage" placeholder="URL immagine…">
                                            <button class="btn btn-outline-secondary" type="button" id="bgImageClear">Rimuovi</button>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="bgImageFit">
                                                        <option value="cover">cover</option>
                                                        <option value="contain">contain</option>
                                                        <option value="auto">auto</option>
                                                    </select>
                                                    <label>Fit</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="bgImageRepeat">
                                                        <option value="no-repeat">no-repeat</option>
                                                        <option value="repeat">repeat</option>
                                                        <option value="repeat-x">repeat-x</option>
                                                        <option value="repeat-y">repeat-y</option>
                                                    </select>
                                                    <label>Repeat</label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="bgAttachment">
                                                        <option value="">scroll</option>
                                                        <option value="fixed">fixed</option>
                                                        <option value="local">local</option>
                                                    </select>
                                                    <label>Attachment</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="bgImagePos">
                                                        <option value="center center">center center</option>
                                                        <option value="top center">top center</option>
                                                        <option value="bottom center">bottom center</option>
                                                        <option value="center left">center left</option>
                                                        <option value="center right">center right</option>
                                                        <option value="top left">top left</option>
                                                        <option value="top right">top right</option>
                                                        <option value="bottom left">bottom left</option>
                                                        <option value="bottom right">bottom right</option>
                                                    </select>
                                                    <label>Posizione</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Full viewport height -->
                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">Altezza</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="stFullHeight" data-style="fullHeight">
                                            <label class="form-check-label" for="stFullHeight">Occupa tutta la viewport (100vh)</label>
                                        </div>
                                    </div>

                                    <!-- Sfondo video + overlay -->
                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">
                                            <span class="me-2">Video di sfondo</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="bgVideoPick">
                                                <i class="bi bi-collection-play me-1"></i>Media
                                            </button>
                                        </label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="bi bi-camera-video"></i></span>
                                            <input type="text" class="form-control" data-style="bgVideo" placeholder="URL .mp4 / .webm…">
                                            <button class="btn btn-outline-secondary" type="button" id="bgVideoClear">Rimuovi</button>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <select class="form-select" data-style="bgVideoFit">
                                                        <option value="cover">cover</option>
                                                        <option value="contain">contain</option>
                                                    </select>
                                                    <label>Object-fit</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" data-style="overlay" placeholder="es. rgba(0,0,0,.35)">
                                                    <label>Overlay (CSS color)</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" data-style="bgVideoAutoplay" checked>
                                                    <label class="form-check-label small">Autoplay</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" data-style="bgVideoMuted" checked>
                                                    <label class="form-check-label small">Muted</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" data-style="bgVideoLoop" checked>
                                                    <label class="form-check-label small">Loop</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" data-style="bgVideoPlaysinline" checked>
                                                    <label class="form-check-label small">Inline</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <hr>

                                    <!-- Bordo (opz.) -->
                                    <div class="row g-2">
                                        <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="border.w" placeholder="spessore"><label>w (px)</label></div></div>
                                        <div class="col-3"><div class="form-floating">
                                                <select class="form-select" data-style="border.s">
                                                    <option value="solid">solid</option><option value="dashed">dashed</option><option value="dotted">dotted</option><option value="double">double</option>
                                                </select><label>stile</label></div></div>
                                        <div class="col-3"><div class="form-floating"><input type="text" class="form-control" data-style="border.c" placeholder="#e5e7eb"><label>colore</label></div></div>
                                        <div class="col-3"><div class="form-floating"><input type="number" class="form-control" data-style="border.r" placeholder="raggio"><label>r (px)</label></div></div>
                                    </div>
                                </div>
                            </div>


                        </div> {{-- /settings-content --}}
                    </div>
                </div>
            </div>

            {{-- Page Builder --}}
            <div class="col-12 col-lg builder-col" id="builderCol">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span><i class="bi bi-layers me-1"></i> Page Builder</span>
                            <span class="small text-muted">Trascina i Blocchi per riordinarli</span>
                        </div>
                        <div class="palette">
                            <button type="button" class="btn btn-soft btn-pill" data-action="add-section">
                                <i class="bi bi-plus-lg me-1"></i> Blocco
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-pill" data-action="add-item" data-type="text">
                                <i class="bi bi-type me-1"></i> Testo
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-pill" data-action="add-item" data-type="image">
                                <i class="bi bi-image me-1"></i> Immagine
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-pill" data-action="add-item" data-type="gallery">
                                <i class="bi bi-images me-1"></i> Galleria
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-pill" data-action="add-item" data-type="carousel">
                                <i class="bi bi-collection me-1"></i> Carosello
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-pill" data-action="add-item" data-type="video">
                                <i class="bi bi-camera-video me-1"></i> Video
                            </button>
                        </div>
                    </div>

                    <div class="card-body" id="builderContainer">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <div>Caricamento editor…</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden input per content JSON --}}
        <input type="hidden" name="content" id="contentJson">

        {{-- Floating Add --}}
        <button type="button" class="btn btn-primary btn-pill shadow pb-fab" id="fabAdd">
            <i class="bi bi-plus-lg me-1"></i> Blocco
        </button>

        {{-- Backdrop + pannello slide-out (mobile/tablet) --}}
        <div class="settings-backdrop d-lg-none" id="settingsBackdrop"></div>
        <div class="settings-offcanvas d-lg-none" id="settingsOffcanvas" aria-hidden="true"></div>

        {{-- Floating gear (solo mobile) --}}
        <button type="button" class="btn btn-primary pb-gear-fab d-lg-none btn-pill" id="pbGearFab" aria-label="Impostazioni">
            <i class="bi bi-gear"></i>
        </button>

        {{-- Modal impostazioni blocco (custom) --}}
        <div id="pbSettingsModal" aria-hidden="true">
            <div class="pfm-header" id="pbSettingsDrag">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-sliders"></i>
                    <strong id="pbSettingsTitle">Impostazioni blocco</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-ghost" id="pbSettingsMin"><i class="bi bi-dash"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="pbSettingsClose"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="pfm-body">
                <div class="pfm-tabs">
                    <button type="button" class="btn btn-ghost btn-sm" data-tab="content">Contenuto</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-tab="style">Stile</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-tab="effects">Effetti/Animazione</button>
                </div>
                <div id="pbSettingsContentArea"><!-- riempito via JS --></div>
            </div>
            <div class="pfm-footer">
                <button type="button" class="btn btn-ghost" id="pbSettingsApply">Applica</button>
                <button type="button" class="btn btn-primary" id="pbSettingsClose2">Chiudi</button>
            </div>
        </div>
    </form>

    {{-- Overlay caricamento file --}}
    <div class="upload-overlay" id="uploadOverlay" aria-hidden="true">
        <div class="upload-card">
            <div class="d-flex align-items-center mb-2">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <div class="fw-semibold">Caricamento file…</div>
            </div>
            <div class="progress mb-2">
                <div class="progress-bar" id="uploadProgress" role="progressbar" style="width:0%"></div>
            </div>
            <div class="small msg" id="uploadMessage">Preparazione…</div>
        </div>
    </div>

    {{-- Toasts --}}
    <div class="upload-toasts" id="toastArea" aria-live="polite" aria-atomic="true"></div>

    {{-- ====== Media Picker Modal ====== --}}
    <div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-images me-2"></i>Seleziona da Archivio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col">
                            <input id="mpSearch" class="form-control" placeholder="Cerca per nome/titolo/alt…">
                        </div>
                        <div class="col-auto">
                            <span class="text-muted small" id="mpCounter"></span>
                        </div>
                    </div>
                    <div id="mpGrid" class="row g-3">
                        <div class="col-12 text-center text-muted py-5">
                            <div class="spinner-border spinner-border-sm"></div>
                            <div>Caricamento…</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="mpPrev" disabled>&laquo; Precedente</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="mpNext" disabled>Successivo &raquo;</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="button" class="btn btn-primary" id="mpConfirm" disabled>Seleziona</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== Modal "Incolla URL immagine" (con fallback se manca Bootstrap) ====== --}}
    <div class="modal fade" id="imageUrlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-link-45deg me-2"></i>Imposta URL immagine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">URL</label>
                    <input type="text" id="imageUrlInput" class="form-control" placeholder="https://…">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="imageUrlSetFull" checked>
                        <label class="form-check-label small" for="imageUrlSetFull">
                            Usa lo stesso URL anche come <code>full</code>
                        </label>
                    </div>
                    <div id="imageUrlPreviewWrap" class="mt-3" style="display:none;">
                        <div class="small text-muted mb-1">Anteprima</div>
                        <img id="imageUrlPreview" src="" alt="" class="img-fluid rounded border" style="max-height:260px; object-fit:contain;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" id="imageUrlConfirm">Usa URL</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            'use strict';

            const MEDIA_BROWSE_URL = '{{ route('admin.media.browse') }}';

            const initialRaw = @json($page->content ?? []);
            let initial = initialRaw;
            if (typeof initialRaw === 'string') {
                try { initial = JSON.parse(initialRaw); } catch(_) { initial = []; }
            }
            window.builderData = Array.isArray(initial) ? initial : [];


            const PB_COLLAPSE_KEY = 'pb_collapse_{{ $page->id }}';
            let collapseState = {};
            try { collapseState = JSON.parse(localStorage.getItem(PB_COLLAPSE_KEY) || '{}') || {}; } catch(_) { collapseState = {}; }
            const isCollapsed = (secId) => !!collapseState[secId];
            const setCollapsed = (secId, v) => { collapseState[secId] = !!v; localStorage.setItem(PB_COLLAPSE_KEY, JSON.stringify(collapseState)); };

            const DENSITY_KEY = 'pb_density';
            function applyDensity(d){ document.documentElement.setAttribute('data-density', d); }
            applyDensity(localStorage.getItem(DENSITY_KEY) || 'cozy');

            const uid = (p='id') => `${p}_${Date.now()}_${Math.floor(Math.random()*10000)}`;
            const q = (sel, ctx=document) => ctx.querySelector(sel);
            const qa = (sel, ctx=document) => Array.prototype.slice.call(ctx.querySelectorAll(sel));
            const cloneDeep = (obj) => JSON.parse(JSON.stringify(obj));

            const DEFAULT_STYLE = () => ({
                margin:{t:0,r:0,b:0,l:0},
                padding:{t:0,r:0,b:0,l:0},
                border:{w:0,s:'solid',c:'#000000',r:0},
                maxWidth:'', minHeight:'', height:'',
                align:'left', hAlign:'start',

                // Sfondo base
                bg:'', bgType:'none', bg1:'#ffffff', bg2:'#000000', bgAngle:90,

                // Sfondo immagine
                bgImage:'', bgImageFit:'cover', bgImagePos:'center center',
                bgImageRepeat:'no-repeat', bgAttachment:'',

                // ▼▼ Nuovo: Video di sfondo + overlay + full-height ▼▼
                fullHeight:false,                // 100vh
                bgVideo:'',                      // URL file video
                bgVideoFit:'cover',              // cover | contain
                bgVideoMuted:true,
                bgVideoLoop:true,
                bgVideoAutoplay:true,
                bgVideoPlaysinline:true,
                overlay:''                       // es. 'rgba(0,0,0,.35)'
            });


            const ensureShape = () => {
                if (!Array.isArray(window.builderData)) window.builderData = [];
                window.builderData = window.builderData.map(s => ({
                    id: s.id || uid('sec'),
                    blocks: Array.isArray(s.blocks) ? s.blocks : []
                }));
                window.builderData.forEach(sec => {
                    (sec.blocks||[]).forEach(b => { b.style = normStyle(b.style); });
                });
            };

            const normStyle = (st) => {
                const base = DEFAULT_STYLE();
                st = st && typeof st === 'object' ? st : {};
                st.margin   = Object.assign({}, base.margin,   st.margin  || {});
                st.padding  = Object.assign({}, base.padding,  st.padding || {});
                st.border   = Object.assign({}, base.border,   st.border  || {});
                st.maxWidth = (st.maxWidth ?? base.maxWidth);
                st.minHeight= (st.minHeight ?? base.minHeight);
                st.height   = (st.height ?? base.height);
                st.align    = st.align  || base.align;
                st.hAlign   = st.hAlign || base.hAlign;

                st.bgType   = st.bgType || 'none';
                st.bg1      = st.bg1 ?? '#ffffff';
                st.bg2      = st.bg2 ?? '#000000';
                st.bgAngle  = Number.isFinite(+st.bgAngle) ? parseInt(st.bgAngle,10) : 90;
                st.bg       = st.bg ?? '';

                st.bgImage        = st.bgImage ?? '';
                st.bgImageFit     = st.bgImageFit || 'cover';
                st.bgImagePos     = st.bgImagePos || 'center center';
                st.bgImageRepeat  = st.bgImageRepeat || 'no-repeat';
                st.bgAttachment   = st.bgAttachment || '';

                st.fullHeight = !!(st.fullHeight);
                st.bgVideo = st.bgVideo ?? '';
                st.bgVideoFit = st.bgVideoFit || 'cover';
                st.bgVideoMuted = (st.bgVideoMuted!==false);
                st.bgVideoLoop = (st.bgVideoLoop!==false);
                st.bgVideoAutoplay = (st.bgVideoAutoplay!==false);
                st.bgVideoPlaysinline = (st.bgVideoPlaysinline!==false);
                st.overlay = st.overlay ?? '';

                return st;
            };


            const styleToCss = (style, blockType = 'text') => {
                style = normStyle(style);
                const px = n => (Number.isFinite(+n) ? `${parseInt(n,10)}px` : '0px');
                const pxOrEmpty = n => (Number.isFinite(+n) ? `${parseInt(n,10)}px` : '');

                // ⚠️ va dichiarata PRIMA di usarla
                let inner = '';

                // Full height 100vh
                if (style.fullHeight) {
                    inner += `min-height:100vh;height:100vh;`;
                }

                // --- background base: color/gradient/fallback string ---
                let bgColor = '';
                let bgGradient = '';
                if (style.bgType === 'color') {
                    bgColor = style.bg1 || '';
                } else if (style.bgType === 'gradient') {
                    const ang = Number.isFinite(+style.bgAngle) ? parseInt(style.bgAngle,10) : 0;
                    bgGradient = `linear-gradient(${ang}deg, ${style.bg1||'#000'}, ${style.bg2||'#fff'})`;
                }

                // --- background image ---
                const hasImg = !!style.bgImage;
                const imgUrl = hasImg ? `url("${style.bgImage.replace(/"/g,'\\"')}")` : '';

                // background-color (se scelto)
                if (bgColor) inner += `background-color:${bgColor};`;

                // background-image: gradient + image (in stacking), altrimenti solo image o fallback "bg"
                if (bgGradient && hasImg)      inner += `background-image:${bgGradient},${imgUrl};`;
                else if (bgGradient)           inner += `background-image:${bgGradient};`;
                else if (hasImg)               inner += `background-image:${imgUrl};`;
                else if (style.bg)             inner += `background:${style.bg};`;

                if (hasImg) {
                    if (style.bgImageFit && style.bgImageFit!=='auto') inner += `background-size:${style.bgImageFit};`;
                    if (style.bgImagePos)    inner += `background-position:${style.bgImagePos};`;
                    if (style.bgImageRepeat) inner += `background-repeat:${style.bgImageRepeat};`;
                    if (style.bgAttachment)  inner += `background-attachment:${style.bgAttachment};`;
                }

                // spacing & border
                inner += `padding:${px(style.padding.t)} ${px(style.padding.r)} ${px(style.padding.b)} ${px(style.padding.l)};`;
                inner += `border:${px(style.border.w)} ${style.border.s} ${style.border.c||'#000'};border-radius:${px(style.border.r)};`;

                // dimensioni interne
                const mw = parseInt(style.maxWidth,10);
                if (!isNaN(mw) && mw>0){ inner += `max-width:${mw}px;margin-left:auto;margin-right:auto;`; }
                const mih = pxOrEmpty(style.minHeight);
                if (mih) inner += `min-height:${mih};`;
                const h = pxOrEmpty(style.height);
                if (h) inner += `height:${h};`;

                // allineamento
                if (blockType === 'text'){
                    inner += `text-align:${style.align||'left'};`;
                } else {
                    inner += `display:flex;`;
                    const map = { start:'flex-start', center:'center', end:'flex-end' };
                    inner += `justify-content:${map[style.hAlign]||'flex-start'};`;
                }

                // outer (margini)
                const outer = `margin:${px(style.margin.t)} ${px(style.margin.r)} ${px(style.margin.b)} ${px(style.margin.l)};`;

                return { inner, outer };
            };

            // Esporto per il pannello laterale
            window.__styleToCss = styleToCss;


            const findSection = id => window.builderData.find(s => s.id === id);
            const findBlockById = (bid) => {
                for (const sec of window.builderData){
                    const blk = (sec.blocks||[]).find(b => b.id === bid);
                    if (blk) return { sec, blk };
                }
                return null;
            };
            const findBlock = (sid, bid) => { const s = findSection(sid); if (!s) return null; return (s.blocks || []).find(b => b.id === bid) || null; };

            /* Toast & Overlay */
            function showToast(text, type=''){
                const host = q('#toastArea'); if (!host) return;
                const el = document.createElement('div');
                el.className = 'upload-toast ' + (type||'');
                el.textContent = text;
                host.appendChild(el);
                setTimeout(()=> { el.style.opacity='0'; el.style.transform='translateY(8px)'; }, 2500);
                setTimeout(()=> host.removeChild(el), 3000);
            }
            function setOverlay(show, msg=null, pct=null){
                const ov = q('#uploadOverlay'), bar = q('#uploadProgress'), label = q('#uploadMessage');
                if (!ov) return;
                if (show) ov.classList.add('show'); else ov.classList.remove('show');
                if (msg && label) label.textContent = msg;
                if (pct!=null && bar){ bar.style.width = Math.max(0,Math.min(100, pct)) + '%'; }
            }

            /* Upload helpers */
            function parseUploadResponse(xhr){
                const ct = (xhr.getResponseHeader('Content-Type')||'').toLowerCase();
                let data = null;
                if (xhr.response && typeof xhr.response === 'object') data = xhr.response;
                else if (xhr.responseText){ try { data = JSON.parse(xhr.responseText); } catch(e){} }
                const url   = data?.url || data?.path || data?.location || data?.file?.url || data?.data?.url || null;
                const thumb = data?.thumb || data?.thumbnail || data?.file?.thumb || url || null;
                return { data, url, thumb, ct };
            }


            // --- Varianti media: scegli una non-croppata per il blocco immagine ---
            function pickNonCroppedVariant(item){
                const v = item?.variants || {};
                const asStr = x => (typeof x === 'string' && x) ? x : null;
                return asStr(v['59']) || asStr(v['75']) || asStr(v['full']) || item?.url || item?.path || item?.src || item?.thumb || '';
            }

            // Se l'URL ha il suffisso _thumb/_25/_59/_75/_full, lo sostituisce
            function swapVariantInUrl(url, key){
                return (url || '').replace(/_(thumb|25|59|75|full)\.(\w+)$/i, `_${key}.$2`);
            }

            // --- Guardia anti-taglio (post-render) ---
            async function ensureNoCropForImage(secId, blockId){
                const b = (typeof findBlock === 'function') ? findBlock(secId, blockId) : null;
                if (!b || !b.image) return;

                const opt = b.image.options || {};
                const hMode = opt.heightMode || 'auto';
                const fit   = opt.objectFit || 'cover';
                if (hMode === 'auto') return;           // in auto non si taglia mai
                if (fit !== 'cover') return;            // contain/fill/none/scale-down non tagliano

                const wrap = document.querySelector(`.pb-imgwrap[data-bid="${blockId}"]`);
                const img  = wrap ? wrap.querySelector('img') : null;
                if (!img) return;

                if (!img.complete) {
                    await new Promise(res => img.addEventListener('load', res, { once:true }));
                }
                const nW = img.naturalWidth || 0, nH = img.naturalHeight || 0;
                if (!nW || !nH) return;

                const imgAR = nW / nH;
                let boxAR = null;

                if (hMode === 'fixed'){
                    const cw   = wrap.clientWidth || wrap.getBoundingClientRect().width || 0;
                    const hPx  = Math.max(50, parseInt(opt.heightPx || 450, 10));
                    if (cw && hPx) boxAR = cw / hPx;
                } else if (hMode === 'ratio'){
                    const parts = String(opt.aspectRatio || '16 / 9').split('/');
                    const a = parseFloat(parts[0]); const bAR = parseFloat(parts[1]);
                    if (a > 0 && bAR > 0) boxAR = a / bAR;
                }

                if (!boxAR) return;

                // se differenza di AR > 2% e siamo in cover, l'immagine verrà tagliata
                const willCrop = Math.abs(imgAR - boxAR) > 0.02;
                if (willCrop){
                    // Passa a contain per evitare il taglio
                    updateImageOption(secId, blockId, 'objectFit', 'contain');
                    // renderBuilder è già richiamato da updateImageOption
                    showToast('Immagine adattata per evitare il taglio (object-fit: contain).', 'success');
                }
            }

            // Chiama la guardia dopo il prossimo paint
            function guardNoCropSoon(secId, blockId){
                requestAnimationFrame(()=> requestAnimationFrame(()=> ensureNoCropForImage(secId, blockId)));
            }


            function xhrUpload(file, onProgress){
                return new Promise((resolve, reject) => {
                    const form = new FormData();
                    form.append('file', file, file.name);
                    form.append('_token', '{{ csrf_token() }}');

                    // 👇 prova a recuperare il blocco corrente (se presente nello scope di pickImage)
                    try {
                        const b = (typeof findBlock === 'function' && window.__CURRENT_UPLOAD_CTX__)
                            ? findBlock(window.__CURRENT_UPLOAD_CTX__.secId, window.__CURRENT_UPLOAD_CTX__.blockId) : null;
                        const prof = b?.image?.uploadProfile || 'photo';
                        form.append('profile', prof);
                        if (prof === 'logo') form.append('fit', 'contain');
                    } catch(_) {}

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route("admin.media.store") }}', true);
                    xhr.responseType = 'json';
                    xhr.setRequestHeader('Accept','application/json');
                    xhr.upload.onprogress = function(ev){
                        if (ev.lengthComputable && typeof onProgress === 'function'){
                            const p = (ev.loaded / ev.total) * 100;
                            onProgress(p, ev);
                        }
                    };
                    xhr.onload = function(){
                        const { data, url, thumb, ct } = parseUploadResponse(xhr);
                        if (xhr.status >= 200 && xhr.status < 300){
                            if (url){ resolve({ url, thumb, raw:data }); }
                            else { console.warn('Upload OK ma payload inatteso', {status:xhr.status, ct, data, text:xhr.responseText}); reject(new Error('Upload riuscito ma risposta senza URL')); }
                        } else {
                            const msg = data?.message || `Upload fallito (${xhr.status})`;
                            reject(new Error(msg));
                        }
                    };
                    xhr.onerror = function(){ reject(new Error('Errore di rete durante l’upload')); };
                    xhr.send(form);
                });
            }
            async function uploadFiles(files){
                const out=[]; let processed = 0; const total = files.length;
                setOverlay(true, 'Preparazione…', 0);
                for (const file of files){
                    await xhrUpload(file, (p)=>{
                        const overall = (processed/total)*100 + (p/100)*(100/total);
                        setOverlay(true, `Caricamento ${processed+1}/${total}… ${overall.toFixed(0)}%`, overall);
                    }).then(d=>{
                        out.push(d); processed++;
                        setOverlay(true, `File ${processed}/${total} caricato`, (processed/total)*100);
                    }).catch(err=>{
                        setOverlay(false); showToast('Errore upload: ' + err.message, 'error'); throw err;
                    });
                }
                setOverlay(false);
                showToast(total>1 ? 'Immagini caricate' : 'Immagine caricata', 'success');
                return out;
            }

            /* R4 RICH TEXT EDITOR v2 (stateful + format painter) */
            window.__R4_EDIT_LOCK = 0;          // >0 = in editing
            window.__R4_NEED_RERENDER = false;  // true = re-render appena esci dall'editor

            const r4Editors = new Map();
            let __r4SelectionObserverBound = false;

            /* --- Utils selezione e caret --- */
            function getSelectionIn(el){
                const sel = window.getSelection();
                if (!sel || sel.rangeCount === 0) return null;
                const r = sel.getRangeAt(0);
                if (!el.contains(r.commonAncestorContainer)) return null;
                return r;
            }
            function insertHtmlAtCaret(editable, html){
                editable.focus();
                document.execCommand('insertHTML', false, html);
            }
            function closestSelectedImage(editable){
                const sel = window.getSelection();
                if (!sel || sel.rangeCount===0) return null;
                let node = sel.anchorNode;
                if (node && node.nodeType===3) node = node.parentNode;
                if (!node) return null;
                if (node.tagName === 'IMG') return node;
                return node.closest && node.closest('img');
            }

            /* --- Sanitizzazione & normalizzazione --- */
            function rgbToHex(rgb){
                if (!rgb) return null;
                const m = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
                if (!m) return rgb.startsWith('#') ? rgb : null;
                const n = x => ('0' + (parseInt(x,10)|0).toString(16)).slice(-2);
                return '#' + n(m[1]) + n(m[2]) + n(m[3]);
            }
            function sanitizeHtml(html){
                const ALLOWED = {
                    'P':true, 'BR':true, 'H1':true, 'H2':true, 'H3':true, 'H4':true,
                    'UL':true, 'OL':true, 'LI':true, 'STRONG':true, 'B':true, 'EM':true, 'I':true, 'U':true, 'S':true,
                    'BLOCKQUOTE':true, 'PRE':true, 'CODE':true, 'SPAN':true, 'A':true, 'IMG':true
                };
                const ALLOWED_STYLES = new Set(['color','backgroundColor','fontSize','fontFamily']);
                const parser = new DOMParser();
                const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
                const root = doc.body.firstChild;
                function clean(node){
                    [...node.childNodes].forEach(child=>{
                        if (child.nodeType === 1){
                            const tag = child.tagName;
                            if (!ALLOWED[tag]){
                                const parent = child.parentNode;
                                while (child.firstChild) parent.insertBefore(child.firstChild, child);
                                parent.removeChild(child);
                                return;
                            }
                            [...child.attributes].forEach(a=>{
                                const n = a.name.toLowerCase();
                                if (n.startsWith('on')) child.removeAttribute(a.name);
                                if (n === 'style'){
                                    const tmp = child.style, keep = {};
                                    ALLOWED_STYLES.forEach(k=>{ if (tmp[k]) keep[k]=tmp[k]; });
                                    child.removeAttribute('style');
                                    const s=[];
                                    if (keep.color) s.push(`color:${keep.color}`);
                                    if (keep.backgroundColor) s.push(`background-color:${keep.backgroundColor}`);
                                    if (keep.fontSize) s.push(`font-size:${keep.fontSize}`);
                                    if (keep.fontFamily) s.push(`font-family:${keep.fontFamily}`);
                                    if (s.length) child.setAttribute('style', s.join(';'));
                                }
                                if (tag==='A' && (n==='href'||n==='target'||n==='rel')){ /* ok */ }
                                else if (tag==='IMG' && (n==='src'||n==='alt'||n==='title'||n==='class')){ /* ok */ }
                                else if (!['style','href','target','rel','src','alt','title','class','id'].includes(n)){
                                    child.removeAttribute(a.name);
                                }
                            });
                        }
                        if (child.childNodes && child.childNodes.length) clean(child);
                    });
                }
                clean(root);
                return root.innerHTML;
            }
            function sanitizeEditable(node){
                node.querySelectorAll('script,iframe,object,embed').forEach(n=>n.remove());
                node.innerHTML = sanitizeHtml(node.innerHTML || '');
            }
            function stylesEq(a,b){
                return (a.color||'')===(b.color||'') &&
                    (a.backgroundColor||'')===(b.backgroundColor||'') &&
                    (String(a.fontSize||'')===String(b.fontSize||'')) &&
                    (String(a.fontFamily||'')===String(b.fontFamily||''));
            }
            function pickSpanStyle(span){
                const st = span.style || {};
                return {
                    color: st.color || '',
                    backgroundColor: st.backgroundColor || '',
                    fontSize: st.fontSize || '',
                    fontFamily: st.fontFamily || ''
                };
            }
            function normalizeSpans(root){
                // 1) rimuovi span vuoti e zeri width
                root.querySelectorAll('span').forEach(sp=>{
                    sp.innerHTML = sp.innerHTML.replace(/\u200b+/g,'');
                    if (!sp.textContent.trim() && !sp.querySelector('img')) sp.replaceWith(document.createTextNode(''));
                });
                // 2) merge adiacenti con stesso stile
                const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT, null);
                const spans = [];
                let n;
                while ( (n = walker.nextNode()) ){
                    if (n.tagName === 'SPAN') spans.push(n);
                }
                spans.forEach(sp=>{
                    const next = sp.nextSibling;
                    if (next && next.nodeType===1 && next.tagName==='SPAN'){
                        const sa = pickSpanStyle(sp), sb = pickSpanStyle(next);
                        if (stylesEq(sa,sb)){
                            while (next.firstChild) sp.appendChild(next.firstChild);
                            next.remove();
                        }
                    }
                    // pulisci style vuoto
                    if (!sp.getAttribute('style')) sp.outerHTML = sp.innerHTML;
                });
            }
            function cleanupZeroWidth(editable){
                editable.innerHTML = editable.innerHTML.replace(/\u200b+/g,'');
            }

            /* --- Applicazione/clear stili inline --- */
            function wrapSelectionWithSpan(editable, styleObj){
                const range = getSelectionIn(editable);
                if (!range) return;
                if (range.collapsed){
                    const span = document.createElement('span');
                    Object.assign(span.style, styleObj);
                    span.appendChild(document.createTextNode('\u200b')); // zws
                    range.insertNode(span);
                    const sel = window.getSelection();
                    const r2 = document.createRange();
                    r2.setStart(span.firstChild, 1);
                    r2.collapse(true);
                    sel.removeAllRanges(); sel.addRange(r2);
                    editable.focus();
                    return;
                }
                const frag = range.extractContents();
                const span = document.createElement('span');
                Object.assign(span.style, styleObj);
                span.appendChild(frag);
                range.insertNode(span);
                normalizeSpans(editable);
                const sel = window.getSelection();
                const end = document.createRange();
                end.setStartAfter(span); end.collapse(true);
                sel.removeAllRanges(); sel.addRange(end);
                editable.focus();
            }
            function clearInlineStyle(editable, styleKeys){
                const range = getSelectionIn(editable);
                if (!range) return;
                document.execCommand('removeFormat', false, null);
                const container = document.createElement('div');
                container.appendChild(range.cloneContents());
                container.querySelectorAll('span').forEach(sp=>{
                    styleKeys.forEach(k=> sp.style[k] = '');
                    if (!sp.getAttribute('style')){
                        const parent = sp.parentNode;
                        while (sp.firstChild) parent.insertBefore(sp.firstChild, sp);
                        parent.removeChild(sp);
                    }
                });
                range.deleteContents();
                while (container.firstChild) range.insertNode(container.firstChild);
                container.remove();
            }

            /* --- Lettura stile corrente e sync toolbar --- */
            function readStyleAtCaret(editable){
                const r = getSelectionIn(editable);
                const sel = window.getSelection();
                const res = {
                    bold: document.queryCommandState('bold'),
                    italic: document.queryCommandState('italic'),
                    underline: document.queryCommandState('underline'),
                    strike: document.queryCommandState('strikeThrough'),
                    block: 'P',
                    align: 'left',
                    fontFamily: '',
                    fontSize: '',
                    color: '',
                    backgroundColor: ''
                };
                let node = r ? r.startContainer : editable.firstChild;
                if (node && node.nodeType===3) node = node.parentElement;
                if (!node) node = editable;
                // blocco
                const block = node.closest('h1,h2,h3,h4,blockquote,pre,p,li,div') || editable;
                res.block = (block.tagName||'P').toUpperCase();
                if (res.block==='DIV') res.block='P';
                // allineamento
                res.align = window.getComputedStyle(block).textAlign || 'left';
                // stile inline
                const el = node.closest('span,code,em,strong,a,img') || node;
                const cs = window.getComputedStyle(el);
                if (cs){
                    res.fontFamily = cs.fontFamily || '';
                    res.fontSize = cs.fontSize || '';
                    const c = rgbToHex(cs.color);
                    const bg = rgbToHex(cs.backgroundColor);
                    res.color = c || '';
                    res.backgroundColor = (bg && bg !== '#000000' && bg !== '#000' && bg !== '#ffffff' && bg !== '#fff') ? bg : '';
                }
                return res;
            }
            function setToolbarState(toolbar, state){
                const setActive = (qsel, on)=> toolbar.querySelectorAll(qsel).forEach(b=> b.classList.toggle('active', !!on));
                setActive('button[data-cmd="bold"]', state.bold);
                setActive('button[data-cmd="italic"]', state.italic);
                setActive('button[data-cmd="underline"]', state.underline);
                setActive('button[data-cmd="strikeThrough"]', state.strike);

                // block
                const selBlock = toolbar.querySelector('select.r4-block');
                if (selBlock){
                    const optTag = ['H1','H2','H3','H4','BLOCKQUOTE','PRE'].includes(state.block) ? state.block : 'P';
                    selBlock.value = optTag;
                }
                // font family (se non presente nella lista, lascio vuoto)
                const selFont = toolbar.querySelector('select.r4-font');
                if (selFont){
                    let matched = '';
                    const clean = (s)=> String(s||'').toLowerCase().replace(/['"]/g,'').trim();
                    const cur = clean(state.fontFamily);
                    [...selFont.options].forEach(op=>{
                        if (clean(op.value) && cur.includes(clean(op.value))) matched = op.value;
                    });
                    selFont.value = matched || '';
                }
                // font size
                const selSize = toolbar.querySelector('select.r4-size');
                if (selSize){
                    const px = parseInt(state.fontSize||'',10);
                    selSize.value = isFinite(px) ? String(px) : '';
                    // aggiorna label "Dim." con indicazione corrente
                    selSize.querySelector('option[value=""]').textContent = 'Dim.' + (isFinite(px)?` (${px}px)`:'' );
                }
                // colors
                const cInp = toolbar.querySelector('input.r4-color');
                if (cInp && state.color) cInp.value = state.color;
                const bInp = toolbar.querySelector('input.r4-bg');
                if (bInp && state.backgroundColor) bInp.value = state.backgroundColor;

                // align (non attivo via execCommandState, ma potremmo evidenziare con una classe)
                toolbar.querySelectorAll('button[data-cmd^="justify"]').forEach(btn=> btn.classList.remove('active'));
                const map = { left:'justifyLeft', center:'justifyCenter', right:'justifyRight', justify:'justifyFull' };
                const btn = toolbar.querySelector(`button[data-cmd="${map[state.align]||'justifyLeft'}"]`);
                if (btn) btn.classList.add('active');
            }

            /* --- Format painter (copia/applica stile) --- */
            let formatClipboard = null; // {color, backgroundColor, fontFamily, fontSize}
            function copyFormatFromCaret(editable, toolbar){
                const s = readStyleAtCaret(editable);
                formatClipboard = {
                    color: s.color || '',
                    backgroundColor: s.backgroundColor || '',
                    fontFamily: s.fontFamily || '',
                    fontSize: s.fontSize || ''
                };
                const ap = toolbar.querySelector('button[data-action="apply-format"]');
                if (ap) ap.disabled = false;
            }
            function applyFormatToSelection(editable){
                if (!formatClipboard) return;
                const st = {};
                if (formatClipboard.color) st.color = formatClipboard.color;
                if (formatClipboard.backgroundColor) st.backgroundColor = formatClipboard.backgroundColor;
                if (formatClipboard.fontFamily) st.fontFamily = formatClipboard.fontFamily;
                if (formatClipboard.fontSize) st.fontSize = formatClipboard.fontSize;
                if (Object.keys(st).length) wrapSelectionWithSpan(editable, st);
            }

            /* --- Toolbar --- */
            function buildToolbar(){
                const bar = document.createElement('div');
                bar.className = 'r4-toolbar';
                bar.innerHTML = `
    <div class="group">
      <button type="button" data-cmd="undo" title="Annulla (Ctrl+Z)"><i class="bi bi-arrow-counterclockwise"></i></button>
      <button type="button" data-cmd="redo" title="Ripeti (Ctrl+Y)"><i class="bi bi-arrow-clockwise"></i></button>
    </div>

    <div class="group">
      <button type="button" data-cmd="bold" title="Grassetto"><i class="bi bi-type-bold"></i></button>
      <button type="button" data-cmd="italic" title="Corsivo"><i class="bi bi-type-italic"></i></button>
      <button type="button" data-cmd="underline" title="Sottolineato"><i class="bi bi-type-underline"></i></button>
      <button type="button" data-cmd="strikeThrough" title="Barrato"><i class="bi bi-type-strikethrough"></i></button>
    </div>

    <div class="group">
      <select class="r4-block" title="Stile blocco">
        <option value="P">Paragrafo</option>
        <option value="H1">Titolo 1</option>
        <option value="H2">Titolo 2</option>
        <option value="H3">Titolo 3</option>
        <option value="H4">Titolo 4</option>
        <option value="BLOCKQUOTE">Citazione</option>
        <option value="PRE">Codice</option>
      </select>
    </div>

    <div class="group">
      <button type="button" data-cmd="justifyLeft" title="Allinea a sinistra"><i class="bi bi-text-left"></i></button>
      <button type="button" data-cmd="justifyCenter" title="Centra"><i class="bi bi-text-center"></i></button>
      <button type="button" data-cmd="justifyRight" title="Allinea a destra"><i class="bi bi-text-right"></i></button>
      <button type="button" data-cmd="justifyFull" title="Giustifica"><i class="bi bi-justify"></i></button>
    </div>

    <div class="group">
      <button type="button" data-cmd="insertUnorderedList" title="Elenco puntato"><i class="bi bi-list-ul"></i></button>
      <button type="button" data-cmd="insertOrderedList" title="Elenco numerato"><i class="bi bi-list-ol"></i></button>
      <button type="button" data-cmd="outdent" title="Riduci rientro"><i class="bi bi-text-indent-right"></i></button>
      <button type="button" data-cmd="indent" title="Aumenta rientro"><i class="bi bi-text-indent-left"></i></button>
    </div>

    <div class="group">
      <button type="button" data-action="link" title="Crea link"><i class="bi bi-link-45deg"></i></button>
      <button type="button" data-action="unlink" title="Rimuovi link"><i class="bi bi-x-lg"></i></button>
      <button type="button" data-action="anchor" title="Aggiungi ancora"><i class="bi bi-bookmark"></i></button>
    </div>

    <div class="group">
      <select class="r4-font" title="Font">
        <option value="">Font di tema</option>
        <option value="Inter, system-ui, Arial">Inter</option>
        <option value="Georgia, 'Times New Roman', serif">Georgia</option>
        <option value="Arial, Helvetica, sans-serif">Arial</option>
        <option value="'Courier New', monospace">Courier New</option>
        <option value="Montserrat, Arial, sans-serif">Montserrat</option>
        <option value="'Open Sans', Arial, sans-serif">Open Sans</option>
      </select>
      <select class="r4-size" title="Dimensione (px)">
        <option value="">Dim.</option>
        ${[12,14,16,18,20,24,28,32,40].map(n=>`<option value="${n}">${n}px</option>`).join('')}
      </select>
      <input type="color" class="r4-color" title="Colore testo">
      <input type="color" class="r4-bg" title="Evidenzia">
      <button type="button" data-action="clearstyles" title="Pulisci formattazione"><i class="bi bi-eraser"></i></button>
    </div>

    <div class="group">
      <button type="button" data-action="pick-format" title="Copia formato (format painter)"><i class="bi bi-eyedropper"></i></button>
      <button type="button" data-action="apply-format" title="Applica formato" disabled><i class="bi bi-brush"></i></button>
      <span class="note d-none d-lg-inline">Seleziona testo e applica</span>
    </div>

    <div class="group">
      <button type="button" data-action="img-upload" title="Immagine (upload)"><i class="bi bi-upload"></i></button>
      <button type="button" data-action="img-media" title="Immagine (archivio)"><i class="bi bi-images"></i></button>
      <button type="button" data-action="img-url" title="Immagine (URL)"><i class="bi bi-link-45deg"></i></button>
      <button type="button" data-action="img-left" title="Allinea immagine a sinistra"><i class="bi bi-arrow-left-square"></i></button>
      <button type="button" data-action="img-center" title="Centra immagine"><i class="bi bi-arrows"></i></button>
      <button type="button" data-action="img-right" title="Allinea immagine a destra"><i class="bi bi-arrow-right-square"></i></button>
      <button type="button" data-action="img-reset" title="Reset allineamento"><i class="bi bi-arrow-counterclockwise"></i></button>
    </div>

    <span class="txt-count" aria-live="polite"></span>
  `;
                return bar;
            }

            /* --- Init / collect / destroy --- */
            function initR4Editors(){
                document.querySelectorAll('.r4-editor').forEach(host=>{
                    const bid = host.getAttribute('data-bid');
                    if (r4Editors.has(bid)) return;

                    const toolbar = buildToolbar();
                    const editable = document.createElement('div');
                    // Lock in focus per non perdere il caret
                    editable.addEventListener('focus', ()=>{
                        window.__R4_EDIT_LOCK = (window.__R4_EDIT_LOCK || 0) + 1;
                    });
                    editable.addEventListener('blur', ()=>{
                        window.__R4_EDIT_LOCK = Math.max(0, (window.__R4_EDIT_LOCK || 0) - 1);
                        if (!window.__R4_EDIT_LOCK && window.__R4_NEED_RERENDER){
                            window.__R4_NEED_RERENDER = false;
                            renderBuilder(); // esegue il render “rimasto in coda”
                        }
                    });

                    editable.className = 'r4-editable';
                    editable.contentEditable = 'true';
                    editable.setAttribute('data-placeholder', 'Scrivi qui…');

                    const ref = (findBlockById(bid)||{}).blk;
                    editable.innerHTML = (ref && ref.content) ? ref.content : '<p></p>';

                    // Conteggio
                    const counter = toolbar.querySelector('.txt-count');
                    const updateCount = ()=>{
                        const txt = editable.innerText || '';
                        const words = (txt.trim().match(/\S+/g)||[]).length;
                        const chars = txt.replace(/\s/g,'').length;
                        if (counter) counter.textContent = `${words} parole • ${chars} caratteri`;
                    };

                    // Toolbar state sync
                    const updateToolbar = ()=>{
                        const s = readStyleAtCaret(editable);
                        setToolbarState(toolbar, s);
                        updateCount();
                    };

                    // PASTE sanitizzato
                    editable.addEventListener('paste', (e)=>{
                        const cd = e.clipboardData; if (!cd) return;
                        e.preventDefault();
                        const html = cd.getData('text/html') || cd.getData('text/plain').replace(/\n/g,'<br>');
                        insertHtmlAtCaret(editable, sanitizeHtml(html));
                        // niente normalize qui: lo faremo on-blur / on-save
                        updateToolbar();

                    });

                    // Input / mouseup / keyup -> sync
                    let debounce;
                    const debounced = (fn)=>{ clearTimeout(debounce); debounce = setTimeout(fn, 120); };
                    editable.addEventListener('input', ()=>{
                        // durante la digitazione NON tocchiamo l’HTML, aggiorniamo solo la toolbar
                        debounced(updateToolbar);
                    });

                    editable.addEventListener('keyup', updateToolbar);
                    editable.addEventListener('mouseup', updateToolbar);
                    editable.addEventListener('focus', updateToolbar);

                    // Comandi toolbar
                    toolbar.addEventListener('click', async (e)=>{
                        const btn = e.target.closest('button'); if (!btn) return;
                        const cmd = btn.getAttribute('data-cmd');
                        const act = btn.getAttribute('data-action');
                        editable.focus();

                        if (cmd){ document.execCommand(cmd, false, null); updateToolbar(); return; }

                        if (act === 'clearstyles'){ clearInlineStyle(editable, ['color','backgroundColor','fontSize','fontFamily']); normalizeSpans(editable); updateToolbar(); return; }

                        if (act === 'link'){
                            const r = getSelectionIn(editable);
                            if (!r) return;
                            const url = prompt('URL del link (es. https://...)');
                            if (url){
                                document.execCommand('createLink', false, url);
                                const sel = window.getSelection();
                                let a = sel.anchorNode?.parentElement?.closest('a');
                                if (!a) a = editable.querySelector(`a[href="${url}"]`);
                                if (a && /^https?:\/\//i.test(url)){ a.setAttribute('target','_blank'); a.setAttribute('rel','noopener'); }
                            }
                            updateToolbar(); return;
                        }
                        if (act === 'unlink'){ document.execCommand('unlink', false, null); updateToolbar(); return; }

                        if (act === 'anchor'){
                            const id = prompt('ID ancora (senza spazi):');
                            if (id){
                                const safe = id.trim().replace(/\s+/g,'-').replace(/[^-\w]/g,'');
                                insertHtmlAtCaret(editable, `<span id="${safe}"></span>`);
                            }
                            updateToolbar(); return;
                        }

                        // Format painter
                        if (act === 'pick-format'){ copyFormatFromCaret(editable, toolbar); updateToolbar(); return; }
                        if (act === 'apply-format'){ applyFormatToSelection(editable); normalizeSpans(editable); updateToolbar(); return; }

                        // Immagini inline
                        if (act === 'img-upload'){
                            const input = document.createElement('input');
                            input.type = 'file'; input.accept = 'image/*';
                            input.onchange = async ()=>{
                                try{ const [d] = await uploadFiles(input.files);
                                    const url = d?.url || d?.thumb;
                                    if (url) insertHtmlAtCaret(editable, `<img src="${url}" alt="">`);
                                }catch(_){}
                                updateToolbar();
                            };
                            input.click(); return;
                        }
                        if (act === 'img-media'){
                            const picked = await openMediaPicker({ multiple:false });
                            if (picked && picked.length){
                                const it = picked[0];
                                const url = it?.variants?.full || it?.url || it?.thumb;
                                const alt = (it?.alt || it?.title || '').replace(/"/g,'&quot;');
                                if (url) insertHtmlAtCaret(editable, `<img src="${url}" alt="${alt}">`);
                            }
                            updateToolbar(); return;
                        }
                        if (act === 'img-url'){
                            const url = prompt('URL immagine:');
                            if (url) insertHtmlAtCaret(editable, `<img src="${url}" alt="">`);
                            updateToolbar(); return;
                        }
                        if (act?.startsWith('img-')){
                            const img = closestSelectedImage(editable);
                            if (!img) { alert('Seleziona un’immagine nel testo.'); return; }
                            img.classList.remove('align-left','align-right','align-center');
                            if (act === 'img-left')  img.classList.add('align-left');
                            if (act === 'img-right') img.classList.add('align-right');
                            if (act === 'img-center')img.classList.add('align-center');
                            updateToolbar(); return;
                        }
                    });

                    // Selects
                    toolbar.querySelector('select.r4-block').addEventListener('change', (e)=>{
                        editable.focus();
                        const tag = e.target.value;                   // P, H1, H2, H3, H4, BLOCKQUOTE, PRE
                        // Alcuni browser gradiscono il tag tra <>
                        const value = tag === 'P' ? 'P' : `<${tag}>`;
                        document.execCommand('formatBlock', false, value);
                        updateToolbar();
                    });

                    toolbar.querySelector('select.r4-font').addEventListener('change', (e)=>{
                        const v = e.target.value;
                        if (!v) { clearInlineStyle(editable, ['fontFamily']); normalizeSpans(editable); updateToolbar(); return; }
                        wrapSelectionWithSpan(editable, { fontFamily: v }); normalizeSpans(editable); updateToolbar();
                    });
                    toolbar.querySelector('select.r4-size').addEventListener('change', (e)=>{
                        const v = e.target.value;
                        if (!v) { clearInlineStyle(editable, ['fontSize']); normalizeSpans(editable); updateToolbar(); return; }
                        wrapSelectionWithSpan(editable, { fontSize: `${parseInt(v,10)}px` }); normalizeSpans(editable); updateToolbar();
                    });
                    toolbar.querySelector('input.r4-color').addEventListener('input', (e)=>{
                        wrapSelectionWithSpan(editable, { color: e.target.value }); normalizeSpans(editable); updateToolbar();
                    });
                    toolbar.querySelector('input.r4-bg').addEventListener('input', (e)=>{
                        wrapSelectionWithSpan(editable, { backgroundColor: e.target.value }); normalizeSpans(editable); updateToolbar();
                    });

                    // Mount
                    host.innerHTML = '';
                    host.appendChild(toolbar);
                    host.appendChild(editable);

                    // First sync
                    setTimeout(()=>{ normalizeSpans(editable); cleanupZeroWidth(editable); }, 0);

                    r4Editors.set(bid, {
                        editable,
                        toolbar,
                        updateUI: ()=> setToolbarState(toolbar, readStyleAtCaret(editable)),
                        cleanup: ()=>{}
                    });
                });

                // Unico osservatore globale per la selezione (sincronizza l’editor attivo)
                if (!__r4SelectionObserverBound){
                    document.addEventListener('selectionchange', ()=>{
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount===0) return;
                        const node = sel.anchorNode && (sel.anchorNode.nodeType===3 ? sel.anchorNode.parentElement : sel.anchorNode);
                        if (!node) return;
                        const host = node.closest('.r4-editor');
                        if (!host) return;
                        const bid = host.getAttribute('data-bid');
                        const ref = r4Editors.get(bid);
                        if (ref) ref.updateUI();
                    });
                    __r4SelectionObserverBound = true;
                }
            }
            function collectR4Editors(){
                r4Editors.forEach(({editable}, bid)=>{
                    sanitizeEditable(editable);
                    normalizeSpans(editable);
                    cleanupZeroWidth(editable);
                    const fb = findBlockById(bid);
                    if (fb && fb.blk && fb.blk.type === 'text'){
                        fb.blk.content = editable.innerHTML;
                    }
                });
            }
            function destroyR4Editors(){
                r4Editors.forEach(({cleanup})=> cleanup && cleanup());
                r4Editors.clear();
            }



            /* Sezioni */
            window.addSection = function(){ const id = uid('sec'); window.builderData.push({ id, blocks: [] }); setCollapsed(id, false); renderBuilder(); };
            window.removeSection = function(secId){ if (!confirm('Eliminare questo Blocco?')) return; window.builderData = window.builderData.filter(s => s.id !== secId); delete collapseState[secId]; localStorage.setItem(PB_COLLAPSE_KEY, JSON.stringify(collapseState)); renderBuilder(); };
            window.duplicateSection = function(secId){ const src = findSection(secId); if (!src) return; const copy = cloneDeep(src); copy.id = uid('sec'); (copy.blocks||[]).forEach(b => b.id = uid('block')); window.builderData.push(copy); setCollapsed(copy.id, isCollapsed(secId)); renderBuilder(); };
            window.toggleSection = function(secId){ setCollapsed(secId, !isCollapsed(secId)); renderBuilder(); };
            function collapseAll(expand){ window.builderData.forEach(s => setCollapsed(s.id, !expand)); renderBuilder(); }

            window.updateGalleryItem = (secId, blockId, idx, key, value, target='gallery') => {
                const b = findBlock(secId, blockId); if (!b) return;
                const arr = target==='gallery'
                    ? (b.gallery ||= [])
                    : ((b.carousel ||= {items:[], options:{}}).items ||= []);
                if (!Array.isArray(arr) || !arr[idx]) return;
                arr[idx][key] = value;
            };

            window.setImageCropMode = (secId, blockId, mode) => {
                const b = findBlock(secId, blockId); if (!b) return;
                b.image ||= {}; b.image.options ||= {};
                const noCrop = (mode === 'nocrop');
                // Backend: mappiamo su 'logo' (no crop) / 'photo' (crop)
                b.image.uploadProfile = noCrop ? 'logo' : 'photo';
                // Frontend: allineiamo anche il rendering
                b.image.options.objectFit = noCrop ? 'contain' : 'cover';

                renderBuilder();
            };


            /* Blocchi */
            window.addBlock = function(secId, cols = 12, type = 'text'){
                const sec = findSection(secId); if (!sec) return;
                const base = { id: uid('block'), columns: parseInt(cols), type, animation: { name:'none', duration:600, delay:0 }, style: DEFAULT_STYLE() };
                if (type === 'text'){ base.content = ''; }
                else if (type === 'image'){
                    base.image = {
                        uploadProfile: 'photo',
                        src:'', full:'', alt:'',
                        caption:'',                       // testo
                        captionAlign:'left',              // allineamento
                        captionColor:'#6c757d',           // colore default coerente col tema
                        captionPad:{ t:0, r:0, b:0, l:0}, // padding
                        captionSize:'',                   // px (vuoto = default)
                        captionBold:false,                // grassetto
                        captionItalic:false,              // corsivo
                        quality:'75',
                        border: { w:0, s:'solid', c:'#000000', r:0 },
                        options:{ heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center', aspectRatio:'16/9' },
                        fx:{ parallax:false, parallaxMode:'y', parallaxStrength:20, parallaxPerspective:800, ripple:false, rippleRadius:60, rippleDuration:1200, rippleThrottle:120 }
                    };
                }
                else if (type === 'gallery'){
                    base.gallery = []; base.galleryQuality = 'thumb';
                } else if (type === 'video'){
                    base.video = {
                        url:'', provider:'', id:'',
                        options: {
                            autoplay:false, controls:true, loop:false, muted:false, playsinline:true,
                            aspect:'16 / 9', poster:''
                        }
                    };
                } else if (type === 'carousel'){
                    base.carousel = {
                        items: [],
                        options: { autoplay:true, interval:5000, indicators:true, controls:true, heightMode:'auto', heightPx:450, objectFit:'cover', quality:'thumb' }
                    };
                } else if (typeof type === 'string' && type.startsWith('plugin:')){ base.data = {}; }
                sec.blocks.push(base); renderBuilder();
            };
            window.removeBlock = function(secId, blockId){ const sec = findSection(secId); if (!sec) return; if (!confirm('Eliminare questo elemento?')) return; sec.blocks = (sec.blocks || []).filter(b => b.id !== blockId); renderBuilder(); };
            window.duplicateBlock = function(secId, blockId){ const sec = findSection(secId); if (!sec) return; const src = sec.blocks.find(b => b.id === blockId); if (!src) return; const copy = cloneDeep(src); copy.id = uid('block'); sec.blocks.push(copy); renderBuilder(); };
            window.changeColumns = (secId, blockId, v) => { const b = findBlock(secId, blockId); if (b){ b.columns = parseInt(v)||12; renderBuilder(); } };
            window.changeType    = (secId, blockId, t) => { const b = findBlock(secId, blockId); if (!b) return; b.type=t; renderBuilder(); };
            window.updateAnim    = (secId, blockId, k, v) => { const b = findBlock(secId, blockId); if (!b) return; b.animation = b.animation || { name:'none', duration:600, delay:0 }; b.animation[k] = (k==='duration'||k==='delay') ? (parseInt(v)||0) : v; };

            const setByPath = (obj, path, value) => { const parts = path.split('.'); let ref = obj; while (parts.length>1){ const k = parts.shift(); if (typeof ref[k] !== 'object' || ref[k]===null) ref[k] = {}; ref = ref[k]; } ref[parts[0]] = value; };
            window.updateStyle = (secId, blockId, path, value) => { const b = findBlock(secId, blockId); if (!b) return; b.style = normStyle(b.style); setByPath(b, 'style.'+path, value); renderBuilder(); };
            window.updateStyleNum = (secId, blockId, path, value, min=0) => { let v = parseInt(value,10); if (isNaN(v)) v = 0; if (v<min) v = min; window.updateStyle(secId, blockId, path, v); };

            /* Upload immagini */
            // Upload da disco
            window.pickImage = async function(secId, blockId){
                const input = document.createElement('input');
                input.type = 'file'; input.accept = 'image/*';
                input.onchange = async () => {
                    try{

                        window.__CURRENT_UPLOAD_CTX__ = { secId, blockId };
                        const [d] = await uploadFiles(input.files);
                        window.__CURRENT_UPLOAD_CTX__ = null;

                        const b = findBlock(secId, blockId); if (!b) return;
                        b.image ||= {};
                        // Usa SEMPRE l'URL pieno per evitare il thumb croppato
                        b.image.src  = d.url;   // <--- prima era d.thumb || d.url
                        b.image.full = d.url;
                        renderBuilder();
                        guardNoCropSoon(secId, blockId);
                    }catch(e){}
                };
                input.click();
            };
            window.pickGallery = async function(secId, blockId, target='gallery'){
                const input=document.createElement('input'); input.type='file'; input.accept='image/*'; input.multiple=true;
                input.onchange=async ()=>{ try{
                    const datas=await uploadFiles(input.files); const b=findBlock(secId,blockId); if(!b) return;
                    if (target==='gallery'){ b.gallery ||= []; datas.forEach(d => b.gallery.push({ src:d.thumb||d.url, full:d.url, alt:'' })); }
                    else { b.carousel ||= { items: [], options: {} }; b.carousel.items ||= []; datas.forEach(d => b.carousel.items.push({ src:d.thumb||d.url, full:d.url, alt:'' })); }
                    renderBuilder();
                }catch(e){} };
                input.click();
            };
            window.removeGalleryItem = (secId, blockId, idx, target='gallery') => {
                const b=findBlock(secId,blockId); if(!b) return;
                const arr= target==='gallery' ? (b.gallery ||= []) : ((b.carousel ||= {items:[]}).items ||= []);
                if(Array.isArray(arr)){ arr.splice(idx,1); renderBuilder(); }
            };

            /* Video */
            const parseVideoUrl = (url) => {
                url=String(url||'').trim(); if(!url) return {provider:'',id:''};
                const yt = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_\-]{6,})/);
                if (yt) return {provider:'youtube', id:yt[1]};
                const vm = url.match(/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/);
                if (vm) return {provider:'vimeo', id:vm[1]};
                return {provider:'', id:''};
            };
            window.updateVideoUrl = (secId, blockId, el) => {
                const b=findBlock(secId, blockId); if(!b) return;
                const url=el.value; const info=parseVideoUrl(url);
                b.video = { url, provider:info.provider, id:info.id };
                renderBuilder();
            };

            /* Immagine: campi/opzioni */
            window.updateImageField = (secId, blockId, path, value) => {
                const b=findBlock(secId, blockId); if (!b) return;
                const parts = path.split('.'); let ref=b; while(parts.length>1){ const k=parts.shift(); if(typeof ref[k]!=='object'||ref[k]===null) ref[k]={}; ref=ref[k]; } ref[parts[0]] = value;
            };
            window.updateImageOption = (secId, blockId, key, value) => {
                const b=findBlock(secId, blockId); if (!b) return;
                b.image ||= {}; b.image.options ||= { heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center' };
                if (key === 'heightPx') b.image.options[key] = Math.max(50, parseInt(value)||450);
                else b.image.options[key] = value; renderBuilder();
            };
            window.updateImageFx = (secId, blockId, key, value) => {
                const b=findBlock(secId, blockId); if (!b) return;
                b.image ||= {}; b.image.fx ||= { parallax:false, parallaxMode:'y', parallaxStrength:20, parallaxPerspective:800, ripple:false, rippleRadius:60, rippleDuration:1200, rippleThrottle:120 };
                const intKeys = ['parallaxStrength','parallaxPerspective','rippleRadius','rippleDuration','rippleThrottle'];
                if (intKeys.includes(key)) b.image.fx[key] = Math.max(0, parseInt(value)||0);
                else if (key==='parallax' || key==='ripple') b.image.fx[key] = (value===true || value==='1' || value===1);
                else b.image.fx[key] = value;
            };
            window.updateImageCustomPos = (secId, blockId) => {
                const xSel = document.getElementById('posX_' + blockId);
                const ySel = document.getElementById('posY_' + blockId);
                const x = xSel ? parseInt(xSel.value) : 50;
                const y = ySel ? parseInt(ySel.value) : 50;
                const pos = `${Math.max(0,Math.min(100,x))}% ${Math.max(0,Math.min(100,y))}%`;
                window.updateImageOption(secId, blockId, 'objectPosition', pos);
            };
            window.onPresetPositionChange = (secId, blockId, el) => {
                const val = el.value;
                if (val === '__custom__'){ window.updateImageCustomPos(secId, blockId); }
                else { window.updateImageOption(secId, blockId, 'objectPosition', val); }
            };

            /* Plugin API */
            window.updatePluginField = (secId, blockId, path, value) => {
                const b = findBlock(secId, blockId); if (!b) return;
                const parts = path.split('.'); let ref = b; while (parts.length > 1) { const k = parts.shift(); if (typeof ref[k] !== 'object' || ref[k] === null) ref[k] = {}; ref = ref[k]; }
                ref[parts[0]] = value;
            };

            // Imposta opzioni video
            window.updateVideoOpt = (secId, blockId, key, value) => {
                const b = findBlock(secId, blockId); if(!b) return;
                b.video ||= { url:'', provider:'', id:'', options:{} };
                const boolKeys = ['autoplay','controls','loop','muted','playsinline'];
                if (!b.video.options) b.video.options = {};
                b.video.options[key] = boolKeys.includes(key) ? (value===true || value==='1' || value==1) : value;
                // Autoplay richiede muted sui browser moderni
                if (key === 'autoplay' && b.video.options.autoplay) b.video.options.muted = true;
                renderBuilder();
            };

// Upload video (max 5MB)
            window.pickVideo = function(secId, blockId){
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'video/mp4,video/webm,video/ogg';
                input.onchange = async ()=>{
                    const file = input.files && input.files[0];
                    if (!file) return;
                    if (file.size > 5*1024*1024) { showToast('Video oltre 5MB', 'error'); return; }
                    try{
                        const [d] = await uploadFiles([file]); // riusa xhrUpload
                        const b = findBlock(secId, blockId); if(!b) return;
                        b.video ||= { url:'', provider:'', id:'', options:{ autoplay:false, controls:true, loop:false, muted:false, playsinline:true, aspect:'16 / 9', poster:'' } };
                        b.video.url = d?.url || '';
                        b.video.provider = ''; b.video.id = '';
                        renderBuilder();
                    }catch(e){ showToast('Errore upload: '+ (e?.message||'imprevisto'), 'error'); }
                };
                input.click();
            };

// Scelta video da archivio
            window.chooseVideoFromMedia = async function(secId, blockId){
                const picked = await openMediaPicker({ multiple:false, type:'video' });
                if (!picked || !picked.length) return;
                const it = picked[0];
                const b = findBlock(secId, blockId); if (!b) return;
                b.video ||= { url:'', provider:'', id:'', options:{ autoplay:false, controls:true, loop:false, muted:false, playsinline:true, aspect:'16 / 9', poster:'' } };
                b.video.url = it.url;
                b.video.provider = ''; b.video.id = '';
                renderBuilder();
            };

            // Poster immagine per video
            window.setVideoPosterFromMedia = async function(secId, blockId){
                const picked = await openMediaPicker({ multiple:false, type:'image' });
                if (!picked || !picked.length) return;
                const it = picked[0];
                const url = it?.variants?.full || it?.url || it?.thumb || '';
                const b = findBlock(secId, blockId); if (!b) return;
                b.video ||= { url:'', provider:'', id:'', options:{} };
                b.video.options ||= {};
                b.video.options.poster = url;
                renderBuilder();
            };
            window.clearVideoPoster = function(secId, blockId){
                const b = findBlock(secId, blockId); if (!b) return;
                if (b.video?.options) b.video.options.poster = '';
                renderBuilder();
            };

            function isPluginType(t){ return typeof t === 'string' && t.startsWith('plugin:'); }
            function pluginDef(t){ return (window.BuilderPlugins||{})[t]; }

            /* DnD sezioni */
            let dragIndex = null;
            function attachDnD(){
                qa('.pb-section').forEach((section, idx) => {
                    section.removeAttribute('draggable');
                    const handle = section.querySelector('.drag-handle');
                    if (handle){
                        handle.setAttribute('draggable','true');
                        handle.addEventListener('dragstart', (e)=>{
                            dragIndex = idx; e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain', String(idx));
                        });
                    }
                    section.addEventListener('dragover', (e)=>{ e.preventDefault(); section.classList.add('pb-drag-over'); e.dataTransfer.dropEffect='move'; });
                    section.addEventListener('dragleave', ()=>{ section.classList.remove('pb-drag-over'); });
                    section.addEventListener('drop', (e)=>{
                        e.preventDefault(); section.classList.remove('pb-drag-over');
                        const from = dragIndex, to = idx; if (from===null || from===to) return;
                        const arr = window.builderData; arr.splice(to, 0, arr.splice(from,1)[0]); dragIndex=null; renderBuilder();
                    });
                });
            }

            function bindPalette(){
                const header = document.querySelector('.card-header .palette'); if (!header) return;
                header.querySelector('[data-action="add-section"]')?.addEventListener('click', ()=> addSection());
                header.querySelectorAll('[data-action="add-item"]').forEach(btn=>{
                    btn.addEventListener('click', ()=>{
                        if (!window.builderData.length) addSection();
                        const last = window.builderData[window.builderData.length-1];
                        addBlock(last.id, 12, btn.dataset.type);
                    });
                });
            }

            /* MEDIA PICKER (con fallback semplice) */
            let mpModal, mpState = null;
            function mpEnsureModal(){
                if (mpModal) return mpModal;
                const el = document.getElementById('mediaPickerModal');
                if (window.bootstrap && bootstrap.Modal) {
                    mpModal = new bootstrap.Modal(el);
                } else {
                    mpModal = {
                        show(){ el.classList.add('show'); el.style.display='block'; el.removeAttribute('aria-hidden'); document.body.classList.add('modal-open'); },
                        hide(){ el.classList.remove('show'); el.style.display='none'; el.setAttribute('aria-hidden','true'); document.body.classList.remove('modal-open'); }
                    };
                }
                return mpModal;
            }

            function mpRender(items, pagination) {
                // --- requisiti esterni ---
                // mpState: { items: Array, selected: Map|Set, multiple: boolean }
                // mpLoad(page: number)
                // mpConfirmSelection()

                // --- helpers ----------------------------------------------------
                const byId = (id) => document.getElementById(id);
                const esc = (v) => {
                    const s = (v ?? '').toString();
                    return s
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                // fallback pagination se non arriva dal server
                const pg = {
                    current_page: Number(pagination?.current_page ?? 1),
                    last_page: Number(pagination?.last_page ?? 1),
                    total: Number(pagination?.total ?? (Array.isArray(items) ? items.length : 0)),
                };

                // --- cache riferimenti DOM -------------------------------------
                const grid = byId('mpGrid');
                const counter = byId('mpCounter');
                const prev = byId('mpPrev');
                const next = byId('mpNext');
                const confirm = byId('mpConfirm');

                if (!grid) return; // niente DOM, niente party :)

                // --- normalizzazione items -------------------------------------
                const list = Array.isArray(items) ? items : [];
                // mantieni un riferimento agli items correnti per click handler
                if (typeof mpState === 'object') {
                    mpState.items = list;
                }

                // --- render -----------------------------------------------------
                if (!list.length) {
                    grid.innerHTML = `<div class="col-12 text-center text-muted py-5">Nessun risultato</div>`;
                } else {
                    const html = list
                        .map((it) => {
                            const id = it?.id ?? '';
                            const key = String(id);
                            const selected =
                                mpState?.selected &&
                                (mpState.selected instanceof Map
                                    ? mpState.selected.has(key)
                                    : mpState.selected.has
                                        ? mpState.selected.has(key)
                                        : false);

                            const thumb =
                                it?.thumb ||
                                it?.variants?.thumb ||
                                it?.url ||
                                ''; // fallback vuoto

                            const title = it?.title || it?.original_name || '';
                            const caption = esc(it?.title || it?.alt || '—');

                            return `
          <div class="col-6 col-md-3 col-xl-2">
            <div class="mp-item ${selected ? 'active' : ''}" data-id="${esc(key)}" role="option" aria-selected="${selected ? 'true' : 'false'}" tabindex="0">
              <span class="mp-check" aria-hidden="true"><i class="bi bi-check"></i></span>
              <img loading="lazy" src="${esc(thumb)}" alt="${esc(it?.alt || title || '')}">
              <div class="mp-caption" title="${esc(title)}">${caption}</div>
            </div>
          </div>
        `;
                        })
                        .join('');

                    grid.innerHTML = html;

                    // event handlers per le card
                    grid.querySelectorAll('.mp-item').forEach((card) => {
                        const toggleSelect = () => {
                            const id = String(card.dataset.id || '');
                            if (!id) return;

                            // cerca item corrispondente
                            const pool = mpState?.items || list;
                            const existing = Array.isArray(pool)
                                ? pool.find((x) => String(x?.id ?? '') === id) || { id }
                                : { id };

                            // se selezione singola, svuota prima
                            if (!mpState?.multiple) {
                                if (mpState?.selected?.clear) mpState.selected.clear();
                                grid.querySelectorAll('.mp-item.active').forEach((x) => {
                                    x.classList.remove('active');
                                    x.setAttribute('aria-selected', 'false');
                                });
                            }

                            const has =
                                mpState?.selected &&
                                (mpState.selected instanceof Map
                                    ? mpState.selected.has(id)
                                    : mpState.selected.has
                                        ? mpState.selected.has(id)
                                        : false);

                            if (has) {
                                // rimuovi
                                if (mpState.selected instanceof Map) mpState.selected.delete(id);
                                else if (mpState.selected?.delete) mpState.selected.delete(id);
                                card.classList.remove('active');
                                card.setAttribute('aria-selected', 'false');
                            } else {
                                // aggiungi
                                if (mpState.selected instanceof Map) mpState.selected.set(id, existing);
                                else if (mpState.selected?.add) mpState.selected.add(id);
                                card.classList.add('active');
                                card.setAttribute('aria-selected', 'true');
                            }

                            if (confirm) confirm.disabled = (mpState?.selected?.size ?? 0) === 0;

                            // conferma automatica in modalità singola
                            if (!mpState?.multiple && (mpState?.selected?.size ?? 0) === 1 && typeof mpConfirmSelection === 'function') {
                                mpConfirmSelection();
                            }
                        };

                        card.addEventListener('click', toggleSelect);
                        card.addEventListener('keydown', (e) => {
                            // Enter/Space per selezionare da tastiera
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                toggleSelect();
                            }
                        });
                        // doppio click = conferma
                        card.addEventListener('dblclick', () => {
                            if (typeof mpConfirmSelection === 'function') mpConfirmSelection();
                        });
                    });
                }

                // --- footer/paginazione ----------------------------------------
                if (counter) {
                    counter.textContent = `Pagina ${pg.current_page} di ${pg.last_page} — ${pg.total} elementi`;
                }
                if (prev) {
                    prev.disabled = pg.current_page <= 1;
                    prev.onclick = () => {
                        if (pg.current_page > 1 && typeof mpLoad === 'function') mpLoad(pg.current_page - 1);
                    };
                }
                if (next) {
                    next.disabled = pg.current_page >= pg.last_page;
                    next.onclick = () => {
                        if (pg.current_page < pg.last_page && typeof mpLoad === 'function') mpLoad(pg.current_page + 1);
                    };
                }
                if (confirm) {
                    confirm.disabled = (mpState?.selected?.size ?? 0) === 0;
                }
            }


            function mpLoad(page = 1){
                const grid = document.getElementById('mpGrid');
                grid.innerHTML = `
                <div class="col-12 text-center text-muted py-5">
                  <div class="spinner-border spinner-border-sm"></div>
                  <div>Caricamento…</div>
                </div>`;
                const url = new URL(MEDIA_BROWSE_URL, window.location.origin);
                if (mpState.type === 'image') { url.searchParams.set('images_only','1'); }
                else if (mpState.type === 'video') { url.searchParams.set('type','video'); }
                else { url.searchParams.set('type','any'); }
                url.searchParams.set('page', String(page));
                if (mpState.q) url.searchParams.set('q', mpState.q);
                fetch(url.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        mpState.page = page;
                        mpState.items = data.items || [];
                        mpState.pagination = data.pagination || { current_page:1, last_page:1, total:0 };
                        mpRender(mpState.items, mpState.pagination);
                    })
                    .catch(()=> {
                        grid.innerHTML = `<div class="col-12 text-center text-muted py-5">Errore di caricamento</div>`;
                    });
            }
            function mpBindUI(){
                const search = document.getElementById('mpSearch');
                const confirm = document.getElementById('mpConfirm');
                let t;
                search.oninput = () => {
                    clearTimeout(t);
                    t = setTimeout(()=> {
                        mpState.q = search.value.trim();
                        mpLoad(1);
                    }, 250);
                };
                confirm.onclick = mpConfirmSelection;
            }
            function mpConfirmSelection(){
                const selected = Array.from(mpState.selected.values());
                const resolver = mpState.resolver;
                mpEnsureModal().hide();
                setTimeout(()=> { resolver(selected); }, 10);
            }
            function openMediaPicker({multiple=false, type='image'}={}) {
                return new Promise((resolve) => {
                    mpState = { multiple, type, selected: new Map(), items: [], page: 1, q: '', resolver: resolve, pagination:{} };
                    mpEnsureModal().show();
                    mpBindUI();
                    mpLoad(1);
                    document.getElementById('mpSearch').value = '';
                    document.getElementById('mpConfirm').disabled = true;
                });
            }
            // Selezione da archivio (singola)
            window.chooseImageFromMedia = async function(secId, blockId){
                const picked = await openMediaPicker({ multiple:false });
                if (!picked || !picked.length) return;
                const it = picked[0];
                const b  = findBlock(secId, blockId); if (!b) return;
                b.image ||= {};

                // Preferisci una variante non croppata
                const safe = pickNonCroppedVariant(it);               // 59 -> 75 -> full -> url
                const full = (it.variants && (it.variants.full || it.full)) || it.url || safe;

                b.image.src  = safe;
                b.image.full = full;
                if (!b.image.alt) b.image.alt = it.alt || it.title || '';
                renderBuilder();
                guardNoCropSoon(secId, blockId);
            };
            window.chooseFromMediaMultiple = async function(secId, blockId, target='gallery'){
                const picked = await openMediaPicker({ multiple:true });
                if (!picked || !picked.length) return;
                const b = (findBlock(secId, blockId));
                if (!b) return;
                if (target === 'gallery') {
                    b.gallery ||= [];
                    picked.forEach(it => {
                        b.gallery.push({
                            src:  it.variants?.thumb || it.thumb || it.url,
                            full: it.variants?.full  || it.url,
                            alt:  it.alt || it.title || ''
                        });
                    });
                } else {
                    b.carousel ||= { items: [], options:{} };
                    b.carousel.items ||= [];
                    picked.forEach(it => {
                        b.carousel.items.push({
                            src:  it.variants?.thumb || it.thumb || it.url,
                            full: it.variants?.full  || it.url,
                            alt:  it.alt || it.title || ''
                        });
                    });
                }
                renderBuilder();
            };

            /* Modal URL Immagine (Bootstrap o fallback) */
            let imgUrlModal = null;
            const imgUrlEls = {};
            const imgUrlState = { secId:null, blockId:null };
            function ensureImgUrlModal(){
                if (imgUrlModal) return imgUrlModal;
                const el = document.getElementById('imageUrlModal');
                if (window.bootstrap && bootstrap.Modal){
                    imgUrlModal = new bootstrap.Modal(el);
                } else {
                    imgUrlModal = {
                        show(){ el.classList.add('show'); el.style.display='block'; el.removeAttribute('aria-hidden'); document.body.classList.add('modal-open'); },
                        hide(){ el.classList.remove('show'); el.style.display='none'; el.setAttribute('aria-hidden','true'); document.body.classList.remove('modal-open'); }
                    };
                }
                imgUrlEls.input   = document.getElementById('imageUrlInput');
                imgUrlEls.setFull = document.getElementById('imageUrlSetFull');
                imgUrlEls.prevWrap= document.getElementById('imageUrlPreviewWrap');
                imgUrlEls.prev    = document.getElementById('imageUrlPreview');
                imgUrlEls.confirm = document.getElementById('imageUrlConfirm');
                imgUrlEls.input.addEventListener('input', ()=>{
                    const v = imgUrlEls.input.value.trim();
                    imgUrlEls.prev.src = v || '';
                    imgUrlEls.prevWrap.style.display = v ? '' : 'none';
                });
                imgUrlEls.confirm.addEventListener('click', applyImageUrlModal);
                return imgUrlModal;
            }
            window.openImageUrlModal = (secId, blockId) => {
                ensureImgUrlModal();
                imgUrlState.secId = secId; imgUrlState.blockId = blockId;
                const b = findBlock(secId, blockId);
                const im = b?.image || {};
                const cur = im.full || im.src || '';
                imgUrlEls.input.value = cur;
                imgUrlEls.setFull.checked = true;
                imgUrlEls.prev.src = cur || '';
                imgUrlEls.prevWrap.style.display = cur ? '' : 'none';
                imgUrlModal?.show();
                setTimeout(()=> imgUrlEls.input.focus(), 50);
            };

            // URL manuale
            function applyImageUrlModal(){
                const url = (imgUrlEls.input.value || '').trim();
                if (!imgUrlState.secId || !imgUrlState.blockId) return;
                const b = findBlock(imgUrlState.secId, imgUrlState.blockId); if (!b) return;
                b.image ||= {};
                // Se l’URL ha un suffisso variante "croppata", prova a portarlo a 59
                const safeUrl = /_(thumb|25)\.\w+$/i.test(url) ? swapVariantInUrl(url, '59') : url;
                b.image.src = safeUrl;
                if (imgUrlEls.setFull.checked) b.image.full = safeUrl;
                imgUrlModal?.hide();
                renderBuilder();
                guardNoCropSoon(imgUrlState.secId, imgUrlState.blockId);
            }

            /* STYLE PANEL generator (riutilizzato nel modal) */
            function stylePanel(sec, block){
                const st = normStyle(block.style||{});
                const isText = block.type === 'text';
                return `
                <details class="fieldset collapsible mt-2" open>
                  <summary><i class="bi bi-sliders me-1"></i> Stile</summary>
                  <div class="pt-2">
                    <div class="row g-2 align-items-end">
                      <div class="col-6 col-md-3">
                        <label class="small">Margine Top</label>
                        <input type="number" class="form-control form-control-sm" value="${st.margin.t}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','margin.t', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Right</label>
                        <input type="number" class="form-control form-control-sm" value="${st.margin.r}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','margin.r', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Bottom</label>
                        <input type="number" class="form-control form-control-sm" value="${st.margin.b}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','margin.b', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Left</label>
                        <input type="number" class="form-control form-control-sm" value="${st.margin.l}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','margin.l', this.value, 0)">
                      </div>

                      <div class="col-6 col-md-3">
                        <label class="small">Padding Top</label>
                        <input type="number" class="form-control form-control-sm" value="${st.padding.t}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','padding.t', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Right</label>
                        <input type="number" class="form-control form-control-sm" value="${st.padding.r}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','padding.r', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Bottom</label>
                        <input type="number" class="form-control form-control-sm" value="${st.padding.b}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','padding.b', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Left</label>
                        <input type="number" class="form-control form-control-sm" value="${st.padding.l}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','padding.l', this.value, 0)">
                      </div>

                      <div class="col-6 col-md-3">
                        <label class="small">Bordo px</label>
                        <input type="number" class="form-control form-control-sm" min="0" value="${st.border.w}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','border.w', this.value, 0)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Stile</label>
                        <select class="form-select form-select-sm"
                          onchange="updateStyle('${sec.id}','${block.id}','border.s', this.value)">
                          ${['solid','dashed','dotted','double','groove','ridge','inset','outset','none'].map(v=>`
                            <option value="${v}" ${(st.border.s||'solid')===v?'selected':''}>${v}</option>`).join('')}
                        </select>
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Colore</label>
                        <input type="color" class="form-control form-control-color p-0" value="${st.border.c||'#000000'}"
                          onchange="updateStyle('${sec.id}','${block.id}','border.c', this.value)">
                      </div>
                      <div class="col-6 col-md-3">
                        <label class="small">Raggio</label>
                        <input type="number" class="form-control form-control-sm" min="0" value="${st.border.r}"
                          onchange="updateStyleNum('${sec.id}','${block.id}','border.r', this.value, 0)">
                      </div>

                      <div class="col-6 col-md-3">
                        <label class="small">Max width (px)</label>
                        <input type="number" class="form-control form-control-sm" min="0" value="${parseInt(st.maxWidth)||''}"
                          onchange="updateStyle('${sec.id}','${block.id}','maxWidth', this.value)">
                      </div>

                      ${isText ? `
                        <div class="col-6 col-md-3">
                          <label class="small">Allineamento</label>
                          <select class="form-select form-select-sm"
                            onchange="updateStyle('${sec.id}','${block.id}','align', this.value)">
                            ${['left','center','right','justify'].map(v=>`<option value="${v}" ${st.align===v?'selected':''}>${v}</option>`).join('')}
                          </select>
                        </div>
                      ` : `
                        <div class="col-6 col-md-3">
                          <label class="small">Allineamento orizz.</label>
                          <select class="form-select form-select-sm"
                            onchange="updateStyle('${sec.id}','${block.id}','hAlign', this.value)">
                            ${['start','center','end'].map(v=>`<option value="${v}" ${st.hAlign===v?'selected':''}>${v}</option>`).join('')}
                          </select>
                        </div>
                      `}
                    </div>

                    <div class="mt-2">
                      <label class="small d-block">Sfondo</label>
                      <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                          <label class="small">Tipo</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateStyle('${sec.id}','${block.id}','bgType', this.value)">
                            ${['none','color','gradient'].map(v=>`<option value="${v}" ${st.bgType===v?'selected':''}>${v}</option>`).join('')}
                          </select>
                        </div>
                        <div class="col-6 col-md-3">
                          <label class="small">Colore 1</label>
                          <input type="color" class="form-control form-control-color p-0"
                                 value="${st.bg1}"
                                 onchange="updateStyle('${sec.id}','${block.id}','bg1', this.value)">
                        </div>
                        <div class="col-6 col-md-3">
                          <label class="small">Colore 2</label>
                          <input type="color" class="form-control form-control-color p-0"
                                 value="${st.bg2}"
                                 onchange="updateStyle('${sec.id}','${block.id}','bg2', this.value)"
                                 ${st.bgType!=='gradient' ? 'disabled' : ''}>
                        </div>
                        <div class="col-6 col-md-3">
                          <label class="small">Angolo (°)</label>
                          <input type="number" min="0" max="360" class="form-control form-control-sm"
                                 value="${st.bgAngle}"
                                 onchange="updateStyleNum('${sec.id}','${block.id}','bgAngle', this.value, 0)"
                                 ${st.bgType!=='gradient' ? 'disabled' : ''}>
                        </div>
                      </div>
                    </div>
                  </div>
                </details>
                `;
            }

            /* Toggle preview immagini */
            window.toggleImagePreview = (blockId, btnEl) => {
                const wrap = document.querySelector(`.pb-imgwrap[data-bid="${blockId}"]`);
                if (!wrap) return;
                const on = wrap.classList.toggle('editor-safe'); // true => modalità "editor" (immagine non tagliata)
                btnEl.innerHTML = on
                    ? '<i class="bi bi-aspect-ratio"></i> Anteprima front-end'
                    : '<i class="bi bi-aspect-ratio"></i> Anteprima editor';
            };


            /* CONTENUTO BLOCCO (ridotto: niente controlli duplicati) */
            function renderBlockContent(sec, block){
                if (block.type === 'text'){
                    return `
                      <div class="block-form">
                        <div class="r4-editor" data-bid="${block.id}"></div>
                      </div>
                    `;
                }

                if (block.type === 'video') {
                    const v   = block.video || { url:'', provider:'', id:'', options:{} };
                    const url = v.url || '';
                    const pr  = v.provider || '';
                    const vid = v.id || '';
                    const op  = v.options || { autoplay:false, controls:true, loop:false, muted:false, playsinline:true, aspect:'16 / 9', poster:'' };

                    // Anteprima (YouTube/Vimeo o file locale)
                    let preview = '';
                    if ((pr==='youtube' || pr==='vimeo') && vid) {
                        const ratio = (op.aspect || '16 / 9').replace(/\s*\/\s*/,' / ');
                        let src = pr==='youtube'
                            ? `https://www.youtube.com/embed/${vid}?rel=0&modestbranding=1${op.autoplay?'&autoplay=1&mute=1':''}${op.loop?`&loop=1&playlist=${vid}`:''}`
                            : `https://player.vimeo.com/video/${vid}${op.autoplay?'?autoplay=1&muted=1':''}${op.loop? (op.autoplay? '&' : '?')+'loop=1' : ''}`;

                        preview = `
          <div class="pb-imgwrap is-ratio" style="--pb-ar:${ratio}">
            <iframe src="${src}" title="Video" loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                    allowfullscreen style="width:100%;height:100%;border:0;"></iframe>
          </div>`;
                    } else if (url) {
                        const ratio = (op.aspect || '16 / 9').replace(/\s*\/\s*/,' / ');
                        preview = `
          <div class="pb-imgwrap is-ratio" style="--pb-ar:${ratio}">
            <video src="${url}" ${op.poster?`poster="${op.poster}"`:''}
                   style="width:100%;height:100%;object-fit:contain"
                   ${op.controls?'controls':''} ${op.autoplay?'autoplay muted':''}
                   ${op.loop?'loop':''} ${op.playsinline?'playsinline':''}
                   preload="metadata"></video>
          </div>`;
                    } else {
                        preview = `<div class="small-muted mb-2"><i class="bi bi-camera-video me-1"></i> Nessun video</div>`;
                    }

                    return `
      <div class="block-form">
        <div class="mb-2">${preview}</div>

        <div class="row g-2 align-items-end">
          <div class="col-12 col-lg-8">
            <label class="small">URL YouTube/Vimeo **oppure** URL file (mp4/webm/ogg)</label>
            <input type="text" class="form-control form-control-sm"
                   placeholder="https://youtu.be/..., https://vimeo.com/..., oppure https://.../video.mp4"
                   value="${(url||'').replace(/"/g,'&quot;')}"
                   oninput="updateVideoUrl('${sec.id}','${block.id}', this)">
            <div class="small text-muted mt-1">Per i file locali usa il pulsante <em>Carica</em> (max 5 MB) o seleziona dall’archivio.</div>
          </div>
          <div class="col d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-soft btn-pill" onclick="pickVideo('${sec.id}','${block.id}')">
              <i class="bi bi-upload me-1"></i> Carica
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-pill" onclick="chooseVideoFromMedia('${sec.id}','${block.id}')">
              <i class="bi bi-collection-play me-1"></i> Da archivio
            </button>
            <button type="button" class="btn btn-sm btn-ghost btn-pill" onclick="updateVideoUrl('${sec.id}','${block.id}', { value:'' })">
              <i class="bi bi-x-lg me-1"></i> Svuota
            </button>
          </div>
        </div>

        <details class="fieldset collapsible mt-2" open>
          <summary><i class="bi bi-sliders me-1"></i> Opzioni</summary>
          <div class="pt-2 row g-2 align-items-end">
            <div class="col-6 col-lg-3">
              <label class="small">Aspect ratio</label>
              <select class="form-select form-select-sm"
                      onchange="updateVideoOpt('${sec.id}','${block.id}','aspect', this.value)">
                ${['16 / 9','4 / 3','1 / 1','21 / 9','3 / 2'].map(v=>`<option value="${v}" ${(op.aspect||'16 / 9')===v?'selected':''}>${v}</option>`).join('')}
              </select>
            </div>
            <div class="col-6 col-lg-3">
              <label class="small d-block">Autoplay</label>
              <select class="form-select form-select-sm" onchange="updateVideoOpt('${sec.id}','${block.id}','autoplay', this.value==='1')">
                <option value="0" ${!op.autoplay?'selected':''}>No</option>
                <option value="1" ${op.autoplay?'selected':''}>Sì (muted)</option>
              </select>
            </div>
            <div class="col-6 col-lg-2">
              <label class="small d-block">Loop</label>
              <select class="form-select form-select-sm" onchange="updateVideoOpt('${sec.id}','${block.id}','loop', this.value==='1')">
                <option value="0" ${!op.loop?'selected':''}>No</option>
                <option value="1" ${op.loop?'selected':''}>Sì</option>
              </select>
            </div>
            <div class="col-6 col-lg-2">
              <label class="small d-block">Controls</label>
              <select class="form-select form-select-sm" onchange="updateVideoOpt('${sec.id}','${block.id}','controls', this.value==='1')">
                <option value="1" ${op.controls?'selected':''}>Sì</option>
                <option value="0" ${!op.controls?'selected':''}>No</option>
              </select>
            </div>
            <div class="col-6 col-lg-2">
              <label class="small d-block">Inline</label>
              <select class="form-select form-select-sm" onchange="updateVideoOpt('${sec.id}','${block.id}','playsinline', this.value==='1')">
                <option value="1" ${op.playsinline?'selected':''}>Sì</option>
                <option value="0" ${!op.playsinline?'selected':''}>No</option>
              </select>
            </div>

            <div class="col-12">
              <label class="small d-block">Poster (immagine di anteprima)</label>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-primary btn-pill" onclick="setVideoPosterFromMedia('${sec.id}','${block.id}')">
                  <i class="bi bi-image me-1"></i> Scegli immagine
                </button>
                ${op.poster ? `<span class="badge text-bg-light text-truncate-inline" title="${op.poster}"><i class="bi bi-link-45deg me-1"></i>${op.poster}</span>` : '<span class="small text-muted">Nessun poster</span>'}
                ${op.poster ? `<button type="button" class="btn btn-sm btn-ghost btn-pill" onclick="clearVideoPoster('${sec.id}','${block.id}')"><i class="bi bi-trash me-1"></i> Rimuovi</button>` : ''}
              </div>
            </div>
          </div>
        </details>
      </div>
    `;
                }


                if (block.type === 'image'){
                    const im  = block.image || {};
                    //const src = im.src || '';

                    const baseFull = im.full || im.src || '';
                    const qSel = im.quality || 'thumb';
                    // prova a sostituire il suffisso _thumb/_25/_59/_75/_full; se non c'è, resta invariato
                    const src = baseFull ? swapVariantInUrl(baseFull, qSel) : '';

                    const alt = im.alt || '';
                    const opt = im.options || { heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center', aspectRatio:'16 / 9' };

                    // Didascalia
                    const capTxt    = im.caption || '';
                    const capColor  = im.captionColor || '';
                    const capPad    = im.captionPad || { t:0, r:0, b:0, l:0 };
                    const capAlign  = im.captionAlign || 'left';
                    const capSize   = parseInt(im.captionSize || 0, 10);
                    const capBold   = !!im.captionBold;
                    const capItalic = !!im.captionItalic;

                    const capStyle = `
    ${capColor ? `color:${capColor};` : ''}
    ${capSize>0 ? `font-size:${capSize}px;` : ''}
    ${capBold ? 'font-weight:700;' : ''}
    ${capItalic ? 'font-style:italic;' : ''}
    padding:${parseInt(capPad.t||0)}px ${parseInt(capPad.r||0)}px ${parseInt(capPad.b||0)}px ${parseInt(capPad.l||0)}px;
    text-align:${capAlign};
  `.replace(/\s+/g,' ');

                    // Bordo/radius sull'immagine
                    const borderCss =
                        `${(im.border?.w||0)>0?`border:${im.border.w}px ${im.border.s||'solid'} ${im.border.c||'#000'};`:''}` +
                        `${(im.border?.r||0)>0?`border-radius:${im.border.r}px;`:''}`;

                    // Wrapper stile coerente col frontend
                    const hMode = opt.heightMode || 'auto';
                    const hPx   = Math.max(50, parseInt(opt.heightPx||450,10));
                    const fit   = opt.objectFit || 'cover';
                    const pos   = opt.objectPosition || 'center center';
                    const ar    = (opt.aspectRatio || '16 / 9').replace(/\s*\/\s*/,' / ');

                    const wrapCls = ['pb-imgwrap'];
                    let wrapStyle = '';
                    if (hMode === 'fixed'){
                        wrapCls.push('is-fixed');
                        wrapStyle = `--pb-ch:${hPx}px;--pb-of:${fit};--pb-op:${pos};`;
                    } else if (hMode === 'ratio'){
                        wrapCls.push('is-ratio');
                        wrapStyle = `--pb-ar:${ar};--pb-of:${fit};--pb-op:${pos};`;
                    }

                    return `
    <div class="block-form">
      <div class="mb-2">
        ${
                        src
                            ? `<figure class="m-0">
                 <div class="${wrapCls.join(' ')}" data-bid="${block.id}" style="${wrapStyle}">
                   <a class="d-block w-100${hMode!=='auto' ? ' h-100' : ''}">
                     <img src="${src}" alt="${(alt||'').replace(/"/g,'&quot;')}"
                          class="${hMode==='auto' ? 'img-fluid' : ''}" style="${borderCss}">
                   </a>
                 </div>
                 ${capTxt ? `<figcaption class="small" style="${capStyle}">${(capTxt||'').replace(/</g,'&lt;')}</figcaption>` : ''}
               </figure>`
                            : `<div class="small-muted mb-2"><i class="bi bi-image me-1"></i> Nessuna immagine</div>`
                    }
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <button type="button" class="btn btn-sm btn-soft btn-pill" onclick="pickImage('${sec.id}','${block.id}')">
            <i class="bi bi-upload me-1"></i> Carica
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary btn-pill" onclick="chooseImageFromMedia('${sec.id}','${block.id}')">
            <i class="bi bi-images me-1"></i> Da archivio
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" onclick="openImageUrlModal('${sec.id}','${block.id}')">
            <i class="bi bi-link-45deg me-1"></i> URL…
          </button>
          ${src ? `<span class="badge text-bg-light text-truncate-inline" title="${src}"><i class="bi bi-link-45deg me-1"></i>${src}</span>` : ''}
          <button type="button" class="btn btn-sm btn-ghost btn-pill ms-auto"
                  onclick="toggleImagePreview('${block.id}', this)">
            <i class="bi bi-aspect-ratio"></i> Anteprima editor
          </button>
        </div>
      </div>
    </div>
  `;
                }


                if (block.type === 'gallery'){
                    const items = Array.isArray(block.gallery) ? block.gallery : [];
                    const thumbs = items.map((it,i)=>`
                      <div class="col-6 col-md-4 col-xl-3">
                        <div class="thumb-tile">
                          <img src="${it.src}" alt="">
                          <div class="tile-actions">
                            <button type="button" class="btn btn-outline-danger btn-icon" title="Rimuovi"
                                    onclick="removeGalleryItem('${sec.id}','${block.id}', ${i}, 'gallery')">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                          <div class="tile-body">
                            <input type="text" class="form-control form-control-sm" placeholder="ALT"
                                   value="${(it.alt||'').replace(/"/g,'&quot;')}"
                                   oninput="updateGalleryItem('${sec.id}','${block.id}', ${i}, 'alt', this.value, 'gallery')">
                          </div>
                        </div>
                      </div>
                    `).join('');
                    return `
                      <div class="block-form">
                        <div class="d-flex flex-wrap gap-2 align-items-end mb-2">
                          <button type="button" class="btn btn-sm btn-soft btn-pill" onclick="pickGallery('${sec.id}','${block.id}','gallery')">
                            <i class="bi bi-upload me-1"></i> Aggiungi immagini
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-primary btn-pill" onclick="chooseFromMediaMultiple('${sec.id}','${block.id}','gallery')">
                            <i class="bi bi-images me-1"></i> Da archivio
                          </button>
                          <span class="inline-help">Opzioni avanzate nel modal.</span>
                        </div>
                        <div class="row g-2">
                          ${thumbs || '<div class="col-12 small-muted py-3"><i class="bi bi-images me-1"></i> Nessuna immagine</div>'}
                        </div>
                      </div>
                    `;
                }

                if (block.type === 'carousel'){
                    const items = (block.carousel?.items) || [];
                    const thumbs = items.map((it,i)=>`
                      <div class="col-6 col-md-4 col-xl-3">
                        <div class="thumb-tile">
                          <img src="${it.src}" alt="">
                          <div class="tile-actions">
                            <button type="button" class="btn btn-outline-danger btn-icon" title="Rimuovi"
                                    onclick="removeGalleryItem('${sec.id}','${block.id}', ${i}, 'carousel')">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                          <div class="tile-body">
                            <input type="text" class="form-control form-control-sm" placeholder="ALT"
                                   value="${(it.alt||'').replace(/"/g,'&quot;')}"
                                   oninput="updateGalleryItem('${sec.id}','${block.id}', ${i}, 'alt', this.value, 'carousel')">
                          </div>
                        </div>
                      </div>
                    `).join('');
                    return `
                      <div class="block-form">
                        <div class="d-flex flex-wrap gap-2 align-items-end mb-2">
                          <button type="button" class="btn btn-sm btn-soft btn-pill" onclick="pickGallery('${sec.id}','${block.id}','carousel')">
                            <i class="bi bi-upload me-1"></i> Aggiungi immagini
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-primary btn-pill" onclick="chooseFromMediaMultiple('${sec.id}','${block.id}','carousel')">
                            <i class="bi bi-images me-1"></i> Da archivio
                          </button>
                          <span class="inline-help">Opzioni avanzate nel modal.</span>
                        </div>
                        <div class="row g-2">
                          ${thumbs || '<div class="col-12 small-muted py-3"><i class="bi bi-collection me-1"></i> Nessuna immagine</div>'}
                        </div>
                      </div>
                    `;
                }

                if (typeof block.type === 'string' && block.type.startsWith('plugin:')) {
                    const def = pluginDef(block.type);

                    // Editor nativo del plugin
                    if (def && typeof def.renderEditor === 'function') {
                        return def.renderEditor(sec, block);
                    }

                    // Fallback: anteprima se esiste renderView() o FrontPlugins
                    const front = window.FrontPlugins?.[block.type];
                    const preview = (def && typeof def.renderView === 'function')
                        ? (def.renderView(block) ?? '')
                        : (typeof front === 'function' ? (front(block) ?? '') : '');

                    return `
    <div class="border rounded p-2 bg-light">
      <div class="small text-muted mb-1">
        <i class="bi bi-puzzle me-1"></i> ${def ? 'Plugin senza editor' : 'Plugin non inizializzato'}: <code>${block.type}</code>
      </div>
      ${preview ? `<div class="mb-2">${preview}</div>` : ''}
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="renderBuilder()">
        <i class="bi bi-arrow-repeat me-1"></i> Riprova
      </button>
    </div>`;
                }

                return '<div class="text-muted">Tipo non supportato.</div>';
            }

            /* TOOLBAR BLOCCO (ridotta) */
            function blockToolbar(sec, block){
                return `
                  <div class="pb-toolbar">
                    <div class="small-muted">Tipo: <strong>${block.type}</strong> • Col-${Number(block.columns)||12}</div>
                    <div class="d-flex align-items-center gap-2">
                      <button type="button" class="btn btn-sm btn-soft" title="Impostazioni"
                              onclick="openBlockSettings('${sec.id}','${block.id}')">
                        <i class="bi bi-sliders"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-ghost" title="Duplica" onclick="duplicateBlock('${sec.id}','${block.id}')">
                        <i class="bi bi-files"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" title="Elimina" onclick="removeBlock('${sec.id}','${block.id}')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>`;
            }

            function renderBuilder(){

                if (window.__R4_EDIT_LOCK){
                    window.__R4_NEED_RERENDER = true;
                    return;
                }
                ensureShape();
                const el = document.getElementById('builderContainer');
                collectR4Editors(); destroyR4Editors();

                if (!window.builderData.length){
                    el.innerHTML = `<div class="text-center py-5 text-muted">Nessun <strong>Blocco</strong>. Usa “Aggiungi Blocco”.</div>`;
                    bindPalette(); return;
                }

                let html = '';
                window.builderData.forEach((sec, idx) => {
                    const collapsed = isCollapsed(sec.id);
                    const collapseId = `secbody_${sec.id}`;
                    const pluginBtns = (window.__PLUGIN_BLOCKS__ || [])
                        .map(p => `<button type="button" class="btn btn-outline-primary btn-pill" onclick="addBlock('${sec.id}', ${p.add_columns||12}, '${p.type}')">
                          <i class="bi ${p.icon||'bi-puzzle'} me-1"></i> ${p.label}
                      </button>`).join('');

                    const secHtml = `
                    <div class="pb-section" data-sec="${sec.id}">
                        <div class="pb-head">
                            <div class="pb-title">
                                <i class="bi bi-grip-vertical drag-handle" title="Trascina per riordinare i Blocchi"></i>
                                <span>Blocco ${idx+1}</span>
                            </div>
                            <div class="pb-actions">
                                <button type="button" class="btn btn-sm btn-ghost ${collapsed ? 'collapsed' : ''}" title="Comprimi / Espandi" onclick="toggleSection('${sec.id}')">
                                    <i class="bi bi-chevron-down caret"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost" title="Duplica" onclick="duplicateSection('${sec.id}')">
                                    <i class="bi bi-files"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Elimina" onclick="removeSection('${sec.id}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div id="${collapseId}" class="pb-body ${collapsed ? 'd-none' : ''}">
                            <div class="palette mb-3">
                                <button type="button" class="btn btn-soft btn-pill" onclick="addBlock('${sec.id}',12,'text')">
                                    <i class="bi bi-type me-1"></i> Testo
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-pill" onclick="addBlock('${sec.id}',6,'image')">
                                    <i class="bi bi-image me-1"></i> Immagine
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-pill" onclick="addBlock('${sec.id}',4,'gallery')">
                                    <i class="bi bi-images me-1"></i> Galleria
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-pill" onclick="addBlock('${sec.id}',12,'carousel')">
                                    <i class="bi bi-collection me-1"></i> Carosello
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-pill" onclick="addBlock('${sec.id}',6,'video')">
                                    <i class="bi bi-camera-video me-1"></i> Video
                                </button>
                                ${pluginBtns}
                            </div>

                            <div class="row g-3">
                                ${(sec.blocks||[]).map(b => {
                        b.style = normStyle(b.style);
                        const css = styleToCss(b.style, b.type);
                        return `
                                      <div class="col-md-${Number(b.columns)||12}" style="${css.outer}">
                                        <div class="pb-block" data-bid="${b.id}">
                                            ${blockToolbar(sec, b)}
                                            <div class="block-preview" style="${css.inner}">
  ${b.style?.bgVideo ? `
    <video class="pb-bgvideo" style="--pb-bgvideo-fit:${b.style.bgVideoFit||'cover'}"
           ${b.style.bgVideoAutoplay!==false?'autoplay':''}
           ${b.style.bgVideoMuted!==false?'muted':''}
           ${b.style.bgVideoLoop!==false?'loop':''}
           ${b.style.bgVideoPlaysinline!==false?'playsinline':''}
           preload="auto" playsinline>
      <source src="${(b.style.bgVideo||'').replace(/"/g,'&quot;')}" type="video/mp4">
    </video>` : ''
                        }
  ${b.style?.overlay ? `<div class="pb-overlay" style="background:${b.style.overlay}"></div>` : ''}
  <div class="pb-content">
    ${renderBlockContent(sec, b)}
  </div>
</div>

                                        </div>
                                      </div>`;
                    }).join('')}
                            </div>
                        </div>
                    </div>`;
                    html += secHtml;
                });

                el.innerHTML = html;
                attachDnD(); bindPalette(); initR4Editors();
                // Selezione blocchi -> notifica pannello stile
                // ——— selezione/persistenza del blocco attivo ———
                (function(){
                    const host = document.getElementById('builderContainer');
                    let last = window.__LAST_SELECTED_BID__ || null;

                    function selectCard(card){
                        if (!card) return;
                        host.querySelectorAll('.pb-block.selected').forEach(n=>n.classList.remove('selected'));
                        card.classList.add('selected');
                        const bid = card.getAttribute('data-bid');
                        const fb  = findBlockById(bid);
                        window.__LAST_SELECTED_BID__ = bid;
                        document.dispatchEvent(new CustomEvent('pb:block-selected', { detail:{ block: fb?.blk, el: card } }));
                    }

                    // click -> seleziona
                    host.querySelectorAll('.pb-block').forEach(card=>{
                        card.addEventListener('click', ()=> selectCard(card));
                    });

                    // ripristina selezione precedente oppure seleziona il primo
                    const prev = last ? host.querySelector(`.pb-block[data-bid="${last}"]`) : null;
                    selectCard(prev || host.querySelector('.pb-block'));
                })();

                (function bindBlockSelection(){
                    const host = document.getElementById('builderContainer');
                    host.querySelectorAll('.pb-block').forEach(card=>{
                        card.addEventListener('click', (ev)=>{
                            // evidenzia
                            host.querySelectorAll('.pb-block.selected').forEach(n=>n.classList.remove('selected'));
                            card.classList.add('selected');

                            const bid = card.getAttribute('data-bid');
                            const fb  = findBlockById(bid);
                            document.dispatchEvent(new CustomEvent('pb:block-selected', { detail: { block: fb?.blk, el: card } }));
                        });
                    });
                })();

            }

            // Retry se ci sono plugin non ancora registrati
            const missing = [];
            window.builderData.forEach(sec => (sec.blocks||[]).forEach(b => {
                if (typeof b.type === 'string' && b.type.startsWith('plugin:')) {
                    const reg = (window.BuilderPlugins||{})[b.type];
                    if (!reg) missing.push(b.type);
                }
            }));
            if (missing.length) {
                renderBuilder.__tries = (renderBuilder.__tries||0) + 1;
                if (renderBuilder.__tries <= 20) {
                    setTimeout(()=>renderBuilder(), 200);
                }
            } else {
                renderBuilder.__tries = 0;
            }


            window.addEventListener('plugins:ready', () => {
                try { renderBuilder(); } catch(_) {}
            });

            /* Submit */
            q('#pageForm').addEventListener('submit', function(){
                collectR4Editors();
                q('#contentJson').value = JSON.stringify(window.builderData || []);
            });

            /* Evita submit/click interni */
            document.addEventListener('click', (e) => {
                const a = e.target.closest('#builderContainer a');
                if (a && !a.hasAttribute('data-allow-nav')) e.preventDefault();
            });
            document.addEventListener('submit', (e) => {
                if (e.target.id !== 'pageForm' && e.target.closest('#builderContainer')) {
                    e.preventDefault(); e.stopPropagation();
                }
            });

            /* UI extra */
            function autoGrow(el){ el.style.height='auto'; el.style.height = Math.min(240, el.scrollHeight) + 'px'; }
            document.addEventListener('DOMContentLoaded', function(){
                // densità
                q('#densityToggle')?.addEventListener('click', ()=>{
                    const cur = document.documentElement.getAttribute('data-density') || 'cozy';
                    const next = (cur === 'compact') ? 'cozy' : 'compact';
                    applyDensity(next);
                    localStorage.setItem('pb_density', next);
                    q('#densityToggle').innerHTML = next==='compact'
                        ? '<i class="bi bi-arrows-expand"></i> Normale'
                        : '<i class="bi bi-arrows-collapse"></i> Compatta';
                });
                // force refresh toggle label
                q('#densityToggle')?.dispatchEvent(new Event('click'));
                q('#densityToggle')?.dispatchEvent(new Event('click'));

                qa('textarea[autogrow]').forEach(t=>{ autoGrow(t); t.addEventListener('input', ()=>autoGrow(t)); });

                (function(){
                    const t  = q('input[name="title"]');
                    const mt = q('input[name="meta[title]"]');
                    const ex = q('textarea[name="excerpt"]');
                    const md = q('textarea[name="meta[description]"]');
                    function onceFill(src, dst){ if(!dst || !src) return; if(!dst.value && src.value) dst.value = src.value; }
                    t?.addEventListener('blur', ()=>onceFill(t, mt));
                    ex?.addEventListener('blur', ()=>onceFill(ex, md));
                    function bindCounter(input, max, out){
                        if(!input || !out) return;
                        const update = ()=> { out.textContent = `${input.value.length}/${max}`; };
                        input.addEventListener('input', update); update();
                    }
                    bindCounter(mt, 60,  q('#mtCounter'));
                    bindCounter(md, 160, q('#mdCounter'));
                })();

                q('#btnAddSection').addEventListener('click', addSection);
                q('#btnCollapseAll').addEventListener('click', ()=> collapseAll(false));
                q('#btnExpandAll').addEventListener('click', ()=> collapseAll(true));
                q('#fabAdd').addEventListener('click', addSection);

                const settingsCol = q('#settingsCol');
                const settingsContent = q('#settingsContent');
                const settingsHost = q('.settings-content-host');
                const settingsToggle = q('#settingsToggle');
                const gearFab = q('#pbGearFab');
                const offcanvas = q('#settingsOffcanvas');
                const backdrop = q('#settingsBackdrop');

                function isDesktop(){ return window.matchMedia('(min-width: 992px)').matches; }
                function setCollapsedDesktop(flag){
                    if (!isDesktop()) return;
                    settingsCol.classList.toggle('collapsed', !!flag);
                    localStorage.setItem('pb_settings_collapsed', flag ? '1' : '0');
                }
                const saved = localStorage.getItem('pb_settings_collapsed'); if (saved === '1') setCollapsedDesktop(true);
                settingsToggle?.addEventListener('click', () => { setCollapsedDesktop(!settingsCol.classList.contains('collapsed')); });

                function openSettingsMobile(){
                    if (isDesktop()) return; offcanvas.appendChild(settingsContent);
                    offcanvas.classList.add('show'); backdrop.classList.add('show'); offcanvas.setAttribute('aria-hidden','false');
                }
                function closeSettingsMobile(){
                    if (isDesktop()) return; settingsHost.appendChild(settingsContent);
                    offcanvas.classList.remove('show'); backdrop.classList.remove('show'); offcanvas.setAttribute('aria-hidden','true');
                }
                gearFab?.addEventListener('click', openSettingsMobile);
                backdrop?.addEventListener('click', closeSettingsMobile);
                window.addEventListener('resize', () => {
                    if (isDesktop()){
                        if (!settingsHost.contains(settingsContent)) settingsHost.appendChild(settingsContent);
                        closeSettingsMobile();
                    } else {
                        settingsCol.classList.remove('collapsed');
                    }
                });

                renderBuilder();
            });

            /* ESPORTO funzioni usate dal modal custom */
            window.findBlockById = findBlockById;
            window.stylePanel = stylePanel;
            window.renderBuilder = renderBuilder;
            window.openMediaPicker = openMediaPicker;
        })();


        // Aggiorna solo il blocco selezionato quando cambia lo stile dal pannello (niente re-render)
        document.addEventListener('pb:style-changed', (e)=>{
            const b = e.detail?.block;
            if (!b || !b.id) return;
            const wrap = document.querySelector(`.pb-block[data-bid="${b.id}"]`);
            if (!wrap) return;
            const preview = wrap.querySelector('.block-preview');
            const css = window.__styleToCss(b.style, b.type);
            if (preview) preview.setAttribute('style', css.inner);
            // outer (colonna)
            const col = wrap.closest('[class*="col-"]');
            if (col) col.setAttribute('style', css.outer);
        });

    </script>


    <script>
        (function(){
            // --- helpers ---
            const $ = (s, r=document)=>r.querySelector(s);
            const $$ = (s, r=document)=>Array.from(r.querySelectorAll(s));
            const panel = document.getElementById('pbStylePanel');
            const idBadge = document.getElementById('pbStyleBlockId');

            // === Help inline per i campi del pannello ===
            window.injectFieldHelpsInto = function(rootEl){
                if (!rootEl) return;

                const HELP = {
                    'maxWidth'        : 'Larghezza massima interna del contenuto (px). Vuoto = piena larghezza.',
                    'minHeight'       : 'Altezza minima del blocco (px).',
                    'height'          : 'Altezza fissa del blocco (px).',
                    'margin.t'        : 'Spazio esterno sopra il blocco (px).',
                    'margin.r'        : 'Spazio esterno a destra (px).',
                    'margin.b'        : 'Spazio esterno sotto (px).',
                    'margin.l'        : 'Spazio esterno a sinistra (px).',
                    'padding.t'       : 'Spazio interno sopra (px).',
                    'padding.r'       : 'Spazio interno a destra (px).',
                    'padding.b'       : 'Spazio interno sotto (px).',
                    'padding.l'       : 'Spazio interno a sinistra (px).',
                    'align'           : 'Allineamento del testo nei blocchi “text”.',
                    'hAlign'          : 'Allineamento orizzontale del contenuto non testuale.',
                    'bgType'          : 'Seleziona sfondo: nessuno, colore pieno o gradiente.',
                    'bg'              : 'Valore CSS completo per background (avanzato).',
                    'bg1'             : 'Colore principale di sfondo o del gradiente.',
                    'bg2'             : 'Secondo colore del gradiente.',
                    'bgAngle'         : 'Angolo del gradiente (0–360°).',
                    'bgImage'         : 'URL dell’immagine di sfondo.',
                    'bgImageFit'      : 'Corrisponde a background-size (cover/contain/auto).',
                    'bgImageRepeat'   : 'Ripetizione dell’immagine (no-repeat/repeat/…)',
                    'bgAttachment'    : 'Comportamento allo scroll (scroll/fixed/local).',
                    'bgImagePos'      : 'Posizione dell’immagine (es. center center, 50% 30%).',
                    'fullHeight'      : 'Forza il blocco a occupare l’intera viewport (100vh).',
                    'bgVideo'         : 'URL del file video di sfondo (.mp4/.webm).',
                    'bgVideoFit'      : 'Adattamento del video (cover/contain).',
                    'overlay'         : 'Colore sovrapposto (es. rgba(0,0,0,.35)).',
                    'border.w'        : 'Spessore del bordo (px).',
                    'border.s'        : 'Stile bordo (solid/dashed/…).',
                    'border.c'        : 'Colore del bordo.',
                    'border.r'        : 'Raggio degli angoli (px).'
                };

                rootEl.querySelectorAll('[data-style]').forEach(inp=>{
                    const key = inp.getAttribute('data-style');
                    const tip = HELP[key];
                    if (!tip) return;

                    const wrap = inp.closest('.form-floating') || inp.closest('.input-group') || inp.parentElement;
                    if (!wrap || wrap.querySelector('.info-badge')) return;

                    const badge = document.createElement('button');
                    badge.type = 'button';
                    badge.className = 'info-badge';
                    badge.setAttribute('data-help', tip);
                    badge.setAttribute('aria-label','Informazioni');
                    badge.textContent = 'i';

                    wrap.appendChild(badge);
                    wrap.classList.add('with-help');
                });
            };



            let selectedBlock = null;
            let selectedBlockEl = null;

            function getByPath(obj, path){
                const parts = path.split('.');
                let cur = obj;
                for(const p of parts){ if(!cur || typeof cur!=='object') return undefined; cur = cur[p]; }
                return cur;
            }
            function setByPath(obj, path, value){
                const parts = path.split('.');
                let cur = obj;
                for(let i=0;i<parts.length-1;i++){
                    const k = parts[i];
                    if (!cur[k] || typeof cur[k] !== 'object') cur[k] = {};
                    cur = cur[k];
                }
                cur[parts[parts.length-1]] = value;
            }
            const toIntOrEmpty = (v)=>{
                if (v === '' || v === null || typeof v === 'undefined') return '';
                const n = parseInt(v,10);
                return Number.isFinite(n) ? n : '';
            };

            function toggleBgRows(){
                const type = $('#bgTypeSel', panel)?.value || '';
                const colorRow = document.getElementById('bgColorRow');
                if (colorRow) colorRow.classList.toggle('d-none', !(type==='color' || type==='gradient'));
            }

            function readPanel(){
                const style = {};
                $$('[data-style]', panel).forEach(inp=>{
                    const key = inp.getAttribute('data-style');
                    let val;
                    if (inp.type === 'checkbox') {
                        val = inp.checked ? true : false;
                    } else {
                        val = inp.value;
                        if (/(\.t|\.b|\.l|\.r|Height|Width|^height$|^maxWidth$|^minHeight$|^bgAngle$|^border\.w$|^border\.r$)/.test(key)) {
                            val = (val===''||val===null)?'':(parseInt(val,10)||0);
                        }
                    }
                    setByPath(style, key, val);
                });
                return style;
            }

            function writePanel(style){
                style = style || {};
                $$('[data-style]', panel).forEach(inp=>{
                    const key = inp.getAttribute('data-style');
                    const cur = getByPath(style, key);
                    if (typeof cur === 'number') inp.value = String(cur);
                    else inp.value = (cur ?? '');
                });
                toggleBgRows();

                injectFieldHelpsInto(panel);
            }




            // Applica sul blocco selezionato (senza re-render)
            const applyFromPanel = (function(){
                let t=null;
                return function(){
                    clearTimeout(t);
                    t = setTimeout(()=>{
                        if (!selectedBlock) return;
                        selectedBlock.style = readPanel();

                        // notifica builder (che aggiornerà SOLO il DOM del blocco selezionato)
                        document.dispatchEvent(new CustomEvent('pb:style-changed', { detail: { block: selectedBlock } }));

                        // opzionale: marcatore
                        if (selectedBlockEl) selectedBlockEl.setAttribute('data-has-custom-style','1');
                    }, 100);
                };
            })();

            // Apertura Media Picker
            async function pickMediaUrl(){
                if (window.openMediaPicker){
                    const items = await window.openMediaPicker({ multiple:false, type:'image' });
                    const it = items?.[0];
                    return it?.variants?.full || it?.url || it?.thumb || null;
                }
                const url = prompt('URL immagine di sfondo');
                return url || null;
            }

            // Bind pannello
            panel.addEventListener('input', (e)=>{
                if (!e.target.matches('[data-style]')) return;
                if (e.target.id === 'bgTypeSel') toggleBgRows();
                applyFromPanel();
            });
            $('#bgImagePick', panel)?.addEventListener('click', async ()=>{
                const url = await pickMediaUrl();
                if (!url) return;
                const input = $('[data-style="bgImage"]', panel);
                input.value = url;
                applyFromPanel();
            });
            $('#bgImageClear', panel)?.addEventListener('click', ()=>{
                const input = $('[data-style="bgImage"]', panel);
                input.value = '';
                applyFromPanel();
            });

            // --- Hook bottoni "Video di sfondo" (media / clear) ---
            // NOTA: deve stare nello stesso IIFE del pannello, dopo che esistono $, $$ e panel.
            if (panel && !panel.dataset.bgVideoHooks) {
                panel.dataset.bgVideoHooks = '1';

                $('#bgVideoPick', panel)?.addEventListener('click', async () => {
                    try {
                        const items = await (window.openMediaPicker
                            ? openMediaPicker({ multiple:false, type:'video' })
                            : Promise.resolve([]));
                        const it = items?.[0]; if (!it) return;
                        const input = $('[data-style="bgVideo"]', panel);
                        if (!input) return;
                        input.value = it.url || '';
                        // riallinea immediatamente lo stile del blocco selezionato
                        input.dispatchEvent(new Event('input', { bubbles:true }));
                    } catch (err) {
                        console.warn('bgVideo pick error', err);
                    }
                });

                $('#bgVideoClear', panel)?.addEventListener('click', () => {
                    const input = $('[data-style="bgVideo"]', panel);
                    if (!input) return;
                    input.value = '';
                    input.dispatchEvent(new Event('input', { bubbles:true }));
                });
            }


            // Selezione blocco dal builder
            function setBlock(block, el){
                selectedBlock = block || null;
                selectedBlockEl = el || null;
                if (!selectedBlock){ panel.classList.add('d-none'); return; }
                idBadge.textContent = selectedBlock.id ? ('#'+selectedBlock.id) : '';
                writePanel(selectedBlock.style || {});
                panel.classList.remove('d-none');
            }
            document.addEventListener('pb:block-selected', (e)=>{
                setBlock(e.detail?.block, e.detail?.el);
            });

        })();
    </script>



    <script>
        /* ====== Modal Impostazioni (custom) ====== */
        const PBMOD = { el:null, body:null, title:null, drag:null, tab:'content', secId:null, blockId:null };

        function ensurePbModal(){
            if (PBMOD.el) return PBMOD;
            PBMOD.el   = document.getElementById('pbSettingsModal');
            PBMOD.body = document.getElementById('pbSettingsContentArea');
            PBMOD.title= document.getElementById('pbSettingsTitle');
            PBMOD.drag = document.getElementById('pbSettingsDrag');

            const close = ()=> PBMOD.el.classList.remove('show');
            document.getElementById('pbSettingsClose')?.addEventListener('click', close);
            document.getElementById('pbSettingsClose2')?.addEventListener('click', close);

            document.getElementById('pbSettingsApply')?.addEventListener('click', function(e){
                e.preventDefault();
                renderBuilder();
                renderPbSettingsInner(); // rinfresca i valori nel modal
            });

            // prevenzione submit accidentale dal tasto Enter dentro ai campi del modal
            PBMOD.el.addEventListener('keydown', (e)=>{
                if (e.key === 'Enter' && !e.target.matches('textarea')) e.preventDefault();
            });

            // minimizza
            document.getElementById('pbSettingsMin')?.addEventListener('click', (e)=>{
                e.preventDefault();
                PBMOD.el.style.left = 'auto'; PBMOD.el.style.right = '18px';
                PBMOD.el.style.top = 'auto';  PBMOD.el.style.bottom = '18px';
                PBMOD.el.style.transform = 'none';
            });

            // tabs (tutti type="button" in markup)
            PBMOD.el.querySelectorAll('.pfm-tabs .btn').forEach(btn=>{
                btn.addEventListener('click', (e)=>{
                    e.preventDefault();
                    PBMOD.tab = btn.dataset.tab;
                    renderPbSettingsInner();
                });
            });

            // drag
            let moving = false, sX=0, sY=0, oL=0, oT=0;
            PBMOD.drag.addEventListener('mousedown', (e)=>{
                moving = true; sX = e.clientX; sY = e.clientY;
                const rect = PBMOD.el.getBoundingClientRect();
                oL = rect.left; oT = rect.top;
                PBMOD.el.style.transform = 'none';
                document.body.style.userSelect = 'none';
            });
            window.addEventListener('mousemove', (e)=>{
                if (!moving) return;
                const left = oL + (e.clientX - sX);
                const top  = oT + (e.clientY - sY);
                const vw = window.innerWidth, vh = window.innerHeight, rect = PBMOD.el.getBoundingClientRect();
                PBMOD.el.style.left = Math.max(8, Math.min(vw - rect.width - 8, left)) + 'px';
                PBMOD.el.style.top  = Math.max(8, Math.min(vh - rect.height - 8, top)) + 'px';
                PBMOD.el.style.right = 'auto'; PBMOD.el.style.bottom='auto';
            });
            window.addEventListener('mouseup', ()=>{ moving=false; document.body.style.userSelect=''; });
            return PBMOD;
        }

        function openBlockSettings(secId, blockId){
            ensurePbModal();
            PBMOD.secId = secId; PBMOD.blockId = blockId; PBMOD.tab = 'content';
            const fb = findBlockById(blockId);
            PBMOD.title.textContent = `Impostazioni • ${fb?.blk?.type || 'blocco'}`;
            PBMOD.el.classList.add('show');
            renderPbSettingsInner();
        }
        window.openBlockSettings = openBlockSettings;

        function renderPbSettingsInner(){
            const ctx = findBlockById(PBMOD.blockId) || {};
            const sec = ctx.sec, blk = ctx.blk;
            if (!blk){ PBMOD.body.innerHTML = '<div class="text-muted">Blocco non trovato.</div>'; return; }

            /* Header generico: Tipo + Colonne */
            const genericHeader = `
              <div class="row g-2 align-items-end mb-2">
                <div class="col-6 col-md-4">
                  <label class="small">Tipo</label>
                  <select class="form-select form-select-sm"
                          onchange="changeType('${sec.id}','${blk.id}', this.value); renderPbSettingsInner();">
                    <option value="text" ${blk.type==='text'?'selected':''}>Testo</option>
                    <option value="image" ${blk.type==='image'?'selected':''}>Immagine</option>
                    <option value="gallery" ${blk.type==='gallery'?'selected':''}>Galleria</option>
                    <option value="carousel" ${blk.type==='carousel'?'selected':''}>Carosello</option>
                    <option value="video" ${blk.type==='video'?'selected':''}>Video</option>
                    ${(window.__PLUGIN_BLOCKS__||[]).map(p => `<option value="${p.type}" ${blk.type===p.type?'selected':''}>${p.label}</option>`).join('')}
                  </select>
                </div>
                <div class="col-6 col-md-4">
                  <label class="small">Colonne</label>
                  <select class="form-select form-select-sm"
                          onchange="changeColumns('${sec.id}','${blk.id}', this.value); renderBuilder();">
                    ${[1,2,3,4,5,6,7,8,9,10,11,12].map(n=>`<option value="${n}" ${Number(blk.columns)===n?'selected':''}>Col-${n}</option>`).join('')}
                  </select>
                </div>
              </div>
            `;

            /* Tab contenuto: per tipo */
            const contentUI = (()=> {
                if (blk.type === 'text'){
                    return `<div class="small text-muted">Modifica il testo direttamente nell’editor sul blocco.</div>`;
                }

                if (blk.type === 'image'){
                    const im  = blk.image || {};
                    const opt = im.options || { heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center', aspectRatio:'16 / 9' };
                    const capPad = im.captionPad || { t:0, r:0, b:0, l:0 };
                    const hMode = opt.heightMode || 'auto';

                    return `
    ${genericHeader}

    <div class="mb-3 d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-sm btn-soft" onclick="pickImage('${sec.id}','${blk.id}')"><i class="bi bi-upload me-1"></i> Carica</button>
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="chooseImageFromMedia('${sec.id}','${blk.id}')"><i class="bi bi-images me-1"></i> Da archivio</button>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openImageUrlModal('${sec.id}','${blk.id}')"><i class="bi bi-link-45deg me-1"></i> URL…</button>
    </div>

    <div class="row g-2">
      <div class="col-12 col-md-6">
        <label class="small">Altezza</label>
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <select class="form-select form-select-sm w-auto"
                  onchange="updateImageOption('${sec.id}','${blk.id}','heightMode', this.value); renderBuilder();">
            <option value="auto"  ${hMode==='auto'?'selected':''}>Auto</option>
            <option value="fixed" ${hMode==='fixed'?'selected':''}>Fissa (px)</option>
            <option value="ratio" ${hMode==='ratio'?'selected':''}>Rapporto (aspect-ratio)</option>
          </select>

          <input type="number" class="form-control form-control-sm w-auto" min="50" step="10"
                 ${hMode==='fixed' ? '' : 'disabled'}
                 value="${opt.heightPx ?? 450}"
                 onchange="updateImageOption('${sec.id}','${blk.id}','heightPx', this.value); renderBuilder();">

          <div class="d-flex align-items-center gap-2" ${hMode==='ratio' ? '' : 'style="display:none"'} id="arWrap_${blk.id}">
            <select class="form-select form-select-sm w-auto"
                    onchange="updateImageOption('${sec.id}','${blk.id}','aspectRatio', this.value); renderBuilder();">
              ${['16 / 9','4 / 3','1 / 1','21 / 9'].map(v=>`<option value="${v}" ${(opt.aspectRatio||'16 / 9')===v?'selected':''}>${v}</option>`).join('')}
              <option value="__custom__">Personalizzato…</option>
            </select>
            <input type="text" class="form-control form-control-sm w-auto" placeholder="es. 3 / 2"
                   value="${(opt.aspectRatio||'16 / 9')}"
                   oninput="updateImageOption('${sec.id}','${blk.id}','aspectRatio', this.value.replace(/\\s*\\/\\s*/,' / '));"
                   ${hMode==='ratio' ? '' : 'disabled'}>
          </div>
        </div>
        <div class="small text-muted mt-1">
          <strong>Nota:</strong> con <code>Auto</code> l’immagine non ha altezza vincolata, quindi <code>object-fit</code> può non essere visibile.
          Usa <code>Fissa</code> o <code>Rapporto</code> per vedere l’effetto di <em>cover/contain/fill</em>.
        </div>
      </div>

      <div class="col-6 col-md-3">
        <label class="small">Adattamento</label>
        <select class="form-select form-select-sm"
                onchange="updateImageOption('${sec.id}','${blk.id}','objectFit', this.value); renderBuilder();">
          ${['cover','contain','fill','none','scale-down'].map(v=>`<option value="${v}" ${opt.objectFit===v?'selected':''}>${v}</option>`).join('')}
        </select>
      </div>
<div class="col-6 col-md-3">
  <label class="small">Qualità</label>
  <select class="form-select form-select-sm"
          onchange="updateImageField('${sec.id}','${blk.id}','image.quality', this.value); renderBuilder();">
    ${['thumb','25','59','75','full']
                        .map(v => `<option value="${v}" ${(im.quality||'thumb')===v?'selected':''}>${v}</option>`)
                        .join('')}
  </select>
  <div class="small text-muted">Scegli la variante del file (_thumb/_25/_59/_75/_full).</div>
</div>


      <div class="col-6 col-md-3">
        <label class="small">Posizione</label>
        <input type="text" class="form-control form-control-sm" value="${opt.objectPosition||'center center'}"
               oninput="updateImageOption('${sec.id}','${blk.id}','objectPosition', this.value); renderBuilder();">
        <div class="small text-muted">Esempi: <code>center center</code>, <code>50% 30%</code></div>
      </div>
<div class="col-6 col-md-3">
  <label class="small">Ritaglio</label>
  <select class="form-select form-select-sm"
          onchange="setImageCropMode('${sec.id}','${blk.id}', this.value)">
    <option value="nocrop" ${im.uploadProfile==='logo'?'selected':''}>No crop</option>
    <option value="crop"   ${im.uploadProfile!=='logo'?'selected':''}>Crop</option>
  </select>
  <div class="small text-muted">“No crop” = varianti senza taglio + object-fit: contain.</div>
</div>



      <div class="col-6">
        <label class="small">ALT</label>
        <input type="text" class="form-control form-control-sm"
               value="${(im.alt||'').replace(/"/g,'&quot;')}"
               oninput="updateImageField('${sec.id}','${blk.id}','image.alt', this.value);">
      </div>
      <div class="col-6">
        <label class="small">Didascalia</label>
        <input type="text" class="form-control form-control-sm"
               value="${(im.caption||'').replace(/"/g,'&quot;')}"
               oninput="updateImageField('${sec.id}','${blk.id}','image.caption', this.value); renderBuilder();">
      </div>

      <div class="col-6">
        <label class="small">Colore didascalia</label>
        <input type="color" class="form-control form-control-color p-0"
               value="${im.captionColor || '#6c757d'}"
               onchange="updateImageField('${sec.id}','${blk.id}','image.captionColor', this.value); renderBuilder();">
      </div>
      <div class="col-6">
        <label class="small">Allineamento didascalia</label>
        <select class="form-select form-select-sm"
                onchange="updateImageField('${sec.id}','${blk.id}','image.captionAlign', this.value); renderBuilder();">
          ${['left','center','right'].map(v=>`<option value="${v}" ${(im.captionAlign||'left')===v?'selected':''}>${v}</option>`).join('')}
        </select>
      </div>

      <div class="col-6">
        <label class="small">Dimensione didascalia (px)</label>
        <input type="number" min="0" step="1" class="form-control form-control-sm"
               value="${im.captionSize ?? ''}"
               oninput="updateImageField('${sec.id}','${blk.id}','image.captionSize', this.value ? parseInt(this.value,10) : ''); renderBuilder();">
        <div class="small text-muted">Vuoto = default Bootstrap (.875rem)</div>
      </div>
      <div class="col-6">
        <label class="small d-block">Stile didascalia</label>
        <div class="d-flex align-items-center gap-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="capBold_${blk.id}"
                   ${im.captionBold ? 'checked' : ''}
                   onchange="updateImageField('${sec.id}','${blk.id}','image.captionBold', this.checked); renderBuilder();">
            <label class="form-check-label small" for="capBold_${blk.id}">Grassetto</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="capItalic_${blk.id}"
                   ${im.captionItalic ? 'checked' : ''}
                   onchange="updateImageField('${sec.id}','${blk.id}','image.captionItalic', this.checked); renderBuilder();">
            <label class="form-check-label small" for="capItalic_${blk.id}">Corsivo</label>
          </div>
        </div>
      </div>

      <div class="col-12">
        <label class="small d-block">Spaziatura didascalia (padding)</label>
        <div class="row g-2">
          <div class="col-3">
            <input type="number" class="form-control form-control-sm" min="0" placeholder="Top"
                   value="${(capPad.t ?? 0)}"
                   onchange="updateImageField('${sec.id}','${blk.id}','image.captionPad.t', parseInt(this.value||0,10)); renderBuilder();">
          </div>
          <div class="col-3">
            <input type="number" class="form-control form-control-sm" min="0" placeholder="Right"
                   value="${(capPad.r ?? 0)}"
                   onchange="updateImageField('${sec.id}','${blk.id}','image.captionPad.r', parseInt(this.value||0,10)); renderBuilder();">
          </div>
          <div class="col-3">
            <input type="number" class="form-control form-control-sm" min="0" placeholder="Bottom"
                   value="${(capPad.b ?? 0)}"
                   onchange="updateImageField('${sec.id}','${blk.id}','image.captionPad.b', parseInt(this.value||0,10)); renderBuilder();">
          </div>
          <div class="col-3">
            <input type="number" class="form-control form-control-sm" min="0" placeholder="Left"
                   value="${(capPad.l ?? 0)}"
                   onchange="updateImageField('${sec.id}','${blk.id}','image.captionPad.l', parseInt(this.value||0,10)); renderBuilder();">
          </div>
        </div>
        <div class="small text-muted mt-1">Agisce solo sulla didascalia.</div>
      </div>
    </div>

    <details class="fieldset collapsible mt-2" ${(im.border?.w||0)>0||(im.border?.r||0)>0?'open':''}>
      <summary><i class="bi bi-brush me-1"></i> Bordo immagine</summary>
      <div class="pt-2 row g-2">
        <div class="col-3">
          <label class="small">Spessore</label>
          <input type="number" class="form-control form-control-sm" min="0"
                 value="${im.border?.w ?? 0}" oninput="updateImageField('${sec.id}','${blk.id}','image.border.w', this.value); renderBuilder();">
        </div>
        <div class="col-3">
          <label class="small">Stile</label>
          <select class="form-select form-select-sm"
                  onchange="updateImageField('${sec.id}','${blk.id}','image.border.s', this.value); renderBuilder();">
            ${['solid','dashed','dotted','double','groove','ridge','inset','outset','none'].map(v=>`<option value="${v}" ${(im.border?.s||'solid')===v?'selected':''}>${v}</option>`).join('')}
          </select>
        </div>
        <div class="col-3">
          <label class="small">Colore</label>
          <input type="color" class="form-control form-control-color p-0"
                 value="${im.border?.c || '#000000'}"
                 onchange="updateImageField('${sec.id}','${blk.id}','image.border.c', this.value); renderBuilder();">
        </div>
        <div class="col-3">
          <label class="small">Raggio</label>
          <input type="number" class="form-control form-control-sm" min="0"
                 value="${im.border?.r ?? 0}" oninput="updateImageField('${sec.id}','${blk.id}','image.border.r', this.value); renderBuilder();">
        </div>
      </div>
    </details>
  `;
                }


                if (blk.type === 'gallery'){
                    const ql = blk.galleryQuality || 'thumb';
                    return `
                      ${genericHeader}
                      <div class="row g-2 align-items-end">
                        <div class="col-auto">
                          <label class="small">Qualità anteprima</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','galleryQuality', this.value)">
                            ${['thumb','25','59','75','full'].map(v=>`<option value="${v}" ${ql===v?'selected':''}>${v}</option>`).join('')}
                          </select>
                        </div>
                        <div class="col">
                          <div class="small text-muted">Aggiungi/Rimuovi immagini direttamente sul blocco.</div>
                        </div>
                      </div>
                    `;
                }
                if (blk.type === 'carousel'){
                    const opt = blk.carousel?.options || {};
                    const hMode = opt.heightMode || 'auto';
                    const hPx = opt.heightPx ?? 450;
                    const fit = opt.objectFit || 'cover';
                    const ql  = opt.quality || 'thumb';
                    const autoplay = !!(opt.autoplay ?? true);
                    const interval = opt.interval ?? 5000;
                    const indicators = !!(opt.indicators ?? true);
                    const controls   = !!(opt.controls ?? true);
                    return `
                      ${genericHeader}
                      <div class="row g-2 align-items-end">
                        <div class="col-auto">
                          <label class="small">Qualità</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','carousel.options.quality', this.value)">
                            ${['thumb','25','59','75','full'].map(v=>`<option value="${v}" ${ql===v?'selected':''}>${v}</option>`).join('')}
                          </select>
                        </div>
                        <div class="col-auto">
                          <label class="small">Altezza</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','carousel.options.heightMode', this.value)">
                            <option value="auto" ${hMode==='auto'?'selected':''}>Auto</option>
                            <option value="fixed" ${hMode==='fixed'?'selected':''}>Fissa (px)</option>
                          </select>
                        </div>
                        <div class="col-auto">
                          <label class="small">Px</label>
                          <input type="number" class="form-control form-control-sm" min="100" step="10" ${hMode==='fixed'?'':'disabled'}
                                 value="${hPx}" oninput="updateImageField('${sec.id}','${blk.id}','carousel.options.heightPx', this.value)">
                        </div>
                        <div class="col-auto">
                          <label class="small">Object-fit</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','carousel.options.objectFit', this.value)">
                            ${['cover','contain'].map(v=>`<option value="${v}" ${fit===v?'selected':''}>${v}</option>`).join('')}
                          </select>
                        </div>
                        <div class="col-auto">
                          <label class="small">Autoplay</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','carousel.options.autoplay', this.value==='1')">
                            <option value="1" ${autoplay?'selected':''}>Sì</option>
                            <option value="0" ${!autoplay?'selected':''}>No</option>
                          </select>
                        </div>
                        <div class="col-auto">
                          <label class="small">Interval (ms)</label>
                          <input type="number" class="form-control form-control-sm" min="1000" step="500"
                                 value="${interval}" oninput="updateImageField('${sec.id}','${blk.id}','carousel.options.interval', this.value)">
                        </div>
                        <div class="col-auto">
                          <label class="small">Indicatori</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','carousel.options.indicators', this.value==='1')">
                            <option value="1" ${indicators?'selected':''}>Sì</option>
                            <option value="0" ${!indicators?'selected':''}>No</option>
                          </select>
                        </div>
                        <div class="col-auto">
                          <label class="small">Controlli</label>
                          <select class="form-select form-select-sm"
                                  onchange="updateImageField('${sec.id}','${blk.id}','carousel.options.controls', this.value==='1')">
                            <option value="1" ${controls?'selected':''}>Sì</option>
                            <option value="0" ${!controls?'selected':''}>No</option>
                          </select>
                        </div>
                      </div>
                      <div class="small text-muted mt-1">Aggiungi/Rimuovi immagini direttamente sul blocco.</div>
                    `;
                }
                return `${genericHeader}<div class="small text-muted">Nessuna opzione specifica.</div>`;
            })();

            const styleUI = stylePanel(sec, blk);
            const effectsUI = `
              ${genericHeader}
              <div class="row g-2 align-items-end">
                <div class="col-6 col-lg-4">
                  <label class="small">Animazione</label>
                  <select class="form-select form-select-sm"
                          onchange="updateAnim('${sec.id}','${blk.id}','name', this.value);">
                    ${['none','fade','slide-up','slide-left','zoom','flip'].map(a=>`<option value="${a}" ${blk.animation?.name===a?'selected':''}>${a}</option>`).join('')}
                  </select>
                </div>
                <div class="col-3 col-lg-4">
                  <label class="small">Durata (ms)</label>
                  <input type="number" class="form-control form-control-sm" min="0" step="50"
                         value="${blk.animation?.duration??600}"
                         onchange="updateAnim('${sec.id}','${blk.id}','duration', this.value)">
                </div>
                <div class="col-3 col-lg-4">
                  <label class="small">Ritardo (ms)</label>
                  <input type="number" class="form-control form-control-sm" min="0" step="50"
                         value="${blk.animation?.delay??0}"
                         onchange="updateAnim('${sec.id}','${blk.id}','delay', this.value)">
                </div>
              </div>
            `;

            PBMOD.body.innerHTML = PBMOD.tab === 'content' ? contentUI : (PBMOD.tab === 'style' ? styleUI : effectsUI);

            if (window.injectFieldHelpsInto) { injectFieldHelpsInto(PBMOD.body); }
        }
    </script>
@endsection
