(function () {
    'use strict';

    const SECTION_PROPS = [
        ['r4ColumnsDefault', 'Colonne generale', 'number'],
        ['r4LayoutDesktop', 'Disposizione desktop', 'select', [['horizontal', 'Orizzontale'], ['vertical', 'Verticale']]],
        ['r4ColumnsDesktop', 'Colonne desktop', 'number'],
        ['r4LayoutTablet', 'Disposizione tablet', 'select', [['horizontal', 'Orizzontale'], ['vertical', 'Verticale']]],
        ['r4ColumnsTablet', 'Colonne tablet', 'number'],
        ['r4LayoutMobile', 'Disposizione mobile', 'select', [['horizontal', 'Orizzontale'], ['vertical', 'Verticale']]],
        ['r4ColumnsMobile', 'Colonne mobile', 'number'],
        ['r4ColumnGap', 'Distanza colonne', 'number'],
        ['r4RowGap', 'Distanza righe', 'number'],
        ['r4PaddingTop', 'Padding top', 'number'],
        ['r4PaddingRight', 'Padding right', 'number'],
        ['r4PaddingBottom', 'Padding bottom', 'number'],
        ['r4PaddingLeft', 'Padding left', 'number'],
        ['r4MarginTop', 'Distanza top', 'number'],
        ['r4MarginBottom', 'Margine bottom', 'number'],
        ['r4MaxWidth', 'Larghezza max', 'number'],
        ['r4MinHeight', 'Altezza minima', 'text'],
        ['r4Background', 'Sfondo / gradiente', 'text']
    ];

    const COLUMN_PROPS = [
        ['r4ColumnPadding', 'Padding colonna', 'number'],
        ['r4ColumnBackground', 'Sfondo colonna', 'text'],
        ['r4ColumnBorder', 'Bordo colonna', 'text'],
        ['r4ColumnRadius', 'Radius colonna', 'number'],
        ['r4ColumnShadow', 'Ombra colonna', 'text']
    ];

    let collapsed = false;

    function editor() { return window.r4VisualEditorV4Instance || null; }
    function selected() { const e = editor(); return e && e.getSelected ? e.getSelected() : null; }

    function attrs(component) { return component && component.getAttributes ? (component.getAttributes() || {}) : {}; }

    function hasClass(component, className) {
        const classAttr = String(attrs(component).class || '');
        if (classAttr.split(/\s+/).includes(className)) return true;
        if (component && typeof component.getClasses === 'function') return (component.getClasses() || []).includes(className);
        return false;
    }

    function kind(component) {
        if (!component) return '';
        const type = component.get ? component.get('type') : '';
        const a = attrs(component);
        if (type === 'r4-section-grid' || a['data-r4-component'] === 'section-grid' || hasClass(component, 'r4v4-section-grid')) return 'section';
        if (type === 'r4-section-column' || a['data-r4-component'] === 'section-column' || hasClass(component, 'r4v4-section-column')) return 'column';
        return '';
    }

    function fieldHtml(prop, label, type, options) {
        if (type === 'select') {
            const opts = (options || []).map((o) => '<option value="' + o[0] + '">' + o[1] + '</option>').join('');
            return '<label class="r4v4-section-grid-panel__field r4v4-section-grid-panel__field--wide"><span>' + label + '</span><select data-r4-section-grid-prop="' + prop + '">' + opts + '</select></label>';
        }

        const wide = type === 'text' ? ' r4v4-section-grid-panel__field--wide' : '';
        return '<label class="r4v4-section-grid-panel__field' + wide + '"><span>' + label + '</span><input type="' + type + '" data-r4-section-grid-prop="' + prop + '"></label>';
    }

    function mountTarget() {
        const blocks = document.getElementById('r4v4-blocks');
        const blocksPanel = blocks ? blocks.closest('.r4v4-panel') : null;
        return blocksPanel || document.querySelector('.r4v4-sidebar-left') || document.body;
    }

    function insertPanel(panel, target) {
        const tabs = target.querySelector ? target.querySelector('.r4v4-left-tabs') : null;
        if (tabs && tabs.parentNode) {
            tabs.parentNode.insertBefore(panel, tabs.nextSibling);
            return;
        }

        const title = target.querySelector ? target.querySelector('.r4v4-panel-title') : null;
        if (title && title.parentNode) {
            title.parentNode.insertBefore(panel, title.nextSibling);
            return;
        }

        target.prepend(panel);
    }

    function createPanel() {
        if (document.getElementById('r4v4SectionGridPanel')) return true;
        const target = mountTarget();
        if (!target) return false;

        const panel = document.createElement('div');
        panel.id = 'r4v4SectionGridPanel';
        panel.className = 'r4v4-section-grid-panel';
        panel.hidden = true;
        panel.innerHTML = '' +
            '<style>' +
            '.r4v4-section-grid-panel{margin:10px 0 14px;padding:14px;border:1px solid rgba(59,130,246,.45);border-radius:18px;background:linear-gradient(180deg,#101827,#070b12);color:#e5e7eb;box-shadow:0 16px 36px rgba(0,0,0,.28);max-height:calc(100vh - 260px);overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;position:relative;z-index:5}' +
            '.r4v4-section-grid-panel.is-collapsed{max-height:none;overflow:hidden;padding:10px 12px}' +
            '.r4v4-section-grid-panel.is-collapsed .r4v4-section-grid-panel__hint,.r4v4-section-grid-panel.is-collapsed .r4v4-section-grid-panel__grid,.r4v4-section-grid-panel.is-collapsed .r4v4-section-grid-panel__actions{display:none}' +
            '.r4v4-section-grid-panel__head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}' +
            '.r4v4-section-grid-panel__title{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#fff}' +
            '.r4v4-section-grid-panel__toggle{width:28px;height:28px;border:1px solid rgba(148,163,184,.28);border-radius:9px;background:#020617;color:#e5e7eb;font-size:16px;font-weight:900;line-height:1;cursor:pointer}' +
            '.r4v4-section-grid-panel__hint{font-size:11px;line-height:1.45;color:#94a3b8;margin:0 0 12px}' +
            '.r4v4-section-grid-panel__grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;align-items:start}' +
            '.r4v4-section-grid-panel__field{display:flex;flex-direction:column;gap:4px;font-size:10px;font-weight:800;color:#cbd5e1;align-items:flex-start;min-width:0}' +
            '.r4v4-section-grid-panel__field span{display:block;max-width:100%;line-height:1.25}' +
            '.r4v4-section-grid-panel__field input,.r4v4-section-grid-panel__field select{height:32px;border-radius:10px;border:1px solid rgba(148,163,184,.32);background:#020617;color:#fff;padding:0 9px;font-size:11px;outline:none;box-sizing:border-box;width:100%}' +
            '.r4v4-section-grid-panel__field input[type="number"]{width:108px;max-width:108px}' +
            '.r4v4-section-grid-panel__field--wide{grid-column:1/-1;width:100%}' +
            '.r4v4-section-grid-panel__actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;position:sticky;bottom:-14px;padding-top:10px;padding-bottom:2px;background:linear-gradient(180deg,rgba(11,18,32,0),#070b12 32%)}' +
            '.r4v4-section-grid-panel__btn{border:0;border-radius:10px;padding:9px 10px;background:#2563eb;color:#fff;font-size:11px;font-weight:900;cursor:pointer}' +
            '.r4v4-section-grid-panel__btn.secondary{background:#334155}' +
            '</style>' +
            '<div class="r4v4-section-grid-panel__head"><div class="r4v4-section-grid-panel__title" data-r4-section-grid-title>Sezione avanzata</div><button type="button" class="r4v4-section-grid-panel__toggle" data-r4-section-grid-toggle title="Apri/chiudi pannello">×</button></div>' +
            '<p class="r4v4-section-grid-panel__hint" data-r4-section-grid-hint>Seleziona una sezione avanzata o una colonna per modificare layout e stile.</p>' +
            '<div class="r4v4-section-grid-panel__grid" data-r4-section-grid-fields></div>' +
            '<div class="r4v4-section-grid-panel__actions"><button type="button" class="r4v4-section-grid-panel__btn" data-r4-section-grid-add-column>Aggiungi colonna</button><button type="button" class="r4v4-section-grid-panel__btn secondary" data-r4-section-grid-select-parent>Seleziona sezione</button></div>';

        insertPanel(panel, target);
        bindPanel(panel);
        return true;
    }

    function renderFields(panel, currentKind) {
        const fields = panel.querySelector('[data-r4-section-grid-fields]');
        if (!fields) return;
        const props = currentKind === 'section' ? SECTION_PROPS : COLUMN_PROPS;
        fields.innerHTML = props.map((p) => fieldHtml(p[0], p[1], p[2] || 'text', p[3])).join('');
    }

    function readValue(component, prop) {
        const value = component && component.get ? component.get(prop) : '';
        return value === null || typeof value === 'undefined' ? '' : String(value);
    }

    function writeValue(component, prop, value) {
        if (!component || !component.set) return;
        component.set(prop, value);
        if (typeof component.trigger === 'function') component.trigger('change:' + prop, component, value);
        const instance = editor();
        if (instance && typeof instance.trigger === 'function') {
            instance.trigger('component:update', component);
            instance.trigger('update');
        }
    }

    function syncPanel() {
        const panel = document.getElementById('r4v4SectionGridPanel');
        if (!panel) return;

        const component = selected();
        const currentKind = kind(component);

        if (!currentKind) {
            panel.hidden = true;
            collapsed = false;
            panel.classList.remove('is-collapsed');
            return;
        }

        panel.hidden = false;
        panel.classList.toggle('is-collapsed', collapsed);
        panel.dataset.kind = currentKind;
        panel.dataset.cid = component.cid || '';

        const title = panel.querySelector('[data-r4-section-grid-title]');
        const hint = panel.querySelector('[data-r4-section-grid-hint]');
        const addBtn = panel.querySelector('[data-r4-section-grid-add-column]');
        const parentBtn = panel.querySelector('[data-r4-section-grid-select-parent]');
        const toggle = panel.querySelector('[data-r4-section-grid-toggle]');

        if (title) title.textContent = currentKind === 'section' ? 'Sezione avanzata' : 'Colonna avanzata';
        if (hint) hint.textContent = currentKind === 'section'
            ? 'Gestisci colonne generali, disposizione desktop/tablet/mobile, spaziature, sfondo e responsive.'
            : 'Gestisci sfondo, padding, bordo, radius e ombra della colonna selezionata.';
        if (toggle) toggle.textContent = collapsed ? '+' : '×';
        if (addBtn) addBtn.style.display = currentKind === 'section' ? '' : 'none';
        if (parentBtn) parentBtn.style.display = currentKind === 'column' ? '' : 'none';

        renderFields(panel, currentKind);
        panel.querySelectorAll('[data-r4-section-grid-prop]').forEach((field) => {
            field.value = readValue(component, field.getAttribute('data-r4-section-grid-prop'));
        });
    }

    function bindPanel(panel) {
        panel.querySelector('[data-r4-section-grid-toggle]')?.addEventListener('click', () => {
            collapsed = !collapsed;
            syncPanel();
        });

        panel.addEventListener('input', (event) => {
            const field = event.target.closest('[data-r4-section-grid-prop]');
            if (!field) return;
            const component = selected();
            if (!kind(component)) return;
            writeValue(component, field.getAttribute('data-r4-section-grid-prop'), field.value);
        });

        panel.addEventListener('change', (event) => {
            const field = event.target.closest('[data-r4-section-grid-prop]');
            if (!field) return;
            const component = selected();
            if (!kind(component)) return;
            writeValue(component, field.getAttribute('data-r4-section-grid-prop'), field.value);
        });

        panel.querySelector('[data-r4-section-grid-add-column]')?.addEventListener('click', () => {
            const component = selected();
            if (kind(component) !== 'section') return;
            const inner = component.find('.r4v4-section-grid-inner')[0];
            if (!inner) return;
            inner.append({
                type: 'r4-section-column',
                components: '<h3 style="font-size:26px;font-weight:900;letter-spacing:-.02em;margin:0 0 12px;color:#111827;">Nuova colonna</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Inserisci qui il contenuto della nuova colonna.</p>'
            });
            const instance = editor();
            if (instance && typeof instance.trigger === 'function') instance.trigger('update');
        });

        panel.querySelector('[data-r4-section-grid-select-parent]')?.addEventListener('click', () => {
            const component = selected();
            if (kind(component) !== 'column') return;
            let section = component.closest('[data-r4-component="section-grid"]');
            if (!section && typeof component.closest === 'function') section = component.closest('.r4v4-section-grid');
            const instance = editor();
            if (section && instance && instance.select) instance.select(section);
        });
    }

    function bootPanel() {
        if (!createPanel()) return false;
        const instance = editor();
        if (!instance) return false;
        if (instance.__r4SectionGridPanelBooted) return true;
        instance.__r4SectionGridPanelBooted = true;
        instance.on('component:selected', () => { collapsed = false; syncPanel(); });
        instance.on('component:deselected', syncPanel);
        instance.on('component:update', syncPanel);
        instance.on('load', syncPanel);
        syncPanel();
        return true;
    }

    document.addEventListener('DOMContentLoaded', () => {
        let attempts = 0;
        const timer = window.setInterval(() => {
            attempts++;
            if (bootPanel() || attempts > 80) window.clearInterval(timer);
        }, 150);
    });
})();
