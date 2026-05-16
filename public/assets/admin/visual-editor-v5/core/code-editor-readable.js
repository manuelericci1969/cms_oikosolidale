(function () {
    'use strict';

    const INDENT = '  ';

    function escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function formatHtml(input) {
        let html = String(input || '').trim();
        if (!html) return '';

        const preserve = [];
        html = html.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, function (match) {
            const token = '___R4V5_PRESERVE_' + preserve.length + '___';
            preserve.push(match.trim());
            return token;
        });

        html = html
            .replace(/>\s+</g, '><')
            .replace(/(<[^>]+>)/g, '\n$1\n')
            .replace(/\n{2,}/g, '\n')
            .trim();

        preserve.forEach(function (block, index) {
            html = html.replace(new RegExp(escapeRegExp('___R4V5_PRESERVE_' + index + '___'), 'g'), block);
        });

        const voidTags = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
        const lines = html.split('\n').map(function (line) { return line.trim(); }).filter(Boolean);
        let level = 0;

        return lines.map(function (line) {
            const closing = /^<\//.test(line);
            const fullTag = line.match(/^<\/?([a-zA-Z0-9:-]+)/);
            const tag = fullTag ? fullTag[1].toLowerCase() : '';
            const selfClosing = /\/>$/.test(line) || voidTags.has(tag) || /^<!--/.test(line) || /^<!/.test(line);
            const sameLinePair = /^<([a-zA-Z0-9:-]+)\b[^>]*>.*<\/\1>$/.test(line);

            if (closing) level = Math.max(0, level - 1);
            const output = INDENT.repeat(level) + line;
            if (!closing && !selfClosing && !sameLinePair && /^<[^/!][^>]*>$/.test(line)) level++;
            return output;
        }).join('\n');
    }

    function formatCss(input) {
        let css = String(input || '').trim();
        if (!css) return '';

        css = css
            .replace(/\/\*([\s\S]*?)\*\//g, function (match) { return '\n' + match.trim() + '\n'; })
            .replace(/\s*{\s*/g, ' {\n')
            .replace(/;\s*/g, ';\n')
            .replace(/\s*}\s*/g, '\n}\n\n')
            .replace(/,\s*/g, ',\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();

        const lines = css.split('\n');
        let level = 0;
        return lines.map(function (raw) {
            const line = raw.trim();
            if (!line) return '';
            if (line === '}') level = Math.max(0, level - 1);
            const output = INDENT.repeat(level) + line;
            if (line.endsWith('{')) level++;
            return output;
        }).join('\n').replace(/\n{3,}/g, '\n\n');
    }

    function formatJs(input) {
        let js = String(input || '').trim();
        if (!js) return '';

        js = js
            .replace(/\s*{\s*/g, ' {\n')
            .replace(/\s*}\s*/g, '\n}\n')
            .replace(/;\s*/g, ';\n')
            .replace(/\s*,\s*/g, ', ')
            .replace(/\n{3,}/g, '\n\n')
            .trim();

        const lines = js.split('\n');
        let level = 0;
        return lines.map(function (raw) {
            const line = raw.trim();
            if (!line) return '';
            if (/^[}\])]/.test(line)) level = Math.max(0, level - 1);
            const output = INDENT.repeat(level) + line;
            if (/[{[(]$/.test(line) && !/^\s*\/\//.test(line)) level++;
            return output;
        }).join('\n').replace(/\n{3,}/g, '\n\n');
    }

    function activePanel(modal) {
        return modal.querySelector('.r4v5-code-panel:not([hidden]) textarea');
    }

    function textareas(modal) {
        return {
            html: modal.querySelector('#r4v5CodeHtml'),
            css: modal.querySelector('#r4v5CodeCss'),
            js: modal.querySelector('#r4v5CodeJs')
        };
    }

    function formatAll(modal) {
        const areas = textareas(modal);
        if (areas.html) areas.html.value = formatHtml(areas.html.value);
        if (areas.css) areas.css.value = formatCss(areas.css.value);
        if (areas.js) areas.js.value = formatJs(areas.js.value);
        updateStats(modal);
    }

    function formatActive(modal) {
        const area = activePanel(modal);
        if (!area) return;
        if (area.id === 'r4v5CodeHtml') area.value = formatHtml(area.value);
        if (area.id === 'r4v5CodeCss') area.value = formatCss(area.value);
        if (area.id === 'r4v5CodeJs') area.value = formatJs(area.value);
        updateStats(modal);
    }

    function updateStats(modal) {
        const stats = modal.querySelector('[data-r4v5-code-stats]');
        const area = activePanel(modal);
        if (!stats || !area) return;
        const lines = area.value ? area.value.split('\n').length : 0;
        const chars = area.value ? area.value.length : 0;
        stats.textContent = lines + ' righe · ' + chars + ' caratteri';
    }

    function enhanceModal(modal) {
        if (!modal || modal.dataset.r4v5Readable === '1') return;
        modal.dataset.r4v5Readable = '1';

        const style = document.createElement('style');
        style.id = 'r4v5-code-readable-style';
        style.textContent = [
            '.r4v5-code-dialog{width:min(1440px,98vw)!important;height:min(900px,94vh)!important;border-radius:18px!important;overflow:hidden}',
            '.r4v5-code-head{padding:16px 18px!important}',
            '.r4v5-code-body{background:#0b1120!important}',
            '.r4v5-code-panel{position:relative!important}',
            '.r4v5-code-editor{font-size:14px!important;line-height:1.75!important;padding:22px 24px!important;color:#dbeafe!important;background:linear-gradient(90deg,#111827 0,#111827 56px,#0b1120 56px,#0b1120 100%)!important;border-top:1px solid rgba(148,163,184,.12)!important;white-space:pre!important;overflow:auto!important}',
            '.r4v5-code-editor::selection{background:rgba(13,110,253,.38)!important}',
            '.r4v5-code-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;border-bottom:1px solid rgba(148,163,184,.16);background:#020617}',
            '.r4v5-code-tools-left,.r4v5-code-tools-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}',
            '.r4v5-code-mini-btn{border:1px solid rgba(148,163,184,.28);background:#111827;color:#fff;border-radius:999px;padding:8px 11px;font-size:11px;font-weight:900;cursor:pointer}',
            '.r4v5-code-mini-btn:hover{background:#1f2937}',
            '.r4v5-code-mini-btn-primary{background:#0d6efd;border-color:#0d6efd}',
            '.r4v5-code-stats{font-size:11px;color:#94a3b8;font-weight:800}',
            '.r4v5-code-note{max-width:520px;line-height:1.45}'
        ].join('');
        document.head.appendChild(style);

        const body = modal.querySelector('.r4v5-code-body');
        if (body && !modal.querySelector('.r4v5-code-tools')) {
            const tools = document.createElement('div');
            tools.className = 'r4v5-code-tools';
            tools.innerHTML = [
                '<div class="r4v5-code-tools-left">',
                    '<button type="button" class="r4v5-code-mini-btn r4v5-code-mini-btn-primary" data-r4v5-format-active>Formatta tab attiva</button>',
                    '<button type="button" class="r4v5-code-mini-btn" data-r4v5-format-all>Formatta tutto</button>',
                    '<button type="button" class="r4v5-code-mini-btn" data-r4v5-code-wrap>Testo a capo</button>',
                '</div>',
                '<div class="r4v5-code-tools-right"><span class="r4v5-code-stats" data-r4v5-code-stats>0 righe · 0 caratteri</span></div>'
            ].join('');
            body.parentNode.insertBefore(tools, body);
        }

        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-r4v5-format-active]')) formatActive(modal);
            if (event.target.closest('[data-r4v5-format-all]')) formatAll(modal);
            if (event.target.closest('[data-r4v5-code-wrap]')) {
                modal.querySelectorAll('.r4v5-code-editor').forEach(function (area) {
                    area.style.whiteSpace = area.style.whiteSpace === 'pre-wrap' ? 'pre' : 'pre-wrap';
                });
            }
        });

        modal.addEventListener('input', function (event) {
            if (event.target.classList && event.target.classList.contains('r4v5-code-editor')) updateStats(modal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-r4v5-code-tab]')) setTimeout(function () { updateStats(modal); }, 30);
        });
    }

    function watchCodeModal() {
        const enhanceIfReady = function () {
            const modal = document.getElementById('r4v5CodeEditorModal');
            if (!modal) return;
            enhanceModal(modal);

            const observer = new MutationObserver(function () {
                if (!modal.hidden) {
                    setTimeout(function () {
                        formatAll(modal);
                        updateStats(modal);
                    }, 40);
                }
            });
            observer.observe(modal, { attributes: true, attributeFilter: ['hidden'] });
        };

        enhanceIfReady();
        const rootObserver = new MutationObserver(enhanceIfReady);
        rootObserver.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchCodeModal);
    } else {
        watchCodeModal();
    }
})();
