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

    function tabs() {
        return document.querySelector('.nav.nav-tabs');
    }

    function contentAnchor() {
        var nav = tabs();
        return nav ? nav.nextElementSibling : null;
    }

    function addTab() {
        var nav = tabs();
        if (!nav || nav.querySelector('[data-r4-chatbot-tab]')) return;

        var active = currentTab() === 'chatbot';
        var li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = '<a class="nav-link ' + (active ? 'active' : '') + '" data-r4-chatbot-tab="1" href="?tab=chatbot"><i class="bi bi-chat-dots"></i> ChatBot</a>';

        var calendar = Array.prototype.slice.call(nav.querySelectorAll('a')).find(function (a) {
            return /tab=calendar/.test(a.getAttribute('href') || '');
        });

        if (calendar && calendar.parentElement) nav.insertBefore(li, calendar.parentElement);
        else nav.appendChild(li);
    }

    function hideNonChatbotPanels() {
        if (currentTab() !== 'chatbot') return;
        var anchor = contentAnchor();
        if (!anchor) return;

        var node = anchor;
        while (node) {
            if (node.id !== 'r4ChatbotSettingsPanel') node.style.display = 'none';
            node = node.nextElementSibling;
        }
    }

    function render(enabled) {
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
                        (enabled
                            ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> ChatBot visibile</span>'
                            : '<span class="badge text-bg-secondary"><i class="bi bi-eye-slash me-1"></i> ChatBot nascosto</span>') +
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

    function loadAndRender() {
        if (currentTab() !== 'chatbot') return;
        hideNonChatbotPanels();

        fetch('/admin/settings/chatbot/status', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.ok ? res.json() : { enabled: false }; })
            .then(function (json) { render(!!json.enabled); })
            .catch(function () { render(false); });
    }

    ready(function () {
        if (!/\/admin\/settings/.test(window.location.pathname)) return;
        addTab();
        loadAndRender();
    });
})();
