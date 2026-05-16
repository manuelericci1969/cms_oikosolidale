(function () {
    'use strict';

    function editor() {
        return window.r4VisualEditorV4Instance || null;
    }

    function attrs(component) {
        return component && component.getAttributes ? (component.getAttributes() || {}) : {};
    }

    function tagName(component) {
        return String(component && component.get ? (component.get('tagName') || '') : '').toLowerCase();
    }

    function typeName(component) {
        return String(component && component.get ? (component.get('type') || '') : '').toLowerCase();
    }

    function hasClass(component, className) {
        if (!component) return false;
        const currentAttrs = attrs(component);
        const classAttr = String(currentAttrs.class || '');
        if (classAttr.split(/\s+/).includes(className)) return true;
        if (typeof component.getClasses === 'function') {
            return (component.getClasses() || []).includes(className);
        }
        return false;
    }

    function isSectionGrid(component) {
        const currentAttrs = attrs(component);
        return typeName(component) === 'r4-section-grid' ||
            currentAttrs['data-r4-component'] === 'section-grid' ||
            hasClass(component, 'r4v4-section-grid');
    }

    function isSectionColumn(component) {
        const currentAttrs = attrs(component);
        return typeName(component) === 'r4-section-column' ||
            currentAttrs['data-r4-component'] === 'section-column' ||
            hasClass(component, 'r4v4-section-column');
    }

    function isTextual(component) {
        const tag = tagName(component);
        const type = typeName(component);
        return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'strong', 'em', 'small', 'a', 'button', 'li'].includes(tag) ||
            ['text', 'link', 'button'].includes(type);
    }

    function isMedia(component) {
        const tag = tagName(component);
        const type = typeName(component);
        const currentAttrs = attrs(component);
        return ['img', 'picture', 'video'].includes(tag) ||
            ['image', 'video'].includes(type) ||
            currentAttrs['data-r4-slider'] ||
            currentAttrs['data-r4-bg-slider'];
    }

    function tabButton(key) {
        return document.querySelector('[data-r4v4-sidebar-tab="' + key + '"]');
    }

    function activeTabKey() {
        const active = document.querySelector('[data-r4v4-sidebar-tab].is-active');
        return active ? active.getAttribute('data-r4v4-sidebar-tab') : '';
    }

    function activateTab(key) {
        const button = tabButton(key);
        if (!button || activeTabKey() === key) return;
        button.click();
    }

    function contextualTab(component) {
        if (!component) return 'elements';

        if (isSectionGrid(component) || isSectionColumn(component)) return 'spacing';
        if (isTextual(component)) return 'typography';
        if (isMedia(component)) return 'background';

        return 'spacing';
    }

    function markContext(component) {
        const root = document.querySelector('.r4v4-blocks-panel');
        if (!root) return;

        root.dataset.r4v4Context = component ? contextualTab(component) : 'elements';
        root.classList.toggle('has-component-selected', !!component);
    }

    function syncContext(component) {
        markContext(component);
        if (!component) return;

        const target = contextualTab(component);
        window.setTimeout(function () {
            activateTab(target);
        }, 50);
    }

    function boot() {
        const instance = editor();
        if (!instance || instance.__r4ContextControlsBooted) return false;
        instance.__r4ContextControlsBooted = true;

        instance.on('component:selected', function (component) {
            syncContext(component || instance.getSelected());
        });

        instance.on('component:deselected', function () {
            window.setTimeout(function () {
                syncContext(instance.getSelected());
            }, 80);
        });

        instance.on('component:remove', function () {
            window.setTimeout(function () {
                syncContext(instance.getSelected());
            }, 80);
        });

        syncContext(instance.getSelected());
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        let attempts = 0;
        const timer = window.setInterval(function () {
            attempts++;
            if (boot() || attempts > 80) window.clearInterval(timer);
        }, 150);
    });
})();
