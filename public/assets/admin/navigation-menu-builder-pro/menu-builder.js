(function () {
    'use strict';

    function qs(selector, context) {
        return (context || document).querySelector(selector);
    }

    function qsa(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function field(name) {
        return qs('[name="' + name + '"]');
    }

    function value(name, fallback) {
        var el = field(name);
        if (!el) return fallback;
        if (el.type === 'checkbox') return el.checked ? el.value : fallback;
        return el.value || fallback;
    }

    function checkedValue(name, fallback) {
        var el = qs('[name="' + name + '"]:checked');
        return el ? el.value : fallback;
    }

    function checkbox(name) {
        var el = field(name);
        return !!(el && el.checked);
    }

    function normalizePx(value, fallback, min, max) {
        var n = parseInt(value, 10);
        if (Number.isNaN(n)) return fallback;
        return Math.max(min, Math.min(max, n));
    }

    function moveModalsToBody() {
        qsa('.r4-nav-builder-pro .modal').forEach(function (modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }

    function updatePreview() {
        var preview = qs('.r4-nav-preview-header');
        if (!preview) return;

        var bgMode = checkedValue('settings[item_bg_mode]', 'transparent');
        var scrolledMode = checkedValue('settings[scrolled_mode]', 'transparent');
        var bg = value('settings[bg_color]', '#ffffff');
        var itemBg = value('settings[item_bg_color]', '#ffffff');
        var link = value('settings[link_color]', '#111827');
        var hover = value('settings[link_color_hover]', '#0d6efd');
        var size = normalizePx(value('settings[font_size]', 16), 16, 8, 90);
        var weight = value('settings[font_weight]', '600');
        var font = value('settings[font_family]', 'system-ui');
        var align = value('settings[nav_align]', 'left');
        var textAlign = value('settings[text_align]', 'left');
        var italic = checkbox('settings[font_style]');
        var headerHeight = normalizePx(value('settings[header_height]', 76), 76, 40, 220);
        var logoHeight = normalizePx(value('settings[logo_height]', 28), 28, 16, 160);
        var bottomGap = normalizePx(value('settings[bottom_gap]', 0), 0, 0, 240);
        var firstBlockOffset = normalizePx(value('settings[first_block_offset]', 0), 0, -240, 240);
        var removeFirstGap = checkbox('settings[remove_first_gap]');
        var effectiveGap = removeFirstGap ? 0 : bottomGap + firstBlockOffset;

        preview.style.setProperty('--preview-bg', bg);
        preview.style.setProperty('--preview-text', link);
        preview.style.setProperty('--preview-hover', hover);
        preview.style.setProperty('--preview-size', size + 'px');
        preview.style.setProperty('--preview-weight', weight);
        preview.style.setProperty('--preview-font', font);
        preview.style.minHeight = headerHeight + 'px';
        preview.style.fontFamily = font === 'system-ui' ? "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" : font;
        preview.style.fontStyle = italic ? 'italic' : 'normal';

        var logoMark = qs('.r4-nav-preview-logo__mark', preview);
        if (logoMark) {
            logoMark.style.width = logoHeight + 'px';
            logoMark.style.height = logoHeight + 'px';
            logoMark.style.borderRadius = Math.max(10, Math.round(logoHeight / 3)) + 'px';
            logoMark.style.fontSize = Math.max(11, Math.round(logoHeight / 2.6)) + 'px';
        }

        var menu = qs('.r4-nav-preview-menu', preview);
        if (menu) {
            menu.style.textAlign = textAlign;
            menu.style.justifyContent = align === 'center' ? 'center' : (align === 'right' ? 'flex-end' : 'flex-start');
        }

        qsa('.r4-nav-preview-menu a', preview).forEach(function (linkEl) {
            linkEl.style.background = bgMode === 'color' ? itemBg : 'transparent';
            linkEl.style.padding = bgMode === 'color' ? '8px 11px' : '';
            linkEl.style.borderRadius = bgMode === 'color' ? '999px' : '';
        });

        var device = qs('.r4-nav-preview-device');
        if (device) {
            device.style.marginTop = effectiveGap + 'px';
            device.style.transform = effectiveGap < 0 ? 'translateY(' + effectiveGap + 'px)' : '';
        }

        var note = qs('.r4-nav-preview-note');
        if (note) {
            var gapText = removeFirstGap
                ? 'spazio tra menu e primo blocco eliminato'
                : 'spazio effettivo sotto menu: ' + effectiveGap + 'px';
            note.textContent = (scrolledMode === 'color'
                ? 'Preview live attiva: stato sticky/scrolled con colore dedicato; '
                : 'Preview live attiva: colori, font, header, logo e distanze si aggiornano subito; ') + gapText + '.';
        }
    }

    function bindStyleTabs() {
        qsa('[data-r4-nav-style-tab]').forEach(function (tab) {
            if (tab.dataset.r4NavBound === '1') return;
            tab.dataset.r4NavBound = '1';
            tab.addEventListener('click', function () {
                var key = tab.dataset.r4NavStyleTab;
                qsa('[data-r4-nav-style-tab]').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn === tab);
                });
                qsa('[data-r4-nav-style-panel]').forEach(function (panel) {
                    panel.hidden = panel.dataset.r4NavStylePanel !== key;
                });
            });
        });
    }

    function bindPreviewFields() {
        qsa('[name^="settings["]').forEach(function (el) {
            if (el.dataset.r4NavPreviewBound === '1') return;
            el.dataset.r4NavPreviewBound = '1';
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });
    }

    function bindDeleteModal() {
        var modalEl = qs('#confirmDeleteModal');
        var nameSpan = qs('#whatToDelete');
        var confirmBtn = qs('#confirmDeleteBtn');
        if (!modalEl || !confirmBtn || confirmBtn.dataset.r4NavDeleteBound === '1') return;

        var pendingForm = null;
        confirmBtn.dataset.r4NavDeleteBound = '1';

        document.addEventListener('click', function (event) {
            var ask = event.target.closest('[data-action="ask-delete"]');
            if (!ask) return;
            pendingForm = document.getElementById(ask.dataset.form);
            if (nameSpan) nameSpan.textContent = ask.dataset.name || 'questo elemento';
            if (window.bootstrap && window.bootstrap.Modal) {
                moveModalsToBody();
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        confirmBtn.addEventListener('click', function () {
            if (pendingForm) pendingForm.submit();
        });
    }

    function bindEditModalTriggers() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-bs-toggle="modal"][data-bs-target^="#editItemModal"]');
            if (!trigger || !window.bootstrap || !window.bootstrap.Modal) return;
            var target = trigger.getAttribute('data-bs-target');
            var modalEl = qs(target);
            if (!modalEl) return;
            event.preventDefault();
            moveModalsToBody();
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }, true);
    }

    function bindSeparatorForms() {
        qsa('form').forEach(function (form) {
            var typeSel = qs('select[name="type"]', form);
            if (!typeSel || typeSel.dataset.r4NavSeparatorBound === '1') return;
            typeSel.dataset.r4NavSeparatorBound = '1';
            var url = qs('input[name="url"]', form);
            var target = qs('select[name="target"]', form);
            function sync() {
                var isSep = typeSel.value === 'separator';
                [url, target].forEach(function (el) {
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
        moveModalsToBody();
        bindStyleTabs();
        bindPreviewFields();
        bindEditModalTriggers();
        bindDeleteModal();
        bindSeparatorForms();
        updatePreview();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
