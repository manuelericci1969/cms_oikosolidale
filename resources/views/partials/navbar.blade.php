{{-- resources/views/partials/navbar.blade.php --}}
@php
    use App\Models\Media;
    use App\Models\Menu;

    $brandAlt   = setting('branding.site_name', config('app.name'));
    $logoUrl    = setting('branding.logo_url');
    $logoId     = (int) setting('branding.logo_id');
    $logoDarkId = (int) setting('branding.logo_dark_id');

    if (!$logoUrl && $logoId) {
        if ($m = Media::find($logoId)) {
            $logoUrl = $m->variantUrl('thumb') ?: $m->url;
        }
    }

    $logoDarkUrl = null;
    if ($logoDarkId) {
        if ($md = Media::find($logoDarkId)) {
            $logoDarkUrl = $md->variantUrl('thumb') ?: $md->url;
        }
    }

    if (!$logoUrl && file_exists(public_path('images/logo.svg'))) {
        $logoUrl = asset('images/logo.svg');
    }

    /** @var \App\Models\Menu|null $mainMenu */
    $mainMenu = Menu::byLocation('header')
        ->active()
        ->with(['items' => fn($q) => $q->active()->orderBy('order'), 'items.children' => fn($q) => $q->active()->orderBy('order')])
        ->first();

    $cfg = ($mainMenu->settings ?? []) + [
        'is_sticky' => false,
        'bg_color' => 'transparent',
        'scrolled_mode' => 'transparent',
        'scrolled_bg_color' => 'transparent',
        'font_family' => 'system-ui',
        'font_size' => 16,
        'font_weight' => '600',
        'font_style' => 'normal',
        'text_align' => 'left',
        'nav_align' => 'left',
        'item_bg_mode' => 'transparent',
        'item_bg_color' => 'transparent',
        'sub_bg_mode' => 'color',
        'sub_bg_color' => '#ffffff',
        'link_color' => '#111827',
        'link_color_hover' => '#0d6efd',
        'header_height' => 76,
        'logo_height' => 28,
        'bottom_gap' => 0,
        'first_block_offset' => 0,
        'remove_first_gap' => false,
        'mobile_mode' => 'collapse',
    ];

    $mobileMode = in_array(($cfg['mobile_mode'] ?? 'collapse'), ['collapse','offcanvas','fullscreen'], true) ? $cfg['mobile_mode'] : 'collapse';
    $isEnhancedMobile = in_array($mobileMode, ['offcanvas','fullscreen'], true);

    $alignClass = match($cfg['nav_align']) {
        'center' => 'mx-auto',
        'right'  => 'ms-auto',
        default  => '',
    };

    $fontStyle = ($cfg['font_style'] ?? 'normal') === 'italic' ? 'italic' : 'normal';
    $stickyClass = !empty($cfg['is_sticky']) ? ' sticky-top' : '';
    $headerHeight = max(40, min(220, (int)($cfg['header_height'] ?? 76)));
    $logoHeight = max(16, min(160, (int)($cfg['logo_height'] ?? 28)));
    $removeFirstGap = !empty($cfg['remove_first_gap']);
    $bottomGap = max(0, min(240, (int)($cfg['bottom_gap'] ?? 0)));
    $firstBlockOffset = max(-240, min(240, (int)($cfg['first_block_offset'] ?? 0)));
    $effectiveContentGap = $removeFirstGap ? 0 : ($bottomGap + $firstBlockOffset);

    $renderMenuItems = function($items, $isMobile = false) use (&$renderMenuItems, $cfg) {
        foreach($items as $it) {
            $hasChildren = $it->children && $it->children->count();
            $kind = $it->type ?? 'link';
            if($kind === 'separator') {
                $hasLabel = trim($it->title) !== '';
                if($hasLabel) {
                    echo '<li class="nav-item"><span class="nav-link text-uppercase small fw-semibold text-muted pe-none">'.e($it->title).'</span></li>';
                } else {
                    echo '<li class="nav-item d-none d-lg-flex align-items-center nav-sep" role="separator" aria-hidden="true"><span class="vr"></span></li><li class="nav-item d-lg-none w-100 px-2" role="separator" aria-hidden="true"><hr class="dropdown-divider my-2"></li>';
                }
                continue;
            }
            $url = $it->url ?: '#';
            $target = $it->target ? ' target="'.e($it->target).'" rel="'.($it->target === '_blank' ? 'noopener' : '').'"' : '';
            echo '<li class="nav-item '.($hasChildren ? 'dropdown' : '').'">';
            echo '<a class="nav-link '.($hasChildren ? 'dropdown-toggle' : '').'" href="'.e($url).'" '.($hasChildren ? 'data-bs-toggle="dropdown" role="button" aria-expanded="false"' : '').$target.' style="background:var(--nav-item-bg); border-radius:.5rem; color:var(--nav-link);">';
            if($it->icon) echo '<i class="'.e($it->icon).'"></i> ';
            echo '<span>'.e($it->title).'</span></a>';
            if($hasChildren) {
                echo '<ul class="dropdown-menu" style="background:var(--nav-sub-bg); border:1px solid rgba(0,0,0,.06); border-radius:.75rem;">';
                foreach($it->children as $ch) {
                    $childKind = $ch->type ?? 'link';
                    if($childKind === 'separator') {
                        $hasChildLabel = trim($ch->title) !== '';
                        echo $hasChildLabel ? '<li class="dropdown-header fw-semibold">'.e($ch->title).'</li>' : '<li><hr class="dropdown-divider"></li>';
                    } else {
                        $childTarget = $ch->target ? ' target="'.e($ch->target).'" rel="'.($ch->target === '_blank' ? 'noopener' : '').'"' : '';
                        echo '<li><a class="dropdown-item" href="'.e($ch->url ?: '#').'"'.$childTarget.'>'.e($ch->title).'</a></li>';
                    }
                }
                echo '</ul>';
            }
            echo '</li>';
        }
    };
@endphp

<style id="r4-public-navbar-builder-style">
    .navbar[data-r4-nav="1"]{min-height:var(--nav-height,76px)!important;height:auto!important;background:var(--nav-bg,transparent)!important;padding-top:0!important;padding-bottom:0!important;transition:background .25s ease,box-shadow .25s ease,min-height .25s ease}
    .navbar[data-r4-nav="1"]>.container,.navbar[data-r4-nav="1"]>.container-fluid,.navbar[data-r4-nav="1"] .r4-nav__container{min-height:var(--nav-height,76px)!important;display:flex!important;align-items:center!important}
    .navbar[data-r4-nav="1"] .navbar-brand{min-height:var(--nav-height,76px)!important;display:inline-flex!important;align-items:center!important;padding-top:0!important;padding-bottom:0!important;font-weight:900;letter-spacing:-.03em;color:var(--nav-link,#111827)!important;text-decoration:none!important;white-space:nowrap}
    .navbar[data-r4-nav="1"] .navbar-brand img{height:var(--nav-logo-height,28px)!important;max-height:calc(var(--nav-height,76px) - 12px)!important;width:auto!important;object-fit:contain!important}
    .navbar[data-r4-nav="1"] .navbar-collapse{min-height:var(--nav-height,76px)!important;align-items:center!important}
    .navbar[data-r4-nav="1"] .navbar-nav{align-items:center!important}.navbar[data-r4-nav="1"] .nav-item{display:flex;align-items:center}
    .navbar[data-r4-nav="1"] .nav-link{min-height:calc(var(--nav-height,76px) - 24px)!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:.5rem .85rem!important;color:var(--nav-link)!important;transition:color .2s ease,background .2s ease,transform .2s ease}
    .navbar[data-r4-nav="1"] .nav-link:hover,.navbar[data-r4-nav="1"] .dropdown-item:hover{color:var(--nav-link-hover)!important}.navbar[data-r4-nav="1"] .navbar-toggler{border-color:rgba(0,0,0,.08)}.navbar[data-r4-nav="1"] .dropdown-menu{margin-top:.35rem}.navbar[data-r4-nav="1"] .nav-sep .vr{width:1px;height:1.25rem;background:rgba(0,0,0,.15);display:inline-block}
    .navbar[data-r4-nav="1"].is-scrolled,.navbar[data-r4-nav="1"].r4-nav--scrolled{background:var(--nav-bg-scrolled,var(--nav-bg,transparent))!important}
    .r4-mobile-menu-panel{--bs-offcanvas-bg:var(--nav-bg,#fff);--bs-offcanvas-color:var(--nav-link,#111827);background:var(--nav-bg,#fff);color:var(--nav-link,#111827)}
    .r4-mobile-menu-panel .offcanvas-header{min-height:72px;border-bottom:1px solid rgba(148,163,184,.18)}
    .r4-mobile-menu-panel .navbar-brand{min-height:auto!important}.r4-mobile-menu-panel .navbar-brand img{height:var(--nav-logo-height,28px)!important;width:auto!important}
    .r4-mobile-menu-panel .navbar-nav{align-items:stretch!important;width:100%;gap:4px}.r4-mobile-menu-panel .nav-item{display:block!important}.r4-mobile-menu-panel .nav-link{min-height:auto!important;justify-content:flex-start!important;padding:.85rem 1rem!important;border-radius:14px;color:var(--nav-link,#111827)!important;background:transparent!important}
    .r4-mobile-menu-panel .nav-link:hover{background:rgba(13,110,253,.08)!important;color:var(--nav-link-hover,#0d6efd)!important}.r4-mobile-menu-panel .dropdown-menu{position:static!important;transform:none!important;width:100%;box-shadow:none;border:1px solid rgba(148,163,184,.16);margin:.25rem 0 .5rem;padding:.5rem}
    .r4-mobile-menu-panel--fullscreen{width:100vw!important;max-width:100vw!important;height:100vh!important}.r4-mobile-menu-panel--fullscreen .offcanvas-body{display:flex;align-items:center;justify-content:center}.r4-mobile-menu-panel--fullscreen .navbar-nav{max-width:520px;text-align:center}.r4-mobile-menu-panel--fullscreen .nav-link{justify-content:center!important;font-size:clamp(1.35rem,4vw,2.4rem);font-weight:800}
    @media(max-width:991.98px){.navbar[data-r4-nav="1"] .navbar-collapse{min-height:auto!important;align-items:stretch!important;padding-top:.75rem;padding-bottom:.75rem}.navbar[data-r4-nav="1"] .navbar-nav{align-items:stretch!important}.navbar[data-r4-nav="1"] .nav-item{display:block}.navbar[data-r4-nav="1"] .nav-link{min-height:auto!important;justify-content:flex-start!important}}
</style>

<nav class="navbar navbar-expand-lg navbar-light border-bottom r4-nav{{ $stickyClass }}{{ !empty($cfg['is_sticky']) ? ' r4-nav--sticky' : '' }}" data-r4-nav="1" data-mobile-mode="{{ $mobileMode }}" data-scroll-toggle="1" data-threshold="10" data-header-height="{{ $headerHeight }}" data-logo-height="{{ $logoHeight }}" data-content-gap="{{ $effectiveContentGap }}" style="--nav-bg: {{ $cfg['bg_color'] }}; --nav-bg-scrolled: {{ $cfg['scrolled_mode']==='color' ? ($cfg['scrolled_bg_color'] ?? 'transparent') : 'transparent' }}; --nav-font: {{ $cfg['font_family'] }}; --nav-font-size: {{ (int)$cfg['font_size'] }}px; --nav-weight: {{ $cfg['font_weight'] }}; --nav-style: {{ $fontStyle }}; --nav-item-bg: {{ $cfg['item_bg_mode']==='color' ? ($cfg['item_bg_color'] ?? 'transparent') : 'transparent' }}; --nav-sub-bg: {{ $cfg['sub_bg_mode']==='color' ? ($cfg['sub_bg_color'] ?? 'transparent') : 'transparent' }}; --nav-link: {{ $cfg['link_color'] }}; --nav-link-hover: {{ $cfg['link_color_hover'] }}; --nav-height: {{ $headerHeight }}px; --nav-logo-height: {{ $logoHeight }}px;">
    <div class="container r4-nav__container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}" aria-label="{{ $brandAlt }}">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brandAlt }}" class="align-text-top d-inline-block" style="height:var(--nav-logo-height,28px);">
            @else
                <span>{{ $brandAlt }}</span>
            @endif
            @if($logoDarkUrl)
                <img src="{{ $logoDarkUrl }}" alt="{{ $brandAlt }}" class="align-text-top d-inline-block d-none" data-brand="dark" style="height:var(--nav-logo-height,28px);">
            @endif
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="{{ $isEnhancedMobile ? 'offcanvas' : 'collapse' }}" data-bs-target="{{ $isEnhancedMobile ? '#mainNavbarOffcanvas' : '#mainNavbar' }}" aria-controls="{{ $isEnhancedMobile ? 'mainNavbarOffcanvas' : 'mainNavbar' }}" aria-expanded="false" aria-label="Apri menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse{{ $isEnhancedMobile ? ' d-none d-lg-flex' : '' }}" id="mainNavbar">
            @if($mainMenu && $mainMenu->items->count())
                <ul class="navbar-nav {{ $alignClass }}" style="font-family:var(--nav-font); font-size:var(--nav-font-size); font-weight:var(--nav-weight); font-style:var(--nav-style); text-align:{{ $cfg['text_align'] ?? 'left' }};">
                    @php $renderMenuItems($mainMenu->items, false); @endphp
                </ul>
            @endif
            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item dropdown"><a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle"></i><span>{{ auth()->user()->name }}</span></a><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>@if(auth()->user()->hasPermission('view.admin'))<li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}"><i class="bi bi-gear-fill"></i><span>Admin</span></a></li>@endif<li><hr class="dropdown-divider"></li><li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button></form></li></ul></li>
                @else
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i><span>Login</span></a></li>
                    @if(Route::has('register'))<li class="nav-item"><a class="nav-link d-flex align-items-center gap-1" href="{{ route('register') }}"><i class="bi bi-pencil-square"></i><span>Registrati</span></a></li>@endif
                @endauth
            </ul>
        </div>
    </div>
</nav>

@if($isEnhancedMobile)
    <div class="offcanvas {{ $mobileMode === 'fullscreen' ? 'offcanvas-top r4-mobile-menu-panel--fullscreen' : 'offcanvas-end' }} r4-mobile-menu-panel" tabindex="-1" id="mainNavbarOffcanvas" aria-labelledby="mainNavbarOffcanvasLabel" style="--nav-bg: {{ $cfg['bg_color'] }}; --nav-link: {{ $cfg['link_color'] }}; --nav-link-hover: {{ $cfg['link_color_hover'] }}; --nav-logo-height: {{ $logoHeight }}px; --nav-font: {{ $cfg['font_family'] }}; --nav-font-size: {{ (int)$cfg['font_size'] }}px; --nav-weight: {{ $cfg['font_weight'] }}; --nav-style: {{ $fontStyle }}; --nav-sub-bg: {{ $cfg['sub_bg_mode']==='color' ? ($cfg['sub_bg_color'] ?? '#ffffff') : 'transparent' }};">
        <div class="offcanvas-header">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}" id="mainNavbarOffcanvasLabel">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brandAlt }}">
                @else
                    <span>{{ $brandAlt }}</span>
                @endif
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Chiudi"></button>
        </div>
        <div class="offcanvas-body">
            @if($mainMenu && $mainMenu->items->count())
                <ul class="navbar-nav" style="font-family:var(--nav-font); font-size:var(--nav-font-size); font-weight:var(--nav-weight); font-style:var(--nav-style);">
                    @php $renderMenuItems($mainMenu->items, true); @endphp
                </ul>
            @endif
        </div>
    </div>
@endif

@if($logoDarkUrl)
    <script>
        (function(){
            const root = document.documentElement;
            const isDark = ()=> (root.getAttribute('data-bs-theme')||'light') === 'dark';
            function sync(){
                document.querySelectorAll('.navbar-brand img[data-brand="dark"]').forEach(function(dark){
                    const light = dark.parentElement.querySelector('img:not([data-brand])');
                    if (isDark()) { dark.classList.remove('d-none'); light?.classList.add('d-none'); }
                    else { dark.classList.add('d-none'); light?.classList.remove('d-none'); }
                });
            }
            sync(); window.addEventListener('themechange', sync);
        })();
    </script>
@endif
