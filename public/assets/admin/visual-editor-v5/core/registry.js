(function () {
    'use strict';

    const state = {
        widgets: [],
        tools: [],
        panels: [],
        components: []
    };

    function normalizeItem(item) {
        if (!item || !item.key) return null;
        return Object.assign({
            label: item.key,
            category: 'Base',
            order: 100,
            content: ''
        }, item);
    }

    function sortByOrder(items) {
        return items.slice().sort(function (a, b) {
            return (a.order || 100) - (b.order || 100);
        });
    }

    function registerWidget(item) {
        const normalized = normalizeItem(item);
        if (!normalized) return false;
        state.widgets = state.widgets.filter(function (current) { return current.key !== normalized.key; });
        state.widgets.push(normalized);
        return true;
    }

    function registerTool(item) {
        if (!item || !item.key) return false;
        state.tools = state.tools.filter(function (current) { return current.key !== item.key; });
        state.tools.push(item);
        return true;
    }

    function registerPanel(item) {
        if (!item || !item.key) return false;
        state.panels = state.panels.filter(function (current) { return current.key !== item.key; });
        state.panels.push(item);
        return true;
    }

    function registerComponent(item) {
        if (!item || !item.key) return false;
        state.components = state.components.filter(function (current) { return current.key !== item.key; });
        state.components.push(item);
        return true;
    }

    function widgets() {
        return sortByOrder(state.widgets);
    }

    function tools() {
        return sortByOrder(state.tools);
    }

    function panels() {
        return sortByOrder(state.panels);
    }

    function components() {
        return sortByOrder(state.components);
    }

    window.R4EditorV5Registry = {
        registerWidget: registerWidget,
        registerTool: registerTool,
        registerPanel: registerPanel,
        registerComponent: registerComponent,
        widgets: widgets,
        tools: tools,
        panels: panels,
        components: components
    };
})();
