(function () {
  'use strict';

  function previewUrl() {
    var links = Array.prototype.slice.call(document.querySelectorAll('a[target="_blank"]'));
    var link = links.find(function (a) {
      return (a.textContent || '').toLowerCase().indexOf('anteprima') !== -1 || String(a.href || '').indexOf('preview') !== -1;
    });
    return link ? link.href : '';
  }

  function injectStyle() {
    if (document.getElementById('r4v5-public-preview-style')) return;
    var style = document.createElement('style');
    style.id = 'r4v5-public-preview-style';
    style.textContent = '.r4v5-canvas-area{position:relative}.r4v5-public-preview-toggle{position:absolute;top:24px;right:24px;z-index:80;border:1px solid rgba(255,255,255,.22);border-radius:999px;background:#0d6efd;color:#fff;padding:9px 13px;font-size:12px;font-weight:900;line-height:1;box-shadow:0 12px 28px rgba(0,0,0,.24);cursor:pointer}.r4v5-public-preview{position:absolute;inset:16px;z-index:90;display:none;flex-direction:column;border-radius:18px;background:#020617;border:1px solid rgba(148,163,184,.28);box-shadow:0 24px 80px rgba(0,0,0,.48);overflow:hidden}.r4v5-public-preview.is-open{display:flex}.r4v5-public-preview-head{height:48px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 12px;background:#020617;border-bottom:1px solid rgba(148,163,184,.2);color:#e5e7eb}.r4v5-public-preview-title{font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.06em}.r4v5-public-preview-note{font-size:11px;color:#94a3b8;font-weight:700;margin-right:auto}.r4v5-public-preview-btn{border:1px solid rgba(148,163,184,.28);border-radius:999px;background:#111827;color:#fff;padding:8px 10px;font-size:11px;font-weight:900;cursor:pointer}.r4v5-public-preview-frame{flex:1;width:100%;height:100%;border:0;background:#fff}';
    document.head.appendChild(style);
  }

  function boot() {
    var area = document.querySelector('.r4v5-canvas-area');
    if (!area || area.dataset.r4v5PublicPreviewReady === '1') return;
    area.dataset.r4v5PublicPreviewReady = '1';
    injectStyle();

    var openBtn = document.createElement('button');
    openBtn.type = 'button';
    openBtn.className = 'r4v5-public-preview-toggle';
    openBtn.textContent = 'Preview pagina';

    var panel = document.createElement('div');
    panel.className = 'r4v5-public-preview';

    var head = document.createElement('div');
    head.className = 'r4v5-public-preview-head';

    var title = document.createElement('div');
    title.className = 'r4v5-public-preview-title';
    title.textContent = 'Preview pubblica V5';

    var note = document.createElement('div');
    note.className = 'r4v5-public-preview-note';
    note.textContent = 'Mostra la versione salvata. Salva bozza/pubblica e poi aggiorna.';

    var refreshBtn = document.createElement('button');
    refreshBtn.type = 'button';
    refreshBtn.className = 'r4v5-public-preview-btn';
    refreshBtn.textContent = 'Aggiorna';

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'r4v5-public-preview-btn';
    closeBtn.textContent = 'Chiudi';

    var frame = document.createElement('iframe');
    frame.className = 'r4v5-public-preview-frame';
    frame.title = 'Anteprima pubblica Editor V5';

    function refresh() {
      var url = previewUrl();
      if (!url) {
        alert('URL anteprima non trovato.');
        return;
      }
      frame.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embedded_preview=1&_=' + Date.now();
    }

    openBtn.addEventListener('click', function () {
      panel.classList.add('is-open');
      refresh();
    });
    refreshBtn.addEventListener('click', refresh);
    closeBtn.addEventListener('click', function () { panel.classList.remove('is-open'); });

    head.appendChild(title);
    head.appendChild(note);
    head.appendChild(refreshBtn);
    head.appendChild(closeBtn);
    panel.appendChild(head);
    panel.appendChild(frame);
    area.appendChild(openBtn);
    area.appendChild(panel);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
