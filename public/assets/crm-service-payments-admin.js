document.addEventListener('DOMContentLoaded', function () {
    var editLinkSelector = 'a[href*="/admin/crm/services/"][href$="/edit"]';
    var links = document.querySelectorAll(editLinkSelector);

    links.forEach(function (link) {
        var href = link.getAttribute('href') || '';
        var match = href.match(/\/admin\/crm\/services\/(\d+)\/edit(?:\?.*)?$/);

        if (!match || !match[1]) return;

        var serviceId = match[1];
        var paymentUrl = href.replace(/\/admin\/crm\/services\/\d+\/edit(?:\?.*)?$/, '/admin/crm/services/' + serviceId + '/payment-links');
        var parent = link.parentElement;

        if (!parent || parent.querySelector('[data-service-payment-link="' + serviceId + '"]')) return;

        var paymentLink = document.createElement('a');
        paymentLink.href = paymentUrl;
        paymentLink.className = 'btn btn-outline-success';
        paymentLink.title = 'Pagamento Stripe';
        paymentLink.setAttribute('data-service-payment-link', serviceId);
        paymentLink.innerHTML = '<i class="bi bi-credit-card"></i>';

        if (link.classList.contains('btn-sm')) paymentLink.classList.add('btn-sm');

        if (parent.classList.contains('btn-group')) parent.insertBefore(paymentLink, link.nextSibling);
        else {
            paymentLink.classList.add('ms-1');
            link.insertAdjacentElement('afterend', paymentLink);
        }
    });
});

(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function currentTab() {
        return new URLSearchParams(window.location.search).get('tab') || 'branding';
    }

    function isSettingsIndex() {
        return window.location.pathname === '/admin/settings' || window.location.pathname === '/admin/settings/';
    }

    function isFooterBrandPage() {
        return window.location.pathname === '/admin/settings/footer-brand' || window.location.pathname === '/admin/settings/footer-brand/';
    }

    function isSettingsArea() {
        return isSettingsIndex() || isFooterBrandPage();
    }

    function tabs() {
        return document.querySelector('.nav.nav-tabs');
    }

    function injectSettingsModernStyle() {
        if (!isSettingsArea() || document.getElementById('r4-settings-modern-style')) return;

        var style = document.createElement('style');
        style.id = 'r4-settings-modern-style';
        style.textContent = `
            body.r4-settings-modern{
                background:radial-gradient(circle at top left,rgba(13,110,253,.08),transparent 34rem),linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%)!important;
            }
            body.r4-settings-modern main.col.p-4{padding:1.35rem!important;}
            body.r4-settings-modern .page-topbar{position:relative!important;top:auto!important;z-index:1!important;margin:0 0 1rem!important;border:0!important;background:transparent!important;backdrop-filter:none!important;}
            body.r4-settings-modern .page-topbar .container-fluid{padding:0!important;}
            body.r4-settings-modern .page-topbar .d-flex.align-items-center.justify-content-between{
                padding:1.15rem 1.25rem;border:1px solid rgba(148,163,184,.26);border-radius:1.25rem;background:rgba(255,255,255,.9);box-shadow:0 18px 48px rgba(15,23,42,.07);
            }
            body.r4-settings-modern .page-topbar h1,body.r4-settings-modern .page-topbar .h4{font-size:1.35rem;letter-spacing:-.025em;font-weight:850;color:#0f172a;}
            body.r4-settings-modern .page-topbar i.text-primary{width:2.25rem;height:2.25rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.9rem;background:rgba(13,110,253,.10);}
            body.r4-settings-modern .nav.nav-tabs{gap:.45rem;padding:.55rem;margin-bottom:1rem!important;border:1px solid rgba(148,163,184,.25)!important;border-radius:1.25rem;background:rgba(255,255,255,.82);box-shadow:0 16px 44px rgba(15,23,42,.06);overflow-x:auto;flex-wrap:nowrap;scrollbar-width:thin;}
            body.r4-settings-modern .nav.nav-tabs .nav-item{flex:0 0 auto;}
            body.r4-settings-modern .nav.nav-tabs .nav-link{border:0!important;border-radius:999px!important;padding:.62rem .95rem;color:#475569;font-size:.92rem;font-weight:750;background:transparent;white-space:nowrap;transition:background .16s ease,color .16s ease,box-shadow .16s ease,transform .16s ease;}
            body.r4-settings-modern .nav.nav-tabs .nav-link i{color:#0d6efd;opacity:.9;}
            body.r4-settings-modern .nav.nav-tabs .nav-link:hover{background:#eef5ff;color:#0f172a;transform:translateY(-1px);}
            body.r4-settings-modern .nav.nav-tabs .nav-link.active{background:linear-gradient(135deg,#0d6efd 0%,#2563eb 100%)!important;color:#fff!important;box-shadow:0 14px 28px rgba(13,110,253,.24);}
            body.r4-settings-modern .nav.nav-tabs .nav-link.active i{color:#fff;opacity:1;}
            body.r4-settings-modern .card-soft,body.r4-settings-modern .card,.r4-card{border:1px solid rgba(148,163,184,.24)!important;border-radius:1.25rem!important;background:rgba(255,255,255,.94)!important;box-shadow:0 18px 54px rgba(15,23,42,.075)!important;}
            body.r4-settings-modern .card-soft.p-3,body.r4-settings-modern .card.p-3{padding:1.15rem!important;}
            body.r4-settings-modern .form-label{font-size:.86rem;font-weight:760;color:#172033;margin-bottom:.35rem;}
            body.r4-settings-modern .form-control,body.r4-settings-modern .form-select,body.r4-settings-modern .input-group-text{border-color:#dbe3ee;border-radius:.8rem;min-height:2.55rem;}
            body.r4-settings-modern .input-group>.form-control:not(:first-child),body.r4-settings-modern .input-group>.form-select:not(:first-child){border-top-left-radius:0;border-bottom-left-radius:0;}
            body.r4-settings-modern .input-group>.input-group-text:not(:last-child){border-top-right-radius:0;border-bottom-right-radius:0;}
            body.r4-settings-modern .form-control:focus,body.r4-settings-modern .form-select:focus{border-color:#0d6efd;box-shadow:0 0 0 .22rem rgba(13,110,253,.12);}
            body.r4-settings-modern .media-preview,body.r4-settings-modern .theme-preview{border-radius:1rem!important;border-color:rgba(148,163,184,.34)!important;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%)!important;}
            body.r4-settings-modern .btn{border-radius:.82rem;font-weight:720;}
            body.r4-settings-modern .btn-primary{background:linear-gradient(135deg,#0d6efd 0%,#2563eb 100%);border-color:#0d6efd;box-shadow:0 12px 24px rgba(13,110,253,.18);}
            body.r4-settings-modern .btn-outline-secondary,body.r4-settings-modern .btn-outline-danger{background:#fff;}
            body.r4-settings-modern textarea.form-control{min-height:7rem;}
            body.r4-settings-modern .alert-info{border-color:#b8edf8;background:linear-gradient(135deg,#ecfeff 0%,#cffafe 100%);color:#0f5563;border-radius:1rem;}
            @media (max-width:768px){body.r4-settings-modern .page-topbar .d-flex.align-items-center.justify-content-between{padding:1rem;border-radius:1rem;}body.r4-settings-modern .nav.nav-tabs{border-radius:1rem;}}
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

    function addExtraSettingsTabs() {
        if (!isSettingsIndex()) return;
        addSettingsLink('?tab=chatbot', 'chatbot', 'bi-chat-dots', 'ChatBot', /tab=calendar/);
        addSettingsLink('/admin/settings/footer-brand', 'footer-brand', 'bi-window-dock', 'Footer Brand', /tab=calendar/);
    }

    function hideOtherSettingsPanels() {
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
                '<div class="col-md-6">' +
                    '<div class="form-check form-switch">' +
                        '<input type="hidden" name="chatbot_enabled" value="0">' +
                        '<input class="form-check-input" type="checkbox" role="switch" id="chatbot_enabled" name="chatbot_enabled" value="1" ' + (enabled ? 'checked' : '') + '>' +
                        '<label class="form-check-label fw-semibold" for="chatbot_enabled">Mostra ChatBot sul sito pubblico</label>' +
                    '</div>' +
                    '<div class="form-hint mt-1">Se disattivato, il widget ChatBot non verrà caricato nelle pagine pubbliche del sito.</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="alert alert-info mb-0">' +
                        '<div class="fw-semibold mb-1">Stato attuale</div>' +
                        (enabled ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> ChatBot visibile</span>' : '<span class="badge text-bg-secondary"><i class="bi bi-eye-slash me-1"></i> ChatBot nascosto</span>') +
                        '<div class="small mt-2">Questa impostazione agisce solo sul frontend pubblico, non rimuove le route o lo storico conversazioni dal CRM.</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="mt-3 d-flex justify-content-end gap-2">' +
                '<a href="?tab=chatbot" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Annulla</a>' +
                '<button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva impostazioni ChatBot</button>' +
            '</div>';

        nav.insertAdjacentElement('afterend', panel);
    }

    function loadChatbotPanel() {
        if (!isSettingsIndex() || currentTab() !== 'chatbot') return;
        hideOtherSettingsPanels();

        fetch('/admin/settings/chatbot/status', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.ok ? res.json() : { enabled: false }; })
            .then(function (json) { renderChatbotPanel(!!json.enabled); })
            .catch(function () { renderChatbotPanel(false); });
    }

    ready(function () {
        if (!isSettingsArea()) return;
        injectSettingsModernStyle();
        addExtraSettingsTabs();
        loadChatbotPanel();
    });
})();
