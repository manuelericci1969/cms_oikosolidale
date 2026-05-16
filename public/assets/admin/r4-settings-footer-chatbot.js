(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function isSettingsIndex() {
        return window.location.pathname === '/admin/settings' || window.location.pathname === '/admin/settings/';
    }

    function isFooterBrandPage() {
        return window.location.pathname === '/admin/settings/footer-brand' || window.location.pathname === '/admin/settings/footer-brand/';
    }

    function isCrmCallAutomationPage() {
        return window.location.pathname === '/admin/settings/crm-call-automation' || window.location.pathname === '/admin/settings/crm-call-automation/';
    }

    function isSettingsArea() {
        return isSettingsIndex() || isFooterBrandPage() || isCrmCallAutomationPage();
    }

    function currentTab() {
        return new URLSearchParams(window.location.search).get('tab') || 'branding';
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function tabs() {
        return document.querySelector('.nav.nav-tabs');
    }

    function injectModernStyle() {
        if (!isSettingsArea() || document.getElementById('r4-settings-modern-style')) return;
        var style = document.createElement('style');
        style.id = 'r4-settings-modern-style';
        style.textContent = `
            body.r4-settings-modern{
                background:
                    radial-gradient(circle at top left,rgba(37,99,235,.16),transparent 34rem),
                    radial-gradient(circle at top right,rgba(14,165,233,.11),transparent 30rem),
                    linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%)!important;
            }
            body.r4-settings-modern main.col.p-4{padding:1.35rem!important;}
            body.r4-settings-modern .page-topbar{
                position:relative!important;
                top:auto!important;
                z-index:1!important;
                margin:0!important;
                border:0!important;
                background:transparent!important;
                backdrop-filter:none!important;
            }
            body.r4-settings-modern .page-topbar .container-fluid{padding:0!important;}
            body.r4-settings-modern .page-topbar .d-flex.align-items-center.justify-content-between{
                padding:1.05rem 1.2rem;
                border:1px solid rgba(148,163,184,.22);
                border-radius:1.15rem 1.15rem 0 0;
                background:linear-gradient(135deg,#020617 0%,#0f172a 58%,#1e3a8a 100%);
                box-shadow:0 18px 50px rgba(15,23,42,.18);
                color:#fff;
            }
            body.r4-settings-modern .page-topbar h1,
            body.r4-settings-modern .page-topbar .h4{
                font-size:1.35rem;
                letter-spacing:-.035em;
                font-weight:850;
                color:#fff;
            }
            body.r4-settings-modern .page-topbar i.text-primary{
                width:2.25rem;
                height:2.25rem;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                border-radius:.82rem;
                color:#bfdbfe!important;
                background:rgba(255,255,255,.10);
                border:1px solid rgba(255,255,255,.14);
            }
            body.r4-settings-modern .page-topbar .alert{
                border:0!important;
                border-radius:999px!important;
                box-shadow:none!important;
                font-weight:750;
            }

            body.r4-settings-modern .nav.nav-tabs{
                display:flex!important;
                flex-wrap:nowrap!important;
                gap:0!important;
                padding:0!important;
                margin:0 0 1rem!important;
                min-height:4.05rem;
                border:1px solid rgba(30,41,59,.95)!important;
                border-top:0!important;
                border-radius:0 0 1.15rem 1.15rem!important;
                background:#030712!important;
                box-shadow:0 22px 62px rgba(15,23,42,.18);
                overflow-x:auto!important;
                overflow-y:hidden!important;
                scrollbar-width:thin;
                scrollbar-color:#334155 #020617;
            }
            body.r4-settings-modern .nav.nav-tabs::-webkit-scrollbar{height:8px;}
            body.r4-settings-modern .nav.nav-tabs::-webkit-scrollbar-track{background:#020617;}
            body.r4-settings-modern .nav.nav-tabs::-webkit-scrollbar-thumb{background:#334155;border-radius:999px;}
            body.r4-settings-modern .nav.nav-tabs .nav-item{
                flex:0 0 auto;
                min-width:7.1rem;
                border-right:1px solid rgba(148,163,184,.14);
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link{
                position:relative;
                height:4.05rem;
                min-width:7.1rem;
                border:0!important;
                border-radius:0!important;
                padding:.52rem .85rem .62rem!important;
                color:#cbd5e1!important;
                font-size:.76rem!important;
                line-height:1.12;
                font-weight:850!important;
                letter-spacing:.01em;
                background:transparent!important;
                white-space:nowrap;
                display:flex!important;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                gap:.35rem!important;
                text-align:center;
                text-transform:none;
                transition:background .16s ease,color .16s ease,box-shadow .16s ease,transform .16s ease;
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link i{
                color:#93c5fd!important;
                opacity:.95;
                font-size:1.02rem;
                line-height:1;
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link::after{
                content:'';
                position:absolute;
                left:0;
                right:0;
                bottom:0;
                height:3px;
                background:transparent;
                transition:background .16s ease,box-shadow .16s ease;
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link:hover{
                background:#0f172a!important;
                color:#fff!important;
                transform:none!important;
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link:hover i{color:#e0f2fe!important;}
            body.r4-settings-modern .nav.nav-tabs .nav-link.active{
                background:linear-gradient(180deg,#111827 0%,#020617 100%)!important;
                color:#fff!important;
                box-shadow:inset 0 1px 0 rgba(255,255,255,.06)!important;
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link.active::after{
                background:#0ea5e9;
                box-shadow:0 -8px 22px rgba(14,165,233,.42);
            }
            body.r4-settings-modern .nav.nav-tabs .nav-link.active i{color:#38bdf8!important;opacity:1;}

            body.r4-settings-modern form.card,
            body.r4-settings-modern .card-soft,
            body.r4-settings-modern .card,
            body.r4-settings-modern .r4-card{
                border:1px solid rgba(148,163,184,.24)!important;
                border-radius:1.25rem!important;
                background:rgba(255,255,255,.96)!important;
                box-shadow:0 18px 54px rgba(15,23,42,.075)!important;
                overflow:hidden;
            }
            body.r4-settings-modern .card-soft.p-3,
            body.r4-settings-modern .card.p-3,
            body.r4-settings-modern form.card.p-3{padding:1.15rem!important;}
            body.r4-settings-modern form.card.card-soft::before,
            body.r4-settings-modern form.card:not(#r4ChatbotSettingsPanel)::before{
                content:'';
                display:block;
                height:4px;
                margin:-1.15rem -1.15rem 1.15rem;
                background:linear-gradient(90deg,#2563eb 0%,#0ea5e9 45%,#22c55e 100%);
            }

            body.r4-settings-modern .row.g-3 > [class*='col-'],
            body.r4-settings-modern .row.g-4 > [class*='col-']{position:relative;}
            body.r4-settings-modern .form-label{
                font-size:.86rem;
                font-weight:780;
                color:#172033;
                margin-bottom:.38rem;
                letter-spacing:-.01em;
            }
            body.r4-settings-modern .form-control,
            body.r4-settings-modern .form-select,
            body.r4-settings-modern .input-group-text{
                border-color:#dbe3ee!important;
                border-radius:.85rem!important;
                min-height:2.62rem;
                background-color:#fff;
            }
            body.r4-settings-modern textarea.form-control{border-radius:1rem!important;line-height:1.48;}
            body.r4-settings-modern .input-group>.form-control:not(:first-child),
            body.r4-settings-modern .input-group>.form-select:not(:first-child){border-top-left-radius:0!important;border-bottom-left-radius:0!important;}
            body.r4-settings-modern .input-group>.input-group-text:not(:last-child){border-top-right-radius:0!important;border-bottom-right-radius:0!important;}
            body.r4-settings-modern .form-control:focus,
            body.r4-settings-modern .form-select:focus{
                border-color:#2563eb!important;
                box-shadow:0 0 0 .22rem rgba(37,99,235,.12)!important;
            }
            body.r4-settings-modern .form-hint,
            body.r4-settings-modern .form-text,
            body.r4-settings-modern .text-muted{color:#64748b!important;}
            body.r4-settings-modern code{
                background:#f1f5f9;
                border:1px solid #e2e8f0;
                border-radius:.5rem;
                padding:.13rem .38rem;
                color:#1e293b;
            }

            body.r4-settings-modern .form-check-input{cursor:pointer;}
            body.r4-settings-modern .form-switch .form-check-input{
                width:3rem;
                height:1.55rem;
                border-color:#cbd5e1;
            }
            body.r4-settings-modern .form-check-input:checked{
                background-color:#2563eb;
                border-color:#2563eb;
            }
            body.r4-settings-modern .form-check-label{font-weight:760;color:#172033;}

            body.r4-settings-modern .media-preview,
            body.r4-settings-modern .theme-preview{
                border:1px solid rgba(148,163,184,.35)!important;
                border-radius:1rem!important;
                background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%)!important;
                box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
            }
            body.r4-settings-modern .theme-preview .tp-header{background:linear-gradient(135deg,var(--preview-primary,#2563eb),#1d4ed8)!important;}
            body.r4-settings-modern .theme-preview .tp-btn{font-weight:760;border-radius:999px!important;}

            body.r4-settings-modern .btn{
                border-radius:.85rem!important;
                font-weight:760;
                letter-spacing:-.01em;
            }
            body.r4-settings-modern .btn-primary{
                background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%)!important;
                border-color:#2563eb!important;
                box-shadow:0 12px 24px rgba(37,99,235,.18)!important;
            }
            body.r4-settings-modern .btn-outline-secondary{
                border-color:#cbd5e1!important;
                color:#475569!important;
                background:#fff!important;
            }
            body.r4-settings-modern .btn-outline-secondary:hover{background:#f1f5f9!important;color:#0f172a!important;}
            body.r4-settings-modern .btn-outline-danger{border-color:#fecaca!important;color:#b91c1c!important;background:#fff!important;}
            body.r4-settings-modern .btn-outline-danger:hover{background:#fee2e2!important;}

            body.r4-settings-modern .alert{
                border-radius:1rem!important;
                border-width:1px!important;
                box-shadow:0 12px 32px rgba(15,23,42,.055);
            }
            body.r4-settings-modern .alert-info{border-color:#b8edf8;background:linear-gradient(135deg,#ecfeff 0%,#cffafe 100%);color:#0f5563;}
            body.r4-settings-modern .alert-success{border-color:#bbf7d0;background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);color:#166534;}
            body.r4-settings-modern .alert-warning{border-color:#fde68a;background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);color:#92400e;}
            body.r4-settings-modern .alert-danger{border-color:#fecaca;background:linear-gradient(135deg,#fff1f2 0%,#fee2e2 100%);color:#991b1b;}

            body.r4-settings-modern .card-header,
            body.r4-settings-modern .card-footer{
                border-color:#e2e8f0!important;
                background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%)!important;
            }

            body.r4-settings-modern #r4ChatbotSettingsPanel{
                border:1px solid rgba(148,163,184,.24)!important;
                border-radius:1.25rem!important;
                background:rgba(255,255,255,.96)!important;
                box-shadow:0 18px 54px rgba(15,23,42,.075)!important;
            }
            body.r4-settings-modern #r4ChatbotSettingsPanel::before{
                content:'';
                display:block;
                height:4px;
                margin:-1.15rem -1.15rem 1.15rem;
                background:linear-gradient(90deg,#2563eb 0%,#0ea5e9 45%,#22c55e 100%);
            }

            @media (max-width: 767.98px){
                body.r4-settings-modern main.col.p-4{padding:.85rem!important;}
                body.r4-settings-modern .page-topbar .d-flex.align-items-center.justify-content-between{padding:1rem;border-radius:1.05rem 1.05rem 0 0;}
                body.r4-settings-modern .nav.nav-tabs{border-radius:0 0 1.05rem 1.05rem;min-height:3.8rem;}
                body.r4-settings-modern .nav.nav-tabs .nav-item{min-width:6.4rem;}
                body.r4-settings-modern .nav.nav-tabs .nav-link{height:3.8rem;min-width:6.4rem;padding:.48rem .68rem!important;font-size:.72rem!important;}
            }
        `;
        document.head.appendChild(style);
        document.body.classList.add('r4-settings-modern');
    }

    function addSettingsLink(href, key, icon, label, beforePattern) {
        var nav = tabs();
        if (!nav || nav.querySelector('[data-r4-settings-link="' + key + '"]')) return;
        var active = window.location.pathname === href || (key === 'chatbot' && currentTab() === 'chatbot');
        var li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = '<a class="nav-link ' + (active ? 'active' : '') + '" data-r4-settings-link="' + key + '" href="' + href + '"><i class="bi ' + icon + '"></i> ' + label + '</a>';
        var before = Array.prototype.slice.call(nav.querySelectorAll('a')).find(function (a) {
            return beforePattern && beforePattern.test(a.getAttribute('href') || '');
        });
        if (before && before.parentElement) nav.insertBefore(li, before.parentElement);
        else nav.appendChild(li);
    }

    function addExtraTabs() {
        if (!isSettingsIndex()) return;
        addSettingsLink('/admin/settings/crm-call-automation', 'crm-call-automation', 'bi-telephone-outbound', 'Automazioni chiamate', /tab=seo/);
        addSettingsLink('?tab=chatbot', 'chatbot', 'bi-chat-dots', 'ChatBot', /tab=calendar/);
        addSettingsLink('/admin/settings/footer-brand', 'footer-brand', 'bi-window-dock', 'Footer Brand', /tab=calendar/);
    }

    function hideOtherPanels() {
        if (currentTab() !== 'chatbot') return;
        var nav = tabs();
        if (!nav) return;
        var node = nav.nextElementSibling;
        while (node) {
            if (node.id !== 'r4ChatbotSettingsPanel') node.style.display = 'none';
            node = node.nextElementSibling;
        }
    }

    function renderChatbotPanel(enabled) {
        var nav = tabs();
        if (!nav || document.getElementById('r4ChatbotSettingsPanel')) return;
        var panel = document.createElement('form');
        panel.id = 'r4ChatbotSettingsPanel';
        panel.method = 'POST';
        panel.action = '/admin/settings/chatbot';
        panel.className = 'card card-soft p-3';
        panel.innerHTML = '' +
            '<input type="hidden" name="_token" value="' + csrf() + '">' +
            '<input type="hidden" name="_method" value="PUT">' +
            '<div class="row g-3 align-items-start">' +
                '<div class="col-md-6"><div class="form-check form-switch">' +
                    '<input type="hidden" name="chatbot_enabled" value="0">' +
                    '<input class="form-check-input" type="checkbox" role="switch" id="chatbot_enabled" name="chatbot_enabled" value="1" ' + (enabled ? 'checked' : '') + '>' +
                    '<label class="form-check-label fw-semibold" for="chatbot_enabled">Mostra ChatBot sul sito pubblico</label>' +
                '</div><div class="form-hint mt-1">Se disattivato, il widget ChatBot non verrà caricato nelle pagine pubbliche del sito.</div></div>' +
                '<div class="col-md-6"><div class="alert alert-info mb-0"><div class="fw-semibold mb-1">Stato attuale</div>' +
                    (enabled ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> ChatBot visibile</span>' : '<span class="badge text-bg-secondary"><i class="bi bi-eye-slash me-1"></i> ChatBot nascosto</span>') +
                    '<div class="small mt-2">Questa impostazione agisce solo sul frontend pubblico.</div></div></div>' +
            '</div><div class="mt-3 d-flex justify-content-end gap-2">' +
                '<a href="?tab=chatbot" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Annulla</a>' +
                '<button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva impostazioni ChatBot</button>' +
            '</div>';
        nav.insertAdjacentElement('afterend', panel);
    }

    function loadChatbotPanel() {
        if (!isSettingsIndex() || currentTab() !== 'chatbot') return;
        hideOtherPanels();
        fetch('/admin/settings/chatbot/status', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : { enabled: false }; })
            .then(function (json) { renderChatbotPanel(!!json.enabled); })
            .catch(function () { renderChatbotPanel(false); });
    }

    ready(function () {
        if (!isSettingsArea()) return;
        injectModernStyle();
        addExtraTabs();
        loadChatbotPanel();
    });
})();
