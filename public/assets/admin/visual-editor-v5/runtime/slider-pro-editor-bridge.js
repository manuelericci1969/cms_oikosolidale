(function () {
  'use strict';

  var runtimeSrc = '/assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js?v=20260507-v5-slider-pro-controls-layer';

  function editor() {
    return window.R4EditorV5 || null;
  }

  function frameDocument() {
    var ed = editor();
    if (!ed || !ed.Canvas || !ed.Canvas.getFrameEl) return null;
    var frame = ed.Canvas.getFrameEl();
    return frame && frame.contentDocument ? frame.contentDocument : null;
  }

  function injectRuntime() {
    var doc = frameDocument();
    if (!doc || !doc.body || !doc.head) return false;

    var oldScript = doc.getElementById('r4v5-slider-pro-runtime-iframe');
    if (oldScript && oldScript.src.indexOf('20260507-v5-slider-pro-controls-layer') === -1) {
      oldScript.remove();
      oldScript = null;
    }

    if (!oldScript) {
      var script = doc.createElement('script');
      script.id = 'r4v5-slider-pro-runtime-iframe';
      script.src = runtimeSrc;
      script.onload = function () { initInsideFrame(); };
      doc.body.appendChild(script);
    } else {
      initInsideFrame();
    }

    return true;
  }

  function initInsideFrame() {
    var doc = frameDocument();
    if (!doc || !doc.defaultView) return;
    if (doc.defaultView.R4V5SliderPro && typeof doc.defaultView.R4V5SliderPro.init === 'function') {
      doc.defaultView.R4V5SliderPro.init();
    }
  }

  function bindEditor() {
    var ed = editor();
    if (!ed || ed.__r4v5SliderProIframeBridge) return !!ed;
    ed.__r4v5SliderProIframeBridge = true;

    if (ed.on) {
      ed.on('load', function () { window.setTimeout(injectRuntime, 250); });
      ed.on('canvas:frame:load', function () { window.setTimeout(injectRuntime, 250); });
      ed.on('component:add', function () { window.setTimeout(injectRuntime, 250); });
      ed.on('component:update', function () { window.setTimeout(initInsideFrame, 120); });
    }

    window.setTimeout(injectRuntime, 400);
    window.setTimeout(injectRuntime, 1000);
    return true;
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      if (bindEditor() || attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  window.R4V5SliderProEditorBridge = {
    inject: injectRuntime,
    init: initInsideFrame
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
