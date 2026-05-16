(function () {
  'use strict';

  function injectNavigationFailSafeStyles() {
    if (document.getElementById('r4v5-slider-pro-navigation-failsafe')) return;
    var style = document.createElement('style');
    style.id = 'r4v5-slider-pro-navigation-failsafe';
    style.textContent = '' +
      '.r4v5-slider-pro{position:relative!important;overflow:hidden!important;isolation:isolate!important;}' +
      '.r4v5-slider-pro-track{position:relative!important;z-index:1!important;}' +
      '.r4v5-slider-pro-slide{z-index:1!important;}' +
      '.r4v5-slider-pro-controls{position:absolute!important;inset:0!important;z-index:9999!important;pointer-events:none!important;}' +
      '.r4v5-slider-pro-controls .r4v5-slider-pro-arrow{position:absolute!important;top:50%!important;transform:translateY(-50%)!important;z-index:10000!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;width:44px!important;height:44px!important;border-radius:999px!important;border:1px solid rgba(255,255,255,.32)!important;background:rgba(2,6,23,.62)!important;color:#fff!important;font-size:28px!important;line-height:1!important;cursor:pointer!important;box-shadow:0 14px 30px rgba(2,6,23,.28)!important;}' +
      '.r4v5-slider-pro-controls .r4v5-slider-pro-prev{left:18px!important;right:auto!important;}' +
      '.r4v5-slider-pro-controls .r4v5-slider-pro-next{right:18px!important;left:auto!important;}' +
      '.r4v5-slider-pro-controls .r4v5-slider-pro-dots{position:absolute!important;left:0!important;right:0!important;bottom:18px!important;z-index:10000!important;display:flex!important;justify-content:center!important;gap:8px!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;}' +
      '.r4v5-slider-pro-controls .r4v5-slider-pro-dots button{pointer-events:auto!important;}';
    document.head.appendChild(style);
  }

  function isEditorCanvas() {
    try { return !!(window.parent && window.parent !== window && window.parent.R4EditorV5); } catch (e) { return false; }
  }

  function numberAttr(root, name, fallback, min, max) {
    var value = parseInt(root.dataset[name] || String(fallback), 10);
    if (!Number.isFinite(value)) value = fallback;
    if (typeof min === 'number') value = Math.max(min, value);
    if (typeof max === 'number') value = Math.min(max, value);
    return value;
  }

  function boolAttr(root, name, fallback) {
    var raw = root.dataset[name];
    if (raw === undefined || raw === null || raw === '') return fallback;
    return raw !== 'false' && raw !== '0' && raw !== 'no';
  }

  function closest(target, selector) { return target && target.closest ? target.closest(selector) : null; }

  function isSliderControl(target) {
    return !!closest(target, '[data-r4v5-slider-prev], [data-r4v5-slider-next], [data-r4v5-slider-dot], .r4v5-slider-pro-arrow, .r4v5-slider-pro-dots, .r4v5-slider-pro-controls');
  }

  function isEditableTextTarget(target) {
    if (!target || isSliderControl(target)) return false;
    return !!closest(target, 'h1,h2,h3,h4,h5,h6,p,a,span,strong,em,small,li,blockquote,[contenteditable="true"],[data-gjs-type="text"],[data-gjs-type="link"]');
  }

  function ensureEditBadge(root) {
    if (!isEditorCanvas() || root.querySelector('[data-r4v5-slider-edit-badge]')) return;
    var badge = document.createElement('div');
    badge.setAttribute('data-r4v5-slider-edit-badge', '1');
    badge.textContent = 'Slider bloccato per editing — sblocca dall’Inspector';
    badge.style.cssText = 'position:absolute;left:16px;top:16px;z-index:10001;padding:7px 10px;border-radius:999px;background:rgba(2,6,23,.78);color:#fff;font:800 11px/1 system-ui;letter-spacing:.02em;pointer-events:none;display:none;';
    root.appendChild(badge);
  }

  function updateEditBadge(root) {
    var badge = root.querySelector('[data-r4v5-slider-edit-badge]');
    if (badge) badge.style.display = root.dataset.editorLocked === 'true' ? 'block' : 'none';
  }

  function dotButtonHtml(i) {
    return '<button type="button" data-r4v5-slider-dot="' + i + '" style="width:10px;height:10px;border-radius:999px;border:0;background:#fff;opacity:' + (i === 0 ? '1' : '.45') + ';cursor:pointer;"></button>';
  }

  function ensureControlsLayer(root, slideCount) {
    var oldDirectPrev = root.querySelector(':scope > [data-r4v5-slider-prev]');
    var oldDirectNext = root.querySelector(':scope > [data-r4v5-slider-next]');
    var oldDirectDots = root.querySelector(':scope > [data-r4v5-slider-dots]');
    if (oldDirectPrev) oldDirectPrev.remove();
    if (oldDirectNext) oldDirectNext.remove();
    if (oldDirectDots) oldDirectDots.remove();

    var controls = root.querySelector(':scope > .r4v5-slider-pro-controls');
    if (!controls) {
      controls = document.createElement('div');
      controls.className = 'r4v5-slider-pro-controls';
      controls.setAttribute('data-r4v5-slider-controls', '1');
      root.appendChild(controls);
    }

    var prev = controls.querySelector('[data-r4v5-slider-prev]');
    if (!prev) {
      prev = document.createElement('button');
      prev.type = 'button';
      prev.className = 'r4v5-slider-pro-arrow r4v5-slider-pro-prev';
      prev.setAttribute('data-r4v5-slider-prev', '1');
      prev.innerHTML = '‹';
      controls.appendChild(prev);
    }

    var next = controls.querySelector('[data-r4v5-slider-next]');
    if (!next) {
      next = document.createElement('button');
      next.type = 'button';
      next.className = 'r4v5-slider-pro-arrow r4v5-slider-pro-next';
      next.setAttribute('data-r4v5-slider-next', '1');
      next.innerHTML = '›';
      controls.appendChild(next);
    }

    var dots = controls.querySelector('[data-r4v5-slider-dots]');
    if (!dots) {
      dots = document.createElement('div');
      dots.className = 'r4v5-slider-pro-dots';
      dots.setAttribute('data-r4v5-slider-dots', '1');
      controls.appendChild(dots);
    }

    var currentDots = dots.querySelectorAll('[data-r4v5-slider-dot]').length;
    if (currentDots !== slideCount) {
      var html = '';
      for (var i = 0; i < slideCount; i++) html += dotButtonHtml(i);
      dots.innerHTML = html;
    }

    return { controls: controls, prev: prev, next: next, dots: dots, dotButtons: Array.prototype.slice.call(dots.querySelectorAll('[data-r4v5-slider-dot]')) };
  }

  function normalizeControls(root) {
    if (!root) return { prev: null, next: null, dots: null, dotButtons: [] };
    root.style.position = 'relative';
    root.style.overflow = 'hidden';
    root.style.isolation = 'isolate';

    var track = root.querySelector('.r4v5-slider-pro-track');
    if (track) {
      track.style.position = 'relative';
      track.style.zIndex = '1';
    }
    var slides = Array.prototype.slice.call(root.querySelectorAll('.r4v5-slider-pro-slide'));
    slides.forEach(function (slide) { slide.style.zIndex = '1'; });

    var layer = ensureControlsLayer(root, slides.length);
    layer.controls.style.cssText = 'position:absolute!important;inset:0!important;z-index:9999!important;pointer-events:none!important;';
    layer.prev.style.cssText = 'position:absolute!important;left:18px!important;right:auto!important;top:50%!important;transform:translateY(-50%)!important;z-index:10000!important;pointer-events:auto!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;width:44px!important;height:44px!important;border-radius:999px!important;border:1px solid rgba(255,255,255,.32)!important;background:rgba(2,6,23,.62)!important;color:#fff!important;font-size:28px!important;line-height:1!important;cursor:pointer!important;opacity:1!important;visibility:visible!important;box-shadow:0 14px 30px rgba(2,6,23,.28)!important;';
    layer.next.style.cssText = 'position:absolute!important;right:18px!important;left:auto!important;top:50%!important;transform:translateY(-50%)!important;z-index:10000!important;pointer-events:auto!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;width:44px!important;height:44px!important;border-radius:999px!important;border:1px solid rgba(255,255,255,.32)!important;background:rgba(2,6,23,.62)!important;color:#fff!important;font-size:28px!important;line-height:1!important;cursor:pointer!important;opacity:1!important;visibility:visible!important;box-shadow:0 14px 30px rgba(2,6,23,.28)!important;';
    layer.dots.style.cssText = 'position:absolute!important;left:0!important;right:0!important;bottom:18px!important;z-index:10000!important;pointer-events:auto!important;display:flex!important;justify-content:center!important;gap:8px!important;opacity:1!important;visibility:visible!important;';
    return layer;
  }

  function prepareEffect(root, track, slides, effect, duration) {
    if (!track) return;
    track.style.transitionDuration = duration + 'ms';
    track.style.zIndex = '1';
    if (effect === 'fade') {
      root.style.overflow = 'hidden';
      track.style.display = 'block';
      track.style.position = 'relative';
      track.style.transform = 'none';
      slides.forEach(function (slide) {
        slide.style.position = 'absolute';
        slide.style.inset = '0';
        slide.style.minWidth = '100%';
        slide.style.width = '100%';
        slide.style.opacity = '0';
        slide.style.pointerEvents = 'none';
        slide.style.transition = 'opacity ' + duration + 'ms ease';
        slide.style.zIndex = '1';
      });
      if (slides[0]) {
        root.style.minHeight = slides[0].style.minHeight || root.style.minHeight || '420px';
        track.style.minHeight = slides[0].style.minHeight || root.style.minHeight || '420px';
      }
    } else {
      track.style.display = 'flex';
      track.style.position = 'relative';
      track.style.transition = 'transform ' + duration + 'ms ease';
      slides.forEach(function (slide) {
        slide.style.position = 'relative';
        slide.style.inset = '';
        slide.style.minWidth = '100%';
        slide.style.width = '';
        slide.style.opacity = '';
        slide.style.pointerEvents = '';
        slide.style.transition = '';
        slide.style.zIndex = '1';
      });
    }
    normalizeControls(root);
  }

  function initSlider(root) {
    if (!root) return;

    if (root.dataset.r4v5SliderReady === '1' && root.__r4v5SliderApi) {
      root.__r4v5SliderApi.refresh();
      return;
    }

    root.dataset.r4v5SliderReady = '1';

    var track = root.querySelector('.r4v5-slider-pro-track');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.r4v5-slider-pro-slide'));
    var controls = normalizeControls(root);
    var dots = controls.dotButtons;
    var prev = controls.prev;
    var next = controls.next;
    var index = 0;
    var timer = null;
    var editorCanvas = isEditorCanvas();

    function options() {
      return {
        autoplay: boolAttr(root, 'autoplay', true),
        interval: numberAttr(root, 'interval', 4500, 800, 60000),
        duration: numberAttr(root, 'duration', 550, 100, 10000),
        effect: (root.dataset.effect || 'slide') === 'fade' ? 'fade' : 'slide',
        locked: root.dataset.editorLocked === 'true'
      };
    }

    function bindControls() {
      if (prev && !prev.dataset.r4v5Bound) {
        prev.dataset.r4v5Bound = '1';
        prev.addEventListener('click', function (event) { event.preventDefault(); event.stopPropagation(); go(index - 1); start(); });
      }
      if (next && !next.dataset.r4v5Bound) {
        next.dataset.r4v5Bound = '1';
        next.addEventListener('click', function (event) { event.preventDefault(); event.stopPropagation(); go(index + 1); start(); });
      }
      dots.forEach(function (dot) {
        if (dot.dataset.r4v5Bound) return;
        dot.dataset.r4v5Bound = '1';
        dot.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          go(parseInt(dot.dataset.r4v5SliderDot || '0', 10));
          start();
        });
      });
    }

    function refresh() {
      track = root.querySelector('.r4v5-slider-pro-track');
      slides = Array.prototype.slice.call(root.querySelectorAll('.r4v5-slider-pro-slide'));
      controls = normalizeControls(root);
      dots = controls.dotButtons;
      prev = controls.prev;
      next = controls.next;
      bindControls();
      var opt = options();
      prepareEffect(root, track, slides, opt.effect, opt.duration);
      if (index >= slides.length) index = 0;
      go(index, true);
      if (opt.locked) stop(); else start();
      updateEditBadge(root);
    }

    function go(to, silent) {
      var opt = options();
      if (!slides.length || !track) return;
      index = (to + slides.length) % slides.length;

      if (opt.effect === 'fade') {
        track.style.transform = 'none';
        slides.forEach(function (slide, i) {
          slide.classList.toggle('is-active', i === index);
          slide.style.opacity = i === index ? '1' : '0';
          slide.style.pointerEvents = i === index ? 'auto' : 'none';
          slide.style.zIndex = i === index ? '2' : '1';
        });
      } else {
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === index); slide.style.zIndex = '1'; });
      }

      controls = normalizeControls(root);
      dots = controls.dotButtons;
      dots.forEach(function (dot, i) { dot.style.opacity = i === index ? '1' : '.45'; });
      if (!silent) root.dispatchEvent(new CustomEvent('r4v5-slider-change', { detail: { index: index } }));
    }

    function start() {
      stop();
      var opt = options();
      if (!opt.autoplay || opt.locked || slides.length < 2) return;
      timer = window.setInterval(function () { go(index + 1); }, opt.interval);
    }

    function stop() { if (timer) window.clearInterval(timer); timer = null; }

    function lockEditor() {
      if (!editorCanvas) return;
      root.dataset.editorLocked = 'true';
      stop();
      updateEditBadge(root);
    }

    function handleEditorDblClick(event) {
      if (!editorCanvas) return;
      if (isEditableTextTarget(event.target)) { lockEditor(); return; }
      if (isSliderControl(event.target)) return;
      lockEditor();
    }

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', lockEditor);
    root.addEventListener('click', function (event) { if (editorCanvas && !isSliderControl(event.target)) lockEditor(); }, true);
    root.addEventListener('dblclick', handleEditorDblClick, true);

    ensureEditBadge(root);
    root.__r4v5SliderApi = { refresh: refresh, stop: stop, start: start, go: go, lockEditor: lockEditor };
    refresh();
  }

  function init() {
    injectNavigationFailSafeStyles();
    Array.prototype.slice.call(document.querySelectorAll('[data-r4v5-slider-pro]')).forEach(initSlider);
  }

  window.R4V5SliderPro = { init: init };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
