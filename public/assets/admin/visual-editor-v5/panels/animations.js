(function () {
  'use strict';

  function editor() { return window.R4EditorV5 || null; }
  function cfg() { return window.R4EditorV5Config || {}; }
  function slotBox() { return document.getElementById(cfg().animationsSlotId || 'r4v5AnimationsSlot') || document.getElementById(cfg().controlsId || 'r4v5Controls'); }
  function esc(v) { return String(v || '').replace(/[&<>'"]/g, function (c) { return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' })[c]; }); }
  function attrs(c) { return c && c.getAttributes ? c.getAttributes() || {} : {}; }

  function setAttrs(c, next) {
    if (!c || !c.setAttributes) return;
    c.setAttributes(Object.assign({}, attrs(c), next));
  }

  function cleanAttrs(c, keys) {
    if (!c || !c.setAttributes) return;
    var next = Object.assign({}, attrs(c));
    keys.forEach(function (key) { delete next[key]; });
    c.setAttributes(next);
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
      try {
        var data = ed.getProjectData();
        if (window.R4V5CodeEditorState && typeof window.R4V5CodeEditorState.getCustomJs === 'function') {
          data.r4v5CustomJs = window.R4V5CodeEditorState.getCustomJs();
        }
        json.value = JSON.stringify(data);
      } catch (e) {}
    }
  }

  function refreshRuntime() {
    if (window.R4V5EditorCodeRuntimeBridge && typeof window.R4V5EditorCodeRuntimeBridge.inject === 'function') {
      window.setTimeout(window.R4V5EditorCodeRuntimeBridge.inject, 80);
      window.setTimeout(window.R4V5EditorCodeRuntimeBridge.inject, 260);
      window.setTimeout(window.R4V5EditorCodeRuntimeBridge.inject, 700);
    }
    if (window.R4V5BackgroundSliderBridge && typeof window.R4V5BackgroundSliderBridge.inject === 'function') {
      window.setTimeout(window.R4V5BackgroundSliderBridge.inject, 120);
    }
  }

  function changed() {
    var ed = editor();
    if (ed && ed.trigger) ed.trigger('update');
    syncFields();
    refreshRuntime();
  }

  function field(label, html) { return '<label>' + esc(label) + html + '</label>'; }
  function select(id, options, value) {
    return '<select id="' + id + '">' + options.map(function (o) {
      return '<option value="' + esc(o.value) + '"' + (String(o.value) === String(value || '') ? ' selected' : '') + '>' + esc(o.label) + '</option>';
    }).join('') + '</select>';
  }
  function num(id, value, min, max, step) { return '<input type="number" id="' + id + '" min="' + min + '" max="' + max + '" step="' + step + '" value="' + esc(value || '') + '">'; }

  function removeExistingPanel() {
    var slot = slotBox();
    if (slot) slot.innerHTML = '';
  }

  function apply(c) {
    var animType = document.getElementById('r4v5AnimType').value || '';
    var bgAnimType = document.getElementById('r4v5BgAnimType').value || '';

    var next = {};

    if (animType) {
      next['data-r4-animation'] = animType;
      next['data-r4-animation-trigger'] = document.getElementById('r4v5AnimTrigger').value || 'viewport';
      next['data-r4-animation-duration'] = document.getElementById('r4v5AnimDuration').value || '800';
      next['data-r4-animation-delay'] = document.getElementById('r4v5AnimDelay').value || '0';
      next['data-r4-animation-easing'] = document.getElementById('r4v5AnimEasing').value || 'ease';
      next['data-r4-animation-once'] = document.getElementById('r4v5AnimOnce').value || 'true';
    }

    if (bgAnimType) {
      next['data-r4-bg-animation'] = bgAnimType;
      next['data-r4-bg-animation-duration'] = document.getElementById('r4v5BgAnimDuration').value || '7000';
      next['data-r4-bg-animation-delay'] = document.getElementById('r4v5BgAnimDelay').value || '0';
      next['data-r4-bg-animation-loop'] = document.getElementById('r4v5BgAnimLoop').value || 'true';
      next['data-r4-bg-animation-easing'] = document.getElementById('r4v5BgAnimEasing').value || 'ease-in-out';
    }

    cleanAttrs(c, [
      'data-r4-animation', 'data-r4-animation-trigger', 'data-r4-animation-duration', 'data-r4-animation-delay', 'data-r4-animation-easing', 'data-r4-animation-once',
      'data-r4-bg-animation', 'data-r4-bg-animation-duration', 'data-r4-bg-animation-delay', 'data-r4-bg-animation-loop', 'data-r4-bg-animation-easing'
    ]);
    setAttrs(c, next);
    changed();
  }

  function clear(c) {
    cleanAttrs(c, [
      'data-r4-animation', 'data-r4-animation-trigger', 'data-r4-animation-duration', 'data-r4-animation-delay', 'data-r4-animation-easing', 'data-r4-animation-once',
      'data-r4-bg-animation', 'data-r4-bg-animation-duration', 'data-r4-bg-animation-delay', 'data-r4-bg-animation-loop', 'data-r4-bg-animation-easing'
    ]);
    changed();
  }

  function render(c) {
    var box = slotBox();
    if (!box || !c) return;
    removeExistingPanel();

    var a = attrs(c);
    box.innerHTML = '' +
      '<div id="r4v5AnimationsPanelWrap">' +
      '<div class="r4v5-panel-title">Animazioni</div>' +
      '<div class="r4v5-page-box" id="r4v5AnimationsPanel">' +
        '<div style="font-size:11px;line-height:1.45;color:#94a3b8;margin-bottom:8px;">Gestisci movimento del blocco selezionato e, se presente, dello sfondo immagine/slider.</div>' +
        field('Animazione blocco', select('r4v5AnimType', [
          { value:'', label:'Nessuna' },
          { value:'fade-in', label:'Fade in' },
          { value:'fade-out', label:'Fade out soft' },
          { value:'fade-up', label:'Fade up' },
          { value:'fade-down', label:'Fade down' },
          { value:'fade-left', label:'Fade left' },
          { value:'fade-right', label:'Fade right' },
          { value:'zoom-in', label:'Zoom in' },
          { value:'zoom-out', label:'Zoom out' },
          { value:'flip-up', label:'Flip up' },
          { value:'blur-in', label:'Blur in' },
          { value:'slide-up', label:'Slide up' },
          { value:'slide-left', label:'Slide left' },
          { value:'slide-right', label:'Slide right' }
        ], a['data-r4-animation'] || '')) +
        field('Trigger', select('r4v5AnimTrigger', [
          { value:'viewport', label:'Quando entra in viewport' },
          { value:'load', label:'Al caricamento pagina' }
        ], a['data-r4-animation-trigger'] || 'viewport')) +
        '<div class="r4v5-field-row">' +
          field('Durata ms', num('r4v5AnimDuration', a['data-r4-animation-duration'] || '800', '100', '10000', '50')) +
          field('Delay ms', num('r4v5AnimDelay', a['data-r4-animation-delay'] || '0', '0', '10000', '50')) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Easing', select('r4v5AnimEasing', [
            { value:'ease', label:'Ease' },
            { value:'linear', label:'Linear' },
            { value:'ease-in', label:'Ease in' },
            { value:'ease-out', label:'Ease out' },
            { value:'ease-in-out', label:'Ease in out' },
            { value:'cubic-bezier(.2,.8,.2,1)', label:'Smooth pro' }
          ], a['data-r4-animation-easing'] || 'ease')) +
          field('Una sola volta', select('r4v5AnimOnce', [
            { value:'true', label:'Sì' },
            { value:'false', label:'No / ripeti' }
          ], a['data-r4-animation-once'] || 'true')) +
        '</div>' +
        '<div class="r4v5-panel-title">Animazione sfondo</div>' +
        field('Tipo animazione sfondo', select('r4v5BgAnimType', [
          { value:'', label:'Nessuna' },
          { value:'fade', label:'Fade' },
          { value:'zoom-slow', label:'Zoom lento' },
          { value:'zoom-in', label:'Zoom in' },
          { value:'zoom-out', label:'Zoom out' },
          { value:'ken-burns', label:'Ken Burns' },
          { value:'pan-left', label:'Pan left' },
          { value:'pan-right', label:'Pan right' },
          { value:'pan-up', label:'Pan up' },
          { value:'pan-down', label:'Pan down' },
          { value:'pulse-soft', label:'Pulse soft' }
        ], a['data-r4-bg-animation'] || '')) +
        '<div class="r4v5-field-row">' +
          field('Durata sfondo ms', num('r4v5BgAnimDuration', a['data-r4-bg-animation-duration'] || '7000', '500', '60000', '100')) +
          field('Delay sfondo ms', num('r4v5BgAnimDelay', a['data-r4-bg-animation-delay'] || '0', '0', '10000', '50')) +
        '</div>' +
        '<div class="r4v5-field-row">' +
          field('Loop sfondo', select('r4v5BgAnimLoop', [
            { value:'true', label:'Sì' },
            { value:'false', label:'No' }
          ], a['data-r4-bg-animation-loop'] || 'true')) +
          field('Easing sfondo', select('r4v5BgAnimEasing', [
            { value:'ease', label:'Ease' },
            { value:'linear', label:'Linear' },
            { value:'ease-in', label:'Ease in' },
            { value:'ease-out', label:'Ease out' },
            { value:'ease-in-out', label:'Ease in out' }
          ], a['data-r4-bg-animation-easing'] || 'ease-in-out')) +
        '</div>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" id="r4v5ApplyAnimations">Applica animazioni</button>' +
        '<button type="button" class="r4v5-mini-btn" id="r4v5PreviewAnimations">Anteprima editor</button>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-danger" id="r4v5ClearAnimations">Rimuovi animazioni</button>' +
      '</div></div>';

    document.getElementById('r4v5ApplyAnimations').addEventListener('click', function () { apply(c); render(c); });
    document.getElementById('r4v5PreviewAnimations').addEventListener('click', function () { refreshRuntime(); });
    document.getElementById('r4v5ClearAnimations').addEventListener('click', function () { clear(c); render(c); });
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (c) { window.setTimeout(function () { render(c); }, 40); });
        ed.on('component:deselected', removeExistingPanel);
        window.clearInterval(timer);
      }
      if (attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
