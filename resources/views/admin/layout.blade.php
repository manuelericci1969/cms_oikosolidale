<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title','Admin')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        // Endpoints usati dal media picker in admin.js dei plugin
        window.R4ADMIN = {
            mediaPickerUrl:  @json(route('admin.media.picker')),
            mediaBrowseUrl:  @json(route('admin.media.browse')),
            mediaUploadUrl:  @json(route('admin.media.store'))
        };
    </script>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons (una volta sola) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    {{-- Webfont base: Inter (usato come default body) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet"
          href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">

    @php
        /** @var \App\Services\PluginManager $pm */
        $pm = app(\App\Services\PluginManager::class);

        try {
            $admAssets = $pm->adminAssets();
        } catch (\Throwable $e) {
            // fallback in caso di errore nel manager
            $admAssets = ['css' => [], 'js' => []];
        }

        // Normalizza SEMPRE a array
        $cssAssets = (is_array($admAssets) && isset($admAssets['css']) && is_array($admAssets['css'])) ? $admAssets['css'] : [];
        $jsAssets  = (is_array($admAssets) && isset($admAssets['js'])  && is_array($admAssets['js']))  ? $admAssets['js']  : [];

        // === Tipografia globale ==========================================
        $getSetting = fn(string $key, $default = null) => \App\Models\Setting::get($key, $default);

        $typo = [
            'body_family'    => $getSetting('typography.body_family', 'Inter'),
            'heading_family' => $getSetting('typography.heading_family', 'Inter'),
            'title_family'   => $getSetting('typography.title_family', ''),
            'body_weight'    => $getSetting('typography.body_weight', '400'),
            'heading_weight' => $getSetting('typography.heading_weight', '700'),
            'title_weight'   => $getSetting('typography.title_weight', '700'),
        ];

        // Restituisce una font-stack CSS completa per il nome scelto
        $stackFor = function (?string $font): string {
            $font = trim((string) $font);

            $map = [
                'Arial'          => 'Arial, Helvetica, sans-serif',
                'Verdana'        => 'Verdana, Geneva, sans-serif',
                'Times New Roman'=> '\'Times New Roman\', Times, serif',
                'Georgia'        => 'Georgia, \'Times New Roman\', Times, serif',
                'Tahoma'         => 'Tahoma, Geneva, sans-serif',
                'Trebuchet MS'   => '\'Trebuchet MS\', Helvetica, sans-serif',
                'Courier New'    => '\'Courier New\', Courier, monospace',
            ];

            if ($font === '') {
                return 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif';
            }

            if (isset($map[$font])) {
                return $map[$font];
            }

            // Qualsiasi webfont (Inter, Poppins, ecc.)
            return "'" . $font . "', system-ui, -apple-system, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, \"Noto Sans\", sans-serif";
        };
    @endphp

    {{-- CSS dei plugin (front) --}}
    @foreach($cssAssets as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach

    {{-- CSS custom delle pagine (opzionale) --}}
    @stack('styles')

    <style>
        :root{
            --font-body: {!! $stackFor($typo['body_family']) !!};
            --font-heading: {!! $stackFor($typo['heading_family']) !!};
            --font-title: {!! $stackFor($typo['title_family'] ?: $typo['heading_family']) !!};

            /* Palette sidebar */
            --sidebar-bg: #020617;                /* sfondo principale (molto scuro)           */
            --sidebar-border: #0f172a;            /* bordo destro                              */
            --sidebar-text: #e5e7eb;              /* testo di base                             */
            --sidebar-muted: #9ca3af;             /* testo attenuato                           */
            --sidebar-muted-soft: #6b7280;        /* testo attenuato più scuro                 */
            --sidebar-link: #cbd5f5;              /* link non selezionati                      */
            --sidebar-hover-bg: #111827;          /* background in hover                       */
            --sidebar-hover-text: #f9fafb;        /* testo in hover                            */
            --sidebar-accent: #0ea5e9;            /* colore di accento                         */
            --sidebar-accent-soft: rgba(56,189,248,.16); /* bg link attivo                     */
            --sidebar-danger: #fb7185;            /* logout / azioni pericolose               */
            --sidebar-danger-soft: rgba(248,113,113,.16);
        }

        body {
            min-height: 100vh;
            font-family: var(--font-body);
            font-weight: {{ (int) ($typo['body_weight'] ?? 400) }};
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: {{ (int) ($typo['heading_weight'] ?? 700) }};
        }

        .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 {
            font-family: var(--font-title);
            font-weight: {{ (int) ($typo['title_weight'] ?? 700) }};
        }

        /* ======== SIDEBAR ======================================= */

        .sidebar {
            width: 260px;
        }

        /* colonna sinistra */
        aside.sidebar {
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            border-right: 1px solid var(--sidebar-border);
        }

        /* contenitore interno del menu */
        .sidebar-inner {
            padding: .75rem .65rem 1rem .65rem;
            font-size: .82rem;
        }

        .sidebar-inner a,
        .sidebar-inner button {
            font-family: inherit;
        }

        /* BLOCCO SEZIONE (contenitore) --------------------------- */
        .sidebar-inner .sidebar-section {
            margin-bottom: .4rem;
            padding: .15rem .15rem .25rem .15rem;
            border-radius: .75rem;
            transition:
                background-color .15s ease,
                box-shadow .15s ease;
        }

        .sidebar-inner .sidebar-section:hover {
            background-color: rgba(15,23,42,.9);
        }

        .sidebar-inner .sidebar-section.is-open {
            background-color: rgba(15,23,42,.95);
            box-shadow: 0 0 0 1px rgba(15,23,42,1);
        }

        /* HEADER SEZIONE (toggle) ------------------------------- */
        .sidebar-inner .sidebar-section-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            color: var(--sidebar-muted-soft);
            font-size: .68rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: .3rem .55rem;
            margin: 0 0 .15rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .4rem;
            cursor: pointer;
            border-radius: .55rem;
            outline: none;
        }

        .sidebar-inner .sidebar-section-toggle .label {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .sidebar-inner .sidebar-section-toggle .label i {
            font-size: .9rem;
            color: var(--sidebar-muted);
        }

        .sidebar-inner .sidebar-section-toggle .chevron {
            font-size: .8rem;
            opacity: .7;
            transition: transform .15s ease, opacity .15s ease;
        }

        .sidebar-inner .sidebar-section-toggle:hover {
            background-color: rgba(15,23,42,.8);
            color: var(--sidebar-text);
        }

        .sidebar-inner .sidebar-section-toggle:focus-visible {
            outline: 2px solid var(--sidebar-accent);
            outline-offset: 2px;
        }

        .sidebar-inner .sidebar-section.is-open .sidebar-section-toggle {
            color: var(--sidebar-text);
            font-weight: 600;
        }

        .sidebar-inner .sidebar-section.is-open .sidebar-section-toggle .label i {
            color: var(--sidebar-accent);
        }

        .sidebar-inner .sidebar-section.is-open .sidebar-section-toggle .chevron {
            transform: rotate(180deg);
            opacity: 1;
        }

        .sidebar-inner .sidebar-section-body {
            display: none;
            padding: .1rem .1rem .35rem .1rem;
        }

        .sidebar-inner .sidebar-section.is-open .sidebar-section-body {
            display: block;
        }

        /* LINK DELLA SIDEBAR ------------------------------------- */

        /* Stato base (non selezionato) */
        .sidebar-inner .nav-link {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .3rem .6rem;
            border-radius: .5rem;
            color: var(--sidebar-link);
            text-decoration: none;
            font-size: .82rem;
            opacity: .95;
            background-color: transparent;
            transition:
                background-color .12s ease,
                color .12s ease,
                opacity .12s ease,
                transform .06s ease,
                box-shadow .12s ease;
        }

        .sidebar-inner .nav-link i {
            width: 1.1rem;
            text-align: center;
            font-size: .9rem;
            color: var(--sidebar-muted);
            opacity: .95;
            flex-shrink: 0;
        }

        /* Hover */
        .sidebar-inner .nav-link:hover {
            background-color: var(--sidebar-hover-bg);
            color: var(--sidebar-hover-text);
            opacity: 1;
            transform: translateX(2px);
            box-shadow: 0 0 0 1px rgba(15,23,42,1);
        }

        .sidebar-inner .nav-link:hover i {
            color: var(--sidebar-hover-text);
        }

        /* Attivo / pagina corrente */
        .sidebar-inner .nav-link.active {
            background-color: var(--sidebar-accent-soft);
            color: var(--sidebar-hover-text);
            opacity: 1;
            font-weight: 500;
            box-shadow:
                inset 2px 0 0 0 var(--sidebar-accent),
                0 0 0 1px rgba(15,23,42,.9);
        }

        .sidebar-inner .nav-link.active i {
            color: var(--sidebar-accent);
            opacity: 1;
        }

        /* Link pericolosi (logout, ecc.) ------------------------- */
        .sidebar-inner .nav-link.text-danger,
        .sidebar-inner .nav-link.text-danger i {
            color: var(--sidebar-danger);
        }

        .sidebar-inner .nav-link.text-danger {
            background-color: transparent;
        }

        .sidebar-inner .nav-link.text-danger:hover {
            background-color: var(--sidebar-danger-soft);
            color: #fecaca;
        }

        .sidebar-inner .nav-link.text-danger:hover i {
            color: #fecaca;
        }

        /* Titoletti interni alle sezioni ------------------------- */
        .sidebar-inner .section-title {
            letter-spacing: .08em;
            font-size: .64rem;
            text-transform: uppercase;
            color: var(--sidebar-muted);
            margin: .4rem .2rem .2rem;
        }

        /* Card laterali nel blocco admin ------------------------- */
        .sidebar-inner .side-card {
            border-radius: .65rem;
            border: 1px solid rgba(148, 163, 184, .4);
            padding: .6rem .7rem;
            background: #020617;
            color: var(--sidebar-text);
        }

        .sidebar-inner .side-card p,
        .sidebar-inner .side-card li {
            font-size: .76rem;
        }

        .sidebar-inner .side-card .text-muted {
            color: var(--sidebar-muted) !important;
        }

        .sidebar-inner .btn-outline-secondary.btn-sm {
            --bs-btn-border-color: rgba(148, 163, 184, .7);
            --bs-btn-color: var(--sidebar-text);
            --bs-btn-hover-bg: rgba(148, 163, 184, .18);
            --bs-btn-hover-color: #f9fafb;
            --bs-btn-hover-border-color: rgba(148, 163, 184, .9);
            font-size: .7rem;
            padding-inline: .55rem;
            padding-block: .25rem;
        }

        /* Icone generali ----------------------------------------- */
        .bi {
            vertical-align: -0.125em;
        }

        .navbar .bi {
            width: 1.05rem;
            text-align: center;
            color: var(--bs-secondary-color);
            opacity: .9;
        }

        .navbar .nav-link.active .bi {
            color: var(--bs-primary);
            opacity: 1;
        }

        /* ======== TOPBAR (uguale alla sidebar) ================== */

        .admin-topbar {
            background-color: var(--sidebar-bg);
            border-bottom: 1px solid var(--sidebar-border);
            color: var(--sidebar-text);
            min-height: 3rem;
        }

        .admin-topbar .navbar-brand {
            color: var(--sidebar-text);
            font-weight: 600;
            font-size: .9rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .admin-topbar .navbar-brand:hover {
            color: var(--sidebar-hover-text);
        }

        .admin-topbar .navbar-brand .bi {
            font-size: .9rem;
            color: var(--sidebar-accent);
        }

        .admin-topbar .topbar-user {
            font-size: .8rem;
            color: var(--sidebar-muted);
        }

        .admin-topbar .btn-topbar {
            --bs-btn-border-color: rgba(148, 163, 184, .7);
            --bs-btn-color: var(--sidebar-text);
            --bs-btn-hover-bg: rgba(56, 189, 248, .18);
            --bs-btn-hover-color: #f9fafb;
            --bs-btn-hover-border-color: rgba(56, 189, 248, .9);
            --bs-btn-font-size: .75rem;

            border-radius: 999px;
            padding-inline: .8rem;
            padding-block: .25rem;
        }

        .admin-topbar .btn-topbar .bi {
            color: var(--sidebar-muted);
            font-size: .9rem;
        }

        .admin-topbar .btn-topbar:hover .bi {
            color: var(--sidebar-hover-text);
        }


        :root{
            --topbar-h: 3rem;
            --sidebar-width: 260px;
        }

        /* transizione contenuto sidebar */
        aside.sidebar .sidebar-inner{
            opacity: 1;
            transition: opacity .12s ease;
        }

        /* Desktop/tablet: layout “a pannelli” controllabile */
        @media (min-width: 768px) {
            .admin-shell{
                display: flex;
                flex-wrap: nowrap;
                min-height: calc(100vh - var(--topbar-h));
            }

            /* Override delle width bootstrap (col-md-3/col-xl-2) */
            .admin-shell > aside.sidebar{
                flex: 0 0 var(--sidebar-width) !important;
                width: var(--sidebar-width) !important;
                max-width: var(--sidebar-width) !important;
                transition: width .18s ease, flex-basis .18s ease, max-width .18s ease;
                will-change: width;
            }

            .admin-shell > main{
                flex: 1 1 auto;
                min-width: 0;
            }

            /* Stato collassato: chiude “verso sinistra” */
            body.sidebar-collapsed .admin-shell > aside.sidebar{
                flex-basis: 0 !important;
                width: 0 !important;
                max-width: 0 !important;
                overflow: hidden;
                border-right: 0 !important;
            }

            body.sidebar-collapsed .admin-shell > aside.sidebar .sidebar-inner{
                opacity: 0;
                pointer-events: none;
            }
        }

        /* Mobile: se collassato la sidebar sparisce */
        @media (max-width: 767.98px){
            body.sidebar-collapsed aside.sidebar{
                display: none;
            }
        }


    </style>
</head>

@php
    $autoCollapseSidebar =
        request()->routeIs('admin.pages.editV2') ||
        request()->routeIs('admin.pages.editV2.*') ||
        request()->routeIs('admin.pages.edit_v5') ||
        request()->routeIs('admin.pages.preview_v5') ||
        \Illuminate\Support\Str::contains(request()->path(), 'editV2') ||
        \Illuminate\Support\Str::contains(request()->path(), 'edit-v5');
@endphp


<body class="bg-light" data-sidebar-auto-collapse="{{ $autoCollapseSidebar ? '1' : '0' }}">


@if ($errors->any())
    <div class="alert alert-danger m-3">
        <div class="fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i> Correggi questi errori:
        </div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<nav class="navbar navbar-expand admin-topbar">
    <div class="container-fluid">
        <button type="button"
                id="sidebarToggle"
                class="btn btn-sm btn-topbar me-2"
                aria-label="Mostra/Nascondi menu"
                aria-pressed="false">
            <i class="bi bi-layout-sidebar"></i>
        </button>
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i>
            <span>R4 Admin</span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="topbar-user">{{ auth()->user()->email ?? '' }}</span>

            <a class="btn btn-sm btn-topbar d-flex align-items-center"
               href="{{ route('home') }}" target="_blank">
                <i class="bi bi-box-arrow-up-right me-1"></i>
                <span>Preview sito web</span>
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row admin-shell">

    {{-- RIMOSSO bg-white/border-end: gestiamo da CSS per avere tema scuro --}}
        <aside class="col-12 col-md-3 col-xl-2 sidebar p-0">
            @include('admin.partials.sidebar')
        </aside>

        <main class="col p-4">
            {{-- Flash messages --}}
            @php
                // Prende prima "success", se non c'è usa "ok"
                $flashSuccess = session('success') ?? session('ok');

                // Se vuoi puoi anche unire messaggi diversi:
                // $messages = [];
                // if (session('ok')) $messages[] = session('ok');
                // if (session('success') && session('success') !== session('ok')) $messages[] = session('success');
            @endphp

            {{--@if($flashSuccess)
                <div class="alert alert-success">
                    {{ $flashSuccess }}
                </div>
            @endif--}}

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif


            @yield('content')
        </main>
    </div>
</div>

{{-- Bootstrap Bundle (con Popper) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- JS dei plugin (front + admin_entry) --}}
@foreach($jsAssets as $src)
    <script src="{{ $src }}"></script>
@endforeach
<script>
    // segnala ai builder/preview che i plugin sono pronti (registry popolato)
    window.dispatchEvent(new Event('plugins:ready'));
</script>

<script src="{{ asset('assets/crm-service-payments-admin.js') }}?v={{ @filemtime(public_path('assets/crm-service-payments-admin.js')) ?: time() }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const btn  = document.getElementById('sidebarToggle');
        const key  = 'r4admin.sidebarCollapsed';

        function setIcon(isCollapsed){
            if (!btn) return;
            const i = btn.querySelector('i');
            if (!i) return;
            i.className = isCollapsed ? 'bi bi-layout-sidebar-inset' : 'bi bi-layout-sidebar';
            btn.setAttribute('aria-pressed', isCollapsed ? 'true' : 'false');
        }

        function setCollapsed(isCollapsed, persist = true){
            body.classList.toggle('sidebar-collapsed', isCollapsed);
            setIcon(isCollapsed);
            if (persist) localStorage.setItem(key, isCollapsed ? '1' : '0');
        }

        // stato salvato
        let collapsed = localStorage.getItem(key) === '1';

        // auto-collapse per editV2 (forza true senza “sporcare” la preferenza globale)
        const auto = body.dataset.sidebarAutoCollapse === '1';
        if (auto) collapsed = true;

        setCollapsed(collapsed, !auto);

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                setCollapsed(!body.classList.contains('sidebar-collapsed'));
            });
        }
    });
</script>


{{-- JS custom delle pagine (opzionale) --}}
@stack('scripts')

    <script src="{{ asset('assets/admin/r4-settings-footer-chatbot.js') }}?v={{ time() }}" defer></script>
</body>
</html>
