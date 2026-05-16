(function () {
    'use strict';

    var STYLE_ID = 'r4-menu-modal-runtime-style';

    function qs(selector, context) {
        return (context || document).querySelector(selector);
    }

    function qsa(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function injectRuntimeStyles() {
        if (document.getElementById(STYLE_ID)) return;

        var css = '' +
            'body .modal-backdrop{z-index:3000!important;}\n' +
            'body .modal-backdrop.show{z-index:3000!important;}\n' +
            'body .r4-nav-item-modal,body #confirmDeleteModal{position:fixed!important;inset:0!important;z-index:3010!important;width:100vw!important;height:100vh!important;margin:0!important;padding:24px!important;overflow-x:hidden!important;overflow-y:auto!important;background:transparent!important;pointer-events:auto!important;}\n' +
            'body .r4-nav-item-modal.show,body #confirmDeleteModal.show{display:flex!important;align-items:flex-start!important;justify-content:center!important;}\n' +
            'body .r4-nav-item-modal .modal-dialog,body #confirmDeleteModal .modal-dialog{width:min(1180px,calc(100vw - 48px))!important;max-width:min(1180px,calc(100vw - 48px))!important;margin:24px auto!important;transform:none!important;pointer-events:auto!important;}\n' +
            'body .r4-nav-item-modal .modal-content,body #confirmDeleteModal .modal-content{max-height:calc(100vh - 96px)!important;overflow:hidden!important;border-radius:18px!important;box-shadow:0 28px 80px rgba(2,6,23,.38)!important;background:#fff!important;pointer-events:auto!important;opacity:1!important;filter:none!important;}\n' +
            'body .r4-nav-item-modal .modal-body,body #confirmDeleteModal .modal-body{overflow-y:auto!important;max-height:calc(100vh - 210px)!important;}\n' +
            '@media(max-width:760px){body .r4-nav-item-modal,body #confirmDeleteModal{padding:12px!important;}body .r4-nav-item-modal .modal-dialog,body #confirmDeleteModal .modal-dialog{width:calc(100vw - 24px)!important;max-width:calc(100vw - 24px)!important;margin:12px auto!important;}body .r4-nav-item-modal .modal-content,body #confirmDeleteModal .modal-content{max-height:calc(100vh - 48px)!important;}body .r4-nav-item-modal .modal-body,body #confirmDeleteModal .modal-body{max-height:calc(100vh - 170px)!important;}}';

        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    }

    function teleportModalToBody(modalEl) {
        if (!modalEl) return null;
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
        modalEl.style.zIndex = '3010';
        modalEl.style.pointerEvents = 'auto';
        return modalEl;
    }

    function teleportAllModals() {
        qsa('.r4-nav-builder-pro .modal, .r4-nav-item-modal, #confirmDeleteModal').forEach(function (modalEl) {
            teleportModalToBody(modalEl);
        });
    }

    function normalizeBackdrops() {
        var backdrops = qsa('.modal-backdrop');
        backdrops.forEach(function (backdrop) {
            backdrop.style.zIndex = '3000';
        });

        // Evita accumuli di backdrop quando il click viene intercettato più volte.
        if (backdrops.length > 1) {
            backdrops.slice(0, backdrops.length - 1).forEach(function (oldBackdrop) {
                oldBackdrop.remove();
            });
        }
    }

    function bringModalToFront(modalEl) {
        if (!modalEl) return;
        teleportModalToBody(modalEl);
        modalEl.style.zIndex = '3010';
        modalEl.style.display = modalEl.classList.contains('show') ? 'flex' : modalEl.style.display;
        normalizeBackdrops();
    }

    function showModal(modalEl) {
        if (!modalEl) return;
        modalEl = teleportModalToBody(modalEl);

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            window.setTimeout(function () { bringModalToFront(modalEl); }, 0);
            window.setTimeout(function () { bringModalToFront(modalEl); }, 80);
            return;
        }

        modalEl.classList.add('show');
        modalEl.style.display = 'flex';
        modalEl.removeAttribute('aria-hidden');
        modalEl.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');

        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show r4-nav-fallback-backdrop';
        backdrop.style.zIndex = '3000';
        backdrop.dataset.r4ModalFallback = modalEl.id;
        document.body.appendChild(backdrop);
        bringModalToFront(modalEl);
    }

    function hideModal(modalEl) {
        if (!modalEl) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            return;
        }

        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
        qsa('[data-r4-modal-fallback="' + modalEl.id + '"]').forEach(function (el) { el.remove(); });
    }

    function bindModalTriggers() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-r4-open-modal], [data-bs-toggle="modal"][data-bs-target]');
            if (!trigger) return;

            var target = trigger.dataset.r4OpenModal || trigger.dataset.bsTarget;
            if (!target || target.charAt(0) !== '#') return;

            var modalEl = qs(target);
            if (!modalEl) return;

            event.preventDefault();
            event.stopPropagation();
            showModal(modalEl);
        }, true);

        document.addEventListener('click', function (event) {
            var close = event.target.closest('[data-bs-dismiss="modal"]');
            if (!close) return;
            var modalEl = close.closest('.modal');
            if (modalEl && !(window.bootstrap && window.bootstrap.Modal)) {
                event.preventDefault();
                hideModal(modalEl);
            }
        });

        document.addEventListener('shown.bs.modal', function (event) {
            bringModalToFront(event.target);
        });
    }

    function bindDestinationFields() {
        qsa('form').forEach(function (form) {
            var page = qs('[name="page_id"]', form);
            var url = qs('input[name="url"]', form);
            if (!page || !url || page.dataset.r4NavDestinationBound === '1') return;

            page.dataset.r4NavDestinationBound = '1';

            page.addEventListener('change', function () {
                if (page.value) url.value = '';
            });

            url.addEventListener('input', function () {
                if (url.value.trim() !== '') page.value = '';
            });
        });
    }

    function bindSeparatorForms() {
        qsa('form').forEach(function (form) {
            var typeSel = qs('select[name="type"]', form);
            if (!typeSel || typeSel.dataset.r4NavModalFixSeparatorBound === '1') return;
            typeSel.dataset.r4NavModalFixSeparatorBound = '1';

            var url = qs('input[name="url"]', form);
            var page = qs('select[name="page_id"]', form);
            var target = qs('select[name="target"]', form);

            function sync() {
                var isSep = typeSel.value === 'separator';
                [url, page, target].forEach(function (el) {
                    if (!el) return;
                    el.disabled = isSep;
                    if (isSep) el.value = '';
                });
            }

            typeSel.addEventListener('change', sync);
            sync();
        });
    }

    function boot() {
        injectRuntimeStyles();
        teleportAllModals();
        bindModalTriggers();
        bindDestinationFields();
        bindSeparatorForms();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
