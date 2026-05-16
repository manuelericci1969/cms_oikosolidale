(function () {
  'use strict';

  var runtimeSrc = '/assets/admin/visual-editor-v5/runtime/animations-runtime.js?v=20260506-v5-animations-runtime';

  function editor() { return window.R4EditorV5 || null; }

  function frameDocument() {
    var ed = editor();
    if (!ed || !ed.Canvas || !ed.Canvas.getFrameEl) return null;
    var frame = ed.Canvas.getFrameEl();
    return frame && frame.contentDocument ? frame.contentDocument : null;
  }

  function initInsideFrame() {
    var doc = frameDocument();
    if (!doc || !doc.defaultView) return;
    if (doc.defaultView.R4V5AnimationsRuntime && typeof doc.defaultView.R4V5AnimationsRuntime.refresh === 'function') {
      doc.defaultView.R4V5AnimationsRuntime.refresh();
    }
  }

  function injectRuntime() {
    var doc = frameDocument();
    if (!doc || !doc.body) return false;
    if (!doc.getElementById('r4v5-animations-runtime-iframe')) {
      var script = doc.createElement('script');
      script.id = 'r4v5-animations-runtime-iframe';
      script.src = runtimeSrc;
      script.onload = initInsideFrame;
      doc.body.appendChild(script);
    } else {
      initInsideFrame();
    }
    return true;
  }

  function bindEditor() {
    var ed = editor();
    if (!ed || ed.__r4v5AnimationsBridge) return !!ed;
    ed.__r4v5AnimationsBridge = true;
    if (ed.on) {
      ed.on('load', function () { window.setTimeout(injectRuntime, 250); });
      ed.on('canvas:frame:load', function () { window.setTimeout(injectRuntime, 250); });
      ed.on('component:add', function () { window.setTimeout(injectRuntime, 150); });
      ed.on('component:update', function () { window.setTimeout(initInsideFrame, 120); });
      ed.on('component:selected', function () { window.setTimeout(initInsideFrame, 120); });
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

  window.R4V5AnimationsBridge = {
    inject: injectRuntime,
    init: initInsideFrame
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
