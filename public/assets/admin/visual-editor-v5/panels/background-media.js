(function () {
  'use strict';

  function editor() { return window.R4EditorV5 || null; }
  function cfg() { return window.R4EditorV5Config || {}; }
  function box() { return document.getElementById(cfg().controlsId || 'r4v5Controls'); }
  function esc(v) { return String(v || '').replace(/[&<>'"]/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[c]; }); }
  function tag(c) { return c && c.get ? String(c.get('tagName') || c.get('type') || '').toLowerCase() : ''; }
  function type(c) { return c && c.get ? String(c.get('type') || '').toLowerCase() : ''; }
  function attrs(c) { return c && c.getAttributes ? c.getAttributes() || {} : {}; }
  function attr(c, k, f) { var a = attrs(c); return a[k] || f || ''; }
  function style(c, k, f) { var s = c && c.getStyle ? c.getStyle() || {} : {}; return s[k] || f || ''; }
  function manager() { return window.R4V5BackgroundManager || null; }

  function isText(c) {
    var t = tag(c), y = type(c);
    return y === 'text' || y === 'link' || ['h1','h2','h3','h4','h5','h6','p','span','a','strong','em','small','li','blockquote'].indexOf(t) !== -1;
  }
  function isSliderPro(c) { return attr(c, 'data-r4v5-slider-pro', '') !== ''; }
  function canUse(c) { return !!c && !isText(c) && !isSliderPro(c) && tag(c) !== 'img' && type(c) !== 'image'; }

  function fallbackSync() {
    var ed = editor(), c = cfg(); if (!ed) return;
    var html = c.htmlFieldId ? document.getElementById(c.htmlFieldId) : null;
    var css = c.cssFieldId ? document.getElementById(c.cssFieldId) : null;
    var json = c.jsonFieldId ? document.getElementById(c.jsonFieldId) : null;
    if (html && ed.getHtml) html.value = ed.getHtml();
    if (css && ed.getCss) css.value = ed.getCss();
    if (json && ed.getProjectData) { try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {} }
  }

  function fallbackApply(c, options) {
    var ed = editor();
    options = options || {};
    if (!c || !c.setStyle) return;
    if (options.mode === 'none') c.setStyle({});
    if (options.mode === 'color') c.addStyle({ 'background-color': options.color || '#ffffff' });
    if (ed && ed.trigger) ed.trigger('update');
    fallbackSync();
  }

  function apply(c, options) {
    var m = manager();
    if (m && typeof m.apply === 'function') return m.apply(c, options || {});
    return fallbackApply(c, options || {});
  }

  function read(c) {
    var m = manager();
    if (m && typeof m.read === 'function') return m.read(c);
    return {
      mode: attr(c, 'data-r4v5-bg-mode', 'none'),
      image: attr(c, 'data-r4v5-bg-image', ''),
      images: [],
      color: attr(c, 'data-r4v5-bg-color', style(c, 'background-color', '#ffffff')),
      gradientFrom: attr(c, 'data-r4v5-bg-gradient-from', '#0d6efd'),
      gradientTo: attr(c, 'data-r4v5-bg-gradient-to', '#eaf3ff'),
      gradientAngle: attr(c, 'data-r4v5-bg-gradient-angle', '135'),
      textColor: style(c, 'color', '#111827'),
      size: style(c, 'background-size', attr(c, 'data-r4v5-bg-slider-fit', 'cover')),
      position: style(c, 'background-position', attr(c, 'data-r4v5-bg-slider-position', 'center center')),
      repeat: style(c, 'background-repeat', 'no-repeat'),
      attachment: style(c, 'background-attachment', 'scroll'),
      overlayColor: attr(c, 'data-r4v5-bg-overlay-color', '#000000'),
      overlayOpacity: attr(c, 'data-r4v5-bg-overlay-opacity', '0'),
      autoplay: attr(c, 'data-r4v5-bg-slider-autoplay', 'true'),
      interval: attr(c, 'data-r4v5-bg-slider-interval', '4500'),
      duration: attr(c, 'data-r4v5-bg-slider-duration', '700'),
      minHeight: style(c, 'min-height', attr(c, 'data-r4v5-bg-slider-min-height', ''))
    };
  }

  function openImage(c) {
    if (!window.R4V5Media || typeof window.R4V5Media.openForBackground !== 'function') { alert('Media V5 non disponibile.'); return; }
    window.R4V5Media.openForBackground(c);
  }
  function openSlider(c) {
    if (!window.R4V5Media || typeof window.R4V5Media.openForBackgroundSlider !== 'function') { alert('Media V5 non disponibile.'); return; }
    window.R4V5Media.openForBackgroundSlider(c);
  }

  function select(id, options, value) { return '<select id="' + id + '">' + options.map(function (o) { return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(value || '') ? ' selected' : '') + '>' + esc(o.label) + '</option>'; }).join('') + '</select>'; }
  function color(id, value) { return '<input type="color" id="' + id + '" value="' + esc(value || '#ffffff') + '">'; }
  function num(id, value, min, max, step) { return '<input type="number" id="' + id + '" min="' + min + '" max="' + max + '" step="' + step + '" value="' + esc(value || '') + '">'; }
  function text(id, value, ph) { return '<input type="text" id="' + id + '" value="' + esc(value || '') + '" placeholder="' + esc(ph || '') + '">'; }
  function field(label, html) { return '<label>' + esc(label) + html + '</label>'; }

  function getValue(id, fallback) {
    var el = document.getElementById(id);
    return el ? el.value : fallback;
  }

  function currentOptions(mode, state) {
    mode = mode || getValue('r4v5BgMode', 'none');
    state = state || {};
    var imageFallback = state.image || '';
    if (mode === 'image' && !imageFallback && state.images && state.images.length) imageFallback = state.images[0];
    return {
      mode: mode,
      color: getValue('r4v5BgColor', state.color || '#ffffff'),
      textColor: getValue('r4v5BgTextColor', state.textColor || ''),
      from: getValue('r4v5BgGradientFrom', state.gradientFrom || '#0d6efd'),
      to: getValue('r4v5BgGradientTo', state.gradientTo || '#eaf3ff'),
      angle: getValue('r4v5BgGradientAngle', state.gradientAngle || '135'),
      image: getValue('r4v5BgImagePreview', imageFallback),
      size: getValue('r4v5BgSize', state.size || 'cover'),
      fit: getValue('r4v5BgSize', state.size || 'cover'),
      position: getValue('r4v5BgPosition', state.position || 'center center'),
      repeat: getValue('r4v5BgRepeat', state.repeat || 'no-repeat'),
      attachment: getValue('r4v5BgAttachment', state.attachment || 'scroll'),
      overlayColor: getValue('r4v5BgOverlayColor', state.overlayColor || '#000000'),
      overlayOpacity: getValue('r4v5BgOverlayOpacity', state.overlayOpacity || '0'),
      autoplay: getValue('r4v5BgSliderAutoplay', state.autoplay || 'true'),
      interval: getValue('r4v5BgSliderInterval', state.interval || '4500'),
      duration: getValue('r4v5BgSliderDuration', state.duration || '700'),
      minHeight: getValue('r4v5BgMinHeight', state.minHeight || '')
    };
  }

  function showMode(mode) {
    ['none','color','gradient','image','slider'].forEach(function (name) {
      var el = document.querySelector('[data-r4v5-bg-section="' + name + '"]');
      if (el) el.hidden = name !== mode;
    });
  }

  function render(c) {
    var root = box(); if (!root || !canUse(c)) return;
    var state = read(c);
    var mode = state.mode || 'none';
    var label = String(c.get('tagName') || c.get('type') || 'blocco').toUpperCase();
    var imageValue = state.image || ((state.images || [])[0] || '');

    root.innerHTML = '' +
      '<div class="r4v5-panel-title">Sfondo elemento</div><div class="r4v5-page-box">' +
      '<div style="font-size:11px;line-height:1.45;color:#94a3b8;margin-bottom:8px;">Elemento selezionato: <strong style="color:#e5e7eb;">' + esc(label) + '</strong>.</div>' +
      field('Modalità sfondo', select('r4v5BgMode', [ {value:'none',label:'Nessuno'}, {value:'color',label:'Colore'}, {value:'gradient',label:'Gradiente'}, {value:'image',label:'Immagine'}, {value:'slider',label:'Slider'} ], mode)) +

      '<div data-r4v5-bg-section="none">' +
        '<div class="r4v5-panel-title">Nessuno</div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5BgClear">Rimuovi sfondo</button>' +
      '</div>' +

      '<div data-r4v5-bg-section="color">' +
        '<div class="r4v5-panel-title">Colore</div>' +
        '<div class="r4v5-field-row">' + field('Colore sfondo', color('r4v5BgColor', state.color || '#ffffff')) + field('Colore testo', color('r4v5BgTextColor', state.textColor || '#111827')) + '</div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgApplyColor">Applica colore</button>' +
      '</div>' +

      '<div data-r4v5-bg-section="gradient">' +
        '<div class="r4v5-panel-title">Gradiente</div>' +
        '<div class="r4v5-field-row">' + field('Colore da', color('r4v5BgGradientFrom', state.gradientFrom || '#0d6efd')) + field('Colore a', color('r4v5BgGradientTo', state.gradientTo || '#eaf3ff')) + '</div>' +
        field('Angolo', num('r4v5BgGradientAngle', state.gradientAngle || '135', '0', '360', '1')) +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgApplyGradient">Applica gradiente</button>' +
      '</div>' +

      '<div data-r4v5-bg-section="image">' +
        '<div class="r4v5-panel-title">Immagine</div>' +
        field('URL immagine corrente', text('r4v5BgImagePreview', imageValue, 'Scegli da Media oppure incolla URL')) +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgChooseMedia">Scegli immagine da Media</button>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5BgRemoveImage">Rimuovi immagine</button>' +
        '<div class="r4v5-field-row">' + field('Size', select('r4v5BgSize', [{value:'cover',label:'Cover'},{value:'contain',label:'Contain'},{value:'auto',label:'Auto'}], state.size || 'cover')) + field('Repeat', select('r4v5BgRepeat', [{value:'no-repeat',label:'No repeat'},{value:'repeat',label:'Repeat'},{value:'repeat-x',label:'Repeat X'},{value:'repeat-y',label:'Repeat Y'}], state.repeat || 'no-repeat')) + '</div>' +
        field('Position', select('r4v5BgPosition', [{value:'center center',label:'Centro'},{value:'top center',label:'Alto centro'},{value:'bottom center',label:'Basso centro'},{value:'center left',label:'Centro sinistra'},{value:'center right',label:'Centro destra'}], state.position || 'center center')) +
        field('Attachment', select('r4v5BgAttachment', [{value:'scroll',label:'Scroll'},{value:'fixed',label:'Fixed / Parallax semplice'}], state.attachment || 'scroll')) +
        '<div class="r4v5-field-row">' + field('Overlay colore', color('r4v5BgOverlayColor', state.overlayColor || '#000000')) + field('Overlay opacità', num('r4v5BgOverlayOpacity', state.overlayOpacity || '0', '0', '0.95', '0.05')) + '</div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgApplyImage">Applica immagine</button>' +
      '</div>' +

      '<div data-r4v5-bg-section="slider">' +
        '<div class="r4v5-panel-title">Slider immagini</div>' +
        '<div style="font-size:11px;line-height:1.5;color:#94a3b8;">Immagini selezionate: <strong style="color:#e5e7eb;">' + (state.images || []).length + '</strong></div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgChooseSliderMedia">Scegli immagini slider da Media</button>' +
        '<div class="r4v5-field-row">' + field('Autoplay', select('r4v5BgSliderAutoplay', [{value:'true',label:'Attivo'},{value:'false',label:'Disattivo'}], state.autoplay || 'true')) + field('Intervallo ms', num('r4v5BgSliderInterval', state.interval || '4500', '800', '60000', '100')) + '</div>' +
        field('Durata transizione ms', num('r4v5BgSliderDuration', state.duration || '700', '100', '10000', '50')) +
        '<div class="r4v5-field-row">' + field('Fit', select('r4v5BgSize', [{value:'cover',label:'Cover'},{value:'contain',label:'Contain'},{value:'auto',label:'Auto'}], state.size || 'cover')) + field('Overlay colore', color('r4v5BgOverlayColor', state.overlayColor || '#000000')) + '</div>' +
        field('Position', select('r4v5BgPosition', [{value:'center center',label:'Centro'},{value:'top center',label:'Alto centro'},{value:'bottom center',label:'Basso centro'},{value:'center left',label:'Centro sinistra'},{value:'center right',label:'Centro destra'}], state.position || 'center center')) +
        field('Overlay opacità', num('r4v5BgOverlayOpacity', state.overlayOpacity || '0.35', '0', '0.95', '0.05')) +
        field('Altezza minima', text('r4v5BgMinHeight', state.minHeight || '420px', 'es. 420px oppure 70vh')) +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5BgApplySlider">Applica slider</button>' +
      '</div>' +

      '<button type="button" class="r4v5-mini-btn" id="r4v5BgApplyTextColor">Applica solo colore testo</button>' +
      '</div>';

    showMode(mode);

    var modeField = document.getElementById('r4v5BgMode');
    if (modeField) modeField.addEventListener('change', function () { showMode(modeField.value || 'none'); });

    var chooseMedia = document.getElementById('r4v5BgChooseMedia');
    if (chooseMedia) chooseMedia.addEventListener('click', function () { openImage(c); });
    var chooseSlider = document.getElementById('r4v5BgChooseSliderMedia');
    if (chooseSlider) chooseSlider.addEventListener('click', function () { openSlider(c); });

    var clear = document.getElementById('r4v5BgClear');
    if (clear) clear.addEventListener('click', function () { apply(c, { mode: 'none' }); render(c); });

    var removeImage = document.getElementById('r4v5BgRemoveImage');
    if (removeImage) removeImage.addEventListener('click', function () { apply(c, { mode: 'none' }); render(c); });

    var applyColor = document.getElementById('r4v5BgApplyColor');
    if (applyColor) applyColor.addEventListener('click', function () { apply(c, currentOptions('color', state)); render(c); });

    var applyGradient = document.getElementById('r4v5BgApplyGradient');
    if (applyGradient) applyGradient.addEventListener('click', function () { apply(c, currentOptions('gradient', state)); render(c); });

    var applyImage = document.getElementById('r4v5BgApplyImage');
    if (applyImage) applyImage.addEventListener('click', function () { apply(c, currentOptions('image', state)); render(c); });

    var applySlider = document.getElementById('r4v5BgApplySlider');
    if (applySlider) applySlider.addEventListener('click', function () {
      var opts = currentOptions('slider', state);
      opts.images = state.images || [];
      apply(c, opts);
      render(c);
    });

    var textColor = document.getElementById('r4v5BgApplyTextColor');
    if (textColor) textColor.addEventListener('click', function () {
      var value = getValue('r4v5BgTextColor', '');
      if (value && c.addStyle) c.addStyle({ color: value });
      var ed = editor(); if (ed && ed.trigger) ed.trigger('update');
      var m = manager(); if (m && m.sync) m.sync(); else fallbackSync();
    });
  }

  function boot() {
    var attempts = 0;
    var timer = setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (c) { if (canUse(c)) render(c); });
        ed.on('component:update', function (c) { if (canUse(c)) render(c); });
        clearInterval(timer);
      }
      if (attempts > 100) clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
