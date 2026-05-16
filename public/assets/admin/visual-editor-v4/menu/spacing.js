(function () {
    'use strict';

    const h = window.R4V4MenuHelpers;

    const SECTION_LAYOUT_FIELDS = [
        ['r4ColumnsDefault', 'Colonne generale', 'number'],
        ['r4LayoutDesktop', 'Disposizione desktop', 'select', [['horizontal', 'Orizzontale'], ['vertical', 'Verticale']]],
        ['r4ColumnsDesktop', 'Colonne desktop', 'number'],
        ['r4LayoutTablet', 'Disposizione tablet', 'select', [['horizontal', 'Orizzontale'], ['vertical', 'Verticale']]],
        ['r4ColumnsTablet', 'Colonne tablet', 'number'],
        ['r4LayoutMobile', 'Disposizione mobile', 'select', [['horizontal', 'Orizzontale'], ['vertical', 'Verticale']]],
        ['r4ColumnsMobile', 'Colonne mobile', 'number'],
        ['r4ColumnGap', 'Distanza colonne', 'number'],
        ['r4RowGap', 'Distanza righe', 'number']
    ];

    function editor() {
        return window.r4VisualEditorV4Instance || null;
    }

    function selected() {
        const instance = editor();
        return instance ? instance.getSelected() : null;
    }

    function attrs(component) {
        return component && component.getAttributes ? (component.getAttributes() || {}) : {};
    }

    function hasClass(component, className) {
        const classAttr = String(attrs(component).class || '');
        if (classAttr.split(/\s+/).includes(className)) return true;
        if (component && typeof component.getClasses === 'function') return (component.getClasses() || []).includes(className);
        return false;
    }

    function isAdvancedSection(component) {
        if (!component) return false;
        const type = component.get ? component.get('type') : '';
        const a = attrs(component);
        return type === 'r4-section-grid' || a['data-r4-component'] === 'section-grid' || hasClass(component, 'r4v4-section-grid');
    }

    function normalizeCssValue(value, unit) {
        const raw = String(value || '').trim();
        if (!raw) return '';
        if (/^-?\d+(\.\d+)?$/.test(raw)) return raw + (unit || 'px');
        return raw;
    }

    function getStyleValue(component, prop) {
        if (!component) return '';
        const style = component.getStyle() || {};
        return style[prop] || '';
    }

    function numericPart(value) {
        const match = String(value || '').match(/^-?\d+(\.\d+)?/);
        return match ? match[0] : '';
    }

    function applyStyle(prop, value) {
        const component = selected();
        if (!component) return;
        component.addStyle({ [prop]: value });
    }

    function resetProps(props) {
        const component = selected();
        if (!component) return;
        const reset = {};
        props.forEach((prop) => { reset[prop] = ''; });
        component.addStyle(reset);
    }

    function readComponentProp(component, prop) {
        const value = component && component.get ? component.get(prop) : '';
        return value === null || typeof value === 'undefined' ? '' : String(value);
    }

    function writeComponentProp(component, prop, value) {
        if (!component || !component.set) return;
        component.set(prop, value);
        if (typeof component.trigger === 'function') component.trigger('change:' + prop, component, value);
        const instance = editor();
        if (instance && typeof instance.trigger === 'function') {
            instance.trigger('component:update', component);
            instance.trigger('update');
        }
    }

    function sectionFieldHtml(field) {
        const prop = field[0];
        const label = field[1];
        const type = field[2];
        const options = field[3] || [];

        if (type === 'select') {
            return '<label class="r4v4-section-inline-field r4v4-section-inline-field--wide"><span>' + label + '</span><select data-r4-section-inline-prop="' + prop + '">' +
                options.map((option) => '<option value="' + option[0] + '">' + option[1] + '</option>').join('') +
                '</select></label>';
        }

        return '<label class="r4v4-section-inline-field"><span>' + label + '</span><input type="number" min="1" max="6" data-r4-section-inline-prop="' + prop + '"></label>';
    }

    function ensureSectionInlinePanel(panel) {
        let box = panel.querySelector('[data-r4-section-inline-panel]');
        if (box) return box;

        box = document.createElement('div');
        box.className = 'r4v4-section-inline-panel';
        box.setAttribute('data-r4-section-inline-panel', '1');
        box.innerHTML = '' +
            '<style>' +
            '.r4v4-section-inline-panel{display:none;margin:0 0 14px;padding:14px;border:1px solid rgba(59,130,246,.45);border-radius:16px;background:#07111f;color:#e5e7eb;box-shadow:0 12px 30px rgba(0,0,0,.22)}' +
            '.r4v4-section-inline-panel.is-visible{display:block}' +
            '.r4v4-section-inline-title{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#fff;margin:0 0 5px}' +
            '.r4v4-section-inline-help{font-size:11px;line-height:1.45;color:#94a3b8;margin:0 0 12px}' +
            '.r4v4-section-inline-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 12px}' +
            '.r4v4-section-inline-field{display:flex;flex-direction:column;gap:4px;font-size:10px;font-weight:800;color:#cbd5e1;min-width:0}' +
            '.r4v4-section-inline-field--wide{grid-column:1/-1}' +
            '.r4v4-section-inline-field input,.r4v4-section-inline-field select{height:32px;border-radius:10px;border:1px solid rgba(148,163,184,.32);background:#020617;color:#fff;padding:0 9px;font-size:11px;outline:none;box-sizing:border-box;width:100%}' +
            '.r4v4-section-inline-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}' +
            '.r4v4-section-inline-btn{border:0;border-radius:10px;padding:9px 10px;background:#2563eb;color:#fff;font-size:11px;font-weight:900;cursor:pointer}' +
            '</style>' +
            '<div class="r4v4-section-inline-title">Sezione avanzata</div>' +
            '<p class="r4v4-section-inline-help">Controlli layout della sezione selezionata: colonne, disposizione e distanze responsive.</p>' +
            '<div class="r4v4-section-inline-grid">' + SECTION_LAYOUT_FIELDS.map(sectionFieldHtml).join('') + '</div>' +
            '<div class="r4v4-section-inline-actions"><button type="button" class="r4v4-section-inline-btn" data-r4-section-inline-add-column>Aggiungi colonna</button></div>';

        panel.prepend(box);

        box.addEventListener('input', function (event) {
            const field = event.target.closest('[data-r4-section-inline-prop]');
            if (!field) return;
            const component = selected();
            if (!isAdvancedSection(component)) return;
            writeComponentProp(component, field.getAttribute('data-r4-section-inline-prop'), field.value);
        });

        box.addEventListener('change', function (event) {
            const field = event.target.closest('[data-r4-section-inline-prop]');
            if (!field) return;
            const component = selected();
            if (!isAdvancedSection(component)) return;
            writeComponentProp(component, field.getAttribute('data-r4-section-inline-prop'), field.value);
        });

        box.querySelector('[data-r4-section-inline-add-column]')?.addEventListener('click', function () {
            const component = selected();
            if (!isAdvancedSection(component) || typeof component.find !== 'function') return;
            const inner = component.find('.r4v4-section-grid-inner')[0];
            if (!inner) return;

            inner.append({
                type: 'r4-section-column',
                components: '<h3 style="font-size:26px;font-weight:900;letter-spacing:-.02em;margin:0 0 12px;color:#111827;">Nuova colonna</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Inserisci qui il contenuto della nuova colonna.</p>'
            });

            const instance = editor();
            if (instance && typeof instance.trigger === 'function') instance.trigger('update');
        });

        return box;
    }

    function hydrateSectionInlinePanel(panel, component) {
        const box = ensureSectionInlinePanel(panel);
        const visible = isAdvancedSection(component);
        box.classList.toggle('is-visible', visible);

        if (!visible) return;

        box.querySelectorAll('[data-r4-section-inline-prop]').forEach((field) => {
            field.value = readComponentProp(component, field.getAttribute('data-r4-section-inline-prop'));
        });
    }

    function hydrate(panel) {
        const component = selected();
        hydrateSectionInlinePanel(panel, component);

        panel.querySelectorAll('[data-r4-spacing]').forEach((field) => {
            field.value = numericPart(getStyleValue(component, field.dataset.r4Spacing));
        });
        panel.querySelectorAll('[data-r4-style-prop]').forEach((field) => {
            field.value = getStyleValue(component, field.dataset.r4StyleProp);
        });
    }

    function bindSelectionRefresh(panel) {
        const instance = editor();
        if (!instance || panel.dataset.r4SpacingSelectionBound === '1') return;
        panel.dataset.r4SpacingSelectionBound = '1';
        instance.on('component:selected', function () { hydrate(panel); });
        instance.on('component:deselected', function () { hydrate(panel); });
        instance.on('component:update', function () { hydrate(panel); });
    }

    window.R4V4SidebarMenu.register({
        key: 'spacing',
        label: 'Spaziatura',
        order: 50,
        templateId: 'r4v4-menu-template-spacing',
        selectionOnly: true,
        mount(panel) {
            panel.innerHTML = h.templateHtml(this.templateId);
            ensureSectionInlinePanel(panel);

            panel.querySelectorAll('[data-r4-spacing]').forEach((field) => {
                field.addEventListener('input', function () {
                    const group = field.dataset.r4Spacing.startsWith('margin') ? 'margin' : 'padding';
                    const unit = panel.querySelector('[data-r4-spacing-unit="' + group + '"]')?.value || 'px';
                    applyStyle(field.dataset.r4Spacing, normalizeCssValue(field.value, unit));
                });
            });

            panel.querySelectorAll('[data-r4-style-prop]').forEach((field) => {
                field.addEventListener('input', function () {
                    applyStyle(field.dataset.r4StyleProp, field.value.trim());
                });
            });

            panel.querySelector('[data-r4-spacing-reset="margin"]')?.addEventListener('click', function () {
                resetProps(['margin-top', 'margin-right', 'margin-bottom', 'margin-left']);
                hydrate(panel);
            });

            panel.querySelector('[data-r4-spacing-reset="padding"]')?.addEventListener('click', function () {
                resetProps(['padding-top', 'padding-right', 'padding-bottom', 'padding-left']);
                hydrate(panel);
            });

            setTimeout(function () {
                hydrate(panel);
                bindSelectionRefresh(panel);
            }, 250);
        },
        onActivate(panel) {
            ensureSectionInlinePanel(panel);
            hydrate(panel);
            bindSelectionRefresh(panel);
        }
    });
})();
