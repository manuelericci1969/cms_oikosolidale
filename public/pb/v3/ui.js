import { formatHtml, formatCss, syncEditorToFields } from './helpers.js';

export function initEditorUI(editor, options = {}) {
    const {
        form,
        statusField,
        htmlField,
        cssField,
        jsonField
    } = options;

    const undoBtn = document.getElementById('gjs-undo-btn');
    const redoBtn = document.getElementById('gjs-redo-btn');
    const desktopBtn = document.getElementById('gjs-device-desktop');
    const tabletBtn = document.getElementById('gjs-device-tablet');
    const mobileBtn = document.getElementById('gjs-device-mobile');
    const previewBtn = document.getElementById('gjs-preview-btn');
    const clearBtn = document.getElementById('gjs-clear-btn');

    const openSettingsBtn = document.getElementById('v3OpenSettingsBtn');
    const closeSettingsBtn = document.getElementById('v3CloseSettingsBtn');
    const settingsDrawer = document.getElementById('v3SettingsDrawer');

    const codeBtn = document.getElementById('gjs-code-btn');
    const closeCodeBtn = document.getElementById('v3CloseCodeBtn');
    const applyCodeBtn = document.getElementById('v3ApplyCodeBtn');
    const syncFromCanvasBtn = document.getElementById('v3SyncFromCanvasBtn');
    const codeEditorWrap = document.getElementById('v3CodeEditor');
    const htmlEditor = document.getElementById('v3HtmlEditor');
    const cssEditorLive = document.getElementById('v3CssEditor');
    const editorLayout = document.getElementById('v3EditorLayout');

    function doSync() {
        syncEditorToFields(editor, { htmlField, cssField, jsonField });
    }

    function setActiveDeviceButton(activeBtn) {
        [desktopBtn, tabletBtn, mobileBtn].forEach((btn) => btn?.classList.remove('active'));
        activeBtn?.classList.add('active');
    }

    undoBtn?.addEventListener('click', () => editor.UndoManager.undo());
    redoBtn?.addEventListener('click', () => editor.UndoManager.redo());

    desktopBtn?.addEventListener('click', () => {
        editor.setDevice('Desktop');
        setActiveDeviceButton(desktopBtn);
    });

    tabletBtn?.addEventListener('click', () => {
        editor.setDevice('Tablet');
        setActiveDeviceButton(tabletBtn);
    });

    mobileBtn?.addEventListener('click', () => {
        editor.setDevice('Mobile portrait');
        setActiveDeviceButton(mobileBtn);
    });

    previewBtn?.addEventListener('click', () => {
        editor.runCommand('core:preview');
    });

    clearBtn?.addEventListener('click', () => {
        if (!confirm('Vuoi davvero svuotare il canvas?')) return;
        editor.setComponents('');
        editor.setStyle('');
        doSync();
    });

    openSettingsBtn?.addEventListener('click', () => {
        settingsDrawer?.classList.add('is-open');
    });

    closeSettingsBtn?.addEventListener('click', () => {
        settingsDrawer?.classList.remove('is-open');
    });

    function syncCanvasToCodeEditors() {
        if (!htmlEditor || !cssEditorLive) return;
        htmlEditor.value = formatHtml(editor.getHtml());
        cssEditorLive.value = formatCss(editor.getCss());
    }

    function applyCodeEditorsToCanvas() {
        if (!htmlEditor || !cssEditorLive) return;

        editor.setComponents(htmlEditor.value || '');
        editor.setStyle(cssEditorLive.value || '');
        doSync();
    }

    function openCodeEditor() {
        syncCanvasToCodeEditors();
        codeEditorWrap?.classList.remove('d-none');
        editorLayout?.classList.add('d-none');
    }

    function closeCodeEditor() {
        codeEditorWrap?.classList.add('d-none');
        editorLayout?.classList.remove('d-none');
    }

    codeBtn?.addEventListener('click', openCodeEditor);
    closeCodeBtn?.addEventListener('click', closeCodeEditor);
    syncFromCanvasBtn?.addEventListener('click', syncCanvasToCodeEditors);
    applyCodeBtn?.addEventListener('click', applyCodeEditorsToCanvas);

    document.querySelectorAll('[data-v3-submit-status]').forEach((btn) => {
        btn.addEventListener('click', function () {
            if (statusField) {
                statusField.value = this.getAttribute('data-v3-submit-status') || 'draft';
            }
            doSync();
        });
    });

    form?.addEventListener('submit', () => {
        doSync();
    });

    // background page toggles
    const pageBgType = document.getElementById('pageBgType');
    const pageBgColorFields = document.getElementById('pageBgColorFields');
    const pageBgGradientFields = document.getElementById('pageBgGradientFields');
    const pageBgImageFields = document.getElementById('pageBgImageFields');

    function refreshPageBgFields() {
        const val = pageBgType?.value || 'none';

        pageBgColorFields?.classList.toggle('d-none', val !== 'color');
        pageBgGradientFields?.classList.toggle('d-none', val !== 'gradient');
        pageBgImageFields?.classList.toggle('d-none', val !== 'image');
    }

    pageBgType?.addEventListener('change', refreshPageBgFields);
    refreshPageBgFields();
}
