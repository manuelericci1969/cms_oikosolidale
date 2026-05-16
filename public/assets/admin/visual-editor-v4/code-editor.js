(function () {
    'use strict';

    const cfg = window.R4VisualEditorV4 || {};
    const byId = (id) => id ? document.getElementById(id) : null;

    function getField(id) {
        return byId(id);
    }

    function fieldValue(id) {
        const el = getField(id);
        return el ? el.value || '' : '';
    }

    function setField(id, value) {
        const el = getField(id);
        if (el) el.value = value || '';
    }

    function decodeHtmlEntities(value) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = value || '';
        return textarea.value;
    }

    function extractManualJs(html) {
        const source = String(html || '');
        const scripts = [];
        const clean = source.replace(/<script\b[^>]*data-r4v4-manual-js=["']1["'][^>]*>([\s\S]*?)<\/script>/gi, function (_, code) {
            scripts.push(decodeHtmlEntities(code || ''));
            return '';
        });

        return {
            html: clean.trim(),
            js: scripts.join('\n\n').trim()
        };
    }

    function buildHtmlWithManualJs(html, js) {
        const clean = String(html || '')
            .replace(/<script\b[^>]*data-r4v4-manual-js=["']1["'][^>]*>[\s\S]*?<\/script>/gi, '')
            .trim();
        const code = String(js || '').trim();

        if (!code) return clean;

        return clean + '\n\n<script data-r4v4-manual-js="1">\n' + code + '\n<\/script>';
    }

    function getEditor() {
        return window.R4VisualEditorV4Editor || window.r4VisualEditorV4Editor || window.editorV4 || window.gjsEditor || null;
    }

    function indent(level) {
        return '    '.repeat(Math.max(0, level));
    }

    function formatHtml(source) {
        const voidTags = new Set([
            'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
            'link', 'meta', 'param', 'source', 'track', 'wbr'
        ]);
        const raw = String(source || '').trim();
        if (!raw) return '';

        const tokens = raw
            .replace(/>\s+</g, '><')
            .replace(/(<[^>]+>)/g, '\n$1\n')
            .split('\n')
            .map((token) => token.trim())
            .filter(Boolean);

        let level = 0;
        const lines = [];

        tokens.forEach((token) => {
            const isClosing = /^<\//.test(token);
            const isComment = /^<!--/.test(token);
            const isDoctype = /^<!doctype/i.test(token);
            const tagMatch = token.match(/^<\s*([a-zA-Z0-9:-]+)/);
            const tagName = tagMatch ? tagMatch[1].toLowerCase() : '';
            const isSelfClosing = /\/>$/.test(token) || voidTags.has(tagName) || isComment || isDoctype;
            const isOpening = /^</.test(token) && !isClosing && !isSelfClosing && !/^<!/.test(token);

            if (isClosing) level -= 1;
            lines.push(indent(level) + token);
            if (isOpening) level += 1;
        });

        return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function formatCss(source) {
        const raw = String(source || '').trim();
        if (!raw) return '';

        let css = raw
            .replace(/\s*{\s*/g, ' {\n')
            .replace(/;\s*/g, ';\n')
            .replace(/\s*}\s*/g, '\n}\n')
            .replace(/,\s*/g, ', ')
            .replace(/\n\s*\n/g, '\n');

        let level = 0;
        const lines = css
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean)
            .map((line) => {
                if (line.startsWith('}')) level -= 1;
                const formatted = indent(level) + line;
                if (line.endsWith('{')) level += 1;
                return formatted;
            });

        return lines.join('\n').trim();
    }

    function formatJs(source) {
        const raw = String(source || '').trim();
        if (!raw) return '';

        let js = raw
            .replace(/\s*([{}])\s*/g, '\n$1\n')
            .replace(/;\s*/g, ';\n')
            .replace(/\n\s*\n/g, '\n');

        let level = 0;
        const lines = js
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean)
            .map((line) => {
                if (/^[}\])]/.test(line)) level -= 1;
                const formatted = indent(level) + line;
                if (/[{[(]$/.test(line) && !/^\/\//.test(line)) level += 1;
                return formatted;
            });

        return lines.join('\n').trim();
    }

    function formatCodeEditors() {
        const htmlArea = byId('r4v4CodeHtml');
        const cssArea = byId('r4v4CodeCss');
        const jsArea = byId('r4v4CodeJs');

        if (htmlArea) htmlArea.value = formatHtml(htmlArea.value);
        if (cssArea) cssArea.value = formatCss(cssArea.value);
        if (jsArea) jsArea.value = formatJs(jsArea.value);

        syncHiddenFieldsFromCode();
        refreshPreview();
    }

    function refreshPreview() {
        const preview = byId('r4v4CodePreview');
        if (!preview) return;

        const html = byId('r4v4CodeHtml')?.value || '';
        const css = byId('r4v4CodeCss')?.value || '';
        const js = byId('r4v4CodeJs')?.value || '';
        const doc = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>' + css + '</style></head><body>' + html + '<script>' + js + '<\/script></body></html>';

        preview.srcdoc = doc;
    }

    function loadFromFields() {
        const extracted = extractManualJs(fieldValue(cfg.htmlFieldId));
        const htmlArea = byId('r4v4CodeHtml');
        const cssArea = byId('r4v4CodeCss');
        const jsArea = byId('r4v4CodeJs');

        if (htmlArea) htmlArea.value = extracted.html;
        if (cssArea) cssArea.value = fieldValue(cfg.cssFieldId);
        if (jsArea) jsArea.value = extracted.js;

        refreshPreview();
    }

    function syncHiddenFieldsFromCode() {
        const html = byId('r4v4CodeHtml')?.value || '';
        const css = byId('r4v4CodeCss')?.value || '';
        const js = byId('r4v4CodeJs')?.value || '';

        setField(cfg.htmlFieldId, buildHtmlWithManualJs(html, js));
        setField(cfg.cssFieldId, css);
    }

    function applyToGrapes() {
        syncHiddenFieldsFromCode();

        const editor = getEditor();
        const html = byId('r4v4CodeHtml')?.value || '';
        const css = byId('r4v4CodeCss')?.value || '';

        if (editor) {
            try {
                editor.setComponents(html);
                editor.setStyle(css);
                if (typeof editor.store === 'function') editor.store();
            } catch (error) {
                console.warn('[R4 Editor V4 Code] Impossibile sincronizzare GrapesJS, salvo comunque i campi.', error);
            }
        }

        refreshPreview();
    }

    function openModal() {
        loadFromFields();
        const modal = byId('r4v4CodeModal');
        if (modal) modal.hidden = false;
    }

    function closeModal() {
        const modal = byId('r4v4CodeModal');
        if (modal) modal.hidden = true;
    }

    function setTab(tab) {
        document.querySelectorAll('[data-r4v4-code-tab]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.r4v4CodeTab === tab);
        });
        document.querySelectorAll('[data-r4v4-code-pane]').forEach((pane) => {
            pane.classList.toggle('is-active', pane.dataset.r4v4CodePane === tab);
        });
    }

    function installGrapesHook() {
        if (!window.grapesjs || window.__r4v4CodeGrapesHook) return;

        window.__r4v4CodeGrapesHook = true;
        const originalInit = window.grapesjs.init.bind(window.grapesjs);

        window.grapesjs.init = function (options) {
            const editor = originalInit(options);
            window.R4VisualEditorV4Editor = editor;
            return editor;
        };
    }

    installGrapesHook();

    document.addEventListener('DOMContentLoaded', function () {
        const form = byId(cfg.formId);
        const openBtn = byId('r4v4OpenCodeEditor');

        if (openBtn) openBtn.addEventListener('click', openModal);

        document.querySelectorAll('[data-r4v4-code-close]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });

        document.querySelectorAll('[data-r4v4-code-tab]').forEach((btn) => {
            btn.addEventListener('click', () => setTab(btn.dataset.r4v4CodeTab));
        });

        ['r4v4CodeHtml', 'r4v4CodeCss', 'r4v4CodeJs'].forEach((id) => {
            const el = byId(id);
            if (el) {
                el.addEventListener('input', function () {
                    syncHiddenFieldsFromCode();
                    refreshPreview();
                });
            }
        });

        const formatBtn = byId('r4v4CodeFormat');
        if (formatBtn) formatBtn.addEventListener('click', formatCodeEditors);

        const applyBtn = byId('r4v4CodeApply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                applyToGrapes();
                closeModal();
            });
        }

        const previewBtn = byId('r4v4CodeRefreshPreview');
        if (previewBtn) previewBtn.addEventListener('click', refreshPreview);

        const resetBtn = byId('r4v4CodeReload');
        if (resetBtn) resetBtn.addEventListener('click', loadFromFields);

        if (form) {
            form.addEventListener('submit', function () {
                const modal = byId('r4v4CodeModal');
                if (modal && !modal.hidden) {
                    applyToGrapes();
                }
            }, true);
        }
    });
})();
