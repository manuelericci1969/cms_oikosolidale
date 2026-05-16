(function () {
    'use strict';

    const MAX_LABEL = 52;

    function getEditor() {
        return window.R4EditorV5 || null;
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function styleOnce() {
        if (document.getElementById('r4v5-layers-style')) return;

        const style = document.createElement('style');
        style.id = 'r4v5-layers-style';
        style.textContent = [
            '.r4v5-structure-modal{position:fixed;right:24px;top:86px;z-index:1900;width:min(390px,calc(100vw - 40px));height:min(680px,calc(100vh - 116px));display:flex;flex-direction:column;background:#020617;color:#e5e7eb;border:1px solid rgba(148,163,184,.30);border-radius:18px;box-shadow:0 28px 80px rgba(0,0,0,.48);overflow:hidden;resize:both;min-width:310px;min-height:360px;max-width:calc(100vw - 24px);max-height:calc(100vh - 24px)}',
            '.r4v5-structure-modal[hidden]{display:none!important}',
            '.r4v5-structure-modal.is-dragging{user-select:none;box-shadow:0 34px 100px rgba(0,0,0,.62),0 0 0 1px rgba(56,189,248,.28)}',
            '.r4v5-structure-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 15px;border-bottom:1px solid rgba(148,163,184,.18);background:linear-gradient(135deg,#020617,#0f172a);cursor:move}',
            '.r4v5-structure-title{font-size:14px;font-weight:950;color:#fff;line-height:1}.r4v5-structure-subtitle{font-size:11px;color:#94a3b8;margin-top:5px;line-height:1.35}',
            '.r4v5-structure-close{border:0;background:transparent;color:#cbd5e1;font-size:26px;line-height:1;cursor:pointer;padding:2px 4px}.r4v5-structure-close:hover{color:#fff}',
            '.r4v5-structure-toolbar{display:flex;gap:8px;align-items:center;padding:10px;border-bottom:1px solid rgba(148,163,184,.16);background:#020617}',
            '.r4v5-layers-search{width:100%;height:34px;border:1px solid #334155;background:#0f172a;color:#e5e7eb;border-radius:999px;padding:7px 11px;font-size:12px}',
            '.r4v5-layers-btn{border:1px solid rgba(148,163,184,.25);background:#111827;color:#fff;border-radius:999px;padding:8px 10px;font-size:11px;font-weight:900;cursor:pointer;white-space:nowrap}',
            '.r4v5-layers-btn:hover{background:#1f2937}',
            '.r4v5-structure-selected{display:grid;gap:4px;padding:9px 11px;border-bottom:1px solid rgba(148,163,184,.14);background:#0b1120}',
            '.r4v5-structure-selected strong{font-size:10px;color:#93c5fd;text-transform:uppercase;letter-spacing:.08em}.r4v5-structure-selected span{font-size:12px;color:#e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
            '.r4v5-layers-tree{flex:1;min-height:0;display:grid;align-content:start;gap:5px;overflow:auto;padding:10px;background:#020617}',
            '.r4v5-layer-row{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:7px;min-height:36px;border:1px solid rgba(148,163,184,.16);background:#0f172a;color:#cbd5e1;border-radius:11px;padding:7px 9px;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease,box-shadow .15s ease,transform .15s ease}',
            '.r4v5-layer-row:hover{background:#172033;border-color:rgba(148,163,184,.36);color:#fff;transform:translateX(2px)}',
            '.r4v5-layer-row.is-selected{background:linear-gradient(135deg,#0b2f66,#123b7d);border-color:#60a5fa;color:#fff;box-shadow:0 0 0 1px rgba(96,165,250,.28),inset 5px 0 0 #38bdf8}',
            '.r4v5-layer-row.is-selected .r4v5-layer-label{color:#fff}.r4v5-layer-row.is-selected .r4v5-layer-meta{color:#bfdbfe}.r4v5-layer-row.is-selected .r4v5-layer-tag{background:#dbeafe;color:#0f172a;border-color:#dbeafe}',
            '.r4v5-layer-row.is-hidden-by-search{display:none}',
            '.r4v5-layer-depth{width:calc(var(--r4v5-layer-depth,0) * 13px);height:1px;position:relative}',
            '.r4v5-layer-depth::after{content:"";position:absolute;right:0;top:50%;width:calc(var(--r4v5-layer-depth,0) * 13px);height:1px;background:rgba(148,163,184,.22)}',
            '.r4v5-layer-main{min-width:0;display:grid;gap:3px}',
            '.r4v5-layer-label{font-size:12px;font-weight:950;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#e5e7eb}',
            '.r4v5-layer-meta{font-size:10px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
            '.r4v5-layer-tag{font-size:9px;font-weight:950;color:#93c5fd;background:rgba(37,99,235,.15);border:1px solid rgba(96,165,250,.18);border-radius:999px;padding:3px 6px;text-transform:uppercase}',
            '.r4v5-layer-empty{border:1px dashed rgba(148,163,184,.28);border-radius:14px;padding:20px;text-align:center;color:#94a3b8;font-size:12px;line-height:1.55;background:#0f172a}',
            '.r4v5-structure-foot{padding:9px 11px;border-top:1px solid rgba(148,163,184,.14);background:#020617;color:#94a3b8;font-size:10px;line-height:1.45}',
            '.r4v5-btn[data-r4v5-command="layers"].is-active{color:#fff;box-shadow:inset 0 -3px 0 #38bdf8;background:#111827}',
            '@media(max-width:860px){.r4v5-structure-modal{right:10px;left:10px;top:76px;width:auto;height:calc(100vh - 92px);border-radius:16px;resize:none}}'
        ].join('');
        document.head.appendChild(style);
    }

    function ensureToolbarButton() {
        const actions = document.querySelector('.r4v5-actions');
        if (!actions || actions.querySelector('[data-r4v5-command="layers"]')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'r4v5-btn r4v5-btn-light';
        button.dataset.r4v5Command = 'layers';
        button.textContent = 'Struttura';

        const codeBtn = actions.querySelector('[data-r4v5-command="code"]');
        const mediaBtn = actions.querySelector('[data-r4v5-command="media"]');
        const anchor = codeBtn || mediaBtn;
        if (anchor && anchor.nextSibling) actions.insertBefore(button, anchor.nextSibling);
        else actions.appendChild(button);
    }

    function removeOldSidebarTab() {
        document.querySelectorAll('[data-r4v5-left-tab="layers"],[data-r4v5-left-panel="layers"]').forEach(function (el) {
            el.remove();
        });
        const tabs = document.querySelector('.r4v5-left-tabs');
        if (tabs) tabs.classList.remove('r4v5-left-tabs--five');
    }

    function ensureModal() {
        let modal = byId('r4v5StructureModal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'r4v5StructureModal';
        modal.className = 'r4v5-structure-modal';
        modal.hidden = true;
        modal.innerHTML = [
            '<div class="r4v5-structure-head" data-r4v5-layers-drag-handle>',
                '<div><div class="r4v5-structure-title">Struttura pagina</div><div class="r4v5-structure-subtitle">Trascina questa barra per spostare il pannello.</div></div>',
                '<button type="button" class="r4v5-structure-close" data-r4v5-layers-close aria-label="Chiudi">×</button>',
            '</div>',
            '<div class="r4v5-structure-toolbar">',
                '<input type="search" class="r4v5-layers-search" id="r4v5LayersSearch" placeholder="Cerca elemento...">',
                '<button type="button" class="r4v5-layers-btn" data-r4v5-layers-refresh>Ricarica</button>',
            '</div>',
            '<div class="r4v5-structure-selected"><strong>Elemento selezionato</strong><span id="r4v5StructureSelectedLabel">Nessun elemento selezionato</span></div>',
            '<div class="r4v5-layers-tree" id="r4v5LayersTree"></div>',
            '<div class="r4v5-structure-foot">La riga blu indica l’elemento selezionato. Clicca una riga per selezionarla anche nel canvas.</div>'
        ].join('');
        document.body.appendChild(modal);
        makeModalDraggable(modal);
        return modal;
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function makeModalDraggable(modal) {
        if (!modal || modal.dataset.r4v5Draggable === '1') return;
        modal.dataset.r4v5Draggable = '1';

        let dragging = false;
        let startX = 0;
        let startY = 0;
        let startLeft = 0;
        let startTop = 0;

        const handle = modal.querySelector('[data-r4v5-layers-drag-handle]');
        if (!handle) return;

        handle.addEventListener('pointerdown', function (event) {
            if (event.target.closest('[data-r4v5-layers-close]')) return;
            dragging = true;
            const rect = modal.getBoundingClientRect();
            startX = event.clientX;
            startY = event.clientY;
            startLeft = rect.left;
            startTop = rect.top;
            modal.style.left = rect.left + 'px';
            modal.style.top = rect.top + 'px';
            modal.style.right = 'auto';
            modal.classList.add('is-dragging');
            handle.setPointerCapture && handle.setPointerCapture(event.pointerId);
            event.preventDefault();
        });

        handle.addEventListener('pointermove', function (event) {
            if (!dragging) return;
            const rect = modal.getBoundingClientRect();
            const nextLeft = clamp(startLeft + (event.clientX - startX), 8, window.innerWidth - rect.width - 8);
            const nextTop = clamp(startTop + (event.clientY - startY), 8, window.innerHeight - rect.height - 8);
            modal.style.left = nextLeft + 'px';
            modal.style.top = nextTop + 'px';
            modal.style.right = 'auto';
        });

        const stop = function (event) {
            if (!dragging) return;
            dragging = false;
            modal.classList.remove('is-dragging');
            try { handle.releasePointerCapture && handle.releasePointerCapture(event.pointerId); } catch (error) {}
        };

        handle.addEventListener('pointerup', stop);
        handle.addEventListener('pointercancel', stop);
        window.addEventListener('resize', function () {
            const rect = modal.getBoundingClientRect();
            if (modal.hidden) return;
            modal.style.left = clamp(rect.left, 8, window.innerWidth - rect.width - 8) + 'px';
            modal.style.top = clamp(rect.top, 8, window.innerHeight - rect.height - 8) + 'px';
            modal.style.right = 'auto';
        });
    }

    function setButtonState(open) {
        const button = document.querySelector('[data-r4v5-command="layers"]');
        if (button) button.classList.toggle('is-active', !!open);
    }

    function openModal() {
        const modal = ensureModal();
        modal.hidden = false;
        setButtonState(true);
        renderLayers();
        const input = byId('r4v5LayersSearch');
        if (input) setTimeout(function () { input.focus(); }, 50);
    }

    function closeModal() {
        const modal = ensureModal();
        modal.hidden = true;
        setButtonState(false);
    }

    function toggleModal() {
        const modal = ensureModal();
        if (modal.hidden) openModal();
        else closeModal();
    }

    function componentTag(component) {
        if (!component) return 'el';
        const tag = (component.get && component.get('tagName')) || '';
        return String(tag || 'div').toLowerCase();
    }

    function componentClasses(component) {
        if (!component || !component.getClasses) return '';
        return component.getClasses().slice(0, 2).join('.');
    }

    function componentText(component) {
        if (!component || !component.get) return '';
        const type = component.get('type') || '';
        const content = component.get('content') || '';
        if (content) return String(content).replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
        if (type) return String(type);
        return '';
    }

    function readableLabel(component) {
        const tag = componentTag(component);
        const text = componentText(component);
        const classes = componentClasses(component);
        let label = '';

        if (tag === 'section') label = 'Sezione';
        else if (tag === 'header') label = 'Header';
        else if (tag === 'footer') label = 'Footer';
        else if (tag === 'main') label = 'Main';
        else if (tag === 'article') label = 'Articolo';
        else if (tag === 'div') label = classes ? '.' + classes : 'Blocco';
        else if (/^h[1-6]$/.test(tag)) label = tag.toUpperCase();
        else if (tag === 'p') label = 'Testo';
        else if (tag === 'a') label = 'Link/Bottone';
        else if (tag === 'img') label = 'Immagine';
        else if (tag === 'ul' || tag === 'ol') label = 'Lista';
        else label = tag;

        if (text && !['div', 'section', 'header', 'footer', 'main', 'article'].includes(tag)) {
            label += ' · ' + text;
        }

        if (label.length > MAX_LABEL) label = label.slice(0, MAX_LABEL - 1) + '…';
        return label;
    }

    function metaLabel(component) {
        const tag = componentTag(component);
        const classes = componentClasses(component);
        const id = component && component.getId ? component.getId() : '';
        const parts = [tag];
        if (id) parts.push('#' + id);
        if (classes) parts.push('.' + classes);
        return parts.join(' ');
    }

    function walk(component, depth, rows) {
        if (!component || !component.components) return;
        component.components().forEach(function (child) {
            rows.push({ component: child, depth: depth });
            walk(child, depth + 1, rows);
        });
    }

    function rowsFromEditor(editor) {
        const wrapper = editor.DomComponents && editor.DomComponents.getWrapper ? editor.DomComponents.getWrapper() : null;
        const rows = [];
        walk(wrapper, 0, rows);
        return rows;
    }

    function updateSelectedLabel(selected) {
        const label = byId('r4v5StructureSelectedLabel');
        if (!label) return;
        label.textContent = selected ? readableLabel(selected) + ' — ' + metaLabel(selected) : 'Nessun elemento selezionato';
    }

    function renderLayers() {
        styleOnce();
        ensureToolbarButton();
        removeOldSidebarTab();
        const modal = ensureModal();
        const editor = getEditor();
        const tree = byId('r4v5LayersTree');
        if (!tree || !editor) return;

        const rows = rowsFromEditor(editor);
        const selected = editor.getSelected ? editor.getSelected() : null;
        updateSelectedLabel(selected);

        if (!rows.length) {
            tree.innerHTML = '<div class="r4v5-layer-empty">La pagina non contiene ancora elementi.<br>Trascina un widget nel canvas per iniziare.</div>';
            return;
        }

        tree.innerHTML = '';
        rows.forEach(function (row, index) {
            const component = row.component;
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'r4v5-layer-row' + (component === selected ? ' is-selected' : '');
            item.style.setProperty('--r4v5-layer-depth', row.depth);
            item.dataset.r4v5LayerIndex = String(index);
            item.dataset.r4v5LayerSearch = (readableLabel(component) + ' ' + metaLabel(component)).toLowerCase();
            item.innerHTML = [
                '<span class="r4v5-layer-depth"></span>',
                '<span class="r4v5-layer-main">',
                    '<span class="r4v5-layer-label">' + escapeHtml(readableLabel(component)) + '</span>',
                    '<span class="r4v5-layer-meta">' + escapeHtml(metaLabel(component)) + '</span>',
                '</span>',
                '<span class="r4v5-layer-tag">' + escapeHtml(componentTag(component)) + '</span>'
            ].join('');
            item.addEventListener('click', function () {
                editor.select(component);
                try {
                    const el = component.getEl && component.getEl();
                    if (el && el.scrollIntoView) el.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
                } catch (error) {}
                renderLayers();
            });
            tree.appendChild(item);
        });

        applySearch();
        if (modal && !modal.hidden) scrollSelectedIntoView();
    }

    function scrollSelectedIntoView() {
        const selected = document.querySelector('.r4v5-layer-row.is-selected');
        if (selected && selected.scrollIntoView) {
            selected.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    }

    function applySearch() {
        const input = byId('r4v5LayersSearch');
        const query = input ? input.value.trim().toLowerCase() : '';
        document.querySelectorAll('.r4v5-layer-row').forEach(function (row) {
            row.classList.toggle('is-hidden-by-search', query !== '' && !row.dataset.r4v5LayerSearch.includes(query));
        });
    }

    function bindControls() {
        document.addEventListener('input', function (event) {
            if (event.target && event.target.id === 'r4v5LayersSearch') applySearch();
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-r4v5-command="layers"]')) {
                event.preventDefault();
                toggleModal();
            }
            if (event.target.closest('[data-r4v5-layers-refresh]')) renderLayers();
            if (event.target.closest('[data-r4v5-layers-close]')) closeModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const modal = byId('r4v5StructureModal');
                if (modal && !modal.hidden) closeModal();
            }
        });
    }

    function boot() {
        styleOnce();
        ensureToolbarButton();
        ensureModal();
        removeOldSidebarTab();
        bindControls();

        const wait = setInterval(function () {
            const editor = getEditor();
            if (!editor) return;
            clearInterval(wait);

            renderLayers();
            editor.on('load component:add component:remove component:update component:selected component:deselected', function () {
                const modal = byId('r4v5StructureModal');
                if (modal && !modal.hidden) renderLayers();
                else updateSelectedLabel(editor.getSelected ? editor.getSelected() : null);
            });
        }, 120);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
