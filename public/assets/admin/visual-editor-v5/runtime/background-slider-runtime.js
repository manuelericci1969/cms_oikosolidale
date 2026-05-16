(function () {
  'use strict';

  function parseImages(root) {
    var raw = root.getAttribute('data-r4v5-bg-slider-images') || '[]';
    try {
      var images = JSON.parse(raw);
      return Array.isArray(images) ? images.filter(Boolean) : [];
    } catch (e) {
      return String(raw || '').split('|').map(function (item) { return item.trim(); }).filter(Boolean);
    }
  }

  function numberAttr(root, name, fallback, min, max) {
    var value = parseInt(root.getAttribute(name) || String(fallback), 10);
    if (!Number.isFinite(value)) value = fallback;
    if (typeof min === 'number') value = Math.max(min, value);
    if (typeof max === 'number') value = Math.min(max, value);
    return value;
  }

  function boolAttr(root, name, fallback) {
    var raw = root.getAttribute(name);
    if (raw === null || raw === undefined || raw === '') return fallback;
    return raw !== 'false' && raw !== '0' && raw !== 'no';
  }

  function hexToRgb(hex) {
    var value = String(hex || '').trim().replace('#', '');
    if (value.length === 3) value = value.split('').map(function (c) { return c + c; }).join('');
    if (!/^[0-9a-fA-F]{6}$/.test(value)) return { r: 0, g: 0, b: 0 };
    return {
      r: parseInt(value.slice(0, 2), 16),
      g: parseInt(value.slice(2, 4), 16),
      b: parseInt(value.slice(4, 6), 16)
    };
  }

  function rgba(hex, opacity) {
    var rgb = hexToRgb(hex || '#000000');
    var alpha = Math.max(0, Math.min(0.95, parseFloat(opacity || '0') || 0));
    return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
  }

  function isEditorCanvas() {
    try {
      return !!(window.parent && window.parent !== window && window.parent.R4EditorV5);
    } catch (e) {
      return false;
    }
  }

  function ensureLayer(root) {
    var layer = root.querySelector(':scope > [data-r4v5-bg-slider-layer]');
    if (layer) return layer;

    layer = document.createElement('div');
    layer.setAttribute('data-r4v5-bg-slider-layer', '1');
    layer.style.cssText = 'position:absolute;inset:0;z-index:0;overflow:hidden;border-radius:inherit;pointer-events:none;';
    root.insertBefore(layer, root.firstChild);

    Array.prototype.slice.call(root.children).forEach(function (child) {
      if (child === layer) return;
      if (child.style.position === '' || child.style.position === 'static') child.style.position = 'relative';
      if (!child.style.zIndex) child.style.zIndex = '2';
    });

    return layer;
  }

  function ensureBadge(root) {
    if (!isEditorCanvas() || root.querySelector(':scope > [data-r4v5-bg-slider-editor-badge]')) return;
    var badge = document.createElement('div');
    badge.setAttribute('data-r4v5-bg-slider-editor-badge', '1');
    badge.textContent = 'Sfondo slider attivo';
    badge.style.cssText = 'position:absolute;left:14px;bottom:14px;z-index:20;padding:7px 10px;border-radius:999px;background:rgba(2,6,23,.72);color:#fff;font:800 11px/1 system-ui;letter-spacing:.02em;pointer-events:none;';
    root.appendChild(badge);
  }

  function build(root, images) {
    var layer = ensureLayer(root);
    var overlayColor = root.getAttribute('data-r4v5-bg-overlay-color') || '#000000';
    var overlayOpacity = root.getAttribute('data-r4v5-bg-overlay-opacity') || '0.35';
    var fit = root.getAttribute('data-r4v5-bg-slider-fit') || 'cover';
    var pos = root.getAttribute('data-r4v5-bg-slider-position') || 'center center';
    var duration = numberAttr(root, 'data-r4v5-bg-slider-duration', 700, 100, 10000);

    layer.innerHTML = images.map(function (src, index) {
      var safeSrc = String(src || '').replace(/"/g, '%22');
      return '<div data-r4v5-bg-slider-slide="' + index + '" style="position:absolute;inset:0;opacity:' + (index === 0 ? '1' : '0') + ';transition:opacity ' + duration + 'ms ease;background-image:url(&quot;' + safeSrc + '&quot;);background-size:' + fit + ';background-position:' + pos + ';background-repeat:no-repeat;"></div>';
    }).join('') + '<div data-r4v5-bg-slider-overlay style="position:absolute;inset:0;background:' + rgba(overlayColor, overlayOpacity) + ';"></div>';

    return Array.prototype.slice.call(layer.querySelectorAll('[data-r4v5-bg-slider-slide]'));
  }

  function initOne(root) {
    if (!root) return;

    if (root.__r4v5BgSliderReady && root.__r4v5BgSliderApi) {
      root.__r4v5BgSliderApi.refresh();
      return;
    }

    var images = parseImages(root);
    if (!images.length) return;

    root.__r4v5BgSliderReady = true;
    root.style.position = root.style.position && root.style.position !== 'static' ? root.style.position : 'relative';
    root.style.overflow = root.style.overflow || 'hidden';
    if (!root.style.minHeight) root.style.minHeight = root.getAttribute('data-r4v5-bg-slider-min-height') || '420px';

    var slides = [];
    var index = 0;
    var timer = null;

    function options() {
      return {
        autoplay: boolAttr(root, 'data-r4v5-bg-slider-autoplay', true),
        interval: numberAttr(root, 'data-r4v5-bg-slider-interval', 4500, 800, 60000),
        duration: numberAttr(root, 'data-r4v5-bg-slider-duration', 700, 100, 10000)
      };
    }

    function go(next) {
      if (!slides.length) return;
      var opt = options();
      index = (next + slides.length) % slides.length;
      slides.forEach(function (slide, i) {
        slide.style.transitionDuration = opt.duration + 'ms';
        slide.style.opacity = i === index ? '1' : '0';
      });
    }

    function stop() {
      if (timer) window.clearInterval(timer);
      timer = null;
    }

    function start() {
      stop();
      var opt = options();
      if (!opt.autoplay || slides.length < 2) return;
      timer = window.setInterval(function () { go(index + 1); }, opt.interval);
    }

    function refresh() {
      images = parseImages(root);
      slides = build(root, images);
      ensureBadge(root);
      go(index);
      start();
    }

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.__r4v5BgSliderApi = { refresh: refresh, start: start, stop: stop, go: go };
    refresh();
  }

  function init() {
    Array.prototype.slice.call(document.querySelectorAll('[data-r4v5-bg-slider="1"]')).forEach(initOne);
  }

  window.R4V5BackgroundSlider = { init: init };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
