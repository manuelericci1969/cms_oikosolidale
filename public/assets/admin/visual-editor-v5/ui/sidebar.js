(function () {
    'use strict';

    const assetVersion = '20260507-v5-advanced-section';

    function getRoot() {
        return document.getElementById('r4EditorV5');
    }

    function keepRightSidebarClosed() {
        const root = getRoot();
        if (!root) return;
        root.classList.remove('r4v5-right-open');
        root.classList.add('r4v5-right-closed');
    }

    function loadScriptOnce(id, src) {
        if (document.getElementById(id)) return;
        const script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.defer = true;
        document.body.appendChild(script);
    }

    function loadInspectorTools() {
        loadScriptOnce('r4v5-inspector-tabs-loader', '/assets/admin/visual-editor-v5/ui/inspector-tabs.js?v=' + assetVersion);
        loadScriptOnce('r4v5-animations-panel-loader', '/assets/admin/visual-editor-v5/panels/animations.js?v=' + assetVersion);
        loadScriptOnce('r4v5-animations-bridge-loader', '/assets/admin/visual-editor-v5/runtime/animations-editor-bridge.js?v=' + assetVersion);
        loadScriptOnce('r4v5-image-inspector-loader', '/assets/admin/visual-editor-v5/panels/image-inspector.js?v=' + assetVersion);
        loadScriptOnce('r4v5-advanced-section-loader', '/assets/admin/visual-editor-v5/panels/advanced-section.js?v=' + assetVersion);
    }

    function disableToggleButton() {
        document.querySelectorAll('[data-r4v5-toggle-right]').forEach(function (button) {
            button.hidden = true;
            button.disabled = true;
            button.setAttribute('aria-hidden', 'true');
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                keepRightSidebarClosed();
            });
        });
    }

    function bindEditor() {
        const editor = window.R4EditorV5 || null;
        if (!editor || editor.__r4v5SidebarUiDisabledBound) return false;
        editor.__r4v5SidebarUiDisabledBound = true;

        if (editor.on) {
            editor.on('component:selected', keepRightSidebarClosed);
            editor.on('component:deselected', keepRightSidebarClosed);
            editor.on('component:remove', keepRightSidebarClosed);
            editor.on('load', keepRightSidebarClosed);
        }

        keepRightSidebarClosed();
        return true;
    }

    function boot() {
        keepRightSidebarClosed();
        disableToggleButton();
        loadInspectorTools();

        let attempts = 0;
        const timer = window.setInterval(function () {
            attempts++;
            keepRightSidebarClosed();
            if (bindEditor() || attempts > 80) window.clearInterval(timer);
        }, 100);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
