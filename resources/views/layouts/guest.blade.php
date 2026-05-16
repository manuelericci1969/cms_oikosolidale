{{-- resources/views/layouts/guest.blade.php --}}
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">

    <title>{{ $title ?? config('app.name', 'R4Software') }}</title>

    @isset($head)
        {{ $head }}
    @endisset

    {{-- Bootstrap 5 - no Vite --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --r4-primary: #0d6efd;
            --r4-dark: #101828;
            --r4-muted: #667085;
            --r4-border: #e4e7ec;
            --r4-bg: #f5f7fb;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            font-family: "Figtree", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, .14), transparent 34%),
                linear-gradient(135deg, #f7f9fc 0%, #eef3f9 100%);
            color: var(--r4-dark);
        }

        .auth-shell {
            min-height: 100vh;
        }

        .auth-page-row {
            min-height: 100vh;
            align-items: flex-start;
        }

        .auth-brand-column {
            align-items: flex-start;
        }

        .auth-content-column {
            align-items: flex-start;
            justify-content: center;
        }

        .auth-brand-panel {
            background:
                linear-gradient(135deg, rgba(13, 110, 253, .96), rgba(9, 44, 88, .98)),
                url("data:image/svg+xml,%3Csvg width='120' height='120' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='rgba(255,255,255,0.12)' stroke-width='1'%3E%3Cpath d='M0 60h120M60 0v120'/%3E%3Ccircle cx='60' cy='60' r='36'/%3E%3C/g%3E%3C/svg%3E");
            color: #fff;
            border-radius: 28px;
            min-height: 620px;
            overflow: hidden;
            position: sticky;
            top: 32px;
        }

        .auth-brand-panel::after {
            content: "";
            position: absolute;
            right: -80px;
            bottom: -80px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .10);
        }

        .auth-logo {
            max-width: 150px;
            max-height: 72px;
            object-fit: contain;
        }

        .auth-logo-fallback {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #fff;
            color: var(--r4-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.35rem;
            box-shadow: 0 16px 35px rgba(16, 24, 40, .12);
        }

        .auth-card {
            border: 1px solid var(--r4-border);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(16, 24, 40, .10);
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(10px);
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border-color: #d0d5dd;
            padding: .72rem .9rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--r4-primary);
            box-shadow: 0 0 0 .22rem rgba(13, 110, 253, .14);
        }

        .btn {
            border-radius: 12px;
            padding: .72rem 1rem;
            font-weight: 600;
        }

        .btn-primary {
            box-shadow: 0 10px 25px rgba(13, 110, 253, .24);
        }

        .auth-link {
            color: var(--r4-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .auth-muted {
            color: var(--r4-muted);
        }

        .feature-pill {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .55rem .8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .13);
            color: rgba(255, 255, 255, .95);
            font-size: .92rem;
        }

        @media (max-width: 991.98px) {
            .auth-page-row {
                min-height: auto;
            }

            .auth-brand-panel {
                position: relative;
                top: auto;
                min-height: auto;
                border-radius: 0 0 28px 28px;
            }

            .auth-content-column {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
@php
    $brandingLogoUrl = null;

    try {
        if (class_exists(\App\Models\Setting::class) && class_exists(\App\Models\Media::class)) {
            $brandingLogoId = \App\Models\Setting::get('branding.logo_id');
            $brandingLogo = $brandingLogoId ? \App\Models\Media::find((int) $brandingLogoId) : null;

            if ($brandingLogo) {
                $brandingLogoUrl = $brandingLogo->variantUrl('thumb') ?? $brandingLogo->url ?? null;
            }
        }
    } catch (\Throwable $e) {
        $brandingLogoUrl = null;
    }

    $appName = config('app.name', 'R4Software');
@endphp

<div class="container-fluid auth-shell">
    <div class="row auth-page-row">
        <div class="col-lg-5 d-none d-lg-flex auth-brand-column p-4 p-xl-5">
            <div class="auth-brand-panel w-100 p-5 d-flex flex-column justify-content-between">
                <div class="position-relative z-1">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center text-white text-decoration-none mb-5">
                        @if($brandingLogoUrl)
                            <img src="{{ $brandingLogoUrl }}" alt="{{ $appName }}" class="auth-logo bg-white rounded-4 p-2">
                        @else
                            <span class="auth-logo-fallback">R4</span>
                        @endif
                    </a>

                    <div class="mb-4">
                        <span class="feature-pill mb-3">
                            <i class="bi bi-lightning-charge-fill"></i>
                            Piattaforma digitale R4Software
                        </span>

                        <h1 class="display-6 fw-bold mb-3">
                            R4Software
                        </h1>

                        <p class="fs-5 mb-0 opacity-75">
                            CMS, CRM e strumenti digitali per gestire contenuti, clienti, preventivi, contratti e servizi web.
                        </p>
                    </div>
                </div>

                <div class="position-relative z-1">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-person-badge fs-4"></i>
                                <div>
                                    <div class="fw-semibold">Gestione CMS</div>
                                    <div class="small opacity-75">Crea e aggiorna pagine, contenuti, media e sezioni visuali.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-building-check fs-4"></i>
                                <div>
                                    <div class="fw-semibold">CRM integrato</div>
                                    <div class="small opacity-75">Gestisci clienti, preventivi, contratti, attività e comunicazioni.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-shield-check fs-4"></i>
                                <div>
                                    <div class="fw-semibold">Strumenti professionali</div>
                                    <div class="small opacity-75">Editor visuale, moduli, media library, SEO e automazioni operative.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 d-flex auth-content-column p-4 p-md-5">
            <main class="w-100" style="max-width: 560px;">
                <div class="text-center d-lg-none mb-4">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center text-decoration-none">
                        @if($brandingLogoUrl)
                            <img src="{{ $brandingLogoUrl }}" alt="{{ $appName }}" class="auth-logo">
                        @else
                            <span class="auth-logo-fallback">R4</span>
                        @endif
                    </a>
                </div>

                <div class="auth-card p-4 p-md-5">
                    {{ $slot }}
                </div>

                <div class="text-center small auth-muted mt-4 mb-4">
                    © {{ date('Y') }} {{ $appName }}. Tutti i diritti riservati.
                </div>
            </main>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
</body>
</html>
