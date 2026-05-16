(function () {
  'use strict';

  var BG_STYLE_PROPS = [
    'background', 'background-color', 'background-image', 'background-size',
    'background-position', 'background-repeat', 'background-attachment',
    'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition',
    'backgroundRepeat', 'backgroundAttachment'
  ];

  var BG_ATTR_PROPS = [
    'data-r4v5-bg-color',
    'data-r4v5-bg-gradient-from',
    'data-r4v5-bg-gradient-to',
    'data-r4v5-bg-gradient-angle',
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
  function cfg() { return window.R4EditorV5Config || {}; }
  function byId(id) { return id ? document.getElementById(id) : null; }
  function attrs(component) { return component && component.getAttributes ? component.getAttributes() || {} : {}; }
  function attr(component, key, fallback) { var a = attrs(component); return a[key] || fallback || ''; }
  function style(component, key, fallback) { var s = component && component.getStyle ? component.getStyle() || {} : {}; return s[key] || fallback || ''; }
  function clamp(value, min, max) { value = parseFloat(value); if (!Number.isFinite(value)) value = min; return Math.max(min, Math.min(max, value)); }

  function removeAttrs(component, mode, extra) {
    if (!component || !component.setAttributes) return;
    var nextAttrs = Object.assign({}, attrs(component));
    BG_ATTR_PROPS.forEach(function (key) { delete nextAttrs[key]; });
    if (mode) nextAttrs['data-r4v5-bg-mode'] = mode;
    else delete nextAttrs['data-r4v5-bg-mode'];
    Object.assign(nextAttrs, extra || {});
    component.setAttributes(nextAttrs);
  }

  function getComponentSelectors(component) {
    var out = [];
    var currentAttrs = attrs(component);
    if (currentAttrs.id) out.push('#' + String(currentAttrs.id));
    try { if (component && typeof component.getId === 'function') { var id = component.getId(); if (id) out.push('#' + String(id)); } } catch (e) {}
    String(currentAttrs.class || currentAttrs.className || '').split(/\s+/).filter(Boolean).forEach(function (name) { out.push('.' + name); });
    try { if (component && typeof component.getClasses === 'function') component.getClasses().forEach(function (name) { out.push('.' + name); }); } catch (e) {}
    return out.filter(function (value, index, array) { return value && array.indexOf(value) === index; });
  }

  function removeBackgroundProps(current) {
    var clean = Object.assign({}, current || {});
    BG_STYLE_PROPS.forEach(function (prop) { delete clean[prop]; });
    return clean;
  }

  function cleanStyleObject(current, nextStyle) {
    return Object.assign(removeBackgroundProps(current), nextStyle || {});
  }

  function cleanComponentStyle(component, nextStyle) {
    if (!component || !component.getStyle || !component.setStyle) return;
    component.setStyle(cleanStyleObject(component.getStyle() || {}, nextStyle || {}));
  }

  function cleanCssComposer(component) {
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
      if (matches) rule.setStyle(removeBackgroundProps(rule.getStyle() || {}));
    });
  }

  function cssKey(key) {
    return String(key || '').replace(/-([a-z])/g, function (_, letter) { return letter.toUpperCase(); });
  }

  function cleanCanvasElement(component, nextStyle) {
    try {
      var el = component && component.view && component.view.el ? component.view.el : null;
      if (!el && component && typeof component.getEl === 'function') el = component.getEl();
      if (!el) return;

      Array.prototype.slice.call(el.querySelectorAll(':scope > [data-r4v5-bg-slider-layer], :scope > [data-r4v5-bg-slider-editor-badge]')).forEach(function (node) {
        if (node && node.parentNode) node.parentNode.removeChild(node);
      });

      ['background', 'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition', 'backgroundRepeat', 'backgroundAttachment'].forEach(function (prop) {
        el.style[prop] = '';
      });

      Object.keys(nextStyle || {}).forEach(function (key) { el.style[cssKey(key)] = nextStyle[key]; });
    } catch (e) {}
  }

  function sync() {
    var ed = editor();
    var c = cfg();
    if (!ed) return;
    var html = c.htmlFieldId ? byId(c.htmlFieldId) : null;
    var css = c.cssFieldId ? byId(c.cssFieldId) : null;
    var json = c.jsonFieldId ? byId(c.jsonFieldId) : null;
    if (html && ed.getHtml) html.value = ed.getHtml();
    if (css && ed.getCss) css.value = ed.getCss();
    if (json && ed.getProjectData) { try { json.value = JSON.stringify(ed.getProjectData()); } catch (e) {} }
  }

  function changed() {
    var ed = editor();
    if (ed && ed.trigger) ed.trigger('update');
    sync();
  }

  function hexToRgb(hex) {
    var v = String(hex || '').trim().replace('#', '');
    if (v.length === 3) v = v.split('').map(function (x) { return x + x; }).join('');
    if (!/^[0-9a-fA-F]{6}$/.test(v)) return { r: 0, g: 0, b: 0 };
    return { r: parseInt(v.slice(0, 2), 16), g: parseInt(v.slice(2, 4), 16), b: parseInt(v.slice(4, 6), 16) };
  }

  function rgba(hex, opacity) {
    var rgb = hexToRgb(hex || '#000000');
    var alpha = clamp(opacity || 0, 0, 0.95);
    return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
  }

  function imageLayer(src) {
    return 'url("' + String(src || '').trim().replace(/"/g, '%22') + '")';
  }

  function buildImageBackground(src, overlayColor, overlayOpacity) {
    if (!src) return '';
    var opacity = clamp(overlayOpacity || 0, 0, 0.95);
    var layer = imageLayer(src);
    if (opacity <= 0) return layer;
    var color = rgba(overlayColor || '#000000', opacity);
    return 'linear-gradient(' + color + ',' + color + '),' + layer;
  }

  function storedImage(component) {
    var dataImage = attr(component, 'data-r4v5-bg-image', '');
    if (dataImage) return dataImage;
    var raw = String(style(component, 'background-image', '') || style(component, 'backgroundImage', '') || style(component, 'background', '') || '');
    var matches = raw.match(/url\(["']?([^"')]+)["']?\)/i);
    return matches ? matches[1] : '';
  }

  function parseSliderImages(component) {
    var raw = attr(component, 'data-r4v5-bg-slider-images', '[]');
    try { var arr = JSON.parse(raw); return Array.isArray(arr) ? arr.filter(Boolean) : []; }
    catch (e) { return String(raw || '').split('|').map(function (item) { return item.trim(); }).filter(Boolean); }
  }

  function normalizeOptions(options) {
    var o = Object.assign({}, options || {});
    o.mode = String(o.mode || 'none').toLowerCase();
    if (['none', 'color', 'gradient', 'image', 'slider'].indexOf(o.mode) === -1) o.mode = 'none';
    return o;
  }

  function applyBase(component, mode, nextStyle, nextAttrs) {
    cleanCssComposer(component);
    cleanComponentStyle(component, nextStyle);
    cleanCanvasElement(component, nextStyle);
    removeAttrs(component, mode, nextAttrs || {});
    changed();
  }

  function applyNone(component) {
    applyBase(component, 'none', {}, {});
  }

  function applyColor(component, options) {
    var color = options.color || '#ffffff';
    var nextStyle = { 'background-color': color };
    if (options.textColor) nextStyle.color = options.textColor;
    applyBase(component, 'color', nextStyle, { 'data-r4v5-bg-color': color });
  }

  function applyGradient(component, options) {
    var angle = parseInt(options.angle || 135, 10);
    if (!Number.isFinite(angle)) angle = 135;
    var from = options.from || '#0d6efd';
    var to = options.to || '#eaf3ff';
    var nextStyle = { background: 'linear-gradient(' + angle + 'deg,' + from + ',' + to + ')' };
    if (options.textColor) nextStyle.color = options.textColor;
    applyBase(component, 'gradient', nextStyle, {
      'data-r4v5-bg-gradient-from': from,
      'data-r4v5-bg-gradient-to': to,
      'data-r4v5-bg-gradient-angle': String(angle)
    });
  }

  function applyImage(component, options) {
    var src = String(options.image || options.src || '').trim();
    if (!src) { applyNone(component); return; }
    var overlayColor = options.overlayColor || '#000000';
    var overlayOpacity = clamp(options.overlayOpacity || 0, 0, 0.95);
    var nextStyle = {
      'background-image': buildImageBackground(src, overlayColor, overlayOpacity),
      'background-size': options.size || 'cover',
      'background-position': options.position || 'center center',
      'background-repeat': options.repeat || 'no-repeat',
      'background-attachment': options.attachment || 'scroll'
    };
    if (options.textColor) nextStyle.color = options.textColor;
    if (options.minHeight) nextStyle['min-height'] = options.minHeight;
    applyBase(component, 'image', nextStyle, {
      'data-r4v5-bg-image': src,
      'data-r4v5-bg-overlay-color': overlayColor,
      'data-r4v5-bg-overlay-opacity': String(overlayOpacity)
    });
  }

  function applySlider(component, options) {
    var images = Array.isArray(options.images) ? options.images.map(function (src) { return String(src || '').trim(); }).filter(Boolean) : [];
    if (!images.length) { applyNone(component); return; }
    var overlayColor = options.overlayColor || '#000000';
    var overlayOpacity = clamp(options.overlayOpacity || 0.35, 0, 0.95);
    var minHeight = options.minHeight || style(component, 'min-height', '') || attr(component, 'data-r4v5-bg-slider-min-height', '') || '420px';
    var nextStyle = {
      position: style(component, 'position', '') && style(component, 'position', '') !== 'static' ? style(component, 'position', '') : 'relative',
      overflow: 'hidden',
      'min-height': minHeight
    };
    if (options.textColor) nextStyle.color = options.textColor;
    applyBase(component, 'slider', nextStyle, {
      'data-r4v5-bg-slider': '1',
      'data-r4v5-bg-slider-images': JSON.stringify(images),
      'data-r4v5-bg-slider-autoplay': String(options.autoplay === false || options.autoplay === 'false' ? 'false' : 'true'),
      'data-r4v5-bg-slider-interval': String(options.interval || 4500),
      'data-r4v5-bg-slider-duration': String(options.duration || 700),
      'data-r4v5-bg-slider-fit': options.fit || options.size || 'cover',
      'data-r4v5-bg-slider-position': options.position || 'center center',
      'data-r4v5-bg-slider-min-height': minHeight,
      'data-r4v5-bg-overlay-color': overlayColor,
      'data-r4v5-bg-overlay-opacity': String(overlayOpacity)
    });
    if (window.R4V5BackgroundSliderBridge && typeof window.R4V5BackgroundSliderBridge.inject === 'function') {
      window.setTimeout(window.R4V5BackgroundSliderBridge.inject, 120);
      window.setTimeout(window.R4V5BackgroundSliderBridge.inject, 350);
    }
  }

  function read(component) {
    var mode = attr(component, 'data-r4v5-bg-mode', '');
    if (!mode) {
      if (attr(component, 'data-r4v5-bg-slider', '') === '1') mode = 'slider';
      else if (storedImage(component)) mode = 'image';
      else if (String(style(component, 'background', '') || '').indexOf('linear-gradient') !== -1) mode = 'gradient';
      else if (attr(component, 'data-r4v5-bg-color', '') || style(component, 'background-color', '') || style(component, 'backgroundColor', '')) mode = 'color';
      else mode = 'none';
    }
    return {
      mode: mode,
      image: storedImage(component),
      images: parseSliderImages(component),
      color: attr(component, 'data-r4v5-bg-color', style(component, 'background-color', style(component, 'backgroundColor', '#ffffff'))),
      gradientFrom: attr(component, 'data-r4v5-bg-gradient-from', '#0d6efd'),
      gradientTo: attr(component, 'data-r4v5-bg-gradient-to', '#eaf3ff'),
      gradientAngle: attr(component, 'data-r4v5-bg-gradient-angle', '135'),
      textColor: style(component, 'color', '#111827'),
      size: style(component, 'background-size', attr(component, 'data-r4v5-bg-slider-fit', 'cover')),
      position: style(component, 'background-position', attr(component, 'data-r4v5-bg-slider-position', 'center center')),
      repeat: style(component, 'background-repeat', 'no-repeat'),
      attachment: style(component, 'background-attachment', 'scroll'),
      overlayColor: attr(component, 'data-r4v5-bg-overlay-color', '#000000'),
      overlayOpacity: attr(component, 'data-r4v5-bg-overlay-opacity', mode === 'slider' ? '0.35' : '0'),
      autoplay: attr(component, 'data-r4v5-bg-slider-autoplay', 'true'),
      interval: attr(component, 'data-r4v5-bg-slider-interval', '4500'),
      duration: attr(component, 'data-r4v5-bg-slider-duration', '700'),
      minHeight: style(component, 'min-height', attr(component, 'data-r4v5-bg-slider-min-height', ''))
    };
  }

  function apply(component, options) {
    if (!component) return false;
    var o = normalizeOptions(options);
    if (o.mode === 'none') applyNone(component);
    else if (o.mode === 'color') applyColor(component, o);
    else if (o.mode === 'gradient') applyGradient(component, o);
    else if (o.mode === 'image') applyImage(component, o);
    else if (o.mode === 'slider') applySlider(component, o);
    return true;
  }

  window.R4V5BackgroundManager = {
    apply: apply,
    clear: function (component) { return apply(component, { mode: 'none' }); },
    read: read,
    sync: sync,
    _internals: {
      cleanCssComposer: cleanCssComposer,
      cleanCanvasElement: cleanCanvasElement,
      cleanComponentStyle: cleanComponentStyle,
      removeAttrs: removeAttrs
    }
  };
})();
