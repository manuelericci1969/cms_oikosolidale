(function () {
    'use strict';

    function activate(tab) {
        document.querySelectorAll('[data-r4v5-left-tab]').forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.r4v5LeftTab === tab);
        });
        document.querySelectorAll('[data-r4v5-left-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.r4v5LeftPanel !== tab;
        });
    }

    function loadScriptOnce(id, src) {
        if (document.getElementById(id)) return;
        var script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.defer = true;
        document.body.appendChild(script);
    }

    function loadStyleOnce(id, href) {
        if (document.getElementById(id)) return;
        var link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    }

    function findPageBgInput() {
        return document.getElementById('r4v5PageBgImageSrc') || document.querySelector('input[name="meta[page_bg][image][src]"]');
    }

    function findPageBgType() {
        return document.getElementById('r4v5PageBgType') || document.querySelector('select[name="meta[page_bg][type]"]');
    }

    function injectPageBgMediaButton() {
        var input = findPageBgInput();
        if (!input || input.dataset.r4v5PageBgMediaReady === '1') return;

        input.dataset.r4v5PageBgMediaReady = '1';
        input.id = input.id || 'r4v5PageBgImageSrc';

        var type = findPageBgType();
        if (type && !type.id) type.id = 'r4v5PageBgType';

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'r4v5-mini-btn r4v5-mini-btn-primary';
        button.textContent = 'Scegli da Media';
        button.style.marginTop = '6px';

        button.addEventListener('click', function () {
            if (!window.R4V5Media || typeof window.R4V5Media.openForPageBackground !== 'function') {
                alert('Libreria Media V5 non disponibile.');
                return;
            }
            window.R4V5Media.openForPageBackground(input);
        });

        input.insertAdjacentElement('afterend', button);
    }

    function boot() {
        loadScriptOnce('r4v5-public-preview-loader', '/assets/admin/visual-editor-v5/ui/public-preview.js?v=20260505-v5-public-preview');
        loadScriptOnce('r4v5-slider-pro-runtime-loader', '/assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js?v=20260505-v5-slider-pro-advanced');
        loadScriptOnce('r4v5-slider-pro-widget-loader', '/assets/admin/visual-editor-v5/widgets/slider-pro.js?v=20260505-v5-slider-pro');
        loadScriptOnce('r4v5-slider-pro-media-loader', '/assets/admin/visual-editor-v5/media/slider-pro-media.js?v=20260505-v5-slider-pro-media');
        loadScriptOnce('r4v5-slider-pro-iframe-bridge-loader', '/assets/admin/visual-editor-v5/runtime/slider-pro-editor-bridge.js?v=20260505-v5-slider-pro-iframe');
        loadScriptOnce('r4v5-slider-pro-controls-loader', '/assets/admin/visual-editor-v5/panels/slider-pro-controls.js?v=20260505-v5-slider-pro-controls');
        loadScriptOnce('r4v5-footer-builder-panel-loader', '/assets/admin/visual-editor-v5/panels/footer-builder.js?v=20260507-v5-footer-builder');
        loadScriptOnce('r4v5-insert-slots-loader', '/assets/admin/visual-editor-v5/ui/insert-slots.js?v=20260507-v5-insert-slots');
        loadScriptOnce('r4v5-menu-pro-loader', '/assets/admin/visual-editor-v5/ui/menu-pro.js?v=20260508-v5-menu-pro');
        loadScriptOnce('r4v5-link-url-inspector-loader', '/assets/admin/visual-editor-v5/panels/link-url-inspector.js?v=20260509-v5-link-url-inspector-3');
        loadScriptOnce('r4v5-link-url-quick-loader', '/assets/admin/visual-editor-v5/panels/link-url-quick.js?v=20260509-v5-link-url-quick-3');
        loadScriptOnce('r4v5-hide-native-link-traits-loader', '/assets/admin/visual-editor-v5/panels/hide-native-link-traits.js?v=20260509-v5-hide-native-link-traits');
        loadStyleOnce('r4v5-seo-panel-enhancer-style', '/assets/admin/visual-editor-v5/panels/seo-panel-enhancer.css?v=20260515-seo-smart-core');
        loadScriptOnce('r4v5-seo-panel-enhancer-script', '/assets/admin/visual-editor-v5/panels/seo-panel-enhancer.js?v=20260515-seo-smart-core');

        document.querySelectorAll('[data-r4v5-left-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                activate(button.dataset.r4v5LeftTab);
                if (button.dataset.r4v5LeftTab === 'page') {
                    window.setTimeout(injectPageBgMediaButton, 30);
                }
            });
        });

        activate('widgets');
        injectPageBgMediaButton();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

(function () {
    'use strict';

    var widths = { Desktop: '', Tablet: '768px', Mobile: '375px' };

    function ensureStyle() {
        if (document.getElementById('r4v5-device-preview-fix-style')) return;
        var style = document.createElement('style');
        style.id = 'r4v5-device-preview-fix-style';
        style.textContent = [
            '.r4v5-editor .gjs-cv-canvas__frames{display:flex!important;justify-content:center!important;align-items:flex-start!important;overflow:auto!important;background:#111827!important}',
            '.r4v5-editor.r4v5-device-desktop .gjs-frame-wrapper,.r4v5-editor.r4v5-device-desktop iframe.gjs-frame{width:100%!important;max-width:none!important;margin:0!important}',
            '.r4v5-editor.r4v5-device-tablet .gjs-frame-wrapper,.r4v5-editor.r4v5-device-tablet iframe.gjs-frame{width:768px!important;max-width:calc(100% - 48px)!important;margin:0 auto!important}',
            '.r4v5-editor.r4v5-device-mobile .gjs-frame-wrapper,.r4v5-editor.r4v5-device-mobile iframe.gjs-frame{width:375px!important;max-width:calc(100% - 48px)!important;margin:0 auto!important}',
            '.r4v5-btn[data-r4v5-device].is-active{color:#fff;box-shadow:inset 0 -3px 0 #38bdf8;background:#111827}'
        ].join('');
        document.head.appendChild(style);
    }

    function normalizeDevice(name) {
        name = String(name || 'Desktop').trim();
        return widths.hasOwnProperty(name) ? name : 'Desktop';
    }

    function setButtonState(device) {
        document.querySelectorAll('[data-r4v5-device]').forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.r4v5Device === device);
        });
    }

    function applyDevicePreview(device) {
        ensureStyle();
        var editor = window.R4EditorV5 || null;
        var root = document.getElementById('r4EditorV5') || document.querySelector('.r4v5-editor');
        var current = normalizeDevice(device || (editor && editor.getDevice ? editor.getDevice() : 'Desktop'));
        var width = widths[current];

        if (root) {
            root.classList.remove('r4v5-device-desktop', 'r4v5-device-tablet', 'r4v5-device-mobile');
            root.classList.add('r4v5-device-' + current.toLowerCase());
        }

        setButtonState(current);

        var frame = editor && editor.Canvas && editor.Canvas.getFrameEl ? editor.Canvas.getFrameEl() : null;
        var wrapper = document.querySelector('.r4v5-editor .gjs-frame-wrapper');
        var frames = document.querySelector('.r4v5-editor .gjs-cv-canvas__frames');

        if (frames) {
            frames.style.display = 'flex';
            frames.style.justifyContent = 'center';
            frames.style.alignItems = 'flex-start';
            frames.style.overflow = 'auto';
        }

        [frame, wrapper].forEach(function (el) {
            if (!el) return;
            el.style.width = width || '100%';
            el.style.maxWidth = width ? 'calc(100% - 48px)' : 'none';
            el.style.marginLeft = width ? 'auto' : '0';
            el.style.marginRight = width ? 'auto' : '0';
            el.style.height = '100%';
        });
    }

    function bootDeviceFix() {
        ensureStyle();

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-r4v5-device]');
            if (!button) return;
            window.setTimeout(function () { applyDevicePreview(button.dataset.r4v5Device); }, 40);
            window.setTimeout(function () { applyDevicePreview(button.dataset.r4v5Device); }, 180);
        });

        var wait = window.setInterval(function () {
            var editor = window.R4EditorV5;
            if (!editor) return;
            window.clearInterval(wait);
            applyDevicePreview(editor.getDevice ? editor.getDevice() : 'Desktop');
            editor.on('device:select canvas:frame:load load', function () {
                window.setTimeout(function () {
                    applyDevicePreview(editor.getDevice ? editor.getDevice() : 'Desktop');
                }, 60);
            });
        }, 120);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootDeviceFix);
    else bootDeviceFix();
})();
