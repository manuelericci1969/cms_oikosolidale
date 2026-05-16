@extends('layouts.app')

@php
    $meta = is_array($page->meta ?? null) ? $page->meta : [];

    $showTitle   = (bool) data_get($meta, 'show_title', true);
    $showExcerpt = (bool) data_get($meta, 'show_excerpt', false);
    $showPubdate = (bool) data_get($meta, 'show_pubdate', true);
    $showAuthor  = (bool) data_get($meta, 'show_author', true);

    $metaTitle = method_exists($page, 'getMetaTitle') ? $page->getMetaTitle() : ($page->title ?? '');
    $metaDesc  = method_exists($page, 'getMetaDescription') ? $page->getMetaDescription() : ($page->excerpt ?? '');
    $metaKeys  = method_exists($page, 'getMetaKeywords') ? $page->getMetaKeywords() : data_get($meta, 'keywords', '');

    $editorMode = (string) ($page->editor_mode ?? 'structured');
    $isVisual   = $editorMode === 'visual' && filled($page->visual_html);
    $visualHtml = (string) ($page->visual_html ?? '');
    $visualJson = is_array($page->visual_json ?? null) ? $page->visual_json : [];
    $visualCustomJs = $isVisual ? (string) data_get($visualJson, 'r4v5CustomJs', '') : '';

    if ($visualCustomJs === '' && $isVisual && $visualHtml !== '') {
        $scriptBodies = [];
        $visualHtml = preg_replace_callback('/<script\b([^>]*)>([\s\S]*?)<\/script>/i', function ($matches) use (&$scriptBodies) {
            $attrs = (string) ($matches[1] ?? '');
            $body = trim((string) ($matches[2] ?? ''));
            if (preg_match('/\bid=["\']r4v5-[^"\']*["\']/i', $attrs)) {
                return '';
            }
            if ($body !== '') {
                $scriptBodies[] = $body;
            }
            return '';
        }, $visualHtml) ?? $visualHtml;
        $visualCustomJs = trim(implode("\n\n", $scriptBodies));
    }

    if ($isVisual && $visualHtml !== '') {
        foreach ([
            'r4v5-slider-pro-public-runtime',
            'r4v5-background-slider-public-runtime',
            'r4v5-animations-public-runtime',
            'r4v5-animations-public-fallback',
            'r4v5-widgets-pro-public-runtime',
        ] as $runtimeScriptId) {
            $quotedRuntimeScriptId = preg_quote($runtimeScriptId, '/');
            $visualHtml = preg_replace('/<script\b[^>]*\bid=["\']' . $quotedRuntimeScriptId . '["\'][^>]*>[\s\S]*?<\/script>/i', '', $visualHtml) ?? $visualHtml;
        }

        foreach ([
            'r4v5-widgets-pro-public-style',
        ] as $runtimeLinkId) {
            $quotedRuntimeLinkId = preg_quote($runtimeLinkId, '/');
            $visualHtml = preg_replace('/<link\b[^>]*\bid=["\']' . $quotedRuntimeLinkId . '["\'][^>]*>/i', '', $visualHtml) ?? $visualHtml;
        }
    }

    $needsV5SliderPro = $isVisual && str_contains($visualHtml, 'data-r4v5-slider-pro');
    $needsV5BgSlider = $isVisual && str_contains($visualHtml, 'data-r4v5-bg-slider');
    $needsV5Animations = $isVisual && (
        str_contains($visualHtml, 'data-r4-animation') ||
        str_contains($visualHtml, 'data-r4-bg-animation')
    );
    $needsV5WidgetsPro = $isVisual && (
        str_contains($visualHtml, 'r4v5-pro-') ||
        str_contains($visualHtml, 'data-r4v5-faq-accordion') ||
        str_contains($visualHtml, 'data-r4v5-count')
    );

    if ($isVisual) {
        $visualHtml = preg_replace('/<\/?body\b[^>]*>/i', '', $visualHtml) ?? $visualHtml;
        $visualHtml = preg_replace('/<script\b[^>]*\bid=["\']r4v5-[^"\']*["\'][^>]*>[\s\S]*?<\/script>/i', '', $visualHtml) ?? $visualHtml;
        $visualHtml = preg_replace('/<link\b[^>]*\bid=["\']r4v5-[^"\']*["\'][^>]*>/i', '', $visualHtml) ?? $visualHtml;
    }

    if ($isVisual) {
        // Compatibilità cms_r4software: rimuove eventuale navbar/header salvato nel visual_html.
        // Il menu pubblico ufficiale ora viene renderizzato da layouts.app tramite partials.navbar.
        // Senza questa pulizia, alcune pagine storiche mostrano due menu consecutivi.
        $visualHtml = preg_replace('/^\s*<nav\b[^>]*>[\s\S]*?<\/nav>\s*/i', '', $visualHtml, 1) ?? $visualHtml;
        $visualHtml = preg_replace('/^\s*<header\b[^>]*(?:navbar|r4-nav|r4custom-header|r4custom-nav|site-header)[^>]*>[\s\S]*?<\/header>\s*/i', '', $visualHtml, 1) ?? $visualHtml;
        $visualHtml = preg_replace('/^\s*<section\b[^>]*(?:navbar|r4-nav|r4custom-header|r4custom-nav|site-header)[^>]*>[\s\S]*?<\/section>\s*/i', '', $visualHtml, 1) ?? $visualHtml;
        $visualHtml = preg_replace('/^\s*<div\b[^>]*(?:navbar|r4-nav|r4custom-header|r4custom-nav|site-header)[^>]*>[\s\S]*?<\/div>\s*/i', '', $visualHtml, 1) ?? $visualHtml;

        // Evita markup semanticamente non valido: il layout pubblico ha già un <main> principale.
        // Se una pagina visuale salvata contiene un <main> wrapper iniziale, viene renderizzato come <div>.
        if (preg_match('/^\s*<main\b/i', $visualHtml)) {
            $visualHtml = preg_replace('/^\s*<main\b([^>]*)>/i', '<div$1>', $visualHtml, 1) ?? $visualHtml;
            $visualHtml = preg_replace('/<\/main>\s*$/i', '</div>', $visualHtml, 1) ?? $visualHtml;
        }
    }

    $layoutMeta = is_array(data_get($meta, 'layout')) ? data_get($meta, 'layout') : [];
    $layoutWidth = (string) ($layoutMeta['width'] ?? 'standard');
    $layoutWidth = $layoutWidth === 'container' ? 'standard' : $layoutWidth;

    if (!in_array($layoutWidth, ['standard', 'boxed', 'full'], true)) {
        $layoutWidth = 'standard';
    }

    $layoutGutter = (int) ($layoutMeta['gutter'] ?? 24);
    $layoutGutter = max(0, min(200, $layoutGutter));

    $layoutTop = (int) ($layoutMeta['top'] ?? 0);
    $layoutTop = max(0, min(600, $layoutTop));

    $pageBg = is_array(data_get($meta, 'page_bg')) ? data_get($meta, 'page_bg') : [];
    $pageBgType = (string) ($pageBg['type'] ?? 'none');

    $pageBgCss = '';
    $pageBgOverlayCss = '';

    if ($pageBgType === 'color') {
        $pageBgCss .= 'background:' . e((string) ($pageBg['color'] ?? '#ffffff')) . ';';
    } elseif ($pageBgType === 'gradient') {
        $from  = (string) ($pageBg['from'] ?? data_get($pageBg, 'gradient.from', '#0d6efd'));
        $to    = (string) ($pageBg['to'] ?? data_get($pageBg, 'gradient.to', '#6610f2'));
        $angle = (int) ($pageBg['angle'] ?? data_get($pageBg, 'gradient.angle', 135));
        $pageBgCss .= "background:linear-gradient({$angle}deg, {$from}, {$to});";
    } elseif ($pageBgType === 'image') {
        $img = is_array($pageBg['image'] ?? null) ? $pageBg['image'] : [];
        $src = (string) ($img['src'] ?? '');
        $size = (string) ($img['size'] ?? 'cover');
        $position = (string) ($img['position'] ?? 'center center');
        $repeat = (string) ($img['repeat'] ?? 'no-repeat');
        $attachment = !empty($img['parallax']) ? 'fixed' : (string) ($img['attachment'] ?? 'scroll');

        if ($src !== '') {
            $safeSrc = str_replace(["\n", "\r", "'", '"'], '', $src);
            $pageBgCss .= "background-image:url('{$safeSrc}');";
            $pageBgCss .= "background-size:{$size};";
            $pageBgCss .= "background-position:{$position};";
            $pageBgCss .= "background-repeat:{$repeat};";
            $pageBgCss .= "background-attachment:{$attachment};";
        }

        $overlay = is_array($img['overlay'] ?? null) ? $img['overlay'] : [];

        if (!empty($overlay['enabled'])) {
            $ovColor = (string) ($overlay['color'] ?? '#000000');
            $ovOpacity = (float) ($overlay['opacity'] ?? 0.35);
            $ovOpacity = max(0, min(0.9, $ovOpacity));
            $pageBgOverlayCss = "background:{$ovColor};opacity:{$ovOpacity};";
        }
    }

    $pageWrapperClass = match ($layoutWidth) {
        'full'  => 'container-fluid',
        default => 'container',
    };

    $pageWrapperExtraStyle = $layoutWidth === 'boxed'
        ? 'max-width:1200px;margin-left:auto;margin-right:auto;'
        : '';
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDesc)
@section('meta_keywords', $metaKeys)

@if($isVisual)
    @push('styles')
        <link id="r4v5-widgets-pro-show-style" rel="stylesheet" href="/assets/editor-v5/runtime/widgets-pro.css?v=20260508-v5-show-runtime-relative">
    @endpush

    @push('scripts')
        <script id="r4v5-widgets-pro-show-runtime" src="/assets/editor-v5/runtime/widgets-pro.js?v=20260508-v5-show-runtime-relative" defer></script>
        <script id="r4v5-animations-show-runtime" src="/assets/editor-v5/runtime/public-animations.js?v=20260508-v5-show-runtime-relative" defer></script>
    @endpush
@endif

@if($isVisual && filled($page->visual_css))
    @push('styles')
        <style id="page-visual-css-{{ $page->id }}">
            {!! $page->visual_css !!}
        </style>
    @endpush
@endif

@if($needsV5WidgetsPro)
    @push('styles')
        <link id="r4v5-widgets-pro-public-style"
              rel="stylesheet"
              href="{{ asset('assets/editor-v5/runtime/widgets-pro.css') }}?v=20260509-v5-public-runtime">
    @endpush
@endif

@push('styles')
    <style id="page-visual-runtime-{{ $page->id }}">
        .page-shell {
            position: relative;
            min-height: 100%;
            {!! $pageBgCss !!}
        }

        .page-shell__overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            {!! $pageBgOverlayCss !!}
        }

        .page-shell__content {
            position: relative;
            z-index: 1;
            padding-top: {{ $layoutTop }}px;
            padding-left: {{ $layoutGutter }}px;
            padding-right: {{ $layoutGutter }}px;
            {{ $pageWrapperExtraStyle }}
        }

        .page-visual-content,
        .page-visual-content * {
            box-sizing: border-box;
        }

        .page-visual-content {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .page-visual-content img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .page-visual-content [data-r4v5-code-drop-slot] {
            display: none !important;
        }

        .page-visual-content [data-r4v5-code-drop-slot]:has(> :not(.r4v5-code-drop-placeholder)) {
            display: block !important;
        }

        .page-visual-content [data-r4v5-code-drop-slot] > .r4v5-code-drop-placeholder {
            display: none !important;
        }

        .page-visual-content .r4-gjs-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff !important;
            text-decoration: none !important;
            font-weight: 700;
            line-height: 1.2;
            border: 0;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .page-visual-content .r4-gjs-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(37, 99, 235, .22);
            color: #ffffff !important;
            text-decoration: none !important;
        }

        .page-visual-content .r4-gjs-image {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
        }

        .page-visual-content .r4v5-pro-section,
        .page-visual-content .r4v5-pro-hero,
        .page-visual-content .r4v5-pro-cta {
            display: block;
            width: 100%;
            box-sizing: border-box;
        }

        .page-visual-content .r4v5-pro-btn,
        .page-visual-content a.r4v5-pro-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            padding: 14px 22px !important;
            border-radius: 999px !important;
            text-decoration: none !important;
            font-weight: 900 !important;
            border: 1px solid transparent !important;
            line-height: 1.2 !important;
        }

        .page-visual-content .r4v5-pro-btn-white,
        .page-visual-content a.r4v5-pro-btn-white {
            background: #ffffff !important;
            color: #0d6efd !important;
        }

        .page-visual-content .r4v5-pro-btn-outline-white,
        .page-visual-content a.r4v5-pro-btn-outline-white {
            background: transparent !important;
            color: #ffffff !important;
            border-color: rgba(255,255,255,.42) !important;
        }

        .page-visual-content .r4v5-pro-hero-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr) !important;
            gap: 48px !important;
            align-items: center !important;
        }

        @media (max-width: 980px) {
            .page-visual-content .r4v5-pro-hero-grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            html,
            body {
                max-width: 100%;
                overflow-x: hidden !important;
            }

            .page-shell,
            .page-shell__content,
            .page-visual-content {
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: hidden !important;
            }

            .page-shell__content {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }
    </style>
@endpush

@push('scripts')
    @if($needsV5SliderPro)
        <script id="r4v5-slider-pro-public-runtime"
                src="{{ asset('assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js') }}?v=20260509-v5-public-runtime"
                defer></script>
    @endif

    @if($needsV5BgSlider)
        <script id="r4v5-background-slider-public-runtime"
                src="{{ asset('assets/admin/visual-editor-v5/runtime/background-slider-runtime.js') }}?v=20260509-v5-public-runtime"
                defer></script>
    @endif

    @if($needsV5Animations)
        <script id="r4v5-animations-public-runtime"
                src="{{ asset('assets/editor-v5/runtime/public-animations.js') }}?v=20260509-v5-public-runtime"
                defer></script>
    @endif

    @if($needsV5WidgetsPro)
        <script id="r4v5-widgets-pro-public-runtime"
                src="{{ asset('assets/editor-v5/runtime/widgets-pro.js') }}?v=20260509-v5-public-runtime"
                defer></script>
    @endif

    @if($isVisual && trim($visualCustomJs) !== '')
        <script id="r4v5-page-custom-js-{{ $page->id }}">
            document.addEventListener('DOMContentLoaded', function () {
                try {
                    {!! $visualCustomJs !!}
                } catch (error) {
                    console.warn('[R4 Editor V5] Custom JS pubblico', error);
                }
            });
        </script>
    @endif
@endpush

@section('content')
    <div class="page-shell">
        @if($pageBgOverlayCss !== '')
            <div class="page-shell__overlay"></div>
        @endif

        <div class="{{ $pageWrapperClass }} page-shell__content">
            <article>
                @if($showTitle)
                    <header class="mb-4">
                        <h1 class="display-4">{{ $page->title }}</h1>

                        @if($showExcerpt && filled($page->excerpt))
                            <p class="lead text-muted">{{ $page->excerpt }}</p>
                        @endif

                        <hr>
                    </header>
                @elseif($showExcerpt && filled($page->excerpt))
                    <header class="mb-4">
                        <p class="lead text-muted">{{ $page->excerpt }}</p>
                        <hr>
                    </header>
                @endif

                @if($isVisual)
                    <div class="page-visual-content" id="pageVisualContent">
                        {!! $visualHtml !!}
                    </div>
                @else
                    @include('partials.page_renderer', [
                        'content' => $page->content,
                        'page' => $page,
                    ])
                @endif

                @if($showPubdate || ($showAuthor && $page->updater))
                    <footer class="mt-5 pt-4 border-top">
                        <small class="text-muted">
                            @if($showPubdate && $page->published_at)
                                Pubblicata il {{ $page->published_at->format('d/m/Y') }}
                            @endif

                            @if($showAuthor && $page->updater)
                                @if($showPubdate && $page->published_at)
                                    •
                                @endif

                                Modificata da {{ $page->updater->name }}
                            @endif
                        </small>
                    </footer>
                @endif
            </article>
        </div>
    </div>
@endsection
