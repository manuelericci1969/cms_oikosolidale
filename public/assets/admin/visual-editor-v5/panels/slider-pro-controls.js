(function () {
  'use strict';

  function editor() { return window.R4EditorV5 || null; }
  function controlsBox() { return document.getElementById((window.R4EditorV5Config || {}).controlsId || 'r4v5Controls'); }

  function isSlider(component) {
    if (!component || !component.getAttributes) return false;
    var attrs = component.getAttributes() || {};
    return attrs['data-r4v5-slider-pro'] !== undefined || attrs['data-r4v5-slider-pro'] === '1';
  }

  function attr(component, name, fallback) {
    var attrs = component.getAttributes ? component.getAttributes() || {} : {};
    return attrs[name] !== undefined && attrs[name] !== null && attrs[name] !== '' ? String(attrs[name]) : fallback;
  }

  function setAttr(component, name, value) {
    var attrs = Object.assign({}, component.getAttributes ? component.getAttributes() || {} : {});
    attrs[name] = String(value);
    component.setAttributes(attrs);
  }

  function syncFields(ed) {
    var cfg = window.R4EditorV5Config || {};
    var html = cfg.htmlFieldId ? document.getElementById(cfg.htmlFieldId) : null;
    var css = cfg.cssFieldId ? document.getElementById(cfg.cssFieldId) : null;
    var json = cfg.jsonFieldId ? document.getElementById(cfg.jsonFieldId) : null;
    if (html && ed.getHtml) html.value = ed.getHtml();
    if (css && ed.getCss) css.value = ed.getCss();
    if (json && ed.getProjectData) {
      try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {}
    }
  }

  function reinitSlider() {
    if (window.R4V5SliderProEditorBridge) {
      if (typeof window.R4V5SliderProEditorBridge.inject === 'function') window.R4V5SliderProEditorBridge.inject();
      if (typeof window.R4V5SliderProEditorBridge.init === 'function') window.setTimeout(window.R4V5SliderProEditorBridge.init, 150);
    }
  }

  function setHeight(component, height) {
    var h = Math.max(220, Math.min(900, parseInt(height || '420', 10) || 420));
    component.addStyle({ 'min-height': h + 'px' });
    try {
      component.find('.r4v5-slider-pro-slide').forEach(function (slide) { slide.addStyle({ 'min-height': h + 'px' }); });
      component.find('img').forEach(function (img) { img.addStyle({ 'height': '100%' }); });
    } catch (e) {}
    setAttr(component, 'data-height', h);
  }

  function render(component) {
    var box = controlsBox();
    if (!box) return;

    if (!isSlider(component)) return;

    var autoplay = attr(component, 'data-autoplay', 'true');
    var effect = attr(component, 'data-effect', 'slide');
    var interval = attr(component, 'data-interval', '4500');
    var duration = attr(component, 'data-duration', '550');
    var locked = attr(component, 'data-editor-locked', 'false');
    var height = attr(component, 'data-height', '420');

    box.innerHTML = '' +
      '<div class="r4v5-panel-title">Slider Pro</div>' +
      '<div class="r4v5-page-box">' +
        '<label>Animazione<select data-r4v5-slider-control="data-effect"><option value="slide">Slide</option><option value="fade">Fade</option></select></label>' +
        '<label>Autoplay<select data-r4v5-slider-control="data-autoplay"><option value="true">Attivo</option><option value="false">Disattivo</option></select></label>' +
        '<label>Tempo di sosta ms<input type="number" min="800" max="60000" step="100" data-r4v5-slider-control="data-interval"></label>' +
        '<label>Tempo animazione ms<input type="number" min="100" max="10000" step="50" data-r4v5-slider-control="data-duration"></label>' +
        '<label>Altezza slider px<input type="number" min="220" max="900" step="10" data-r4v5-slider-height></label>' +
        '<label>Blocco editor<select data-r4v5-slider-control="data-editor-locked"><option value="true">Bloccato per editing</option><option value="false">Dinamico</option></select></label>' +
        '<button type="button" class="r4v5-mini-btn r4v5-mini-btn-primary" data-r4v5-slider-apply>Applica impostazioni</button>' +
        '<div style="font-size:11px;line-height:1.5;color:#94a3b8;margin-top:6px;">Nel canvas puoi fare doppio click sullo slider per bloccare/sbloccare il movimento. In modalità bloccata testi e immagini sono più facili da modificare.</div>' +
      '</div>';

    box.querySelector('[data-r4v5-slider-control="data-effect"]').value = effect;
    box.querySelector('[data-r4v5-slider-control="data-autoplay"]').value = autoplay;
    box.querySelector('[data-r4v5-slider-control="data-interval"]').value = interval;
    box.querySelector('[data-r4v5-slider-control="data-duration"]').value = duration;
    box.querySelector('[data-r4v5-slider-control="data-editor-locked"]').value = locked;
    box.querySelector('[data-r4v5-slider-height]').value = height;

    box.querySelector('[data-r4v5-slider-apply]').addEventListener('click', function () {
      var ed = editor();
      box.querySelectorAll('[data-r4v5-slider-control]').forEach(function (field) {
        setAttr(component, field.dataset.r4v5SliderControl, field.value);
      });
      setHeight(component, box.querySelector('[data-r4v5-slider-height]').value);
      if (ed && ed.trigger) ed.trigger('update');
      if (ed) syncFields(ed);
      reinitSlider();
    });
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var ed = editor();
      if (ed && ed.on) {
        ed.on('component:selected', function (component) { render(component); });
        ed.on('component:update', function (component) { if (isSlider(component)) render(component); });
        window.clearInterval(timer);
      }
      if (attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
