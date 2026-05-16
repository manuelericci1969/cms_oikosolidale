(function () {
  'use strict';

  function sliderContent() {
    return '<section class="r4v5-slider-pro" data-r4v5-slider-pro="1" data-autoplay="true" data-interval="4500" data-effect="slide" style="position:relative;overflow:hidden;border-radius:28px;background:#111827;color:#fff;min-height:420px;">' +
      '<div class="r4v5-slider-pro-track" style="display:flex;width:100%;height:100%;transition:transform .55s ease;">' +
        '<article class="r4v5-slider-pro-slide is-active" style="min-width:100%;position:relative;min-height:420px;display:flex;align-items:center;">' +
          '<img src="https://placehold.co/1400x720/0d6efd/ffffff?text=Slider+Pro+1" alt="Slide 1" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">' +
          '<div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,6,23,.78),rgba(2,6,23,.18));"></div>' +
          '<div style="position:relative;z-index:2;max-width:720px;padding:72px 56px;"><span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.16);color:#fff;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Slider Pro</span><h2 style="font-size:clamp(36px,5vw,64px);line-height:1.05;font-weight:900;margin:0 0 16px;color:#fff;">Titolo slide principale</h2><p style="font-size:19px;line-height:1.7;color:#e5e7eb;margin:0 0 24px;">Descrizione della slide modificabile direttamente nell’editor.</p><a href="#" style="display:inline-flex;padding:14px 22px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Call to action</a></div>' +
        '</article>' +
        '<article class="r4v5-slider-pro-slide" style="min-width:100%;position:relative;min-height:420px;display:flex;align-items:center;">' +
          '<img src="https://placehold.co/1400x720/111827/ffffff?text=Slider+Pro+2" alt="Slide 2" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">' +
          '<div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,6,23,.76),rgba(2,6,23,.15));"></div>' +
          '<div style="position:relative;z-index:2;max-width:720px;padding:72px 56px;"><span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.16);color:#fff;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Seconda slide</span><h2 style="font-size:clamp(36px,5vw,64px);line-height:1.05;font-weight:900;margin:0 0 16px;color:#fff;">Altro messaggio forte</h2><p style="font-size:19px;line-height:1.7;color:#e5e7eb;margin:0 0 24px;">Puoi sostituire immagini, testi e pulsanti.</p><a href="#" style="display:inline-flex;padding:14px 22px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Scopri di più</a></div>' +
        '</article>' +
        '<article class="r4v5-slider-pro-slide" style="min-width:100%;position:relative;min-height:420px;display:flex;align-items:center;">' +
          '<img src="https://placehold.co/1400x720/eaf3ff/111827?text=Slider+Pro+3" alt="Slide 3" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">' +
          '<div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,6,23,.62),rgba(2,6,23,.05));"></div>' +
          '<div style="position:relative;z-index:2;max-width:720px;padding:72px 56px;"><span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.16);color:#fff;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Terza slide</span><h2 style="font-size:clamp(36px,5vw,64px);line-height:1.05;font-weight:900;margin:0 0 16px;color:#fff;">Slider riutilizzabile</h2><p style="font-size:19px;line-height:1.7;color:#e5e7eb;margin:0 0 24px;">Inseribile in qualsiasi punto della pagina.</p><a href="#" style="display:inline-flex;padding:14px 22px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Contattaci</a></div>' +
        '</article>' +
      '</div>' +
      '<button type="button" class="r4v5-slider-pro-arrow r4v5-slider-pro-prev" data-r4v5-slider-prev style="position:absolute;left:18px;top:50%;z-index:5;width:44px;height:44px;border-radius:999px;border:1px solid rgba(255,255,255,.32);background:rgba(2,6,23,.52);color:#fff;font-size:28px;line-height:1;cursor:pointer;">‹</button>' +
      '<button type="button" class="r4v5-slider-pro-arrow r4v5-slider-pro-next" data-r4v5-slider-next style="position:absolute;right:18px;top:50%;z-index:5;width:44px;height:44px;border-radius:999px;border:1px solid rgba(255,255,255,.32);background:rgba(2,6,23,.52);color:#fff;font-size:28px;line-height:1;cursor:pointer;">›</button>' +
      '<div class="r4v5-slider-pro-dots" data-r4v5-slider-dots style="position:absolute;left:0;right:0;bottom:18px;z-index:6;display:flex;justify-content:center;gap:8px;"><button type="button" data-r4v5-slider-dot="0" style="width:10px;height:10px;border-radius:999px;border:0;background:#fff;opacity:1;cursor:pointer;"></button><button type="button" data-r4v5-slider-dot="1" style="width:10px;height:10px;border-radius:999px;border:0;background:#fff;opacity:.45;cursor:pointer;"></button><button type="button" data-r4v5-slider-dot="2" style="width:10px;height:10px;border-radius:999px;border:0;background:#fff;opacity:.45;cursor:pointer;"></button></div>' +
    '</section>';
  }

  function addBlock(editor) {
    if (!editor || !editor.BlockManager || editor.BlockManager.get('r4v5-slider-pro')) return;
    editor.BlockManager.add('r4v5-slider-pro', {
      label: 'Slider Pro',
      category: 'Media',
      media: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M8 12h8M15 9l3 3-3 3"/></svg>',
      content: sliderContent()
    });
  }

  function initExisting() {
    if (window.R4V5SliderPro && typeof window.R4V5SliderPro.init === 'function') window.R4V5SliderPro.init();
  }

  function boot() {
    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts++;
      var editor = window.R4EditorV5 || null;
      if (editor && editor.BlockManager) {
        addBlock(editor);
        if (editor.on && !editor.__r4v5SliderProWidgetBound) {
          editor.__r4v5SliderProWidgetBound = true;
          editor.on('component:add', function () { window.setTimeout(initExisting, 120); });
          editor.on('load', function () { window.setTimeout(initExisting, 300); });
        }
        window.clearInterval(timer);
      }
      if (attempts > 80) window.clearInterval(timer);
    }, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
