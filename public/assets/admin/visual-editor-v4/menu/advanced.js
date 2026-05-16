(function () {
    'use strict';

    const h = window.R4V4MenuHelpers;

    function editor() { return window.r4VisualEditorV4Instance || window.R4VisualEditorV4Editor || null; }
    function selected() { const e = editor(); return e ? e.getSelected() : null; }

    function parseInlineStyle(cssText) {
        const out = {};
        String(cssText || '').split(';').forEach((rule) => {
            const parts = rule.split(':');
            if (parts.length < 2) return;
            const prop = parts.shift().trim();
            const value = parts.join(':').trim();
            if (prop && value) out[prop] = value;
        });
        return out;
    }

    function normalizeUrl(value) {
        return String(value || '').trim();
    }

    function componentTag(component) {
        if (!component) return '';
        const tag = component.get('tagName') || component.get('tag') || '';
        return String(tag || '').toLowerCase();
    }

    function componentTypeLabel(component) {
        if (!component) return '';
        const tag = componentTag(component) || 'elemento';
        const type = component.get('type') || '';
        return type ? tag + ' / ' + type : tag;
    }

    function getStyleValue(component, prop) {
        if (!component) return '';
        const style = component.getStyle() || {};
        return style[prop] || '';
    }

    function setStyleValue(prop, value) {
        const component = selected();
        if (!component) return;
        const style = Object.assign({}, component.getStyle() || {});
        if (value) style[prop] = value;
        else delete style[prop];
        component.setStyle(style);
    }

    function hydrate(panel) {
        const component = selected();
        const attrs = component ? (component.getAttributes() || {}) : {};

        const typeField = panel.querySelector('[data-r4-selected-type]');
        if (typeField) typeField.value = component ? componentTypeLabel(component) : '';

        panel.querySelectorAll('[data-r4-attr]').forEach((field) => {
            field.value = attrs[field.dataset.r4Attr] || '';
        });

        panel.querySelectorAll('[data-r4-style]').forEach((field) => {
            field.value = component ? getStyleValue(component, field.dataset.r4Style) : '';
        });

        const classesField = panel.querySelector('[data-r4-classes]');
        if (classesField && component) {
            classesField.value = component.getClasses().join(' ');
        } else if (classesField) {
            classesField.value = '';
        }

        const hrefField = panel.querySelector('[data-r4-link-href]');
        const targetField = panel.querySelector('[data-r4-link-target]');
        if (hrefField) hrefField.value = attrs.href || attrs['data-r4-link'] || '';
        if (targetField) targetField.value = attrs.target || '';
    }

    function applyAttr(name, value) {
        const component = selected();
        if (!component) return;
        const attrs = Object.assign({}, component.getAttributes() || {});
        if (value) attrs[name] = value;
        else delete attrs[name];
        component.setAttributes(attrs);
    }

    function applyClasses(value) {
        const component = selected();
        if (!component) return;
        const classes = String(value || '').split(/\s+/).map((item) => item.trim()).filter(Boolean);
        component.setClass(classes);
    }

    function wrapSelectedWithLink(href, target) {
        const component = selected();
        const instance = editor();
        if (!component || !instance || !href) return;

        const attrs = Object.assign({}, component.getAttributes() || {});
        const tag = componentTag(component);

        if (tag === 'a') {
            attrs.href = href;
            if (target) attrs.target = target;
            else delete attrs.target;
            if (target === '_blank' && !attrs.rel) attrs.rel = 'noopener noreferrer';
            component.setAttributes(attrs);
            return;
        }

        if (tag === 'img') {
            attrs['data-r4-link'] = href;
            if (target) attrs['data-r4-link-target'] = target;
            else delete attrs['data-r4-link-target'];
            attrs.style = attrs.style || '';
            component.setAttributes(attrs);
            component.addStyle({ cursor: 'pointer' });
            return;
        }

        attrs['data-r4-link'] = href;
        if (target) attrs['data-r4-link-target'] = target;
        else delete attrs['data-r4-link-target'];
        component.setAttributes(attrs);
        component.addStyle({ cursor: 'pointer' });
    }

    function clearSelectedLink() {
        const component = selected();
        if (!component) return;
        const attrs = Object.assign({}, component.getAttributes() || {});
        delete attrs.href;
        delete attrs.target;
        delete attrs.rel;
        delete attrs['data-r4-link'];
        delete attrs['data-r4-link-target'];
        component.setAttributes(attrs);
    }

    function applyLink(panel) {
        const href = normalizeUrl(panel.querySelector('[data-r4-link-href]')?.value || '');
        const target = panel.querySelector('[data-r4-link-target]')?.value || '';
        if (!href) return;
        wrapSelectedWithLink(href, target);
        hydrate(panel);
    }

    function resetAdvanced() {
        const component = selected();
        if (!component) return;
        const attrs = Object.assign({}, component.getAttributes() || {});
        delete attrs.id;
        delete attrs.title;
        delete attrs['aria-label'];
        delete attrs.rel;
        delete attrs.href;
        delete attrs.target;
        delete attrs['data-r4-link'];
        delete attrs['data-r4-link-target'];
        component.setAttributes(attrs);
        component.setClass([]);
    }

    function resetQuickStyles() {
        const component = selected();
        if (!component) return;
        const style = Object.assign({}, component.getStyle() || {});
        [
            'color', 'font-size', 'font-weight', 'line-height', 'text-align', 'text-transform',
            'background-color', 'background', 'padding', 'margin', 'width', 'max-width', 'min-height',
            'display', 'gap', 'border-color', 'border', 'border-radius', 'box-shadow', 'opacity',
            'transform', 'transition', 'object-fit', 'object-position', 'cursor'
        ].forEach((prop) => delete style[prop]);
        component.setStyle(style);
    }

    function bindSelectionRefresh(panel) {
        const instance = editor();
        if (!instance || panel.dataset.r4AdvancedSelectionBound === '1') return;
        panel.dataset.r4AdvancedSelectionBound = '1';
        instance.on('component:selected', function () { hydrate(panel); });
        instance.on('component:deselected', function () { hydrate(panel); });
        instance.on('component:update', function () { hydrate(panel); });
    }

    window.R4V4SidebarMenu.register({
        key: 'advanced',
        label: 'Avanzate',
        order: 100,
        templateId: 'r4v4-menu-template-advanced',
        selectionOnly: true,
        mount(panel) {
            panel.innerHTML = h.templateHtml(this.templateId);

            panel.querySelectorAll('[data-r4-attr]').forEach((field) => {
                field.addEventListener('input', function () {
                    applyAttr(field.dataset.r4Attr, field.value.trim());
                });
            });

            panel.querySelectorAll('[data-r4-style]').forEach((field) => {
                field.addEventListener('input', function () {
                    setStyleValue(field.dataset.r4Style, field.value.trim());
                });
                field.addEventListener('change', function () {
                    setStyleValue(field.dataset.r4Style, field.value.trim());
                });
            });

            panel.querySelector('[data-r4-classes]')?.addEventListener('input', function (event) {
                applyClasses(event.target.value);
            });

            panel.querySelector('[data-r4-link-apply]')?.addEventListener('click', function () {
                applyLink(panel);
            });

            panel.querySelector('[data-r4-link-clear]')?.addEventListener('click', function () {
                clearSelectedLink();
                hydrate(panel);
            });

            panel.querySelector('[data-r4-advanced-apply-inline]')?.addEventListener('click', function () {
                const component = selected();
                const css = panel.querySelector('[data-r4-inline-style]')?.value || '';
                if (component) component.addStyle(parseInlineStyle(css));
                hydrate(panel);
            });

            panel.querySelector('[data-r4-style-reset]')?.addEventListener('click', function () {
                resetQuickStyles();
                hydrate(panel);
            });

            panel.querySelector('[data-r4-advanced-reset]')?.addEventListener('click', function () {
                resetAdvanced();
                hydrate(panel);
            });

            setTimeout(function () { hydrate(panel); bindSelectionRefresh(panel); }, 250);
        },
        onActivate(panel) {
            hydrate(panel);
            bindSelectionRefresh(panel);
        }
    });
})();
