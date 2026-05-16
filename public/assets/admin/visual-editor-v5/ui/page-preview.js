(function () {
    'use strict';

    function qs(selector) { return document.querySelector(selector); }
    function qsa(selector) { return Array.prototype.slice.call(document.querySelectorAll(selector)); }

    function value(name, fallback) {
        const el = qs('[name="' + name + '"]');
        return el ? String(el.value || '') : (fallback || '');
    }

    function checked(name) {
        const el = qs('[name="' + name + '"][type="checkbox"]');
        return !!(el && el.checked);
    }

    function getEditor() { return window.R4EditorV5 || null; }

    function getFrameEl() {
        const editor = getEditor();
        if (!editor || !editor.Canvas || !editor.Canvas.getFrameEl) return null;
        return editor.Canvas.getFrameEl();
    }

    function getFrameDoc() {
        const frame = getFrameEl();
        return frame && frame.contentDocument ? frame.contentDocument : null;
    }

    function maxWidthFor(width) {
        if (width === 'boxed') return 960;
        if (width === 'standard') return 1240;
        return null;
    }

    function hexToRgb(hex) {
        const clean = String(hex || '#000000').replace('#', '').trim();
        const normalized = clean.length === 3 ? clean.split('').map(function (c) { return c + c; }).join('') : clean;
        const int = parseInt(normalized, 16);
        if (Number.isNaN(int)) return '0,0,0';
        return [(int >> 16) & 255, (int >> 8) & 255, int & 255].join(',');
    }

    function buildBackground() {
        const type = value('meta[page_bg][type]', 'none');
        if (type === 'color') return value('meta[page_bg][color]', '#ffffff') || '#ffffff';

        if (type === 'gradient') {
            const angle = value('meta[page_bg][angle]', '135') || '135';
            const from = value('meta[page_bg][from]', '#0d6efd') || '#0d6efd';
            const to = value('meta[page_bg][to]', '#ffffff') || '#ffffff';
            return 'linear-gradient(' + angle + 'deg,' + from + ',' + to + ')';
        }

        if (type === 'image') {
            const src = value('meta[page_bg][image][src]', '').trim();
            if (!src) return '#ffffff';
            const size = value('meta[page_bg][image][size]', 'cover') || 'cover';
            const position = value('meta[page_bg][image][position]', 'center center') || 'center center';
            const repeat = value('meta[page_bg][image][repeat]', 'no-repeat') || 'no-repeat';
            const attachment = value('meta[page_bg][image][attachment]', 'scroll') || 'scroll';
            const overlayEnabled = checked('meta[page_bg][image][overlay][enabled]');
            const overlayColor = value('meta[page_bg][image][overlay][color]', '#000000') || '#000000';
            const overlayOpacity = value('meta[page_bg][image][overlay][opacity]', '0.35') || '0.35';
            const overlay = overlayEnabled ? 'linear-gradient(rgba(' + hexToRgb(overlayColor) + ',' + overlayOpacity + '),rgba(' + hexToRgb(overlayColor) + ',' + overlayOpacity + ')),' : '';
            return overlay + 'url("' + src.replace(/"/g, '%22') + '") ' + position + '/' + size + ' ' + repeat + ' ' + attachment;
        }

        return '#ffffff';
    }

    function layoutValues() {
        const width = value('meta[layout][width]', 'standard');
        return {
            width: width,
            maxWidth: maxWidthFor(width),
            gutter: Math.max(0, Math.min(120, parseInt(value('meta[layout][gutter]', '24'), 10) || 0)),
            top: Math.max(0, Math.min(240, parseInt(value('meta[layout][top]', '0'), 10) || 0))
        };
    }

    function buildCss() {
        const layout = layoutValues();
        const bg = buildBackground();
        const maxWidthCss = layout.maxWidth ? layout.maxWidth + 'px' : 'none';

        return [
            'html,body{min-height:100% !important;}',
            'html{background:' + bg + ' !important;}',
            'body{background:' + bg + ' !important;margin:0 !important;min-height:100% !important;}',
            'body::before{content:"Editor V5 preview pagina";position:fixed;right:12px;bottom:10px;z-index:999999;padding:6px 9px;border-radius:999px;background:rgba(15,23,42,.72);color:#fff;font:700 10px/1 system-ui;pointer-events:none;}',
            'body > *:first-child{margin-top:' + layout.top + 'px !important;}',
            layout.width === 'full' ? '' : 'body > *{max-width:' + maxWidthCss + ';margin-left:auto !important;margin-right:auto !important;}',
            'body > *{box-sizing:border-box;}',
            'body > section,body > div{padding-left:max(' + layout.gutter + 'px, env(safe-area-inset-left)) !important;padding-right:max(' + layout.gutter + 'px, env(safe-area-inset-right)) !important;}'
        ].join('\n');
    }

    function applyOuterShell(bg) {
        const area = document.querySelector('.r4v5-canvas-area');
        const canvasShell = document.getElementById('r4v5Canvas');
        const layout = layoutValues();

        if (area) {
            area.style.setProperty('--r4v5-page-preview-bg', bg);
            area.classList.add('r4v5-has-page-preview');
            area.style.background = bg;
            area.style.paddingTop = Math.max(16, layout.top + 16) + 'px';
            area.style.paddingLeft = Math.max(16, layout.gutter + 16) + 'px';
            area.style.paddingRight = Math.max(16, layout.gutter + 16) + 'px';
        }

        if (canvasShell) {
            canvasShell.style.setProperty('--r4v5-page-preview-bg', bg);
            canvasShell.classList.add('r4v5-page-preview-frame');
            canvasShell.style.marginLeft = 'auto';
            canvasShell.style.marginRight = 'auto';
            canvasShell.style.width = layout.width === 'full' ? '100%' : 'min(100%,' + layout.maxWidth + 'px)';
            canvasShell.style.maxWidth = layout.width === 'full' ? 'none' : layout.maxWidth + 'px';
            canvasShell.style.height = 'calc(100% - ' + Math.max(0, layout.top) + 'px)';
            canvasShell.style.border = '18px solid rgba(255,255,255,.28)';
            canvasShell.style.outline = '1px solid rgba(15,23,42,.22)';
            canvasShell.style.boxShadow = '0 24px 70px rgba(0,0,0,.35)';
        }

        const frame = getFrameEl();
        if (frame) {
            frame.style.background = '#ffffff';
            if (frame.parentElement) frame.parentElement.style.background = '#ffffff';
        }
    }

    function applyPreview() {
        const bg = buildBackground();
        applyOuterShell(bg);

        const doc = getFrameDoc();
        if (!doc || !doc.head || !doc.body) return;

        let style = doc.getElementById('r4v5-page-preview-style');
        if (!style) {
            style = doc.createElement('style');
            style.id = 'r4v5-page-preview-style';
            doc.head.appendChild(style);
        }
        style.textContent = buildCss();
    }

    function bind() {
        qsa('[name^="meta[layout]"],[name^="meta[page_bg]"]').forEach(function (field) {
            if (field.dataset.r4v5PagePreviewBound === '1') return;
            field.dataset.r4v5PagePreviewBound = '1';
            field.addEventListener('input', debounce(applyPreview, 80));
            field.addEventListener('change', debounce(applyPreview, 80));
        });
    }

    function debounce(fn, wait) {
        let timer = null;
        return function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(fn, wait);
        };
    }

    function waitEditor() {
        let attempts = 0;
        const timer = window.setInterval(function () {
            attempts++;
            const editor = getEditor();
            if (editor && editor.on) {
                editor.on('load', function () { window.setTimeout(applyPreview, 120); });
                editor.on('canvas:frame:load', function () { window.setTimeout(applyPreview, 120); });
                window.clearInterval(timer);
                window.setTimeout(applyPreview, 250);
                window.setTimeout(applyPreview, 800);
            }
            if (attempts > 80) window.clearInterval(timer);
        }, 100);
    }

    function boot() {
        bind();
        waitEditor();
        window.R4V5PagePreview = { apply: applyPreview };
        window.setTimeout(applyPreview, 500);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
