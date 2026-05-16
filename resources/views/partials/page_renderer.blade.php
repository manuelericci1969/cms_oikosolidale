{{-- resources/views/partials/page_renderer.blade.php --}}
@php
    /**
     * Renderer frontend del Page Builder (compatibile con V1 + V2)
     * - Supporta larghezza: container | custom (maxWidth) | full (full-bleed)
     * - Supporta altezza: auto | px | vh | full (100vh)
     * - Allineamento verticale (hAlign): start|center|end
     * - Background: none|color|gradient|image (+ size/repeat/pos/attachment)
     */

    // === Helpers ===============================================================

    if (!function_exists('pb_variant_url')) {
        function pb_variant_url(?string $prefer, ?string $alt, string $quality): ?string {
            $prefer = $prefer ?: '';
            $alt    = $alt    ?: '';
            if ($quality === 'full' && $prefer) return $prefer;
            $candidate = $prefer ?: $alt;
            if (!$candidate) return null;

            $parts = explode('?', $candidate, 2);
            $base  = $parts[0];
            $qs    = $parts[1] ?? '';

            if (preg_match('/_(thumb|25|59|75|full)\.(\w+)$/', $base, $m)) {
                $base = preg_replace('/_(thumb|25|59|75|full)\.(\w+)$/', "_{$quality}.{$m[2]}", $base);
                return $qs !== '' ? "{$base}?{$qs}" : $base;
            }
            return $candidate;
        }
    }

    if (!function_exists('pb_pick_media_url')) {
        /**
         * Ritorna l'URL migliore per la qualità richiesta, usando:
         * - campi espliciti: thumb, q25/q59/q75, 25/59/75, full
         * - mappe: variants/qualities/urls/sizes/conversions/images/renditions
         * - fallback naming: pb_variant_url(full, src, quality) se filename contiene _thumb/_25/_59/_75/_full
         */
        function pb_pick_media_url($it, string $quality = 'thumb'): ?string
        {
            $it = is_array($it) ? $it : [];
            $allowed = ['thumb','25','59','75','full'];
            if (!in_array($quality, $allowed, true)) $quality = 'thumb';

            $src   = (string)($it['src']  ?? '');
            $full  = (string)($it['full'] ?? $src);
            $thumb = (string)($it['thumb'] ?? ($it['thumbnail'] ?? ''));

            // Varianti "flat"
            $q25 = (string)($it['q25'] ?? ($it['25'] ?? ($it['url_25'] ?? '')));
            $q59 = (string)($it['q59'] ?? ($it['59'] ?? ($it['url_59'] ?? '')));
            $q75 = (string)($it['q75'] ?? ($it['75'] ?? ($it['url_75'] ?? '')));

            // Varianti in mappe annidate
            $maps = [];
            foreach (['variants','qualities','urls','sizes','conversions','images','renditions'] as $k) {
                if (isset($it[$k]) && is_array($it[$k])) $maps[] = $it[$k];
            }

            $fromMap = function(string $q) use ($maps): string {
                foreach ($maps as $m) {
                    if (isset($m[$q]) && is_string($m[$q]) && trim($m[$q]) !== '') return (string)$m[$q];
                    $k1 = 'q'.$q;
                    if (isset($m[$k1]) && is_string($m[$k1]) && trim($m[$k1]) !== '') return (string)$m[$k1];
                    $k2 = 'p'.$q;
                    if (isset($m[$k2]) && is_string($m[$k2]) && trim($m[$k2]) !== '') return (string)$m[$k2];
                }
                return '';
            };

            $fallback = function(string $q) use ($full, $src): string {
                if (function_exists('pb_variant_url')) {
                    $v = pb_variant_url($full, $src, $q);
                    if ($v) return $v;
                }
                return $src ?: $full;
            };

            if ($quality === 'full')  return $full ?: $src ?: $thumb ?: $q75 ?: $q59 ?: $q25 ?: $fromMap('75') ?: $fromMap('59') ?: $fromMap('25');
            if ($quality === 'thumb') return ($thumb ?: $fromMap('thumb') ?: $fallback('thumb')) ?: null;
            if ($quality === '25')    return ($q25   ?: $fromMap('25')    ?: $fallback('25')) ?: null;
            if ($quality === '59')    return ($q59   ?: $fromMap('59')    ?: $fallback('59')) ?: null;
            if ($quality === '75')    return ($q75   ?: $fromMap('75')    ?: $fallback('75')) ?: null;

            return $src ?: $full ?: $thumb ?: $q75 ?: $q59 ?: $q25 ?: null;
        }
    }

    /**
     * Applica qualità alle immagini (<img>) dentro HTML richtext.
     */
    if (!function_exists('pb_richtext_apply_image_quality')) {
        function pb_richtext_apply_image_quality(string $html, string $defaultQ = 'thumb'): string
        {
            $html = (string)$html;
            if (trim($html) === '') return $html;

            $allowed = ['thumb','25','59','75','full'];
            if (!in_array($defaultQ, $allowed, true)) $defaultQ = 'thumb';

            if (!class_exists('DOMDocument')) return $html;

            $internal = libxml_use_internal_errors(true);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $wrapped = '<div id="__pbwrap__">'.$html.'</div>';

            if (function_exists('mb_convert_encoding')) {
                $wrapped = mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8');
            }

            $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $wrap = $dom->getElementById('__pbwrap__');
            if (!$wrap) {
                libxml_clear_errors();
                libxml_use_internal_errors($internal);
                return $html;
            }

            $imgs = $wrap->getElementsByTagName('img');

            for ($i = $imgs->length - 1; $i >= 0; $i--) {
                $img = $imgs->item($i);
                if (!$img) continue;

                $src = (string)$img->getAttribute('src');

                $q = (string)$img->getAttribute('data-pb-quality');
                if ($q === '') $q = $defaultQ;
                if (!in_array($q, $allowed, true)) $q = $defaultQ;

                $full  = (string)$img->getAttribute('data-pb-full');
                $thumb = (string)$img->getAttribute('data-pb-thumb');
                $q25   = (string)$img->getAttribute('data-pb-q25');
                $q59   = (string)$img->getAttribute('data-pb-q59');
                $q75   = (string)$img->getAttribute('data-pb-q75');

                if ($full === '') $full = $src;

                $target = '';
                if ($q === 'thumb')      $target = $thumb ?: '';
                elseif ($q === '25')     $target = $q25 ?: '';
                elseif ($q === '59')     $target = $q59 ?: '';
                elseif ($q === '75')     $target = $q75 ?: '';
                elseif ($q === 'full')   $target = $full ?: '';

                if ($target === '') {
                    if (function_exists('pb_variant_url')) {
                        $target = pb_variant_url($full, $src, $q) ?: $src;
                    } else {
                        $target = $src;
                    }
                }

                if ($target !== '' && $target !== $src) {
                    $img->setAttribute('src', $target);
                }

                $img->setAttribute('data-pb-quality', $q);

                if (!$img->hasAttribute('loading'))  $img->setAttribute('loading', 'lazy');
                if (!$img->hasAttribute('decoding')) $img->setAttribute('decoding', 'async');
            }

            $out = '';
            foreach ($wrap->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }

            libxml_clear_errors();
            libxml_use_internal_errors($internal);

            return $out;
        }
    }

    if (!function_exists('pb_resolve_bg_value')) {
        function pb_resolve_bg_value($style): string {
            $style  = is_array($style) ? $style : [];
            $bgType = (string)($style['bgType'] ?? '');
            $bgFree = (string)($style['bg']     ?? '');
            $bg1    = (string)($style['bg1']    ?? '');
            $bg2    = (string)($style['bg2']    ?? '');
            $angle  = is_numeric($style['bgAngle'] ?? null) ? (int)$style['bgAngle'] : 0;

            if ($bgType === 'color') {
                return $bg1 !== '' ? $bg1 : $bgFree;
            }
            if ($bgType === 'gradient') {
                $c1 = $bg1 !== '' ? $bg1 : '#000000';
                $c2 = $bg2 !== '' ? $bg2 : '#ffffff';
                $ang = $angle % 360;
                return "linear-gradient({$ang}deg, {$c1}, {$c2})";
            }
            return $bgFree;
        }
    }

    if (!function_exists('pb_block_box_css')) {
        function pb_block_box_css($style = null, string $type = 'text'): string {
            $style = is_array($style) ? $style : [];

            $p  = is_array($style['padding'] ?? null)
                ? $style['padding']
                : ['t'=>0,'r'=>0,'b'=>0,'l'=>0];

            $bg = pb_resolve_bg_value($style);

            if ($bg === '' && !empty($style['bgColor'])) {
                $bg = (string)$style['bgColor'];
            }

            $b  = is_array($style['border'] ?? null) ? $style['border'] : [];

            $bw = $b['w'] ?? null;
            $bs = (string)($b['s'] ?? 'solid');
            $bc = (string)($b['c'] ?? '#000');
            $br = $b['r'] ?? null;

            if (array_key_exists('borderWidth', $style) && $style['borderWidth'] !== '') $bw = $style['borderWidth'];
            if (array_key_exists('borderColor', $style) && $style['borderColor'] !== '') $bc = $style['borderColor'];
            if (array_key_exists('borderRadius', $style) && $style['borderRadius'] !== '') $br = $style['borderRadius'];

            $normVal = function($v, string $fallbackUnit = 'px'): string {
                if ($v === null || $v === '' || $v === 0 || $v === '0') return '';
                if (is_numeric($v)) return intval($v) . $fallbackUnit;
                return trim((string)$v);
            };

            $bwCss = $normVal($bw, 'px');
            $brCss = $normVal($br, 'px');

            $s  = 'padding:' . intval($p['t']??0).'px '
                           . intval($p['r']??0).'px '
                           . intval($p['b']??0).'px '
                           . intval($p['l']??0).'px;';

            if ($bg !== '') $s .= "background:{$bg};";

            $bgType = (string)($style['bgType'] ?? '');
            if ($bgType === 'image') {
                $src = (string)($style['bgImage'] ?? '');
                if ($src !== '') {
                    $fit = (string)($style['bgImageFit'] ?? 'cover');
                    $pos = (string)($style['bgImagePos'] ?? 'center center');
                    $rep = (string)($style['bgImageRepeat'] ?? 'no-repeat');

                    $att = (string)($style['bgAttachment'] ?? '');
                    if ($att === '' && !empty($style['bgParallax'])) $att = 'fixed';

                    $s .= "background-image:url('{$src}');background-size:{$fit};background-position:{$pos};background-repeat:{$rep};";
                    if ($att !== '') $s .= "background-attachment:{$att};";
                }
            }

            if ($bwCss !== '') $s .= "border:{$bwCss} {$bs} {$bc};";
            if ($brCss !== '') $s .= "border-radius:{$brCss};";

            if ($type === 'text' || $type === 'richtext') {
                $align = (string)($style['align'] ?? 'left');
                $allowed = ['left','center','right','justify'];
                if (in_array($align, $allowed, true)) $s .= "text-align:{$align};";
            }

            return $s;
        }
    }

    if (!function_exists('pb_layout_css')) {
        function pb_layout_css($style = null): array {
            $style = is_array($style) ? $style : [];
            $px = fn($n) => is_numeric($n) ? intval($n).'px' : '';
            $vh = fn($n) => is_numeric($n) ? intval($n).'vh' : '';

            $outer = '';
            $inner = '';
            $outerClass = '';

            $m = is_array($style['margin'] ?? null) ? $style['margin'] : ['t'=>0,'r'=>0,'b'=>0,'l'=>0];
            $outer .= 'margin:' . intval($m['t']??0).'px '.intval($m['r']??0).'px '.intval($m['b']??0).'px '.intval($m['l']??0).'px;';

            $wMode   = (string)($style['widthMode'] ?? 'container');
            $maxW    = $style['maxWidth'] ?? '';
            if ($wMode === 'custom' && is_numeric($maxW)) {
                $outer .= 'max-width:'.$px($maxW).';margin-left:auto;margin-right:auto;';
            } elseif ($wMode === 'full') {
                $outerClass = 'pb-fullbleed';
            }

            $hMode  = (string)($style['heightMode'] ?? 'auto');
            if (!isset($style['heightMode']) && !empty($style['fullHeight'])) $hMode = 'full';

            if ($hMode === 'full') {
                $inner .= 'min-height:100vh;height:100vh;';
            } elseif ($hMode === 'px' && is_numeric($style['heightPx'] ?? null)) {
                $inner .= 'height:'.$px($style['heightPx']).';';
            } elseif ($hMode === 'vh' && is_numeric($style['heightVh'] ?? null)) {
                $inner .= 'min-height:'.$vh($style['heightVh']).';height:'.$vh($style['heightVh']).';';
            }

            $hAlign = (string)($style['hAlign'] ?? 'start');
            $map = ['start'=>'flex-start','center'=>'center','end'=>'flex-end'];
            $jc = $map[$hAlign] ?? 'flex-start';
            $inner .= "display:flex;flex-direction:column;justify-content:{$jc};";

            return [$outer, $inner, $outerClass];
        }
    }

    if (!function_exists('pb_anim_meta')) {
        function pb_anim_meta($src): array {
            $an = [];

            if (is_array($src)) {
                if (array_key_exists('name', $src) || array_key_exists('duration', $src) || array_key_exists('delay', $src)) {
                    $an = $src;
                } elseif (isset($src['animation']) && is_array($src['animation'])) {
                    $an = $src['animation'];
                } elseif (function_exists('data_get') && is_array(data_get($src, 'data.animation'))) {
                    $an = data_get($src, 'data.animation');
                }
            }

            $name = (string)($an['name'] ?? 'none');
            $dur  = (int)($an['duration'] ?? 600);
            $del  = (int)($an['delay'] ?? 0);

            if ($name === '' || $name === 'none') return ['', ''];

            $cls = 'pb-anim';
            if ($name === 'fade')       $cls .= ' pb-fade';
            if ($name === 'slide-left') $cls .= ' pb-slide-left';
            if ($name === 'slide-up')   $cls .= ' pb-slide-up';
            if ($name === 'zoom')       $cls .= ' pb-zoom';
            if ($name === 'flip')       $cls .= ' pb-flip';

            $style = "--a-dur:{$dur}ms;--a-del:{$del}ms";
            return [$cls, $style];
        }
    }

    /** @var array|string|null $content */
    $rowsRaw = $content ?? [];

    if (is_string($rowsRaw)) {
        $decoded = json_decode($rowsRaw, true);
        $rows = is_array($decoded) ? $decoded : [];
    } elseif (is_array($rowsRaw)) {
        $rows = $rowsRaw;
    } else {
        $rows = [];
    }

    if (isset($rows['rows']) && is_array($rows['rows'])) {
        $rows = $rows['rows'];
    } elseif (isset($rows['sections']) && is_array($rows['sections'])) {
        $rows = $rows['sections'];
    }

    // ✅ FIX PRODUZIONE: meta.layout width/gutter/top (non esplode se $page non c’è)
    $metaRoot = [];
    if (isset($page) && is_object($page) && isset($page->meta) && is_array($page->meta)) {
        $metaRoot = $page->meta;
    } elseif (isset($page) && is_array($page) && isset($page['meta']) && is_array($page['meta'])) {
        $metaRoot = $page['meta'];
    } elseif (isset($meta) && is_array($meta)) {
        $metaRoot = $meta;
    }

    $layoutMeta = is_array($metaRoot['layout'] ?? null) ? $metaRoot['layout'] : [];

    $w = (string)($layoutMeta['width'] ?? 'container');
    if ($w === 'standard') $w = 'container';
    $layoutWidth = in_array($w, ['container','boxed','full'], true) ? $w : 'container';

    $layoutGutter = (int)($layoutMeta['gutter'] ?? 24);
    if ($layoutGutter < 0) $layoutGutter = 0;
    if ($layoutGutter > 200) $layoutGutter = 200;

    $layoutTop = (int)($layoutMeta['top'] ?? 0);
    if ($layoutTop < 0) $layoutTop = 0;
    if ($layoutTop > 600) $layoutTop = 600;

    $wrapBootstrap = ($layoutWidth === 'full') ? 'container-fluid' : 'container';
    $boxedExtraStyle = ($layoutWidth === 'boxed')
        ? 'max-width:1200px;margin-left:auto;margin-right:auto;width:100%;'
        : '';

    $allBlocks = collect($rows)->flatMap(function ($row) {
        return is_array($row['blocks'] ?? null) ? $row['blocks'] : [];
    });

    $componentIds = $allBlocks
        ->filter(function ($block) {
            return is_array($block)
                && (($block['type'] ?? null) === 'component')
                && !empty($block['component_id']);
        })
        ->pluck('component_id')
        ->filter()
        ->unique()
        ->values();

    $componentsMap = class_exists(\App\Models\PageComponent::class)
        ? \App\Models\PageComponent::whereIn('id', $componentIds)->get()->keyBy('id')
        : collect();
@endphp

<style>
    /* Animazioni */
    .pb-anim{opacity:0;transform:translateY(12px);transition:opacity var(--a-dur,600ms) ease,transform var(--a-dur,600ms) ease;transition-delay:var(--a-del,0ms)}
    .pb-anim.in{opacity:1;transform:none}
    .pb-fade{opacity:0}
    .pb-slide-left{transform:translateX(-16px)}
    .pb-slide-up{transform:translateY(16px)}
    .pb-zoom{transform:scale(0.96)}
    .pb-flip{transform:rotateX(90deg);transform-origin:50% 0}

    /* Full-bleed */
    .pb-fullbleed{ width:100vw; margin-left:calc(50% - 50vw); margin-right:calc(50% - 50vw); }

    .pb-box{ overflow: hidden; }
    .pb-inner{ width:100%; }

    .pb-figcap{font-size:.875rem;color:#6c757d}
    .pb-imgwrap{ position:relative; overflow:hidden; width:100%; }
    .pb-imgwrap > a{ display:block; width:100%; }

    .pb-imgwrap.is-fixed{ height: var(--pb-ch, 450px); }
    .pb-imgwrap.is-fixed > a{ height:100%; }

    .pb-imgwrap.is-ratio{ aspect-ratio: var(--pb-ar, 16 / 9); }
    .pb-imgwrap.is-ratio > a{ height:100%; }

    .pb-imgwrap img{
        width:100%;
        height:auto;
        display:block;
        object-fit: var(--pb-of, cover);
        object-position: var(--pb-op, center center);
    }

    .pb-imgwrap.is-fixed img,
    .pb-imgwrap.is-ratio img{ height:100%; }

    .pb-carousel .carousel-item img{
        width:100%;
        object-fit:var(--pb-of,cover);
        object-position:var(--pb-op,center center);
    }
    .pb-carousel.pb-carousel-fixed .carousel-item img{ height:var(--pb-ch,450px); }

    .pb-logo-carousel{ overflow:hidden; position:relative; width:100%; }
    .pb-logo-track{ display:flex; align-items:center; gap:var(--pb-logo-gap,24px); will-change:transform; }
    .pb-logo-item{ flex:0 0 auto; }
    .pb-logo-item a,
    .pb-logo-item .pb-logo-imgwrap{
        display:flex;
        align-items:center;
        justify-content:center;
        width:var(--pb-logo-w,120px);
        height:var(--pb-logo-h,60px);
    }
    .pb-logo-item img{
        width:100%;
        height:100%;
        object-fit:contain;
        filter:grayscale(1);
        opacity:.6;
        transition:filter .25s ease,opacity .25s ease,transform .25s ease;
    }
    .pb-logo-item:hover img{
        filter:none;
        opacity:1;
        transform:translateY(-2px);
    }

    .pb-ripple-ring{
        position:absolute; left:0; top:0; pointer-events:none;
        border: 2px solid rgba(255,255,255,.55); border-radius:50%;
        transform: translate(-50%,-50%) scale(.2); opacity:.85;
        animation: pb-rings var(--ring-dur,1200ms) ease-out forwards;
        mix-blend-mode: screen; box-shadow: 0 2px 6px rgba(0,0,0,.2);
    }
    .pb-ripple-ring.is-2{ animation-delay: 120ms; opacity:.7; }
    .pb-ripple-ring.is-3{ animation-delay: 240ms; opacity:.55; }
    @keyframes pb-rings{ to{ transform: translate(-50%,-50%) scale(2.8); opacity:0; } }

    .pb-imgfx{ will-change: transform; }

    .pb-align-start{ text-align:left; }
    .pb-align-center{ text-align:center; }
    .pb-align-end{ text-align:right; }
    .pb-align-justify{ text-align:justify; }

    .page-content img{ max-width:100%; height:auto; }
    .pb-imgframe{ width:100%; }

    .page-content .fade:not(.show) { opacity: 1 !important; }
    .page-content .fadeIn { opacity: 1 !important; }

    .pb-logo-alert{ pointer-events:none; padding:.35rem .75rem; font-size:.825rem; }

    .pb-alert-popup-root{
        position:fixed; inset:0; z-index:1085;
        display:flex; align-items:center; justify-content:center;
        padding:1rem; opacity:0;
    }
    .pb-alert-popup-root.pb-alert-shown{
        animation-name: pb-alert-fadein;
        animation-duration: var(--pb-alert-fade-dur, 0ms);
        animation-timing-function: ease;
        animation-fill-mode: forwards;
    }
    @keyframes pb-alert-fadein{ from{opacity:0} to{opacity:1} }

    .pb-alert-popup-backdrop{ position:absolute; inset:0; background:#000; opacity:.55; }
    .pb-alert-popup-box{ position:relative; z-index:1; width:100%; }
    .pb-alert-popup-box .alert{ box-shadow:0 1rem 3rem rgba(15,23,42,.35); border-radius:.75rem; }

    /* ===========================================================
       ✅ LIGHTBOX SUPER ROBUSTA (BS5 + fallback)
       - z-index alto (niente overlay sopra)
       - backdrop “taggato” per cleanup
       - X sempre cliccabile
       =========================================================== */

    #pbLightbox{ z-index: 12000 !important; }
    .modal-backdrop.pb-lightbox-backdrop{ z-index: 11990 !important; }

    #pbLightbox .modal-dialog{ max-width: 540px !important; }
    #pbLightbox .modal-content{
        border-radius: .75rem;
        overflow: hidden;
        position: relative !important;
    }

    #pbLightbox .pb-lightbox-x{
        position: absolute !important;
        top: .5rem !important;
        right: .5rem !important;
        z-index: 99999 !important;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        border: 0;
        background: rgba(255,255,255,.9);
        color: #111;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        line-height: 1;
        font-size: 22px;
    }

    #pbLightbox .pb-lightbox-x:focus{
        outline: 2px solid rgba(13,110,253,.6);
        outline-offset: 2px;
    }

    #pbLightbox .pb-lightbox-frame{
        width: min(500px, 100%) !important;
        height: min(400px, calc(100vh - 220px)) !important;
        margin: 0 auto !important;
        display:flex !important;
        align-items:center !important;
        justify-content:center !important;
        overflow:hidden !important;
    }

    #pbLightboxImg{
        max-width:100% !important;
        max-height:100% !important;
        width:auto !important;
        height:auto !important;
        object-fit:contain !important;
        display:block !important;
        opacity: 0.9;
        transition: opacity 200ms ease;

        /* ✅ SCHIARISCE L’IMMAGINE */
        filter: brightness(1.15) contrast(1.05);
    }
</style>

<div class="page-content">
    {{-- ✅ Wrapper layout (container/boxed/full) + gutter/top --}}
    <div class="{{ $wrapBootstrap }}"
         style="padding-left: {{ $layoutGutter }}px; padding-right: {{ $layoutGutter }}px; padding-top: {{ $layoutTop }}px; {{ $boxedExtraStyle }}">

        @foreach($rows as $row)
            @php
                $justify = $row['rowAlign'] ?? 'start';
                $jc = [
                    'start'  => '',
                    'center' => 'justify-content-center',
                    'end'    => 'justify-content-end',
                    'between'=> 'justify-content-between',
                    'around' => 'justify-content-around',
                    'evenly' => 'justify-content-evenly',
                ][$justify] ?? '';

                $blocks = $row['blocks'] ?? [];
            @endphp

            <div class="row g-4 mb-4 {{ $jc }}">
                @foreach($blocks as $bRaw)
                    @php
                        $b = is_array($bRaw) ? $bRaw : [];

                        // colonne bootstrap
                        $cols = max(1, min(12, (int)($b['columns'] ?? 12)));

                        // Animazione blocco
                        [$cls, $animStyle] = pb_anim_meta($b);

                        $type   = (string)($b['type'] ?? 'text');
                        $isPlug = is_string($type) && str_starts_with($type, 'plugin:');

                        // stile blocco
                        $bStyle = [];
                        if (is_array($b['style'] ?? null)) {
                            $bStyle = $b['style'];
                        } elseif (function_exists('data_get') && is_array(data_get($b, 'data.style'))) {
                            $bStyle = data_get($b, 'data.style');
                        }

                        $rawAlign = $bStyle['align'] ?? ($b['align'] ?? 'start');
                        $mapTxt   = ['left'=>'start','right'=>'end','start'=>'start','end'=>'end','center'=>'center','justify'=>'justify'];
                        $alignTxt = $mapTxt[strtolower((string)$rawAlign)] ?? 'start';
                        $alignCls = 'pb-align-' . $alignTxt;

                        [$outerCss, $innerCss, $outerClass] = pb_layout_css($bStyle);
                        $boxCss = pb_block_box_css($bStyle, $type);
                    @endphp

                    <div class="col-md-{{ $cols }}">
                        <div class="{{ $cls }}" style="{{ $animStyle }}">
                            <div class="pb-outer {{ $outerClass }}" style="{{ $outerCss }}">
                                <div class="pb-inner" style="{{ $innerCss }}">
                                    <div class="pb-box {{ $alignCls }}" style="{{ $boxCss }}">
                                        @if(!$isPlug)
                                            @switch($type)
                                                @case('component')
                                                    @php
                                                        $component = !empty($b['component_id'])
                                                            ? ($componentsMap[$b['component_id']] ?? null)
                                                            : null;
                                                    @endphp

                                                    @if($component)
                                                        @include('admin.pages.blocks.component', [
                                                            'block' => $b,
                                                            'component' => $component,
                                                        ])
                                                    @endif
                                                    @break
                                                @case('text')
                                                @case('richtext')
                                                    @php
                                                        $html = '';

                                                        if (isset($b['html']) && is_string($b['html']) && $b['html'] !== '') {
                                                            $html = $b['html'];
                                                        } else {
                                                            if (function_exists('data_get')) {
                                                                $html = (string) data_get($b, 'data.html', '');
                                                            } else {
                                                                $html = isset($b['data']['html']) ? (string) $b['data']['html'] : '';
                                                            }

                                                            if ($html === '' && isset($b['content']) && is_string($b['content'])) {
                                                                $html = $b['content'];
                                                            }

                                                            if ($html === '' && function_exists('data_get')) {
                                                                $html = (string) data_get($b, 'data.content', '');
                                                            }
                                                        }

                                                        $html = preg_replace_callback('/\sclass="([^"]*)"/i', function($m){
                                                            $classes = preg_split('/\s+/', trim($m[1]));
                                                            $classes = array_values(array_filter($classes, function($c){
                                                                return !preg_match('/^fade(?:In|Out)?$/i', $c);
                                                            }));
                                                            return $classes ? ' class="'.implode(' ', $classes).'"' : '';
                                                        }, (string) $html);

                                                        $rt = is_array($b['richtext'] ?? null) ? $b['richtext'] : [];
                                                        $dq = (string)($rt['imageQuality'] ?? 'thumb');
                                                        $allowedQ = ['thumb','25','59','75','full'];
                                                        if (!in_array($dq, $allowedQ, true)) $dq = 'thumb';

                                                        $html = pb_richtext_apply_image_quality((string)$html, $dq);
                                                    @endphp
                                                    {!! $html !!}
                                                    @break

                                                @case('alert')
                                                    {{-- (TUTTO IL TUO BLOCCO ALERT INVARIATO) --}}
                                                    @php
                                                        $a = [];

                                                        if (is_array($b['alert'] ?? null)) {
                                                            $a = $b['alert'];
                                                        } elseif (function_exists('data_get') && is_array(data_get($b, 'data.alert'))) {
                                                            $a = data_get($b, 'data.alert');
                                                        }

                                                        $variant  = (string)($a['variant'] ?? 'info');
                                                        $allowedV = ['primary','secondary','success','danger','warning','info','light','dark','custom'];
                                                        if (!in_array($variant, $allowedV, true)) {
                                                            $variant = 'info';
                                                        }

                                                        $title      = (string)($a['title'] ?? '');
                                                        $text       = (string)($a['text'] ?? '');
                                                        $badge      = (string)($a['badge'] ?? '');
                                                        $small      = (string)($a['small'] ?? '');
                                                        $iconClass  = trim((string)($a['icon'] ?? 'bi bi-megaphone'));
                                                        if ($iconClass === '') $iconClass = 'bi bi-megaphone';
                                                        $showIcon   = (bool)($a['showIcon'] ?? true);
                                                        $dismissible= (bool)($a['dismissible'] ?? false);

                                                        $cta      = is_array($a['cta'] ?? null) ? $a['cta'] : [];
                                                        $ctaLabel = trim((string)($cta['label'] ?? ''));
                                                        $ctaUrl   = trim((string)($cta['url'] ?? ''));
                                                        $ctaTarget= (string)($cta['target'] ?? '_self');
                                                        if ($ctaUrl !== '' && !in_array($ctaTarget, ['_self','_blank'], true)) $ctaTarget = '_self';

                                                        $classes = 'alert d-flex align-items-center';
                                                        if ($variant !== 'custom') $classes .= ' alert-' . $variant;
                                                        else $classes .= ' alert-light';
                                                        if ($dismissible) $classes .= ' alert-dismissible fade show';

                                                        $styleAlert = '';
                                                        if ($variant === 'custom') {
                                                            $bg = (string)($a['bgColor'] ?? '');
                                                            $tx = (string)($a['textColor'] ?? '');
                                                            $bd = (string)($a['borderColor'] ?? '');
                                                            if ($bg !== '') $styleAlert .= "background-color:{$bg};";
                                                            if ($tx !== '') $styleAlert .= "color:{$tx};";
                                                            if ($bd !== '') $styleAlert .= "border-color:{$bd};";
                                                        }

                                                        $img = is_array($a['image'] ?? null) ? $a['image'] : [];
                                                        $imgSrc  = (string)($img['src'] ?? '');
                                                        $imgAlt  = (string)($img['alt'] ?? '');
                                                        $imgFull = (string)($img['full'] ?? $imgSrc);

                                                        $popup   = is_array($a['popup'] ?? null) ? $a['popup'] : [];
                                                        $popupEnabled   = (bool)($popup['enabled'] ?? false);
                                                        $popupShowEvery = (string)($popup['showEvery'] ?? 'always');
                                                        $popupStartAt   = (string)($popup['startAt'] ?? '');
                                                        $popupEndAt     = (string)($popup['endAt'] ?? '');
                                                        $popupWidthPx   = (int)($popup['widthPx'] ?? 480);
                                                        if ($popupWidthPx < 240) $popupWidthPx = 240;
                                                        if ($popupWidthPx > 900) $popupWidthPx = 900;
                                                        $popupOverlayOpacity = (float)($popup['overlayOpacity'] ?? 0.55);
                                                        if ($popupOverlayOpacity < 0)   $popupOverlayOpacity = 0;
                                                        if ($popupOverlayOpacity > 0.9) $popupOverlayOpacity = 0.9;
                                                        $popupAutoClose = (int)($popup['autoCloseSeconds'] ?? 0);
                                                        if ($popupAutoClose < 0) $popupAutoClose = 0;

                                                        $popupDelaySeconds = (int)($popup['delaySeconds'] ?? 0);
                                                        if ($popupDelaySeconds < 0)   $popupDelaySeconds = 0;
                                                        if ($popupDelaySeconds > 600) $popupDelaySeconds = 600;

                                                        $popupTriggerOnScroll = (bool)($popup['triggerOnScroll'] ?? false);
                                                        $popupTriggerScrollPercent = (int)($popup['triggerScrollPercent'] ?? 50);
                                                        if ($popupTriggerScrollPercent < 0)   $popupTriggerScrollPercent = 0;
                                                        if ($popupTriggerScrollPercent > 100) $popupTriggerScrollPercent = 100;

                                                        $popupFadeEnabled = (bool)($popup['fadeEnabled'] ?? true);
                                                        $popupFadeSeconds = (float)($popup['fadeSeconds'] ?? 2);
                                                        if ($popupFadeSeconds < 0)  $popupFadeSeconds = 0;
                                                        if ($popupFadeSeconds > 10) $popupFadeSeconds = 10;
                                                        $popupFadeDurationMs = (int)round($popupFadeSeconds * 1000);

                                                        $blockId = (string)($b['id'] ?? uniqid('blk_'));
                                                    @endphp

                                                    @if($popupEnabled)
                                                        <div class="pb-alert-popup-root"
                                                             data-alert-id="{{ $blockId }}"
                                                             data-show-once="{{ $popupShowEvery === 'once' ? '1' : '0' }}"
                                                             data-start-at="{{ $popupStartAt }}"
                                                             data-end-at="{{ $popupEndAt }}"
                                                             data-auto-close="{{ $popupAutoClose }}"
                                                             data-overlay-opacity="{{ $popupOverlayOpacity }}"
                                                             data-delay-seconds="{{ $popupDelaySeconds }}"
                                                             data-trigger-scroll="{{ $popupTriggerOnScroll ? '1' : '0' }}"
                                                             data-scroll-percent="{{ $popupTriggerScrollPercent }}"
                                                             data-fade="{{ $popupFadeEnabled ? '1' : '0' }}"
                                                             data-fade-duration="{{ $popupFadeDurationMs }}"
                                                             style="display:none;">
                                                            <div class="pb-alert-popup-backdrop"></div>
                                                            <div class="pb-alert-popup-box" style="max-width: {{ $popupWidthPx }}px;">
                                                                <div class="{{ $classes }}" role="alert" @if($styleAlert !== '') style="{{ $styleAlert }}" @endif>
                                                                    @if($showIcon)
                                                                        <div class="me-2 fs-4 flex-shrink-0">
                                                                            <i class="{{ $iconClass }}"></i>
                                                                        </div>
                                                                    @endif

                                                                    <div class="flex-grow-1">
                                                                        @if($imgSrc !== '')
                                                                            <div class="mb-2 text-center">
                                                                                <img src="{{ $imgSrc }}" alt="{{ e($imgAlt) }}" class="img-fluid rounded">
                                                                            </div>
                                                                        @endif

                                                                        @if($badge !== '')
                                                                            <div class="mb-1">
                                                                                <span class="badge rounded-pill text-bg-warning me-1">{{ $badge }}</span>
                                                                            </div>
                                                                        @endif

                                                                        @if($title !== '')
                                                                            <div class="fw-semibold mb-1">{{ $title }}</div>
                                                                        @endif

                                                                        @if($text !== '')
                                                                            <div class="mb-1">{!! nl2br(e($text)) !!}</div>
                                                                        @endif

                                                                        @if($small !== '')
                                                                            <div class="small text-muted">{{ $small }}</div>
                                                                        @endif

                                                                        @if($ctaUrl !== '' && $ctaLabel !== '')
                                                                            <div class="mt-2">
                                                                                <a href="{{ $ctaUrl }}" target="{{ $ctaTarget }}" class="btn btn-sm btn-outline-dark">
                                                                                    {{ $ctaLabel }}
                                                                                </a>
                                                                            </div>
                                                                        @endif
                                                                    </div>

                                                                    <button type="button"
                                                                            class="btn-close ms-2"
                                                                            data-role="pb-alert-close"
                                                                            aria-label="Chiudi"></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="{{ $classes }}" role="alert" @if($styleAlert !== '') style="{{ $styleAlert }}" @endif>
                                                            @if($showIcon)
                                                                <div class="me-2 fs-4 flex-shrink-0">
                                                                    <i class="{{ $iconClass }}"></i>
                                                                </div>
                                                            @endif

                                                            <div class="flex-grow-1">
                                                                @if($imgSrc !== '')
                                                                    <div class="mb-2 text-center">
                                                                        <img src="{{ $imgSrc }}" alt="{{ e($imgAlt) }}" class="img-fluid rounded">
                                                                    </div>
                                                                @endif

                                                                @if($badge !== '')
                                                                    <div class="mb-1">
                                                                        <span class="badge rounded-pill text-bg-warning me-1">{{ $badge }}</span>
                                                                    </div>
                                                                @endif

                                                                @if($title !== '')
                                                                    <div class="fw-semibold mb-1">{{ $title }}</div>
                                                                @endif

                                                                @if($text !== '')
                                                                    <div class="mb-1">{!! nl2br(e($text)) !!}</div>
                                                                @endif

                                                                @if($small !== '')
                                                                    <div class="small text-muted">{{ $small }}</div>
                                                                @endif

                                                                @if($ctaUrl !== '' && $ctaLabel !== '')
                                                                    <div class="mt-2">
                                                                        <a href="{{ $ctaUrl }}" target="{{ $ctaTarget }}" class="btn btn-sm btn-outline-dark">
                                                                            {{ $ctaLabel }}
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            @if($dismissible)
                                                                <button type="button"
                                                                        class="btn-close ms-2"
                                                                        data-bs-dismiss="alert"
                                                                        aria-label="Close"></button>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @break

                                                @case('image')
                                                    @php
                                                        $img  = is_array($b['image'] ?? null) ? $b['image'] : [];
                                                        $src  = (string)($img['src']  ?? '');
                                                        $full = (string)($img['full'] ?? $src);
                                                        $alt  = (string)($img['alt']  ?? '');
                                                        $cap  = (string)($img['caption'] ?? '');
                                                        $allowedQ = ['thumb','25','59','75','full'];
                                                        $q    = (string)($img['quality'] ?? 'thumb');

                                                        $opt  = is_array($img['options'] ?? null) ? $img['options'] : [];
                                                        $hMode= (string)($opt['heightMode'] ?? 'auto');  // auto|fixed|ratio
                                                        $hPx  = (int)($opt['heightPx'] ?? 450);
                                                        $fit  = (string)($opt['objectFit'] ?? 'cover');  // cover|contain|fill|none|scale-down
                                                        $allowedFit = ['cover','contain','fill','none','scale-down'];
                                                        if (!in_array($fit, $allowedFit, true)) $fit = 'cover';

                                                        $pos  = (string)($opt['objectPosition'] ?? 'center center');
                                                        if (trim($pos) === '') $pos = 'center center';

                                                        $ar   = (string)($opt['aspectRatio'] ?? '16 / 9');
                                                        $ar   = preg_replace('/\s*\/\s*/', ' / ', $ar);

                                                        $wMode = (string)($opt['widthMode'] ?? 'auto');           // auto|px|percent
                                                        $wPx   = (int)($opt['widthPx'] ?? 0);
                                                        $wPerc = (int)($opt['widthPercent'] ?? 0);
                                                        $imgAlign = (string)($opt['align'] ?? 'center');          // left|center|right
                                                        if (!in_array($imgAlign, ['left','center','right'], true)) $imgAlign = 'center';

                                                        $mt = (string)($opt['marginTop'] ?? '');
                                                        $mb = (string)($opt['marginBottom'] ?? '');
                                                        $ml = (string)($opt['marginLeft'] ?? '');
                                                        $mr = (string)($opt['marginRight'] ?? '');

                                                        $frameStyle = '';
                                                        $useFrame   = false;

                                                        if ($wMode === 'px' && $wPx > 0) {
                                                            $useFrame   = true;
                                                            $frameStyle = "max-width:{$wPx}px;";
                                                        } elseif ($wMode === 'percent' && $wPerc > 0 && $wPerc <= 100) {
                                                            $useFrame   = true;
                                                            $frameStyle = "max-width:{$wPerc}%;";
                                                        }

                                                        if ($mt !== '' || $mb !== '' || $ml !== '' || $mr !== '') {
                                                            $useFrame = true;
                                                            if ($mt !== '') $frameStyle .= "margin-top:{$mt};";
                                                            if ($mb !== '') $frameStyle .= "margin-bottom:{$mb};";
                                                            if ($ml !== '') $frameStyle .= "margin-left:{$ml};";
                                                            if ($mr !== '') $frameStyle .= "margin-right:{$mr};";
                                                        }

                                                        if ($useFrame) {
                                                            if ($imgAlign === 'left') {
                                                                if ($mr === '') $frameStyle .= 'margin-right:auto;';
                                                            } elseif ($imgAlign === 'right') {
                                                                if ($ml === '') $frameStyle .= 'margin-left:auto;';
                                                            } else {
                                                                if ($ml === '' && $mr === '') $frameStyle .= 'margin-left:auto;margin-right:auto;';
                                                            }
                                                        }

                                                        $imgBorder = is_array($img['border'] ?? null) ? $img['border'] : null;
                                                        $ibw = (int)($imgBorder['w'] ?? 0);
                                                        $ibs = (string)($imgBorder['s'] ?? 'solid');
                                                        $ibc = (string)($imgBorder['c'] ?? '#000000');
                                                        $ibr = (int)($imgBorder['r'] ?? 0);

                                                        $fx   = is_array($img['fx'] ?? null) ? $img['fx'] : [];
                                                        $pxOn = (bool)($fx['parallax'] ?? false);
                                                        $pxMd = (string)($fx['parallaxMode'] ?? 'y');
                                                        $pxSt = (int)($fx['parallaxStrength'] ?? 20);
                                                        $pxPe = (int)($fx['parallaxPerspective'] ?? 800);

                                                        $rpOn = (bool)($fx['ripple'] ?? false);
                                                        $rpRa = (int)($fx['rippleRadius'] ?? 60);
                                                        $rpDu = (int)($fx['rippleDuration'] ?? 1200);
                                                        $rpTh = (int)($fx['rippleThrottle'] ?? 120);

                                                        if (!in_array($q, $allowedQ, true)) $q = 'thumb';
                                                        $display = pb_pick_media_url($img, $q);
                                                        $zoom    = $full ?: $display;

                                                        $dataAttr = '';
                                                        if ($pxOn) {
                                                            $dataAttr .= ' data-parallax="1"';
                                                            $dataAttr .= ' data-parallax-mode="'.e($pxMd).'"';
                                                            $dataAttr .= ' data-parallax-strength="'.$pxSt.'"';
                                                            $dataAttr .= ' data-parallax-perspective="'.$pxPe.'"';
                                                        }
                                                        if ($rpOn) {
                                                            $dataAttr .= ' data-ripple="1"';
                                                            $dataAttr .= ' data-ripple-radius="'.$rpRa.'"';
                                                            $dataAttr .= ' data-ripple-duration="'.$rpDu.'"';
                                                            $dataAttr .= ' data-ripple-throttle="'.$rpTh.'"';
                                                        }

                                                        [$imgAnimCls, $imgAnimStyle] = isset($img['animation']) && is_array($img['animation'])
                                                            ? pb_anim_meta($img['animation'])
                                                            : ['', ''];

                                                        $imgWrapCls   = 'pb-imgwrap pb-imgfx';
                                                        $imgWrapStyle = '';
                                                        if ($hMode === 'fixed') {
                                                            $imgWrapCls  .= ' is-fixed';
                                                            $imgWrapStyle = "--pb-ch:".max(50,$hPx)."px;--pb-of:{$fit};--pb-op:{$pos};";
                                                        } elseif ($hMode === 'ratio') {
                                                            $imgWrapCls  .= ' is-ratio';
                                                            $imgWrapStyle = "--pb-ar:{$ar};--pb-of:{$fit};--pb-op:{$pos};";
                                                        } else {
                                                            $imgWrapStyle = "--pb-of:{$fit};--pb-op:{$pos};";
                                                        }

                                                        if ($imgAnimCls) $imgWrapCls .= ' '.$imgAnimCls;
                                                        if ($imgAnimStyle !== '') {
                                                            $imgWrapStyle = rtrim($imgWrapStyle, ';');
                                                            if ($imgWrapStyle !== '') $imgWrapStyle .= ';';
                                                            $imgWrapStyle .= $imgAnimStyle;
                                                        }

                                                        $imgStyleParts = [];
                                                        if ($ibw > 0) $imgStyleParts[] = "border:{$ibw}px {$ibs} {$ibc}";
                                                        if ($ibr > 0) $imgStyleParts[] = "border-radius:{$ibr}px";
                                                        $imgStyle = implode(';', $imgStyleParts);

                                                        $capCol   = (string)($img['captionColor'] ?? '');
                                                        $capPad   = is_array($img['captionPad'] ?? null) ? $img['captionPad'] : [];
                                                        $cpt = intval($capPad['t'] ?? 0);
                                                        $cpr = intval($capPad['r'] ?? 0);
                                                        $cpb = intval($capPad['b'] ?? 0);
                                                        $cpl = intval($capPad['l'] ?? 0);

                                                        $capAlign = (string)($img['captionAlign'] ?? 'center');
                                                        $capAlign = in_array($capAlign, ['left','center','right'], true) ? $capAlign : 'center';

                                                        $sanitizeCssVal = function($v){
                                                            $v = trim((string)$v);
                                                            return preg_replace('/[^A-Za-z0-9\.\-\s%()\/,]/', '', $v);
                                                        };

                                                        $capSizeRaw = $img['captionSize'] ?? '';
                                                        $capSize = '';
                                                        if (is_numeric($capSizeRaw)) $capSize = intval($capSizeRaw) . 'px';
                                                        elseif (is_string($capSizeRaw) && $capSizeRaw !== '') $capSize = $sanitizeCssVal($capSizeRaw);

                                                        $capWeight = '';
                                                        if (isset($img['captionWeight'])) {
                                                            $w = strtolower((string)$img['captionWeight']);
                                                            if (in_array($w, ['normal','bold','bolder','lighter'], true) || preg_match('/^[1-9]00$/', $w)) $capWeight = $w;
                                                        } elseif (!empty($img['captionBold'])) {
                                                            $capWeight = '700';
                                                        }

                                                        $capFontStyle = '';
                                                        if (isset($img['captionStyle'])) {
                                                            $fs = strtolower((string)$img['captionStyle']);
                                                            if (in_array($fs, ['normal','italic','oblique'], true)) $capFontStyle = $fs;
                                                        } elseif (!empty($img['captionItalic'])) {
                                                            $capFontStyle = 'italic';
                                                        }

                                                        $capStyle = '';
                                                        if ($capCol !== '') $capStyle .= "color:{$capCol};";
                                                        if ($cpt || $cpr || $cpb || $cpl) $capStyle .= "padding:{$cpt}px {$cpr}px {$cpb}px {$cpl}px;";
                                                        $capStyle .= "text-align:{$capAlign};";
                                                        if ($capSize !== '')     $capStyle .= "font-size:{$capSize};";
                                                        if ($capWeight !== '')   $capStyle .= "font-weight:{$capWeight};";
                                                        if ($capFontStyle !== '')$capStyle .= "font-style:{$capFontStyle};";
                                                    @endphp

                                                    @if($display)
                                                        <figure class="m-0">
                                                            @if($useFrame)
                                                                <div class="pb-imgframe" style="{{ $frameStyle }}">
                                                                    @endif

                                                                    <div class="{{ $imgWrapCls }}" style="{{ $imgWrapStyle }}" {!! $dataAttr !!}>
                                                                        <a href="#"
                                                                           class="pb-zoomable{{ $hMode==='auto' ? ' d-block w-100' : ' d-block w-100 h-100' }}"
                                                                           data-full="{{ $zoom }}"
                                                                           data-alt="{{ e($alt) }}"
                                                                           data-caption="{{ e($cap) }}">
                                                                            <img src="{{ $display }}" alt="{{ e($alt) }}" loading="lazy" decoding="async"
                                                                                 class="{{ $hMode==='auto' ? 'img-fluid' : '' }}" style="{{ $imgStyle }}">
                                                                        </a>
                                                                    </div>

                                                                    @if($useFrame)
                                                                </div>
                                                            @endif

                                                            @if($cap !== '')
                                                                <figcaption class="pb-figcap mt-1" style="{{ $capStyle }}">{{ $cap }}</figcaption>
                                                            @endif
                                                        </figure>
                                                    @endif
                                                    @break

                                                @case('gallery')
                                                    @php
                                                        $gal = is_array($b['gallery'] ?? null) ? $b['gallery'] : [];
                                                        $allowedQ = ['thumb','25','59','75','full'];
                                                        $q   = (string)($b['galleryQuality'] ?? 'thumb');
                                                        if (!in_array($q, $allowedQ, true)) $q = 'thumb';
                                                    @endphp
                                                    @if(count($gal))
                                                        <div class="row g-2">
                                                            @foreach($gal as $it)
                                                                @php
                                                                    $s  = (string)($it['src']  ?? '');
                                                                    $f  = (string)($it['full'] ?? $s);
                                                                    $al = (string)($it['alt']  ?? '');
                                                                    $d  = pb_pick_media_url($it, $q);
                                                                    $z  = $f ?: $d;
                                                                @endphp
                                                                @if($d)
                                                                    <div class="col-6 col-md-4 col-lg-3">
                                                                        <a href="#" class="pb-zoomable d-block" data-full="{{ $z }}" data-alt="{{ e($al) }}" data-caption="">
                                                                            <img src="{{ $d }}" alt="{{ e($al) }}" class="img-fluid rounded" loading="lazy" decoding="async">
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @break

                                                @case('logo_carousel')
                                                    @php
                                                        $conf  = is_array($b['logoCarousel'] ?? null) ? $b['logoCarousel'] : [];
                                                        $items = is_array($conf['items'] ?? null) ? $conf['items'] : [];
                                                        $opt   = is_array($conf['options'] ?? null) ? $conf['options'] : [];

                                                        $logoW = max(20, (int)($opt['logoWidth'] ?? 140));
                                                        $logoH = max(20, (int)($opt['logoHeight'] ?? 80));
                                                        $gap   = max(0, (int)($opt['gap'] ?? 32));
                                                        $vis   = max(1, (int)($opt['visible'] ?? 5));

                                                        $speed = (int)($opt['speed'] ?? 40);
                                                        if ($speed < 5)   $speed = 5;
                                                        if ($speed > 300) $speed = 300;

                                                        $pauseHover = (bool)($opt['pauseOnHover'] ?? true);

                                                        $maxWidthPx = $logoW * $vis + $gap * max(0, $vis - 1);

                                                        $outerStyle  = " --pb-logo-w:{$logoW}px;--pb-logo-h:{$logoH}px;--pb-logo-gap:{$gap}px;";
                                                        $outerStyle .= "max-width:{$maxWidthPx}px;margin-left:auto;margin-right:auto;";
                                                    @endphp

                                                    @if(count($items))
                                                        <div class="pb-logo-carousel"
                                                             style="{{ $outerStyle }}"
                                                             data-speed="{{ $speed }}"
                                                             @if($pauseHover) data-pause-hover="1" @endif>
                                                            <div class="pb-logo-track">

                                                                @foreach($items as $it)
                                                                    @php
                                                                        $src  = (string)($it['src'] ?? '');
                                                                        $al   = (string)($it['alt'] ?? '');
                                                                        $lnk  = (string)($it['link'] ?? '');
                                                                        $tgt  = (string)($it['target'] ?? '_self');
                                                                        $msg  = (string)($it['message'] ?? '');
                                                                        if ($lnk !== '' && !in_array($tgt, ['_self','_blank'], true)) $tgt = '_self';
                                                                    @endphp
                                                                    @if($src)
                                                                        <div class="pb-logo-item">
                                                                            <div class="pb-logo-imgwrap" @if($msg !== '') data-logo-msg="{{ e($msg) }}" @endif>
                                                                                @if($lnk !== '')
                                                                                    <a href="{{ $lnk }}" target="{{ $tgt }}" @if($tgt === '_blank') rel="noopener" @endif>
                                                                                        <img src="{{ $src }}" alt="{{ e($al) }}" loading="lazy" decoding="async">
                                                                                    </a>
                                                                                @else
                                                                                    <img src="{{ $src }}" alt="{{ e($al) }}" loading="lazy" decoding="async">
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach

                                                                @foreach($items as $it)
                                                                    @php
                                                                        $src  = (string)($it['src'] ?? '');
                                                                        $al   = (string)($it['alt'] ?? '');
                                                                        $lnk  = (string)($it['link'] ?? '');
                                                                        $tgt  = (string)($it['target'] ?? '_self');
                                                                        $msg  = (string)($it['message'] ?? '');
                                                                        if ($lnk !== '' && !in_array($tgt, ['_self','_blank'], true)) $tgt = '_self';
                                                                    @endphp
                                                                    @if($src)
                                                                        <div class="pb-logo-item">
                                                                            <div class="pb-logo-imgwrap" @if($msg !== '') data-logo-msg="{{ e($msg) }}" @endif>
                                                                                @if($lnk !== '')
                                                                                    <a href="{{ $lnk }}" target="{{ $tgt }}" @if($tgt === '_blank') rel="noopener" @endif>
                                                                                        <img src="{{ $src }}" alt="{{ e($al) }}" loading="lazy" decoding="async">
                                                                                    </a>
                                                                                @else
                                                                                    <img src="{{ $src }}" alt="{{ e($al) }}" loading="lazy" decoding="async">
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach

                                                            </div>
                                                        </div>
                                                    @endif
                                                    @break


                                                @case('video')
                                                    @php
                                                        $v   = is_array($b['video'] ?? null) ? $b['video'] : [];
                                                        $url = (string)($v['url'] ?? '');
                                                        $prov= (string)($v['provider'] ?? '');
                                                        $vid = (string)($v['id'] ?? '');
                                                        $op  = is_array($v['options'] ?? null) ? $v['options'] : [];

                                                        $aspect = (string)($op['aspect'] ?? '16 / 9');
                                                        $aspect = preg_replace('/\s*\/\s*/', ' / ', $aspect);
                                                        $poster = (string)($op['poster'] ?? '');
                                                        $autoplay    = (bool)($op['autoplay'] ?? false);
                                                        $controls    = (bool)($op['controls'] ?? true);
                                                        $loop        = (bool)($op['loop'] ?? false);
                                                        $muted       = (bool)($op['muted'] ?? false) || $autoplay;
                                                        $playsinline = (bool)($op['playsinline'] ?? true);

                                                        $fit = (string)($op['objectFit'] ?? 'contain');
                                                        $allowedFits = ['contain','cover','fill','none','scale-down'];
                                                        if (!in_array($fit, $allowedFits, true)) $fit = 'contain';
                                                    @endphp

                                                    <div class="pb-imgwrap is-ratio" style="--pb-ar: {{ $aspect }}">
                                                        @if($prov === 'youtube' && $vid !== '')
                                                            @php
                                                                $src = 'https://www.youtube.com/embed/'.$vid.'?rel=0&modestbranding=1';
                                                                if ($autoplay) $src .= '&autoplay=1&mute=1';
                                                                if ($loop)     $src .= '&loop=1&playlist='.$vid;
                                                            @endphp
                                                            <iframe
                                                                src="{{ $src }}"
                                                                title="YouTube video"
                                                                loading="lazy"
                                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                                allowfullscreen
                                                                style="width:100%;height:100%;border:0;"></iframe>
                                                        @elseif($prov === 'vimeo' && $vid !== '')
                                                            @php
                                                                $params = [];
                                                                if ($autoplay) $params[] = 'autoplay=1&muted=1';
                                                                if ($loop)     $params[] = 'loop=1';
                                                                $src = 'https://player.vimeo.com/video/'.$vid.(count($params)?'?'.implode('&',$params):'');
                                                            @endphp
                                                            <iframe
                                                                src="{{ $src }}"
                                                                title="Vimeo video"
                                                                loading="lazy"
                                                                allow="autoplay; fullscreen; picture-in-picture"
                                                                allowfullscreen
                                                                style="width:100%;height:100%;border:0;"></iframe>
                                                        @elseif($url !== '')
                                                            <video
                                                                src="{{ $url }}"
                                                                @if($poster) poster="{{ $poster }}" @endif
                                                                style="width:100%;height:100%;object-fit:{{ $fit }};"
                                                                @if($controls) controls @endif
                                                                @if($autoplay) autoplay muted @endif
                                                                @if($loop) loop @endif
                                                                @if($playsinline) playsinline @endif
                                                                preload="metadata"></video>
                                                        @endif
                                                    </div>
                                                    @break

                                                    {{-- (ALTRI CASE INVARIATI: logo_carousel, carousel, spacer, ecc...) --}}
                                            @endswitch
                                        @else
                                            @php
                                                $payload = is_array($b['data'] ?? null) ? $b['data'] : [];
                                                $bid = (string)($b['id'] ?? uniqid('blk_'));
                                            @endphp
                                            <div class="pb-plugin" data-type="{{ $type }}" data-block-id="{{ $bid }}"
                                                 data-payload='@json($payload)'>
                                                <div class="pb-plugin--placeholder">
                                                    <i class="bi bi-puzzle me-1"></i> Plugin <code>{{ $type }}</code> in caricamento…
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

{{-- ✅ Lightbox Bootstrap (super robusta) --}}
<div class="modal fade pb-lightbox" id="pbLightbox" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">

            {{-- ✅ X sempre visibile --}}
            <button type="button"
                    class="pb-lightbox-x"
                    aria-label="Chiudi"
                    data-pb-close-lightbox="1"
                    data-bs-dismiss="modal"
                    data-dismiss="modal">
                &times;
            </button>

            <div class="modal-body text-center">
                <div class="pb-lightbox-frame">
                    <img id="pbLightboxImg" src="" alt="">
                </div>
                <div class="text-white-50 small mt-2" id="pbLightboxCaption"></div>
            </div>

        </div>
    </div>
</div>

<script>
    (function(){
        // Animazioni blocchi / immagini
        const els = document.querySelectorAll('.pb-anim');
        if (els.length){
            if (!('IntersectionObserver' in window)) els.forEach(e=>e.classList.add('in'));
            else {
                const io = new IntersectionObserver((entries)=>{
                    entries.forEach(en=>{
                        if(en.isIntersecting){
                            en.target.classList.add('in');
                            io.unobserve(en.target);
                        }
                    });
                },{threshold:0.1});
                els.forEach(e=>io.observe(e));
            }
        }

        // ===========================
        // ✅ LIGHTBOX SUPER ROBUSTA
        // ===========================
        const lbEl  = document.getElementById('pbLightbox');
        const lbImg = document.getElementById('pbLightboxImg');
        const lbCap = document.getElementById('pbLightboxCaption');

        if (lbEl && lbImg && lbCap) {
            const isBS5 = !!(window.bootstrap && window.bootstrap.Modal);
            const isBS4 = !!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function');

            let bs5Inst = null;
            let fallbackBackdrop = null;

            const xBtn = lbEl.querySelector('[data-pb-close-lightbox="1"]');

            function resetLightbox() {
                lbImg.src = '';
                lbImg.alt = '';
                lbCap.textContent = '';
            }

            function markBackdrop() {
                const bds = document.querySelectorAll('.modal-backdrop');
                if (!bds.length) return;
                const last = bds[bds.length - 1];
                last.classList.add('pb-lightbox-backdrop');
            }

            function cleanupBackdropsAndBody() {
                document.querySelectorAll('.modal-backdrop.pb-lightbox-backdrop').forEach(el => el.remove());

                const anyShown = !!document.querySelector('.modal.show');
                if (!anyShown) {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }
            }

            function showLightbox() {
                if (isBS5) {
                    bs5Inst = window.bootstrap.Modal.getOrCreateInstance(lbEl, { backdrop: true, keyboard: true, focus: true });
                    bs5Inst.show();
                    requestAnimationFrame(() => setTimeout(markBackdrop, 0));
                    return;
                }

                if (isBS4) {
                    window.jQuery(lbEl).modal('show');
                    setTimeout(markBackdrop, 0);
                    return;
                }

                lbEl.classList.add('show');
                lbEl.style.display = 'block';
                lbEl.removeAttribute('aria-hidden');
                document.body.classList.add('modal-open');

                if (!fallbackBackdrop) {
                    fallbackBackdrop = document.createElement('div');
                    fallbackBackdrop.className = 'modal-backdrop fade show pb-lightbox-backdrop';
                    document.body.appendChild(fallbackBackdrop);
                    fallbackBackdrop.addEventListener('click', () => hideLightbox());
                }
            }

            function hideLightbox() {
                if (isBS5) {
                    bs5Inst = window.bootstrap.Modal.getOrCreateInstance(lbEl, { backdrop: true, keyboard: true, focus: true });
                    bs5Inst.hide();
                    setTimeout(cleanupBackdropsAndBody, 200);
                    return;
                }

                if (isBS4) {
                    window.jQuery(lbEl).modal('hide');
                    setTimeout(cleanupBackdropsAndBody, 200);
                    return;
                }

                lbEl.classList.remove('show');
                lbEl.style.display = 'none';
                lbEl.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');

                if (fallbackBackdrop) {
                    fallbackBackdrop.remove();
                    fallbackBackdrop = null;
                }

                cleanupBackdropsAndBody();
            }

            // Apertura su click (delegation)
            document.addEventListener('click', (e) => {
                const a = e.target.closest('.pb-zoomable');
                if (!a) return;

                if (lbEl.classList.contains('show')) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                const full = a.getAttribute('data-full') || a.querySelector('img')?.src || '';
                if (!full) return;

                const alt  = a.getAttribute('data-alt') || '';
                const cap  = a.getAttribute('data-caption') || alt || '';

                lbImg.src = full;
                lbImg.alt = alt;
                lbCap.textContent = cap;

                showLightbox();
            }, false);

            // Chiudi con ESC (sempre)
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && lbEl.classList.contains('show')) {
                    hideLightbox();
                    resetLightbox();
                }
            });

            // Click sulla X: chiudi sempre (anche se data-bs-dismiss fallisce)
            if (xBtn) {
                xBtn.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    ev.stopPropagation();
                    hideLightbox();
                });
            }

            // Click sul backdrop Bootstrap (è FUORI dal modal)
            document.addEventListener('click', (e) => {
                if (!lbEl.classList.contains('show')) return;

                const t = e.target;
                if (t && t.classList && t.classList.contains('modal-backdrop')) {
                    hideLightbox();
                    resetLightbox();
                }
            });

            // Reset quando Bootstrap ha finito di chiudere
            lbEl.addEventListener('hidden.bs.modal', () => {
                resetLightbox();
                cleanupBackdropsAndBody();
            });

            // BS4 event
            if (isBS4) {
                window.jQuery(lbEl).on('hidden.bs.modal', function () {
                    resetLightbox();
                    cleanupBackdropsAndBody();
                });
            }

            // Fallback: click sul “vuoto” del modal (se non c’è Bootstrap)
            lbEl.addEventListener('click', (e) => {
                if (!isBS5 && !isBS4 && e.target === lbEl) {
                    hideLightbox();
                    resetLightbox();
                }
            });
        }

        // Parallax & Ripple
        const PBFX = (function(){
            const raf = window.requestAnimationFrame || (fn=>setTimeout(fn,16));
            const state = { ticking:false };
            function clamp(n,min,max){ return Math.max(min, Math.min(max, n)); }
            function onScroll(){
                if(state.ticking) return;
                state.ticking = true;
                raf(()=>{
                    const items = document.querySelectorAll('[data-parallax="1"]');
                    const wh = window.innerHeight || document.documentElement.clientHeight;
                    items.forEach(el=>{
                        const rect = el.getBoundingClientRect();
                        if (rect.bottom < 0 || rect.top > wh) return;
                        const mode = (el.getAttribute('data-parallax-mode')||'y').toLowerCase();
                        const str  = parseInt(el.getAttribute('data-parallax-strength')||'20',10);
                        const persp= parseInt(el.getAttribute('data-parallax-perspective')||'800',10);
                        const cx = rect.left + rect.width/2;
                        const cy = rect.top  + rect.height/2;
                        const mx = window.innerWidth/2;
                        const my = wh/2;
                        let t = '';
                        if (mode === 'y'){
                            const rel = (cy - my) / my;
                            const ty = clamp(-rel * str, -str, str);
                            t = `translate3d(0, ${ty}px, 0)`;
                        } else if (mode === 'xy'){
                            const rx = (cx - mx) / mx;
                            const ry = (cy - my) / my;
                            const tx = clamp(-rx * str, -str, str);
                            const ty = clamp(-ry * str, -str, str);
                            t = `translate3d(${tx}px, ${ty}px, 0)`;
                        } else if (mode === 'tilt'){
                            const rx = (cx - mx) / mx;
                            const ry = (cy - my) / my;
                            const ax = clamp( ry * 8, -10, 10);
                            const ay = clamp(-rx * 8, -10, 10);
                            t = `perspective(${persp}px) rotateX(${ax}deg) rotateY(${ay}deg)`;
                        }
                        el.style.transform = t;
                    });
                    state.ticking=false;
                });
            }
            function throttle(fn, wait){
                let last=0; return function(...a){
                    const now=Date.now(); if(now-last>=wait){ last=now; fn.apply(this,a); }
                };
            }
            function attachRipple(container){
                const radius   = Math.max(12, parseInt(container.getAttribute('data-ripple-radius')||'60',10));
                const duration = Math.max(300, parseInt(container.getAttribute('data-ripple-duration')||'1200',10));
                const thr      = Math.max(30,  parseInt(container.getAttribute('data-ripple-throttle')||'120',10));
                const spawn = (e)=>{
                    const rect = container.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const base = Math.max(24, radius*2);
                    for(let i=1;i<=3;i++){
                        const ring = document.createElement('span');
                        ring.className = 'pb-ripple-ring' + (i>1 ? (' is-'+i) : '');
                        ring.style.width  = base+'px';
                        ring.style.height = base+'px';
                        ring.style.left   = x+'px';
                        ring.style.top    = y+'px';
                        ring.style.setProperty('--ring-dur', duration+'ms');
                        container.appendChild(ring);
                        setTimeout(()=> ring.remove(), duration + (i-1)*140 + 120);
                    }
                };
                const onMove = throttle(spawn, thr);
                container.addEventListener('mousemove', onMove);
                container.addEventListener('click', spawn);
            }
            function init(){
                window.addEventListener('scroll', onScroll, {passive:true});
                window.addEventListener('resize', onScroll);
                onScroll();
                document.querySelectorAll('[data-ripple="1"]').forEach(attachRipple);
            }
            return { init };
        })();
        PBFX.init();


        // Caroselli loghi: scorrimento fluido + alert su hover (se nessun link)
        (function(){
            const carousels = document.querySelectorAll('.pb-logo-carousel[data-speed]');
            if (!carousels.length) return;

            const instances = [];
            let alertEl = null;

            function ensureAlert() {
                if (alertEl) return alertEl;
                alertEl = document.createElement('div');
                alertEl.className = 'alert alert-info pb-logo-alert position-fixed';
                alertEl.style.zIndex = '1050';
                alertEl.style.display = 'none';
                document.body.appendChild(alertEl);
                return alertEl;
            }

            carousels.forEach(el => {
                const track = el.querySelector('.pb-logo-track');
                if (!track) return;

                const speedAttr = parseFloat(el.getAttribute('data-speed') || '40');
                const speed = isNaN(speedAttr) ? 40 : Math.max(5, Math.min(speedAttr, 300));
                const pauseOnHover = el.getAttribute('data-pause-hover') === '1';

                const inst = { el, track, speed, offset: 0, width: 0, paused: false };

                const measure = () => { inst.width = track.scrollWidth / 2; };
                measure();
                window.addEventListener('resize', measure);

                if (pauseOnHover) {
                    el.addEventListener('mouseenter', () => { inst.paused = true; });
                    el.addEventListener('mouseleave', () => { inst.paused = false; });
                }

                const msgNodes = el.querySelectorAll('[data-logo-msg]');
                msgNodes.forEach(box => {
                    box.addEventListener('mouseenter', () => {
                        const msg = box.getAttribute('data-logo-msg');
                        if (!msg) return;
                        const a = ensureAlert();
                        a.textContent = msg;
                        a.style.display = 'block';
                        a.style.top = '0px';
                        a.style.left = '0px';

                        requestAnimationFrame(() => {
                            const r = box.getBoundingClientRect();
                            const h = a.offsetHeight;
                            const w = a.offsetWidth;
                            let top = window.scrollY + r.top - h - 8;
                            if (top < window.scrollY + 4) top = window.scrollY + r.bottom + 8;
                            let left = window.scrollX + r.left + r.width / 2 - w / 2;
                            if (left < 4) left = 4;
                            a.style.top = `${top}px`;
                            a.style.left = `${left}px`;
                        });
                    });

                    box.addEventListener('mouseleave', () => {
                        if (!alertEl) return;
                        alertEl.style.display = 'none';
                    });
                });

                instances.push(inst);
            });

            if (!instances.length) return;

            let lastTs = null;
            function loop(ts) {
                if (lastTs == null) lastTs = ts;
                const dt = (ts - lastTs) / 1000;
                lastTs = ts;

                instances.forEach(inst => {
                    if (!inst.width || inst.paused) return;
                    inst.offset -= inst.speed * dt;
                    if (inst.offset <= -inst.width) inst.offset += inst.width;
                    inst.track.style.transform = `translate3d(${inst.offset}px,0,0)`;
                });

                window.requestAnimationFrame(loop);
            }

            window.requestAnimationFrame(loop);
        })();

        // Plugins mount loop
        (function(){
            const mounted = new WeakSet();
            function safeParse(json, fallback = {}) { try { return JSON.parse(json); } catch(_) { return fallback; } }
            function mountOnce(el) {
                if (mounted.has(el)) return true;
                const type = el.getAttribute('data-type');
                const data = safeParse(el.getAttribute('data-payload') || '{}');
                const reg  = window.BuilderPlugins || {};
                const api  = reg[type];

                if (api && typeof api.mount === 'function') {
                    try { api.mount(el, data); mounted.add(el); el.querySelector('.pb-plugin--placeholder')?.classList.add('d-none'); return true; }
                    catch (err) { el.innerHTML = `<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Errore nel plugin ${type}: ${String(err)}</div>`; mounted.add(el); return true; }
                } else if (api && typeof api.renderView === 'function') {
                    try { el.innerHTML = api.renderView(data) ?? ''; mounted.add(el); el.querySelector('.pb-plugin--placeholder')?.classList.add('d-none'); return true; }
                    catch (err) { el.innerHTML = `<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Errore nel plugin ${type}: ${String(err)}</div>`; mounted.add(el); return true; }
                }
                return false;
            }
            function sweep() {
                const blocks = document.querySelectorAll('.pb-plugin[data-type]');
                let pending = 0;
                blocks.forEach(el => { if (!mounted.has(el)) { const ok = mountOnce(el); if (!ok) pending++; }});
                return pending;
            }
            function startMountLoop() {
                let tries = 0, maxTries = 40;
                const tick = () => {
                    const pending = sweep();
                    if (pending === 0 || ++tries >= maxTries) clearInterval(timer);
                };
                const timer = setInterval(tick, 150);
                tick();
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startMountLoop);
            else startMountLoop();

            document.addEventListener('r4lc:refresh', sweep);
            window.__mountPluginBlocks = sweep;
        })();

        // Popup alerts (overlay, data start/end, show once, auto-close, delay, scroll, fade)
        (function(){
            const popups = Array.prototype.slice.call(
                document.querySelectorAll('.pb-alert-popup-root[data-alert-id]')
            );
            if (!popups.length) return;

            const scrollQueue = [];

            function showPopup(el, opts) {
                if (!el || el.__pbAlertShown) return;
                el.__pbAlertShown = true;

                const id       = (opts && opts.id) || '';
                const showOnce = !!(opts && opts.showOnce);
                const autoClose= parseInt((opts && opts.autoClose) || 0, 10) || 0;

                const storageKey = id ? 'r4pb_alert_seen_' + id : '';

                if (showOnce && storageKey && window.localStorage) {
                    try { localStorage.setItem(storageKey, '1'); } catch(_) {}
                }

                const fadeAttr = el.getAttribute('data-fade');
                const fadeEnabled = (fadeAttr === null) ? true : (fadeAttr === '1');

                let fadeMs = parseInt(el.getAttribute('data-fade-duration') || '0', 10);
                if (!fadeEnabled || isNaN(fadeMs) || fadeMs <= 0) fadeMs = 0;
                else if (fadeMs > 10000) fadeMs = 10000;

                el.style.display = 'flex';

                if (fadeMs > 0) {
                    el.style.setProperty('--pb-alert-fade-dur', fadeMs + 'ms');
                    el.classList.add('pb-alert-shown');
                } else {
                    el.style.opacity = '1';
                }

                const close = () => { el.style.display = 'none'; };

                const closeBtns = el.querySelectorAll('[data-role="pb-alert-close"]');
                closeBtns.forEach(btn => btn.addEventListener('click', (ev) => { ev.preventDefault(); close(); }));

                el.addEventListener('click', (ev) => {
                    if (ev.target === el || ev.target.classList.contains('pb-alert-popup-backdrop')) close();
                });

                if (autoClose > 0) setTimeout(close, autoClose * 1000);
            }

            function scheduleShowPopup(el, opts, delayMs) {
                if (!el || el.__pbAlertScheduled || el.__pbAlertShown) return;
                el.__pbAlertScheduled = true;

                const ms = Math.max(0, delayMs | 0);
                if (ms > 0) setTimeout(() => showPopup(el, opts), ms);
                else showPopup(el, opts);
            }

            popups.forEach(el => {
                const id         = el.getAttribute('data-alert-id') || '';
                const showOnce   = el.getAttribute('data-show-once') === '1';
                const startAtStr = el.getAttribute('data-start-at') || '';
                const endAtStr   = el.getAttribute('data-end-at') || '';
                const autoClose  = parseInt(el.getAttribute('data-auto-close') || '0', 10) || 0;
                const opacityRaw = parseFloat(el.getAttribute('data-overlay-opacity') || '0.55');
                const delaySec   = parseInt(el.getAttribute('data-delay-seconds') || '0', 10) || 0;
                const triggerScroll = el.getAttribute('data-trigger-scroll') === '1';
                const scrollPercent = parseInt(el.getAttribute('data-scroll-percent') || '50', 10) || 50;

                const backdrop   = el.querySelector('.pb-alert-popup-backdrop');

                if (backdrop && !isNaN(opacityRaw)) {
                    let op = opacityRaw;
                    if (op < 0) op = 0;
                    if (op > 0.9) op = 0.9;
                    backdrop.style.opacity = String(op);
                }

                const now = new Date();

                if (startAtStr) {
                    const s = new Date(startAtStr);
                    if (!isNaN(s.getTime()) && now < s) return;
                }
                if (endAtStr) {
                    const e = new Date(endAtStr);
                    if (!isNaN(e.getTime()) && now > e) return;
                }

                const storageKey = id ? 'r4pb_alert_seen_' + id : '';

                if (showOnce && storageKey && window.localStorage) {
                    try { if (localStorage.getItem(storageKey) === '1') return; } catch(_) {}
                }

                const opts = { id, showOnce, autoClose };
                const delayMs = Math.min(600, Math.max(0, delaySec)) * 1000;

                if (triggerScroll) {
                    const thr = Math.min(100, Math.max(0, scrollPercent));
                    scrollQueue.push({ el, opts, threshold: thr, delayMs });
                } else {
                    scheduleShowPopup(el, opts, delayMs);
                }
            });

            if (!scrollQueue.length) return;

            function checkScroll() {
                if (!scrollQueue.length) {
                    window.removeEventListener('scroll', onScroll);
                    return;
                }
                const doc = document.documentElement;
                const total = doc.scrollHeight - doc.clientHeight;
                if (total <= 0) return;

                const scrolled = (window.scrollY || window.pageYOffset || doc.scrollTop || 0);
                const perc = (scrolled / total) * 100;

                for (let i = scrollQueue.length - 1; i >= 0; i--) {
                    const item = scrollQueue[i];
                    if (perc >= item.threshold) {
                        scheduleShowPopup(item.el, item.opts, item.delayMs);
                        scrollQueue.splice(i, 1);
                    }
                }
            }

            const onScroll = function() {
                window.requestAnimationFrame(checkScroll);
            };

            window.addEventListener('scroll', onScroll, { passive: true });
            checkScroll();
        })();
    })();
</script>
