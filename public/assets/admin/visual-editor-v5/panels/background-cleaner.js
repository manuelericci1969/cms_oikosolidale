(function () {
  'use strict';

  var BG_STYLE_PROPS = [
    'background',
    'background-color',
    'background-image',
    'background-size',
    'background-position',
    'background-repeat',
    'background-attachment',
    'backgroundColor',
    'backgroundImage',
    'backgroundSize',
    'backgroundPosition',
    'backgroundRepeat',
    'backgroundAttachment'
  ];

  var BG_ATTR_PROPS = [
    'data-r4v5-bg-image',
    'data-r4v5-bg-slider',
    'data-r4v5-bg-slider-images',
    'data-r4v5-bg-slider-autoplay',
    'data-r4v5-bg-slider-interval',
    'data-r4v5-bg-slider-duration',
    'data-r4v5-bg-slider-fit',
    'data-r4v5-bg-slider-position',
    'data-r4v5-bg-slider-min-height',
    'data-r4v5-bg-overlay-color',
    'data-r4v5-bg-overlay-opacity'
  ];

  function editor() { return window.R4EditorV5 || null; }
  function byId(id) { return document.getElementById(id); }
  function manager() { return window.R4V5BackgroundManager || null; }

  function selectedComponent() {
    var ed = editor();
    return ed && typeof ed.getSelected === 'function' ? ed.getSelected() : null;
  }

  function attrs(component) {
    return component && component.getAttributes ? component.getAttributes() || {} : {};
  }

  function getComponentSelectors(component) {
    var out = [];
    var currentAttrs = attrs(component);

    if (currentAttrs.id) out.push('#' + String(currentAttrs.id));

    try {
      if (component && typeof component.getId === 'function') {
        var id = component.getId();
        if (id) out.push('#' + String(id));
      }
    } catch (e) {}

    String(currentAttrs.class || currentAttrs.className || '')
      .split(/\s+/)
      .filter(Boolean)
      .forEach(function (name) { out.push('.' + name); });

    try {
      if (component && typeof component.getClasses === 'function') {
        component.getClasses().forEach(function (name) { out.push('.' + name); });
      }
    } catch (e) {}

    return out.filter(function (value, index, array) {
      return value && array.indexOf(value) === index;
    });
  }

  function removeBackgroundPropsFromStyle(style, nextStyle) {
    var clean = Object.assign({}, style || {});
    BG_STYLE_PROPS.forEach(function (prop) { delete clean[prop]; });
    return Object.assign(clean, nextStyle || {});
  }

  function removeBackgroundAttrs(component, mode) {
    if (!component || !component.setAttributes) return;
    var nextAttrs = Object.assign({}, attrs(component));
    BG_ATTR_PROPS.forEach(function (prop) { delete nextAttrs[prop]; });
    nextAttrs['data-r4v5-bg-mode'] = mode || 'none';
    component.setAttributes(nextAttrs);
  }

  function cleanComponentStyle(component, nextStyle) {
    if (!component || !component.getStyle || !component.setStyle) return;
    component.setStyle(removeBackgroundPropsFromStyle(component.getStyle() || {}, nextStyle || {}));
  }

  function cleanCssComposer(component, nextStyle) {
    var ed = editor();
    if (!ed || !ed.CssComposer || typeof ed.CssComposer.getAll !== 'function') return;

    var selectors = getComponentSelectors(component);
    if (!selectors.length) return;

    var rules = ed.CssComposer.getAll();
    if (!rules || !rules.forEach) return;

    rules.forEach(function (rule) {
      if (!rule || !rule.getStyle || !rule.setStyle) return;

      var selectorText = '';
      try {
        if (typeof rule.getSelectorsString === 'function') selectorText = rule.getSelectorsString();
        else if (typeof rule.selectorsToString === 'function') selectorText = rule.selectorsToString();
        else selectorText = String(rule.get && rule.get('selectors') || '');
      } catch (e) {}

      if (!selectorText) return;
      var matches = selectors.some(function (selector) { return selectorText.indexOf(selector) !== -1; });
      if (matches) rule.setStyle(removeBackgroundPropsFromStyle(rule.getStyle() || {}, nextStyle || {}));
    });
  }

  function cleanCanvasElement(component, nextStyle) {
    try {
      var el = null;
      if (component && component.view && component.view.el) el = component.view.el;
      if (!el && component && typeof component.getEl === 'function') el = component.getEl();
      if (!el) return;

      Array.prototype.slice.call(el.querySelectorAll(':scope > [data-r4v5-bg-slider-layer], :scope > [data-r4v5-bg-slider-editor-badge]')).forEach(function (node) {
        if (node && node.parentNode) node.parentNode.removeChild(node);
      });

      ['background', 'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition', 'backgroundRepeat', 'backgroundAttachment'].forEach(function (prop) {
        el.style[prop] = '';
      });

      Object.keys(nextStyle || {}).forEach(function (key) {
        var domKey = key.replace(/-([a-z])/g, function (_, letter) { return letter.toUpperCase(); });
        el.style[domKey] = nextStyle[key];
      });
    } catch (e) {}
  }

  function syncFields() {
    var m = manager();
    if (m && typeof m.sync === 'function') { m.sync(); return; }

    var ed = editor();
    var cfg = window.R4EditorV5Config || {};
    if (!ed || !cfg) return;

    var html = cfg.htmlFieldId ? byId(cfg.htmlFieldId) : null;
    var css = cfg.cssFieldId ? byId(cfg.cssFieldId) : null;
    var json = cfg.jsonFieldId ? byId(cfg.jsonFieldId) : null;

    if (html && ed.getHtml) html.value = ed.getHtml();
    if (css && ed.getCss) css.value = ed.getCss();
    if (json && ed.getProjectData) {
      try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {}
    }
  }

  function nextStyleForCurrentMode() {
    var modeField = byId('r4v5BgMode');
    var mode = modeField ? String(modeField.value || 'none') : 'none';

    if (mode === 'color') {
      var color = byId('r4v5BgColor');
      var textColor = byId('r4v5BgTextColor');
      var colorStyle = { 'background-color': color && color.value ? color.value : '#ffffff' };
      if (textColor && textColor.value) colorStyle.color = textColor.value;
      return { mode: 'color', style: colorStyle };
    }

    if (mode === 'gradient') {
      var angle = byId('r4v5BgGradientAngle');
      var from = byId('r4v5BgGradientFrom');
      var to = byId('r4v5BgGradientTo');
      return {
        mode: 'gradient',
        style: {
          background: 'linear-gradient(' + (angle && angle.value ? angle.value : '135') + 'deg,' + (from && from.value ? from.value : '#0d6efd') + ',' + (to && to.value ? to.value : '#eaf3ff') + ')'
        },
        from: from && from.value ? from.value : '#0d6efd',
        to: to && to.value ? to.value : '#eaf3ff',
        angle: angle && angle.value ? angle.value : '135'
      };
    }

    return { mode: 'none', style: {} };
  }

  function deepCleanBackground(component, forced) {
    var target = component || selectedComponent();
    if (!target) return;

    var next = forced || nextStyleForCurrentMode();
    var m = manager();

    if (m && typeof m.apply === 'function') {
      if (next.mode === 'color') {
        m.apply(target, { mode: 'color', color: next.style['background-color'] || '#ffffff', textColor: next.style.color || '' });
        return;
      }
      if (next.mode === 'gradient') {
        m.apply(target, { mode: 'gradient', from: next.from || '#0d6efd', to: next.to || '#eaf3ff', angle: next.angle || 135 });
        return;
      }
      m.apply(target, { mode: 'none' });
      return;
    }

    cleanComponentStyle(target, next.style);
    cleanCssComposer(target, next.style);
    cleanCanvasElement(target, next.style);
    removeBackgroundAttrs(target, next.mode);

    var imageInput = byId('r4v5BgImagePreview');
    if (imageInput && next.mode !== 'image') imageInput.value = '';

    var ed = editor();
    if (ed && ed.trigger) ed.trigger('update');
    syncFields();
  }

  function shouldDeepCleanFromClick(event) {
    var remove = event.target.closest && event.target.closest('#r4v5BgRemoveImage, #r4v5BgClear');
    if (remove) return true;

    var apply = event.target.closest && event.target.closest('#r4v5BgApply, #r4v5BgApplyColor, #r4v5BgApplyGradient');
    if (!apply) return false;

    var mode = byId('r4v5BgMode');
    return mode && ['none', 'color', 'gradient'].indexOf(String(mode.value || 'none')) !== -1;
  }

  function bindClicks() {
    document.addEventListener('click', function (event) {
      if (!shouldDeepCleanFromClick(event)) return;

      var mode = byId('r4v5BgMode');
      var forced = null;

      if (event.target.closest('#r4v5BgRemoveImage, #r4v5BgClear')) {
        if (mode) mode.value = 'none';
        forced = { mode: 'none', style: {} };
      }

      window.setTimeout(function () { deepCleanBackground(null, forced); }, 0);
      window.setTimeout(function () { deepCleanBackground(null, forced); }, 120);
    }, true);
  }

  window.R4V5BackgroundCleaner = {
    cleanSelected: function () { deepCleanBackground(); },
    cleanComponent: function (component, mode, style) { deepCleanBackground(component, { mode: mode || 'none', style: style || {} }); }
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindClicks);
  else bindClicks();
})();
