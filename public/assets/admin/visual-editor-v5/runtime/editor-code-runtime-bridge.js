(function () {
  'use strict';

  var animationRuntimeSrc = '/assets/editor-v5/runtime/public-animations.js?v=20260512-v5-editor-code-runtime';
  var customJsMemory = '';

  function editor() { return window.R4EditorV5 || null; }
  function byId(id) { return id ? document.getElementById(id) : null; }

  function frameDocument() {
    var ed = editor();
    if (!ed || !ed.Canvas || !ed.Canvas.getFrameEl) return null;
    var frame = ed.Canvas.getFrameEl();
    return frame && frame.contentDocument ? frame.contentDocument : null;
  }

  function visualJsonField() { return byId((window.R4EditorV5Config || {}).jsonFieldId || 'r4v5VisualJson'); }

  function parseJson(value) {
    try { return value && String(value).trim() ? JSON.parse(value) : {}; }
    catch (e) { return {}; }
  }

  function extractScripts(html) {
    var scripts = [];
    String(html || '').replace(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi, function (full, attrs, body) {
      var idMatch = String(attrs || '').match(/\bid=["']([^"']+)["']/i);
      var id = idMatch ? idMatch[1] : '';
      if (id && id.indexOf('r4v5-') === 0) return '';
      if (String(body || '').trim()) scripts.push(String(body || '').trim());
      return '';
    });
    return scripts.join('\n\n').trim();
  }

  function readPersistedCustomJs() {
    if (customJsMemory && customJsMemory.trim()) return customJsMemory;
    var field = visualJsonField();
    var data = parseJson(field ? field.value : '');
    if (typeof data.r4v5CustomJs === 'string' && data.r4v5CustomJs.trim()) return data.r4v5CustomJs;
    return '';
  }

  function writePersistedCustomJs(value) {
    customJsMemory = String(value || '');
    var field = visualJsonField();
    if (!field) return;
    var data = parseJson(field.value || '{}');
    data.r4v5CustomJs = customJsMemory;
    field.value = JSON.stringify(data);
  }

  function captureCustomJsFromModal() {
    var jsField = byId('r4v5CodeJs');
    var htmlField = byId('r4v5CodeHtml');
    var fromJs = jsField ? String(jsField.value || '').trim() : '';
    var fromHtml = htmlField ? extractScripts(htmlField.value || '') : '';
    var value = fromJs || fromHtml || customJsMemory || readPersistedCustomJs();
    writePersistedCustomJs(value);
    return value;
  }

  function patchVisualJsonAfterEditorSync() {
    var value = customJsMemory || readPersistedCustomJs();
    writePersistedCustomJs(value);
  }

  function hydrateModalCustomJs() {
    var jsField = byId('r4v5CodeJs');
    if (!jsField) return;
    var value = readPersistedCustomJs();
    if (value && !String(jsField.value || '').trim()) jsField.value = value;
  }

  function injectAnimationRuntime() {
    var doc = frameDocument();
    if (!doc || !doc.body) return false;

    if (!doc.getElementById('r4v5-editor-animation-runtime-iframe')) {
      var script = doc.createElement('script');
      script.id = 'r4v5-editor-animation-runtime-iframe';
      script.src = animationRuntimeSrc;
      script.onload = initAnimations;
      doc.body.appendChild(script);
    } else {
      initAnimations();
    }

    return true;
  }

  function initAnimations() {
    try {
      var doc = frameDocument();
      if (!doc || !doc.defaultView) return;
      if (doc.defaultView.R4V5PublicAnimations && typeof doc.defaultView.R4V5PublicAnimations.init === 'function') {
        doc.defaultView.R4V5PublicAnimations.init();
      }
    } catch (error) {}
  }

  function executeCustomJs() {
    var doc = frameDocument();
    if (!doc || !doc.body) return false;

    var scripts = [];
    Array.prototype.slice.call(doc.querySelectorAll('script[data-r4v5-custom-js="1"]')).forEach(function (script) {
      var code = script.textContent || '';
      if (code.trim()) scripts.push(code.trim());
    });

    var persisted = readPersistedCustomJs();
    if (persisted && persisted.trim()) scripts.push(persisted.trim());

    var code = scripts.filter(Boolean).join('\n\n').trim();
    if (!code) return false;

    var hash = String(code.length) + ':' + code.slice(0, 80);
    if (doc.body.getAttribute('data-r4v5-custom-js-hash') === hash) return true;
    doc.body.setAttribute('data-r4v5-custom-js-hash', hash);

    try {
      Array.prototype.slice.call(doc.querySelectorAll('[data-r4v5-editor-custom-js-runner]')).forEach(function (node) {
        if (node && node.parentNode) node.parentNode.removeChild(node);
      });
      var runner = doc.createElement('script');
      runner.type = 'text/javascript';
      runner.setAttribute('data-r4v5-editor-custom-js-runner', '1');
      runner.text = '(function(){try{\n' + code + '\n}catch(error){console.warn("[R4 Editor V5] Custom JS editor runtime", error);}})();';
      doc.body.appendChild(runner);
    } catch (error) {}

    return true;
  }

  function applySlotInlineStyles(slot, placeholder) {
    slot.style.setProperty('display', 'block', 'important');
    slot.style.setProperty('width', '100%', 'important');
    slot.style.setProperty('clear', 'both', 'important');
    slot.style.setProperty('position', 'relative', 'important');
    slot.style.setProperty('min-height', '92px', 'important');
    slot.style.setProperty('padding', '18px 24px', 'important');
    slot.style.setProperty('margin', '0', 'important');
    slot.style.setProperty('background', 'linear-gradient(180deg, rgba(248,250,252,.98), rgba(255,255,255,.98))', 'important');
    slot.style.setProperty('box-sizing', 'border-box', 'important');
    slot.style.setProperty('border', '0', 'important');

    placeholder.style.setProperty('display', 'flex', 'important');
    placeholder.style.setProperty('align-items', 'center', 'important');
    placeholder.style.setProperty('justify-content', 'center', 'important');
    placeholder.style.setProperty('gap', '10px', 'important');
    placeholder.style.setProperty('max-width', '1120px', 'important');
    placeholder.style.setProperty('min-height', '54px', 'important');
    placeholder.style.setProperty('margin', '0 auto', 'important');
    placeholder.style.setProperty('border', '1px dashed rgba(13,110,253,.55)', 'important');
    placeholder.style.setProperty('border-radius', '18px', 'important');
    placeholder.style.setProperty('background', 'rgba(13,110,253,.055)', 'important');
    placeholder.style.setProperty('color', '#0d6efd', 'important');
    placeholder.style.setProperty('font', '900 12px/1.2 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif', 'important');
    placeholder.style.setProperty('text-align', 'center', 'important');
    placeholder.style.setProperty('box-shadow', '0 12px 28px rgba(15,23,42,.08)', 'important');
    placeholder.style.setProperty('pointer-events', 'none', 'important');
  }

  function enhanceDropSlots() {
    var doc = frameDocument();
    if (!doc || !doc.body) return false;

    Array.prototype.slice.call(doc.querySelectorAll('[data-r4v5-code-drop-slot]')).forEach(function (slot, index) {
      slot.setAttribute('data-gjs-droppable', 'true');
      slot.setAttribute('data-gjs-selectable', 'true');
      slot.setAttribute('data-gjs-highlightable', 'true');
      slot.setAttribute('data-gjs-hoverable', 'true');

      var placeholder = slot.querySelector('.r4v5-code-drop-placeholder');
      if (!placeholder) {
        placeholder = doc.createElement('div');
        placeholder.className = 'r4v5-code-drop-placeholder';
        slot.appendChild(placeholder);
      }

      placeholder.setAttribute('data-gjs-selectable', 'false');
      placeholder.setAttribute('data-gjs-hoverable', 'false');
      placeholder.innerHTML = '<span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:999px;background:#0d6efd;color:#fff;font-weight:950;line-height:1;">+</span><span>Trascina il widget qui</span>';

      applySlotInlineStyles(slot, placeholder);
      if (!slot.getAttribute('aria-label')) slot.setAttribute('aria-label', 'Area inserimento widget ' + (index + 1));
    });

    return true;
  }

  function injectEditorCompatibilityCss() {
    var doc = frameDocument();
    if (!doc || !doc.head || doc.getElementById('r4v5-editor-code-compat-css')) return;

    var style = doc.createElement('style');
    style.id = 'r4v5-editor-code-compat-css';
    style.textContent = [
      'body{min-width:100%!important;width:100%!important;}',
      '[data-r4-animation]{visibility:visible;}',
      '.r4v5-code-import-root{display:block;width:100%;}',
      '.r4v5-code-import-root>*{box-sizing:border-box;}',
      '.r4v5-code-import-root img{max-width:100%;height:auto;display:block;}',
      '[data-r4v5-code-drop-slot]{display:block!important;width:100%!important;clear:both!important;position:relative!important;min-height:92px!important;padding:18px 24px!important;margin:0!important;background:linear-gradient(180deg,rgba(248,250,252,.98),rgba(255,255,255,.98))!important;border:0!important;box-sizing:border-box!important;}',
      '[data-r4v5-code-drop-slot]>.r4v5-code-drop-placeholder{display:flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;max-width:1120px!important;min-height:54px!important;margin:0 auto!important;border:1px dashed rgba(13,110,253,.55)!important;border-radius:18px!important;background:rgba(13,110,253,.055)!important;color:#0d6efd!important;font:900 12px/1.2 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important;text-align:center!important;box-shadow:0 12px 28px rgba(15,23,42,.08)!important;pointer-events:none!important;}',
      '[data-r4v5-code-drop-slot]:hover>.r4v5-code-drop-placeholder{border-color:rgba(13,110,253,.85)!important;background:rgba(13,110,253,.09)!important;}',
      'script[data-r4v5-custom-js="1"]{display:none!important;}'
    ].join('\n');
    doc.head.appendChild(style);
  }

  function run() {
    hydrateModalCustomJs();
    injectEditorCompatibilityCss();
    injectAnimationRuntime();
    enhanceDropSlots();
    window.setTimeout(initAnimations, 80);
    window.setTimeout(initAnimations, 300);
    window.setTimeout(executeCustomJs, 120);
    window.setTimeout(executeCustomJs, 420);
    window.setTimeout(enhanceDropSlots, 120);
    window.setTimeout(enhanceDropSlots, 420);
    window.setTimeout(enhanceDropSlots, 900);
  }

  function bindDom() {
    document.addEventListener('click', function (event) {
      if (event.target.closest && event.target.closest('[data-r4v5-code-apply]')) {
        captureCustomJsFromModal();
        window.setTimeout(patchVisualJsonAfterEditorSync, 80);
        window.setTimeout(executeCustomJs, 200);
      }

      if (event.target.closest && event.target.closest('[data-r4v5-code-refresh], [data-r4v5-command="code"]')) {
        window.setTimeout(hydrateModalCustomJs, 120);
        window.setTimeout(hydrateModalCustomJs, 420);
      }

      if (event.target.closest && event.target.closest('[data-r4v5-code-clear-js]')) {
        writePersistedCustomJs('');
      }
    }, true);

    var formId = (window.R4EditorV5Config || {}).formId || 'r4v5PageForm';
    var form = byId(formId);
    if (form && !form.__r4v5CustomJsBound) {
      form.__r4v5CustomJsBound = true;
      form.addEventListener('submit', function () {
        captureCustomJsFromModal();
        patchVisualJsonAfterEditorSync();
      });
    }
  }

  function bindEditor() {
    var ed = editor();
    if (!ed || ed.__r4v5EditorCodeRuntimeBridge) return !!ed;
    ed.__r4v5EditorCodeRuntimeBridge = true;

    bindDom();

    if (ed.on) {
      ed.on('load', function () { window.setTimeout(run, 250); });
      ed.on('canvas:frame:load', function () { window.setTimeout(run, 250); });
      ed.on('component:add component:update component:selected', function () { window.setTimeout(run, 180); });
      ed.on('update', function () { window.setTimeout(patchVisualJsonAfterEditorSync, 80); });
    }

    customJsMemory = readPersistedCustomJs();
    window.setTimeout(run, 500);
    window.setTimeout(run, 1200);
    return true;
  }

  function boot() {
    bindDom();
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      if (bindEditor() || attempts > 100) window.clearInterval(timer);
    }, 100);
  }

  window.R4V5EditorCodeRuntimeBridge = {
    inject: run,
    initAnimations: initAnimations,
    executeCustomJs: executeCustomJs,
    enhanceDropSlots: enhanceDropSlots,
    getCustomJs: readPersistedCustomJs,
    setCustomJs: writePersistedCustomJs
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
