(function () {
  'use strict';

  function editor() {
    return window.R4EditorV5 || null;
  }

  function cfg() {
    return window.R4EditorV5Config || {};
  }

  function controlsBox() {
    return document.getElementById(cfg().controlsId || 'r4v5Controls');
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>'"]/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char];
    });
  }

  function textFromComponent(component) {
    if (!component) return '';
    var tag = String(component.get && component.get('tagName') || '').toLowerCase();
    var text = '';

    try {
      var view = component.view && component.view.el ? component.view.el : null;
      if (view) text = tag === 'br' ? '' : (view.innerText || view.textContent || '');
    } catch (e) {}

    if (!text && component.get) {
      text = component.get('content') || '';
    }

    return String(text || '').trim();
  }

  function isTextComponent(component) {
    if (!component || !component.get) return false;
    var type = String(component.get('type') || '').toLowerCase();
    var tag = String(component.get('tagName') || '').toLowerCase();
    if (type === 'text' || type === 'link') return true;
    return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'a', 'strong', 'em', 'small', 'li', 'blockquote'].indexOf(tag) !== -1;
  }

  function isLink(component) {
    if (!component || !component.get) return false;
    return String(component.get('tagName') || '').toLowerCase() === 'a' || String(component.get('type') || '').toLowerCase() === 'link';
  }

  function styleValue(component, prop, fallback) {
    var style = component && component.getStyle ? component.getStyle() || {} : {};
    return style[prop] || fallback || '';
  }

  function attrValue(component, prop, fallback) {
    var attrs = component && component.getAttributes ? component.getAttributes() || {} : {};
    return attrs[prop] || fallback || '';
  }

  function syncFields() {
    var ed = editor();
    var c = cfg();
    if (!ed) return;

    var html = c.htmlFieldId ? document.getElementById(c.htmlFieldId) : null;
    var css = c.cssFieldId ? document.getElementById(c.cssFieldId) : null;
    var json = c.jsonFieldId ? document.getElementById(c.jsonFieldId) : null;

    if (html && ed.getHtml) html.value = ed.getHtml();
    if (css && ed.getCss) css.value = ed.getCss();
    if (json && ed.getProjectData) {
      try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {}
    }
  }

  function applyText(component) {
    var ed = editor();
    if (!component || !ed) return;

    var text = document.getElementById('r4v5TextEditorContent').value || '';
    var safeHtml = escapeHtml(text).replace(/\n/g, '<br>');
    var noBackground = !!document.getElementById('r4v5TextNoBackground') && document.getElementById('r4v5TextNoBackground').checked;

    component.components(safeHtml);

    var styles = {
      'font-size': document.getElementById('r4v5TextFontSize').value || '',
      'font-weight': document.getElementById('r4v5TextFontWeight').value || '',
      'font-style': document.getElementById('r4v5TextFontStyle').value || '',
      'line-height': document.getElementById('r4v5TextLineHeight').value || '',
      'letter-spacing': document.getElementById('r4v5TextLetterSpacing').value || '',
      'text-align': document.getElementById('r4v5TextAlign').value || '',
      color: document.getElementById('r4v5TextColor').value || '',
      'background-color': noBackground ? '' : (document.getElementById('r4v5TextBackground').value || ''),
      background: noBackground ? '' : undefined,
      margin: document.getElementById('r4v5TextMargin').value || '',
      padding: document.getElementById('r4v5TextPadding').value || '',
      'border-radius': document.getElementById('r4v5TextRadius').value || ''
    };

    component.addStyle(styles);

    if (isLink(component)) {
      var attrs = Object.assign({}, component.getAttributes ? component.getAttributes() || {} : {});
      var href = document.getElementById('r4v5TextHref').value || '#';
      var target = document.getElementById('r4v5TextTarget').value || '';
      attrs.href = href;
      if (target) attrs.target = target;
      else delete attrs.target;
      if (target === '_blank') attrs.rel = 'noopener noreferrer';
      component.setAttributes(attrs);
    }

    if (ed.trigger) ed.trigger('update');
    syncFields();
  }

  function wrapField(label, html) {
    return '<label>' + label + html + '</label>';
  }

  function textInput(id, value, placeholder) {
    return '<input type="text" id="' + id + '" value="' + escapeHtml(value || '') + '" placeholder="' + escapeHtml(placeholder || '') + '">';
  }

  function colorInput(id, value) {
    return '<input type="color" id="' + id + '" value="' + escapeHtml(value || '#111827') + '">';
  }

  function selectInput(id, options, value) {
    return '<select id="' + id + '">' + options.map(function (opt) {
      return '<option value="' + escapeHtml(opt.value) + '"' + (String(opt.value) === String(value || '') ? ' selected' : '') + '>' + escapeHtml(opt.label) + '</option>';
    }).join('') + '</select>';
  }

  function checkInput(id, checked, label) {
    return '<label class="r4v5-check"><input type="checkbox" id="' + id + '" ' + (checked ? 'checked' : '') + '> ' + escapeHtml(label) + '</label>';
  }

  function bindBackgroundToggle() {
    var checkbox = document.getElementById('r4v5TextNoBackground');
    var background = document.getElementById('r4v5TextBackground');
    if (!checkbox || !background) return;

    function refresh() {
      background.disabled = checkbox.checked;
      background.style.opacity = checkbox.checked ? '.45' : '1';
    }

    checkbox.addEventListener('change', refresh);
    refresh();
  }

  function render(component) {
    var box = controlsBox();
    if (!box || !isTextComponent(component)) return;

    var tag = String(component.get('tagName') || component.get('type') || 'testo').toUpperCase();
    var currentText = textFromComponent(component);
    var currentBackground = styleValue(component, 'background-color', '');
    var noBackgroundChecked = currentBackground === '' || currentBackground === 'transparent' || currentBackground === 'none';
    var linkFields = '';

    if (isLink(component)) {
      linkFields = '' +
        '<div class="r4v5-panel-title">Link</div>' +
        wrapField('URL link', textInput('r4v5TextHref', attrValue(component, 'href', '#'), 'https://... oppure /pagina')) +
        wrapField('Apertura', selectInput('r4v5TextTarget', [
          { value: '', label: 'Stessa finestra' },
          { value: '_blank', label: 'Nuova finestra' }
        ], attrValue(component, 'target', '')));
    }

    box.innerHTML = '' +
      '<div class="r4v5-panel-title">Editor testo</div>' +
      '<div class="r4v5-page-box">' +
        '<div style="font-size:11px;line-height:1.45;color:#94a3b8;margin-bottom:8px;">Elemento selezionato: <strong style="color:#e5e7eb;">' + escapeHtml(tag) + '</strong>. Modifica il testo da qui se il doppio click nel canvas non è comodo.</div>' +
        '<label>Contenuto<textarea id="r4v5TextEditorContent" rows="6">' + escapeHtml(currentText) + '</textarea></label>' +
        '<div class="r4v5-panel-title">Tipografia</div>' +
        '<div class="r4v5-field-row">' +
          wrapField('Font size', textInput('r4v5TextFontSize', styleValue(component, 'font-size', ''), 'es. 48px')) +
          wrapField('Line height', textInput('r4v5TextLineHeight', styleValue(component, 'line-height', ''), 'es. 1.15')) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          wrapField('Peso', selectInput('r4v5TextFontWeight', [
            { value: '', label: 'Default' },
            { value: '300', label: 'Light' },
            { value: '400', label: 'Regular' },
            { value: '600', label: 'SemiBold' },
            { value: '700', label: 'Bold' },
            { value: '900', label: 'Black' }
          ], styleValue(component, 'font-weight', ''))) +
          wrapField('Stile', selectInput('r4v5TextFontStyle', [
            { value: '', label: 'Default' },
            { value: 'normal', label: 'Normale' },
            { value: 'italic', label: 'Corsivo' }
          ], styleValue(component, 'font-style', ''))) +
        '</div>' +
        wrapField('Letter spacing', textInput('r4v5TextLetterSpacing', styleValue(component, 'letter-spacing', ''), 'es. -.03em oppure 1px')) +
        wrapField('Allineamento', selectInput('r4v5TextAlign', [
          { value: '', label: 'Default' },
          { value: 'left', label: 'Sinistra' },
          { value: 'center', label: 'Centro' },
          { value: 'right', label: 'Destra' },
          { value: 'justify', label: 'Giustificato' }
        ], styleValue(component, 'text-align', ''))) +
        '<div class="r4v5-panel-title">Colori</div>' +
        '<div class="r4v5-field-row">' +
          wrapField('Colore testo', colorInput('r4v5TextColor', styleValue(component, 'color', '#ffffff'))) +
          wrapField('Sfondo testo', colorInput('r4v5TextBackground', currentBackground || '#000000')) +
        '</div>' +
        checkInput('r4v5TextNoBackground', noBackgroundChecked, 'Nessuno sfondo sul testo') +
        '<div class="r4v5-panel-title">Spaziatura</div>' +
        wrapField('Margin', textInput('r4v5TextMargin', styleValue(component, 'margin', ''), 'es. 0 0 16px')) +
        wrapField('Padding', textInput('r4v5TextPadding', styleValue(component, 'padding', ''), 'es. 8px 12px')) +
        wrapField('Radius', textInput('r4v5TextRadius', styleValue(component, 'border-radius', ''), 'es. 12px')) +
        linkFields +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5TextApply">Applica modifiche testo</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5TextQuickTitle">Preset titolo hero</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5TextQuickParagraph">Preset paragrafo</button>' +
      '</div>';

    bindBackgroundToggle();

    document.getElementById('r4v5TextApply').addEventListener('click', function () {
      applyText(component);
    });

    document.getElementById('r4v5TextQuickTitle').addEventListener('click', function () {
      document.getElementById('r4v5TextFontSize').value = 'clamp(36px,5vw,64px)';
      document.getElementById('r4v5TextLineHeight').value = '1.05';
      document.getElementById('r4v5TextFontWeight').value = '900';
      document.getElementById('r4v5TextLetterSpacing').value = '-.04em';
      document.getElementById('r4v5TextNoBackground').checked = true;
      bindBackgroundToggle();
      applyText(component);
    });

    document.getElementById('r4v5TextQuickParagraph').addEventListener('click', function () {
      document.getElementById('r4v5TextFontSize').value = '19px';
      document.getElementById('r4v5TextLineHeight').value = '1.7';
      document.getElementById('r4v5TextFontWeight').value = '400';
      document.getElementById('r4v5TextLetterSpacing').value = '';
      document.getElementById('r4v5TextNoBackground').checked = true;
      bindBackgroundToggle();
      applyText(component);
    });
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (component) {
          if (isTextComponent(component)) render(component);
        });
        ed.on('component:update', function (component) {
          if (isTextComponent(component)) render(component);
        });
        window.clearInterval(timer);
      }
      if (attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
