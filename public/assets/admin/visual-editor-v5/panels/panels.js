(function () {
    'use strict';

    const cfg = window.R4EditorV5Config || {};
    const panelDefs = [
        { key: 'spacing', label: 'Spaziatura', mount: mountSpacing },
        { key: 'typography', label: 'Testo', mount: mountTypography },
        { key: 'background', label: 'Sfondo', mount: mountBackground },
        { key: 'border', label: 'Bordi', mount: mountBorder }
    ];

    function byId(id) { return id ? document.getElementById(id) : null; }
    function editor() { return window.R4EditorV5 || null; }
    function selected() { const ed = editor(); return ed && ed.getSelected ? ed.getSelected() : null; }

    function activateInspectorTab(name) {
        name = name || 'base';
        document.querySelectorAll('[data-r4v5-inspector-tab]').forEach(function (button) {
            const active = (button.getAttribute('data-r4v5-inspector-tab') || 'base') === name;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-r4v5-inspector-panel]').forEach(function (panel) {
            const active = (panel.getAttribute('data-r4v5-inspector-panel') || 'base') === name;
            panel.classList.toggle('is-active', active);
            if (active) panel.removeAttribute('hidden');
            else panel.setAttribute('hidden', 'hidden');
        });
    }

    function bindInspectorTabs() {
        if (document.body.dataset.r4v5InspectorTabsBound === '1') return;
        document.body.dataset.r4v5InspectorTabsBound = '1';

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-r4v5-inspector-tab]');
            if (!button) return;
            event.preventDefault();
            event.stopPropagation();
            activateInspectorTab(button.getAttribute('data-r4v5-inspector-tab') || 'base');
        }, true);

        document.addEventListener('keydown', function (event) {
            const button = event.target.closest && event.target.closest('[data-r4v5-inspector-tab]');
            if (!button || (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight')) return;
            const tabs = Array.prototype.slice.call(document.querySelectorAll('[data-r4v5-inspector-tab]'));
            const index = tabs.indexOf(button);
            if (index < 0) return;
            event.preventDefault();
            let next = event.key === 'ArrowRight' ? index + 1 : index - 1;
            if (next >= tabs.length) next = 0;
            if (next < 0) next = tabs.length - 1;
            tabs[next].focus();
            activateInspectorTab(tabs[next].getAttribute('data-r4v5-inspector-tab') || 'base');
        });

        activateInspectorTab('base');
        window.R4V5InspectorTabs = { activate: activateInspectorTab };
    }

    function syncFields() {
        const ed = editor();
        if (!ed) return;
        const html = byId(cfg.htmlFieldId);
        const css = byId(cfg.cssFieldId);
        const json = byId(cfg.jsonFieldId);
        if (html) html.value = ed.getHtml();
        if (css) css.value = ed.getCss();
        if (json) { try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {} }
    }

    function applyStyle(style) {
        const cmp = selected();
        if (!cmp) { alert('Seleziona prima un elemento nel canvas.'); return; }
        cmp.addStyle(style);
        const ed = editor();
        if (ed) ed.trigger('update');
        syncFields();
    }

    function getStyleValue(prop, fallback) {
        const cmp = selected();
        if (!cmp || !cmp.getStyle) return fallback || '';
        const style = cmp.getStyle() || {};
        return style[prop] || fallback || '';
    }

    function field(label, input) { return '<div class="r4v5-field"><label>' + label + '</label>' + input + '</div>'; }
    function textInput(id, value, placeholder) { return '<input id="' + id + '" type="text" value="' + (value || '') + '" placeholder="' + (placeholder || '') + '">'; }
    function numberInput(id, value, placeholder) { return '<input id="' + id + '" type="number" value="' + (value || '') + '" placeholder="' + (placeholder || '') + '">'; }
    function colorInput(id, value) { return '<input id="' + id + '" type="color" value="' + (value || '#ffffff') + '">'; }
    function selectInput(id, options, value) {
        return '<select id="' + id + '">' + options.map(function (opt) {
            const selectedAttr = opt.value === value ? ' selected' : '';
            return '<option value="' + opt.value + '"' + selectedAttr + '>' + opt.label + '</option>';
        }).join('') + '</select>';
    }

    function mountSpacing(root) {
        root.innerHTML = '<div class="r4v5-help">Applica margini e padding all’elemento selezionato.</div>' +
            field('Padding', textInput('r4v5SpacingPadding', getStyleValue('padding', ''), 'es. 40px 24px')) +
            field('Margin', textInput('r4v5SpacingMargin', getStyleValue('margin', ''), 'es. 0 auto 24px')) +
            '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5SpacingApply">Applica spaziatura</button>' +
            '<button type="button" class="r4v5-mini-btn" id="r4v5SpacingQuick">Preset sezione</button>';
        byId('r4v5SpacingApply').addEventListener('click', function () { applyStyle({ padding: byId('r4v5SpacingPadding').value, margin: byId('r4v5SpacingMargin').value }); });
        byId('r4v5SpacingQuick').addEventListener('click', function () { applyStyle({ padding: '72px 24px', margin: '0' }); });
    }

    function mountTypography(root) {
        root.innerHTML = '<div class="r4v5-help">Controlli tipografici base sull’elemento selezionato.</div>' +
            '<div class="r4v5-field-row">' +
            field('Size', textInput('r4v5TypeSize', getStyleValue('font-size', ''), 'es. 32px')) +
            field('Line height', textInput('r4v5TypeLine', getStyleValue('line-height', ''), 'es. 1.2')) +
            '</div><div class="r4v5-field-row">' +
            field('Peso', selectInput('r4v5TypeWeight', [{value:'',label:'Default'},{value:'400',label:'Regular'},{value:'700',label:'Bold'},{value:'900',label:'Black'}], getStyleValue('font-weight', ''))) +
            field('Colore', colorInput('r4v5TypeColor', getStyleValue('color', '#111827'))) +
            '</div><button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5TypeApply">Applica testo</button>';
        byId('r4v5TypeApply').addEventListener('click', function () {
            applyStyle({ 'font-size': byId('r4v5TypeSize').value, 'line-height': byId('r4v5TypeLine').value, 'font-weight': byId('r4v5TypeWeight').value, color: byId('r4v5TypeColor').value });
        });
    }

    function gradientValue(angle, colorA, colorB, colorC) {
        const colors = [colorA, colorB];
        if (colorC && colorC.trim() !== '') colors.push(colorC);
        return 'linear-gradient(' + (angle || 135) + 'deg,' + colors.join(',') + ')';
    }

    function openMediaForBackground() {
        const cmp = selected();
        if (!cmp) { alert('Seleziona prima una sezione, card o blocco.'); return; }
        if (!window.R4V5Media || typeof window.R4V5Media.openForBackground !== 'function') {
            alert('Media V5 non disponibile.');
            return;
        }
        window.R4V5Media.openForBackground(cmp);
    }

    function applyBackgroundOptions() {
        applyStyle({
            'background-size': byId('r4v5BgImageSize').value,
            'background-position': byId('r4v5BgImagePosition').value,
            'background-repeat': byId('r4v5BgImageRepeat').value
        });
    }

    function mountBackground(root) {
        root.innerHTML = '<div class="r4v5-help">Sfondo colore, gradiente e immagine da Media sull’elemento selezionato.</div>' +
            '<div class="r4v5-field-row">' +
            field('Colore sfondo', colorInput('r4v5BgColor', getStyleValue('background-color', '#ffffff'))) +
            field('Colore testo', colorInput('r4v5BgText', getStyleValue('color', '#111827'))) +
            '</div>' +
            '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgApplyColor">Applica colore</button>' +
            '<hr style="border-color:#334155;width:100%;margin:4px 0;">' +
            '<div class="r4v5-field-row">' +
            field('Gradiente 1', colorInput('r4v5GradA', '#0d6efd')) +
            field('Gradiente 2', colorInput('r4v5GradB', '#eaf3ff')) +
            '</div><div class="r4v5-field-row">' +
            field('Gradiente 3 opz.', textInput('r4v5GradC', '', 'vuoto = 2 colori')) +
            field('Angolo', numberInput('r4v5GradAngle', '135', '135')) +
            '</div>' +
            '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgApplyGradient">Applica gradiente</button>' +
            '<button type="button" class="r4v5-mini-btn" id="r4v5BgPresetBlue">Preset blu</button>' +
            '<button type="button" class="r4v5-mini-btn" id="r4v5BgPresetDark">Preset dark</button>' +
            '<button type="button" class="r4v5-mini-btn" id="r4v5BgPresetWarm">Preset caldo</button>' +
            '<hr style="border-color:#334155;width:100%;margin:4px 0;">' +
            '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgChooseImage">Scegli immagine da Media</button>' +
            '<div class="r4v5-field-row">' +
            field('Size', selectInput('r4v5BgImageSize', [{value:'cover',label:'Cover'},{value:'contain',label:'Contain'},{value:'auto',label:'Auto'}], getStyleValue('background-size', 'cover'))) +
            field('Repeat', selectInput('r4v5BgImageRepeat', [{value:'no-repeat',label:'No repeat'},{value:'repeat',label:'Repeat'},{value:'repeat-x',label:'Repeat X'},{value:'repeat-y',label:'Repeat Y'}], getStyleValue('background-repeat', 'no-repeat'))) +
            '</div>' +
            field('Position', selectInput('r4v5BgImagePosition', [
                {value:'center center',label:'Centro'},
                {value:'top center',label:'Alto centro'},
                {value:'bottom center',label:'Basso centro'},
                {value:'center left',label:'Centro sinistra'},
                {value:'center right',label:'Centro destra'}
            ], getStyleValue('background-position', 'center center'))) +
            '<button type="button" class="r4v5-mini-btn" id="r4v5BgApplyImageOptions">Applica opzioni immagine</button>' +
            '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5BgClear">Rimuovi sfondo</button>';

        byId('r4v5BgApplyColor').addEventListener('click', function () { applyStyle({ background: '', 'background-color': byId('r4v5BgColor').value, color: byId('r4v5BgText').value }); });
        byId('r4v5BgApplyGradient').addEventListener('click', function () { applyStyle({ background: gradientValue(byId('r4v5GradAngle').value, byId('r4v5GradA').value, byId('r4v5GradB').value, byId('r4v5GradC').value), color: byId('r4v5BgText').value }); });
        byId('r4v5BgPresetBlue').addEventListener('click', function () { applyStyle({ background: 'linear-gradient(135deg,#0d6efd,#60a5fa,#eaf3ff)', color: '#111827' }); });
        byId('r4v5BgPresetDark').addEventListener('click', function () { applyStyle({ background: 'linear-gradient(135deg,#020617,#111827,#334155)', color: '#ffffff' }); });
        byId('r4v5BgPresetWarm').addEventListener('click', function () { applyStyle({ background: 'linear-gradient(135deg,#fff7ed,#fed7aa,#ffffff)', color: '#111827' }); });
        byId('r4v5BgChooseImage').addEventListener('click', openMediaForBackground);
        byId('r4v5BgApplyImageOptions').addEventListener('click', applyBackgroundOptions);
        byId('r4v5BgClear').addEventListener('click', function () { applyStyle({ background: '', 'background-color': '', 'background-image': '' }); });
    }

    function mountBorder(root) {
        root.innerHTML = '<div class="r4v5-help">Bordi e ombra base sull’elemento selezionato.</div>' +
            '<div class="r4v5-field-row">' +
            field('Radius', textInput('r4v5BorderRadius', getStyleValue('border-radius', ''), 'es. 24px')) +
            field('Bordo', textInput('r4v5BorderValue', getStyleValue('border', ''), 'es. 1px solid #e5e7eb')) +
            '</div>' +
            field('Ombra', selectInput('r4v5Shadow', [{value:'',label:'Nessuna'},{value:'0 10px 30px rgba(15,23,42,.08)',label:'Soft'},{value:'0 20px 50px rgba(15,23,42,.14)',label:'Strong'}], getStyleValue('box-shadow', ''))) +
            '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BorderApply">Applica bordi</button>';
        byId('r4v5BorderApply').addEventListener('click', function () { applyStyle({ 'border-radius': byId('r4v5BorderRadius').value, border: byId('r4v5BorderValue').value, 'box-shadow': byId('r4v5Shadow').value }); });
    }

    function activate(key) {
        document.querySelectorAll('[data-r4v5-panel-tab]').forEach(function (btn) { btn.classList.toggle('is-active', btn.dataset.r4v5PanelTab === key); });
        document.querySelectorAll('[data-r4v5-panel]').forEach(function (panel) {
            const active = panel.dataset.r4v5Panel === key;
            panel.hidden = !active;
            if (!active || panel.dataset.r4v5Mounted === '1') return;
            const def = panelDefs.find(function (item) { return item.key === key; });
            if (def) { panel.dataset.r4v5Mounted = '1'; def.mount(panel); }
        });
    }

    function boot() {
        bindInspectorTabs();
        const root = byId(cfg.controlsId || 'r4v5Controls');
        if (!root || root.dataset.r4v5PanelsReady === '1') return;
        root.dataset.r4v5PanelsReady = '1';
        root.innerHTML = '<div class="r4v5-control-tabs">' + panelDefs.map(function (def) { return '<button type="button" class="r4v5-control-tab" data-r4v5-panel-tab="' + def.key + '">' + def.label + '</button>'; }).join('') + '</div>' + panelDefs.map(function (def) { return '<div class="r4v5-control-panel" data-r4v5-panel="' + def.key + '" hidden></div>'; }).join('');
        root.querySelectorAll('[data-r4v5-panel-tab]').forEach(function (button) { button.addEventListener('click', function () { activate(button.dataset.r4v5PanelTab); }); });
        activate('spacing');
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
