(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function injectStyle() {
        if (document.getElementById('r4-settings-modern-style')) return;

        var style = document.createElement('style');
        style.id = 'r4-settings-modern-style';
        style.textContent = `
            body {
                background:
                    radial-gradient(circle at top left, rgba(13,110,253,.08), transparent 34rem),
                    linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%) !important;
            }

            .page-topbar {
                position: relative !important;
                top: auto !important;
                z-index: 1 !important;
                margin: 0 0 1rem !important;
                border: 0 !important;
                background: transparent !important;
                backdrop-filter: none !important;
            }

            .page-topbar .container-fluid {
                padding: 0 !important;
            }

            .page-topbar .d-flex.align-items-center.justify-content-between {
                padding: 1.15rem 1.25rem;
                border: 1px solid rgba(148,163,184,.26);
                border-radius: 1.25rem;
                background: rgba(255,255,255,.86);
                box-shadow: 0 18px 48px rgba(15,23,42,.07);
            }

            .page-topbar h1,
            .page-topbar .h4 {
                font-size: 1.35rem;
                letter-spacing: -.025em;
                font-weight: 850;
                color: #0f172a;
            }

            .page-topbar i.text-primary {
                width: 2.25rem;
                height: 2.25rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: .9rem;
                background: rgba(13,110,253,.10);
            }

            body.r4-settings-modern .nav.nav-tabs {
                gap: .45rem;
                padding: .55rem;
                margin-bottom: 1rem !important;
                border: 1px solid rgba(148,163,184,.25);
                border-radius: 1.25rem;
                background: rgba(255,255,255,.76);
                box-shadow: 0 16px 44px rgba(15,23,42,.06);
                overflow-x: auto;
                flex-wrap: nowrap;
                scrollbar-width: thin;
            }

            body.r4-settings-modern .nav.nav-tabs .nav-item {
                flex: 0 0 auto;
            }

            body.r4-settings-modern .nav.nav-tabs .nav-link {
                border: 0 !important;
                border-radius: 999px !important;
                padding: .62rem .95rem;
                color: #475569;
                font-size: .92rem;
                font-weight: 700;
                background: transparent;
                white-space: nowrap;
                transition: background .16s ease, color .16s ease, box-shadow .16s ease, transform .16s ease;
            }

            body.r4-settings-modern .nav.nav-tabs .nav-link i {
                color: #0d6efd;
                opacity: .85;
            }

            body.r4-settings-modern .nav.nav-tabs .nav-link:hover {
                background: #eef5ff;
                color: #0f172a;
                transform: translateY(-1px);
            }

            body.r4-settings-modern .nav.nav-tabs .nav-link.active {
                background: linear-gradient(135deg, #0d6efd 0%, #2563eb 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 14px 28px rgba(13,110,253,.24);
            }

            body.r4-settings-modern .nav.nav-tabs .nav-link.active i {
                color: #ffffff;
                opacity: 1;
            }

            body.r4-settings-modern .card-soft,
            body.r4-settings-modern .card {
                border: 1px solid rgba(148,163,184,.24) !important;
                border-radius: 1.25rem !important;
                background: rgba(255,255,255,.92) !important;
                box-shadow: 0 18px 54px rgba(15,23,42,.075) !important;
            }

            body.r4-settings-modern .card-soft.p-3,
            body.r4-settings-modern .card.p-3 {
                padding: 1.1rem !important;
            }

            body.r4-settings-modern .form-label {
                font-size: .86rem;
                font-weight: 760;
                color: #172033;
                margin-bottom: .35rem;
            }

            body.r4-settings-modern .form-control,
            body.r4-settings-modern .form-select,
            body.r4-settings-modern .input-group-text {
                border-color: #dbe3ee;
                border-radius: .8rem;
                min-height: 2.55rem;
            }

            body.r4-settings-modern .input-group > .form-control:not(:first-child),
            body.r4-settings-modern .input-group > .form-select:not(:first-child) {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }

            body.r4-settings-modern .input-group > .input-group-text:not(:last-child) {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }

            body.r4-settings-modern .form-control:focus,
            body.r4-settings-modern .form-select:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 0 .22rem rgba(13,110,253,.12);
            }

            body.r4-settings-modern .media-preview,
            body.r4-settings-modern .theme-preview {
                border-radius: 1rem !important;
                border-color: rgba(148,163,184,.34) !important;
                background: linear-gradient(180deg,#ffffff 0%,#f8fafc 100%) !important;
            }

            body.r4-settings-modern .btn {
                border-radius: .82rem;
                font-weight: 720;
            }

            body.r4-settings-modern .btn-primary {
                background: linear-gradient(135deg, #0d6efd 0%, #2563eb 100%);
                border-color: #0d6efd;
                box-shadow: 0 12px 24px rgba(13,110,253,.18);
            }

            body.r4-settings-modern .btn-outline-secondary,
            body.r4-settings-modern .btn-outline-danger {
                background: #ffffff;
            }

            body.r4-settings-modern textarea.form-control {
                min-height: 7rem;
            }

            .r4-settings-footer-link .nav-link {
                background: rgba(15,23,42,.04) !important;
            }

            .r4-settings-footer-link .nav-link:hover {
                background: #eef5ff !important;
            }

            @media (max-width: 768px) {
                .page-topbar .d-flex.align-items-center.justify-content-between {
                    padding: 1rem;
                    border-radius: 1rem;
                }
                body.r4-settings-modern .nav.nav-tabs {
                    border-radius: 1rem;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function enhanceSettingsTabs() {
        var tabs = document.querySelector('.nav.nav-tabs');
        if (!tabs) return;

        document.body.classList.add('r4-settings-modern');

        var exists = tabs.querySelector('[data-r4-settings-footer-brand]');
        if (exists) return;

        var li = document.createElement('li');
        li.className = 'nav-item r4-settings-footer-link';
        li.innerHTML = '<a class="nav-link" data-r4-settings-footer-brand="1" href="/admin/settings/footer-brand"><i class="bi bi-window-dock"></i> Footer Brand</a>';
        tabs.appendChild(li);
    }

    function enhanceFooterBrandPage() {
        if (!/\/admin\/settings\/footer-brand/.test(window.location.pathname)) return;
        document.body.classList.add('r4-settings-modern');
    }

    ready(function () {
        if (!/\/admin\/settings/.test(window.location.pathname)) return;
        injectStyle();
        enhanceSettingsTabs();
        enhanceFooterBrandPage();
    });
})();
