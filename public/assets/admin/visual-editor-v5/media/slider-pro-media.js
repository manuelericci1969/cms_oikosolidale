(function () {
  'use strict';

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>'"]/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char];
    });
  }

  function selectedMediaItems() {
    return Array.prototype.slice.call(document.querySelectorAll('.r4v5-media-item.is-selected')).map(function (button) {
      var img = button.querySelector('img');
      var label = button.querySelector('span');
      return {
        src: img ? img.src : '',
        title: label ? label.textContent.trim() : 'Slide'
      };
    }).filter(function (item) { return item.src; });
  }

  function dotsHtml(count) {
    var html = '';
    for (var i = 0; i < count; i++) {
      html += '<button type="button" data-r4v5-slider-dot="' + i + '" style="width:10px;height:10px;border-radius:999px;border:0;background:#fff;opacity:' + (i === 0 ? '1' : '.45') + ';cursor:pointer;"></button>';
    }
    return html;
  }

  function slideHtml(item, index) {
    var title = item.title || ('Slide ' + (index + 1));
    return '<article class="r4v5-slider-pro-slide' + (index === 0 ? ' is-active' : '') + '" style="min-width:100%;position:relative;min-height:420px;display:flex;align-items:center;">' +
      '<img src="' + escapeHtml(item.src) + '" alt="' + escapeHtml(title) + '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">' +
      '<div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,6,23,.78),rgba(2,6,23,.18));"></div>' +
      '<div style="position:relative;z-index:2;max-width:720px;padding:72px 56px;">' +
        '<span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.16);color:#fff;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Slider Pro</span>' +
        '<h2 style="font-size:clamp(36px,5vw,64px);line-height:1.05;font-weight:900;margin:0 0 16px;color:#fff;">' + escapeHtml(title) + '</h2>' +
        '<p style="font-size:19px;line-height:1.7;color:#e5e7eb;margin:0 0 24px;">Modifica questo testo direttamente nell’editor.</p>' +
        '<a href="#" style="display:inline-flex;padding:14px 22px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Call to action</a>' +
      '</div>' +
    '</article>';
  }

  function sliderHtml(items) {
    var slides = items.map(slideHtml).join('');
    return '<section class="r4v5-slider-pro" data-r4v5-slider-pro="1" data-autoplay="true" data-interval="4500" data-effect="slide" style="position:relative;overflow:hidden;border-radius:28px;background:#111827;color:#fff;min-height:420px;">' +
      '<div class="r4v5-slider-pro-track" style="display:flex;width:100%;height:100%;transition:transform .55s ease;">' + slides + '</div>' +
      '<button type="button" class="r4v5-slider-pro-arrow r4v5-slider-pro-prev" data-r4v5-slider-prev style="position:absolute;left:18px;top:50%;z-index:5;width:44px;height:44px;border-radius:999px;border:1px solid rgba(255,255,255,.32);background:rgba(2,6,23,.52);color:#fff;font-size:28px;line-height:1;cursor:pointer;">‹</button>' +
      '<button type="button" class="r4v5-slider-pro-arrow r4v5-slider-pro-next" data-r4v5-slider-next style="position:absolute;right:18px;top:50%;z-index:5;width:44px;height:44px;border-radius:999px;border:1px solid rgba(255,255,255,.32);background:rgba(2,6,23,.52);color:#fff;font-size:28px;line-height:1;cursor:pointer;">›</button>' +
      '<div class="r4v5-slider-pro-dots" data-r4v5-slider-dots style="position:absolute;left:0;right:0;bottom:18px;z-index:6;display:flex;justify-content:center;gap:8px;">' + dotsHtml(items.length) + '</div>' +
    '</section>';
  }

  function closeMediaModal() {
    var modal = document.getElementById('r4v5MediaModal');
    if (modal) modal.hidden = true;
  }

  function syncFields(editor) {
    var cfg = window.R4EditorV5Config || {};
    var html = cfg.htmlFieldId ? document.getElementById(cfg.htmlFieldId) : null;
    var css = cfg.cssFieldId ? document.getElementById(cfg.cssFieldId) : null;
    var json = cfg.jsonFieldId ? document.getElementById(cfg.jsonFieldId) : null;
    if (html && editor.getHtml) html.value = editor.getHtml();
    if (css && editor.getCss) css.value = editor.getCss();
    if (json && editor.getProjectData) {
      try { json.value = JSON.stringify(editor.getProjectData()); } catch (e) {}
    }
  }

  function insertSliderPro() {
    var items = selectedMediaItems();
    if (!items.length) {
      alert('Seleziona almeno una immagine dalla libreria Media.');
      return;
    }
    var editor = window.R4EditorV5 || null;
    if (!editor || !editor.addComponents) {
      alert('Editor V5 non disponibile.');
      return;
    }
    editor.addComponents(sliderHtml(items));
    if (editor.trigger) editor.trigger('update');
    syncFields(editor);
    closeMediaModal();
    if (window.R4V5SliderPro && typeof window.R4V5SliderPro.init === 'function') {
      window.setTimeout(window.R4V5SliderPro.init, 160);
    }
  }

  function ensureButton() {
    var footer = document.querySelector('.r4v5-media-footer');
    if (!footer || document.getElementById('r4v5MediaInsertSliderPro')) return;
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'r4v5-media-btn r4v5-media-btn-primary';
    button.id = 'r4v5MediaInsertSliderPro';
    button.textContent = 'Slider Pro';
    button.addEventListener('click', insertSliderPro);
    footer.appendChild(button);
  }

  function boot() {
    ensureButton();
    var observer = new MutationObserver(ensureButton);
    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
