@php
    $cookieEnabled = (bool) setting('legal.cookie_enabled', true);

    $msg       = trim((string) setting('legal.cookie_message',
        'Utilizziamo cookie tecnici e, previo consenso, cookie di terze parti per migliorare l’esperienza di navigazione.'
    ));
    $btnLabel  = trim((string) setting('legal.cookie_button', 'Accetta'));
    $linkLabel = trim((string) setting('legal.cookie_link_label', 'Leggi l’informativa'));

    // URL privacy
    if (\Illuminate\Support\Facades\Route::has('policy.privacy')) {
        $privacyUrl = route('policy.privacy');
    } else {
        $privacyUrl = trim((string) setting('legal.privacy_url', '/privacy-policy'));
    }
@endphp

@if($cookieEnabled)
    <div id="cookieBanner"
         class="cookie-banner position-fixed bottom-0 start-0 end-0 cookie-hidden">
        <div class="container py-3">
            <div class="card shadow-lg border-0">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="flex-grow-1 small text-body">
                        <strong>Cookie</strong><br>
                        {{ $msg }}
                        @if($privacyUrl)
                            <a href="{{ $privacyUrl }}"
                               class="link-underline link-underline-opacity-0 link-underline-opacity-75-hover ms-1"
                               target="_blank" rel="noopener">
                                {{ $linkLabel }}
                            </a>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-cookie-accept>
                            {{ $btnLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <style>
            .cookie-banner {
                z-index: 1080;
                transition: transform .4s ease, opacity .4s ease;
            }
            .cookie-banner.cookie-hidden {
                transform: translateY(100%);
                opacity: 0;
            }
            .cookie-banner.cookie-visible {
                transform: translateY(0);
                opacity: 1;
            }
        </style>

        <script>
            (function () {
                const COOKIE_NAME = 'cookie_consent';
                const COOKIE_VALUE = '1';
                const COOKIE_DAYS = 365;
                const prefersReduced = window.matchMedia &&
                    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                function hasConsent() {
                    return document.cookie.split(';').some(function (c) {
                        return c.trim().startsWith(COOKIE_NAME + '=');
                    });
                }

                function setConsent() {
                    const d = new Date();
                    d.setTime(d.getTime() + (COOKIE_DAYS * 24 * 60 * 60 * 1000));
                    document.cookie = COOKIE_NAME + '=' + COOKIE_VALUE +
                        ';expires=' + d.toUTCString() + ';path=/';
                }

                function showBanner(banner) {
                    if (!banner) return;
                    banner.classList.remove('cookie-hidden');
                    banner.classList.add('cookie-visible');
                }

                function hideBanner(banner) {
                    if (!banner) return;
                    if (prefersReduced) {
                        banner.classList.add('cookie-hidden');
                        banner.classList.remove('cookie-visible');
                        return;
                    }
                    banner.classList.remove('cookie-visible');
                    banner.classList.add('cookie-hidden');
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var banner = document.getElementById('cookieBanner');
                    if (!banner) return;

                    // Se il consenso esiste già -> non mostrare
                    if (hasConsent()) {
                        hideBanner(banner);
                        return;
                    }

                    // Mostra banner
                    showBanner(banner);

                    var btnAccept = banner.querySelector('[data-cookie-accept]');
                    if (btnAccept) {
                        btnAccept.addEventListener('click', function () {
                            setConsent();
                            hideBanner(banner);
                        });
                    }
                });
            })();
        </script>
    @endonce
@endif
