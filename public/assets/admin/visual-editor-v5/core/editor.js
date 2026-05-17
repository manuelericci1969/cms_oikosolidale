(function () {
    'use strict';

    const cfg = window.R4EditorV5Config || {};

    function byId(id) {
        return id ? document.getElementById(id) : null;
    }

    function readField(id) {
        const field = byId(id);
        return field ? field.value || '' : '';
    }

    function writeField(id, value) {
        const field = byId(id);
        if (field) field.value = value || '';
    }

    function parseJson(value) {
        if (!value || !String(value).trim()) return null;
        try {
            return JSON.parse(value);
        } catch (error) {
            console.warn('[R4 Editor V5] visual_json non valido, uso fallback HTML/CSS.', error);
            return null;
        }
    }

    function readStoredProjectData() {
        const project = parseJson(readField(cfg.jsonFieldId));
        return project && typeof project === 'object' ? project : {};
    }

    function getStoredCustomJs() {
        const project = readStoredProjectData();
        return String(project.r4v5CustomJs || window.R4EditorV5CustomJs || '').trim();
    }

    function setStoredCustomJs(value) {
        window.R4EditorV5CustomJs = String(value || '').trim();
    }

    function syncFields(editor) {
        if (!editor) return;

        const htmlWithPossibleScripts = editor.getHtml() || '';
        const extracted = extractCustomJs(htmlWithPossibleScripts);
        const customJs = String(extracted.js || getStoredCustomJs() || '').trim();
        setStoredCustomJs(customJs);

        writeField(cfg.htmlFieldId, extracted.html || htmlWithPossibleScripts);
        writeField(cfg.cssFieldId, editor.getCss());

        try {
            const project = editor.getProjectData();
            project.r4v5CustomJs = customJs;
            writeField(cfg.jsonFieldId, JSON.stringify(project));
        } catch (error) {
            console.warn('[R4 Editor V5] Sync JSON non riuscito', error);
        }
    }

    function addFallbackBlocks(editor) {
        const bm = editor.BlockManager;
        bm.add('r4v5-heading', { label: 'Titolo', category: 'Base', content: '<h2>Scrivi il tuo titolo</h2>' });
        bm.add('r4v5-paragraph', { label: 'Paragrafo', category: 'Base', content: '<p>Scrivi qui il testo del paragrafo.</p>' });
        bm.add('r4v5-button', { label: 'Bottone', category: 'Base', content: '<a href="#" style="display:inline-block;padding:12px 18px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:700;">Call to action</a>' });
        bm.add('r4v5-section', { label: 'Sezione semplice', category: 'Layout', content: '<section style="padding:64px 24px;"><div style="max-width:1100px;margin:0 auto;"><h2>Sezione V5</h2><p>Contenuto modificabile.</p></div></section>' });
    }

    function addRegistryBlocks(editor) {
        const registry = window.R4EditorV5Registry;
        if (!registry || typeof registry.widgets !== 'function') {
            addFallbackBlocks(editor);
            return;
        }

        const widgets = registry.widgets();
        if (!widgets.length) {
            addFallbackBlocks(editor);
            return;
        }

        widgets.forEach(function (widget) {
            editor.BlockManager.add(widget.key, {
                label: widget.label || widget.key,
                category: widget.category || 'Base',
                content: widget.content || '',
                media: widget.media || undefined
            });
        });
    }

    function bindToolbar(editor) {
        ensureCodeEditorButton(editor);

        document.querySelectorAll('[data-r4v5-command]').forEach(function (button) {
            button.addEventListener('click', function () {
                const command = button.dataset.r4v5Command;
                if (command === 'undo') editor.UndoManager.undo();
                if (command === 'redo') editor.UndoManager.redo();
                if (command === 'code') openCodeEditor(editor);
            });
        });

        document.querySelectorAll('[data-r4v5-device]').forEach(function (button) {
            button.addEventListener('click', function () {
                editor.setDevice(button.dataset.r4v5Device);
                normalizeCanvas(editor);
            });
        });

        document.querySelectorAll('[data-r4v5-submit-status]').forEach(function (button) {
            button.addEventListener('click', function () {
                writeField(cfg.statusFieldId, button.dataset.r4v5SubmitStatus || 'draft');
            });
        });
    }

    function bindForm(editor) {
        const form = byId(cfg.formId);
        if (!form) return;
        form.addEventListener('submit', function () {
            syncFields(editor);
        });
    }

    function normalizeCanvas(editor) {
        const canvasEl = byId(cfg.canvasId);
        if (canvasEl) {
            canvasEl.style.width = '100%';
            canvasEl.style.height = '100%';
        }

        document.querySelectorAll('.r4v5-editor .gjs-editor, .r4v5-editor .gjs-editor-cont, .r4v5-editor .gjs-cv-canvas, .r4v5-editor .gjs-cv-canvas__frames, .r4v5-editor .gjs-frame-wrapper, .r4v5-editor iframe.gjs-frame').forEach(function (el) {
            el.style.width = '100%';
            el.style.maxWidth = 'none';
            el.style.height = '100%';
        });

        const frame = editor && editor.Canvas && editor.Canvas.getFrameEl ? editor.Canvas.getFrameEl() : null;
        if (frame) {
            frame.style.width = '100%';
            frame.style.maxWidth = 'none';
            frame.style.height = '100%';
        }
    }

    function injectCanvasGuards(editor) {
        const doc = editor && editor.Canvas && editor.Canvas.getDocument ? editor.Canvas.getDocument() : null;
        if (!doc || doc.getElementById('r4v5-canvas-guards')) return;

        const style = doc.createElement('style');
        style.id = 'r4v5-canvas-guards';
        style.textContent = [
            'html,body{min-width:100%!important;width:100%!important;}',
            '.r4v5-editor-preview-ghost{display:none!important;}',
            'a[data-r4-preview],button[data-r4-preview],.r4-preview-page,.r4v5-preview-page{display:none!important;}'
        ].join('\n');
        doc.head.appendChild(style);
    }

    function removeStalePreviewButton(editor) {
        const doc = editor && editor.Canvas && editor.Canvas.getDocument ? editor.Canvas.getDocument() : null;
        if (!doc || !doc.body) return;

        Array.from(doc.querySelectorAll('a, button')).forEach(function (el) {
            const text = (el.textContent || '').trim().toLowerCase();
            if (text === 'preview pagina') {
                el.classList.add('r4v5-editor-preview-ghost');
                el.setAttribute('data-r4v5-hidden-in-editor', 'preview-page');
                el.style.display = 'none';
            }
        });
    }

    function installEditorUiGuards(editor) {
        const run = function () {
            normalizeCanvas(editor);
            injectCanvasGuards(editor);
            removeStalePreviewButton(editor);
        };

        run();
        setTimeout(run, 80);
        setTimeout(run, 300);
        setTimeout(run, 900);

        editor.on('component:add component:update component:selected canvas:frame:load device:select', run);
    }

    function ensureCodeEditorButton(editor) {
        const actions = document.querySelector('.r4v5-actions');
        if (!actions || actions.querySelector('[data-r4v5-command="code"]')) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'r4v5-btn r4v5-btn-light';
        btn.dataset.r4v5Command = 'code';
        btn.textContent = 'Codice';

        const mediaBtn = actions.querySelector('[data-r4v5-command="media"]');
        if (mediaBtn && mediaBtn.nextSibling) {
            actions.insertBefore(btn, mediaBtn.nextSibling);
        } else {
            actions.appendChild(btn);
        }
    }

    function ensureCodeEditorModal() {
        let modal = byId('r4v5CodeEditorModal');
        if (modal) return modal;

        const style = document.createElement('style');
        style.id = 'r4v5-code-editor-style';
        style.textContent = [
            '.r4v5-code-modal{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;background:rgba(2,6,23,.78);backdrop-filter:blur(6px)}',
            '.r4v5-code-modal[hidden]{display:none!important}',
            '.r4v5-code-dialog{width:min(1280px,96vw);height:min(840px,92vh);display:flex;flex-direction:column;background:#020617;border:1px solid rgba(148,163,184,.28);box-shadow:0 28px 90px rgba(0,0,0,.45);color:#e5e7eb}',
            '.r4v5-code-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border-bottom:1px solid rgba(148,163,184,.18)}',
            '.r4v5-code-title{font-size:14px;font-weight:950;color:#fff}.r4v5-code-subtitle{font-size:12px;color:#94a3b8;margin-top:3px}',
            '.r4v5-code-close{border:0;background:transparent;color:#cbd5e1;font-size:28px;line-height:1;cursor:pointer}',
            '.r4v5-code-tabs{display:flex;border-bottom:1px solid rgba(148,163,184,.16);background:#020617}',
            '.r4v5-code-tab{border:0;border-right:1px solid rgba(148,163,184,.12);background:#020617;color:#cbd5e1;padding:12px 18px;font-size:12px;font-weight:900;cursor:pointer}',
            '.r4v5-code-tab.is-active{background:#111827;color:#fff;box-shadow:inset 0 -3px 0 #0d6efd}',
            '.r4v5-code-body{flex:1;min-height:0;display:grid;background:#0f172a}',
            '.r4v5-code-panel{min-height:0;display:grid}.r4v5-code-panel[hidden]{display:none!important}',
            '.r4v5-code-editor{width:100%;height:100%;resize:none;border:0;outline:0;background:#0b1120;color:#dbeafe;font:13px/1.55 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;padding:16px;tab-size:2}',
            '.r4v5-code-editor:focus{box-shadow:inset 0 0 0 1px rgba(13,110,253,.55)}',
            '.r4v5-code-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid rgba(148,163,184,.18);background:#020617}',
            '.r4v5-code-note{font-size:12px;color:#94a3b8}.r4v5-code-actions{display:flex;gap:8px;align-items:center}',
            '.r4v5-code-btn{border:1px solid rgba(148,163,184,.28);background:#111827;color:#fff;border-radius:999px;padding:9px 13px;font-size:12px;font-weight:900;cursor:pointer}',
            '.r4v5-code-btn:hover{background:#1f2937}.r4v5-code-btn-primary{background:#0d6efd;border-color:#0d6efd}.r4v5-code-btn-primary:hover{background:#0b5ed7}',
            '.r4v5-code-btn-danger{background:#7f1d1d;border-color:#991b1b}'
        ].join('');
        document.head.appendChild(style);

        modal = document.createElement('div');
        modal.id = 'r4v5CodeEditorModal';
        modal.className = 'r4v5-code-modal';
        modal.hidden = true;
        modal.innerHTML = [
            '<div class="r4v5-code-dialog" role="dialog" aria-modal="true" aria-labelledby="r4v5CodeEditorTitle">',
                '<div class="r4v5-code-head">',
                    '<div><div class="r4v5-code-title" id="r4v5CodeEditorTitle">Code Editor</div><div class="r4v5-code-subtitle">Modifica HTML, CSS e JavaScript della pagina visuale.</div></div>',
                    '<button type="button" class="r4v5-code-close" data-r4v5-code-close aria-label="Chiudi">×</button>',
                '</div>',
                '<div class="r4v5-code-tabs">',
                    '<button type="button" class="r4v5-code-tab is-active" data-r4v5-code-tab="html">HTML</button>',
                    '<button type="button" class="r4v5-code-tab" data-r4v5-code-tab="css">CSS</button>',
                    '<button type="button" class="r4v5-code-tab" data-r4v5-code-tab="js">JavaScript</button>',
                '</div>',
                '<div class="r4v5-code-body">',
                    '<div class="r4v5-code-panel" data-r4v5-code-panel="html"><textarea class="r4v5-code-editor" id="r4v5CodeHtml" spellcheck="false"></textarea></div>',
                    '<div class="r4v5-code-panel" data-r4v5-code-panel="css" hidden><textarea class="r4v5-code-editor" id="r4v5CodeCss" spellcheck="false"></textarea></div>',
                    '<div class="r4v5-code-panel" data-r4v5-code-panel="js" hidden><textarea class="r4v5-code-editor" id="r4v5CodeJs" spellcheck="false" placeholder="// JavaScript personalizzato della pagina"></textarea></div>',
                '</div>',
                '<div class="r4v5-code-foot">',
                    '<div class="r4v5-code-note">Puoi incollare codice completo con HTML, CSS e JS: l’editor lo normalizza prima dell’import.</div>',
                    '<div class="r4v5-code-actions">',
                        '<button type="button" class="r4v5-code-btn" data-r4v5-code-refresh>Ricarica dal canvas</button>',
                        '<button type="button" class="r4v5-code-btn r4v5-code-btn-danger" data-r4v5-code-clear-js>Pulisci JS</button>',
                        '<button type="button" class="r4v5-code-btn" data-r4v5-code-close>Chiudi</button>',
                        '<button type="button" class="r4v5-code-btn r4v5-code-btn-primary" data-r4v5-code-apply>Applica al canvas</button>',
                    '</div>',
                '</div>',
            '</div>'
        ].join('');
        document.body.appendChild(modal);

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-r4v5-code-close]')) {
                modal.hidden = true;
            }
        });

        modal.querySelectorAll('[data-r4v5-code-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                const key = tab.dataset.r4v5CodeTab;
                modal.querySelectorAll('[data-r4v5-code-tab]').forEach(function (btn) { btn.classList.toggle('is-active', btn === tab); });
                modal.querySelectorAll('[data-r4v5-code-panel]').forEach(function (panel) { panel.hidden = panel.dataset.r4v5CodePanel !== key; });
                const active = modal.querySelector('[data-r4v5-code-panel="' + key + '"] textarea');
                if (active) setTimeout(function () { active.focus(); }, 20);
            });
        });

        return modal;
    }

    function extractCustomJs(html) {
        const scripts = [];
        const cleanHtml = String(html || '').replace(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi, function (full, attrs, body) {
            const idMatch = String(attrs || '').match(/\bid=["']([^"']+)["']/i);
            const id = idMatch ? idMatch[1] : '';
            if (id && id.indexOf('r4v5-') === 0) return '';
            scripts.push((body || '').trim());
            return '';
        });
        return { html: cleanHtml.trim(), js: scripts.filter(Boolean).join('\n\n') };
    }

    function extractStyleBlocks(html) {
        const styles = [];
        const cleanHtml = String(html || '').replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, function (full, body) {
            styles.push((body || '').trim());
            return '';
        });
        return { html: cleanHtml.trim(), css: styles.filter(Boolean).join('\n\n') };
    }

    function stripDocumentWrappers(html) {
        let out = String(html || '');
        out = out.replace(/<!doctype[^>]*>/ig, '');
        out = out.replace(/<html\b[^>]*>/ig, '').replace(/<\/html>/ig, '');
        out = out.replace(/<head\b[^>]*>[\s\S]*?<\/head>/ig, '');
        out = out.replace(/<\/?body\b[^>]*>/ig, '');
        return out.trim();
    }

    function serializeChildren(node) {
        return Array.from(node.childNodes || []).map(function (child) {
            if (child.outerHTML) return child.outerHTML;
            return child.textContent || '';
        }).join('').trim();
    }

    function unwrapImportedPageWrapper(html) {
        const source = String(html || '').trim();
        if (!source || !window.DOMParser) return source;

        try {
            const parsed = new DOMParser().parseFromString('<div id="r4v5-import-root">' + source + '</div>', 'text/html');
            const root = parsed.getElementById('r4v5-import-root');
            if (!root) return source;

            const elementChildren = Array.from(root.children || []).filter(function (node) {
                return node.tagName && !['SCRIPT', 'STYLE'].includes(node.tagName.toUpperCase());
            });

            if (elementChildren.length === 1) {
                const wrapper = elementChildren[0];
                const tag = wrapper.tagName.toLowerCase();
                const directSections = Array.from(wrapper.children || []).filter(function (node) {
                    const t = node.tagName ? node.tagName.toLowerCase() : '';
                    return ['section', 'header', 'footer', 'article', 'aside'].includes(t) || node.hasAttribute('data-r4v5-section') || /(?:section|hero|cta|services|portfolio|faq|pricing|prodotti|problema|soluzione)/i.test(node.className || '');
                });

                if (['main', 'article', 'div'].includes(tag) && directSections.length >= 2) {
                    return serializeChildren(wrapper);
                }
            }

            return source;
        } catch (error) {
            return source;
        }
    }

    function dropSlot(index) {
        return '<section class="r4v5-code-drop-slot" data-r4v5-code-drop-slot="' + index + '" data-gjs-droppable="true" data-gjs-selectable="true" data-gjs-highlightable="true" data-gjs-hoverable="true" style="display:block;width:100%;clear:both;position:relative;padding:18px 24px;background:#ffffff;min-height:72px;border:0;">' +
            '<div class="r4v5-code-drop-placeholder" data-gjs-selectable="false" data-gjs-hoverable="false" style="max-width:1120px;margin:0 auto;min-height:48px;border:1px dashed rgba(13,110,253,.32);border-radius:18px;background:rgba(13,110,253,.035);display:flex;align-items:center;justify-content:center;text-align:center;color:#475569;font-size:12px;font-weight:900;font-style:italic;">Trascina il widget qui</div>' +
        '</section>';
    }

    function markImportedSections(html) {
        const source = String(html || '').trim();
        if (!source || !window.DOMParser) return source;

        try {
            const parsed = new DOMParser().parseFromString('<div id="r4v5-import-root">' + source + '</div>', 'text/html');
            const root = parsed.getElementById('r4v5-import-root');
            if (!root) return source;

            Array.from(root.querySelectorAll('[data-r4v5-code-drop-slot]')).forEach(function (slot) {
                slot.remove();
            });

            Array.from(root.children || []).forEach(function (node, index) {
                if (!node.tagName) return;
                const tag = node.tagName.toLowerCase();
                if (!['section', 'header', 'footer', 'article', 'aside', 'div'].includes(tag)) return;

                if (!node.getAttribute('data-r4v5-code-section')) node.setAttribute('data-r4v5-code-section', String(index + 1));
                if (!node.getAttribute('data-gjs-droppable')) node.setAttribute('data-gjs-droppable', 'true');
                if (!node.getAttribute('data-gjs-selectable')) node.setAttribute('data-gjs-selectable', 'true');
                if (!node.getAttribute('data-gjs-highlightable')) node.setAttribute('data-gjs-highlightable', 'true');
                if (!node.getAttribute('data-gjs-hoverable')) node.setAttribute('data-gjs-hoverable', 'true');
            });

            const htmlParts = [];
            const children = Array.from(root.children || []);
            children.forEach(function (node, index) {
                if (index > 0) htmlParts.push(dropSlot(index));
                htmlParts.push(node.outerHTML);
            });
            if (children.length > 0) htmlParts.push(dropSlot(children.length));

            return htmlParts.join('\n').trim() || serializeChildren(root);
        } catch (error) {
            return source;
        }
    }

    function normalizeImportedCode(html, css, js) {
        let nextHtml = String(html || '');
        let nextCss = String(css || '');
        let nextJs = String(js || '');

        const styleResult = extractStyleBlocks(nextHtml);
        nextHtml = styleResult.html;
        if (styleResult.css) nextCss = [nextCss, styleResult.css].filter(Boolean).join('\n\n');

        const scriptResult = extractCustomJs(nextHtml);
        nextHtml = scriptResult.html;
        if (scriptResult.js) nextJs = [nextJs, scriptResult.js].filter(Boolean).join('\n\n');

        nextHtml = stripDocumentWrappers(nextHtml);
        nextHtml = unwrapImportedPageWrapper(nextHtml);
        nextHtml = markImportedSections(nextHtml);

        if (!/class=["'][^"']*r4v5-code-import-root/i.test(nextHtml) && !/^\s*<(section|main|article|div|header|footer|aside)\b/i.test(nextHtml)) {
            nextHtml = '<div class="r4v5-code-import-root" data-gjs-droppable="true" data-gjs-selectable="true">' + nextHtml + '</div>';
        }

        if (!nextHtml.trim()) {
            nextHtml = '<section class="r4v5-code-import-root" data-r4v5-code-section="1" data-gjs-droppable="true" data-gjs-selectable="true" style="padding:64px 24px;"><div style="max-width:1120px;margin:0 auto;"><h2>Codice importato</h2><p>Aggiungi HTML, CSS e JavaScript dal Code Editor.</p></div></section>';
        }

        return { html: nextHtml, css: nextCss.trim(), js: nextJs.trim() };
    }

    function composeHtmlWithJs(html, js) {
        const clean = String(html || '').trim();
        const code = String(js || '').trim();
        if (!code) return clean;
        return clean + '\n<script data-r4v5-custom-js="1">\n' + code + '\n</script>';
    }

    function readCodeFromCanvas(editor) {
        const rawHtml = editor.getHtml() || '';
        const extracted = extractCustomJs(rawHtml);
        const styles = extractStyleBlocks(extracted.html);
        return {
            html: stripDocumentWrappers(styles.html),
            css: [editor.getCss() || '', styles.css || ''].filter(Boolean).join('\n\n').trim(),
            js: extracted.js || getStoredCustomJs()
        };
    }

    function fillCodeEditor(editor) {
        const modal = ensureCodeEditorModal();
        const code = readCodeFromCanvas(editor);
        modal.querySelector('#r4v5CodeHtml').value = code.html;
        modal.querySelector('#r4v5CodeCss').value = code.css;
        modal.querySelector('#r4v5CodeJs').value = code.js;
    }

    function applyCodeEditor(editor) {
        const modal = ensureCodeEditorModal();
        const rawHtml = modal.querySelector('#r4v5CodeHtml').value || '';
        const rawCss = modal.querySelector('#r4v5CodeCss').value || '';
        const rawJs = modal.querySelector('#r4v5CodeJs').value || '';
        const code = normalizeImportedCode(rawHtml, rawCss, rawJs);

        setStoredCustomJs(code.js);
        editor.setComponents(composeHtmlWithJs(code.html, code.js));
        editor.setStyle(code.css);
        syncFields(editor);
        installEditorUiGuards(editor);

        if (window.R4V5EditorCodeRuntimeBridge && typeof window.R4V5EditorCodeRuntimeBridge.inject === 'function') {
            setTimeout(window.R4V5EditorCodeRuntimeBridge.inject, 120);
            setTimeout(window.R4V5EditorCodeRuntimeBridge.inject, 420);
        }

        modal.hidden = true;
    }

    function openCodeEditor(editor) {
        const modal = ensureCodeEditorModal();
        fillCodeEditor(editor);

        const refresh = modal.querySelector('[data-r4v5-code-refresh]');
        const apply = modal.querySelector('[data-r4v5-code-apply]');
        const clearJs = modal.querySelector('[data-r4v5-code-clear-js]');

        if (!refresh.dataset.bound) {
            refresh.dataset.bound = '1';
            refresh.addEventListener('click', function () { fillCodeEditor(editor); });
        }

        if (!apply.dataset.bound) {
            apply.dataset.bound = '1';
            apply.addEventListener('click', function () { applyCodeEditor(editor); });
        }

        if (!clearJs.dataset.bound) {
            clearJs.dataset.bound = '1';
            clearJs.addEventListener('click', function () {
                setStoredCustomJs('');
                modal.querySelector('#r4v5CodeJs').value = '';
            });
        }

        modal.hidden = false;
        setTimeout(function () { modal.querySelector('#r4v5CodeHtml').focus(); }, 30);
    }

    function boot() {
        if (!window.grapesjs) {
            console.error('[R4 Editor V5] GrapesJS non caricato.');
            return;
        }

        const editor = window.grapesjs.init({
            container: '#' + cfg.canvasId,
            height: '100%',
            width: '100%',
            storageManager: false,
            fromElement: false,
            avoidInlineStyle: false,
            blockManager: { appendTo: '#' + cfg.blocksId },
            styleManager: { appendTo: '#' + cfg.stylesId },
            traitManager: { appendTo: '#' + cfg.traitsId },
            panels: { defaults: [] },
            deviceManager: {
                devices: [
                    { name: 'Desktop', width: '' },
                    { name: 'Tablet', width: '768px' },
                    { name: 'Mobile', width: '375px' }
                ]
            },
            canvas: { styles: [], scripts: [] }
        });

        window.R4EditorV5 = editor;

        addRegistryBlocks(editor);

        const project = parseJson(readField(cfg.jsonFieldId));
        const htmlCustomJs = extractCustomJs(readField(cfg.htmlFieldId)).js;
        setStoredCustomJs((project && project.r4v5CustomJs) || htmlCustomJs || '');

        if (project && typeof project === 'object') {
            try {
                editor.loadProjectData(project);
            } catch (error) {
                console.warn('[R4 Editor V5] loadProjectData fallito, uso HTML/CSS.', error);
                editor.setComponents(readField(cfg.htmlFieldId) || '<section style="padding:64px 24px;"><h1>Nuova pagina V5</h1><p>Inizia a scrivere.</p></section>');
                editor.setStyle(readField(cfg.cssFieldId) || '');
            }
        } else {
            editor.setComponents(readField(cfg.htmlFieldId) || '<section style="padding:64px 24px;"><h1>Nuova pagina V5</h1><p>Inizia a scrivere.</p></section>');
            editor.setStyle(readField(cfg.cssFieldId) || '');
        }

        bindToolbar(editor);
        bindForm(editor);
        installEditorUiGuards(editor);

        editor.on('load', function () {
            syncFields(editor);
            installEditorUiGuards(editor);
            if (window.R4V5EditorCodeRuntimeBridge && typeof window.R4V5EditorCodeRuntimeBridge.inject === 'function') {
                setTimeout(window.R4V5EditorCodeRuntimeBridge.inject, 250);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
