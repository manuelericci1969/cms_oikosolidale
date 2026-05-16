// public/admin.js — r4-logos-carousel-v2
(() => {
    const TYPE = 'plugin:r4-logos-carousel-v2';
    window.BuilderPlugins = window.BuilderPlugins || {};

    // ---------- Helpers ---------------------------------------------------------
    function ensureData(block) {
        block.data ||= {};
        block.data.items ||= [];  // [{src, alt, url, target}]
        block.data.options ||= {};
        const o = block.data.options;

        // defaults
        o.height        = toInt(o.height, 72, 24, 1024);
        o.gap           = toInt(o.gap, 24, 0, 200);
        o.speed         = toInt(o.speed, 30, 1, 400);
        o.direction     = (o.direction || 'ltr').toLowerCase() === 'rtl' ? 'rtl' : 'ltr';
        o.pauseOnHover  = (o.pauseOnHover !== false);
        o.sizeMode      = (o.sizeMode === 'box') ? 'box' : 'height';
        o.boxWidth      = toInt(o.boxWidth, 150, 24, 2048);
        o.boxHeight     = toInt(o.boxHeight, 150, 24, 2048);

        // migrazione vecchie proprietà
        block.data.items = block.data.items.map((it) => {
            const out = {
                src: it?.src || it?.url || '',
                alt: it?.alt || '',
                url: it?.url || it?.href || '',
                target: it?.target || '_self'
            };
            return out;
        });

        return block.data;
    }

    function toInt(val, def, min, max) {
        const n = parseInt(val, 10);
        if (Number.isNaN(n)) return def;
        return Math.max(min, Math.min(max, n));
    }

    // scegli una variante "sicura"
    function pickSafeImage(media) {
        if (!media) return '';
        const v = media.variants || media.variant || {};
        return v['full'] || v['75'] || v['59'] || v['thumb'] || media.url || media.src || media.thumb || '';
    }

    const esc = (s) => String(s ?? '').replace(/"/g, '&quot;');

    // Singolo riquadro logo
    function renderItemTile(item, i, sec, block) {
        return `
      <div class="col-6 col-md-4 col-xl-3">
        <div class="thumb-tile">
          <img src="${esc(item.src)}" alt="">
          <div class="tile-actions">
            <button type="button" class="btn btn-outline-secondary btn-icon"
              title="Su" data-r4lc-action="move-up" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}" data-index="${i}">
              <i class="bi bi-arrow-up"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-icon"
              title="Giù" data-r4lc-action="move-down" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}" data-index="${i}">
              <i class="bi bi-arrow-down"></i>
            </button>
            <button type="button" class="btn btn-outline-danger btn-icon"
              title="Rimuovi" data-r4lc-action="remove" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}" data-index="${i}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
          <div class="tile-body">
            <input type="text" class="form-control form-control-sm mb-1" placeholder="ALT"
              value="${esc(item.alt||'')}" data-r4lc-model="data.items.${i}.alt"
              data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            <div class="d-flex gap-1">
              <input type="text" class="form-control form-control-sm" placeholder="Link (opzionale)"
                value="${esc(item.url||'')}" data-r4lc-model="data.items.${i}.url"
                data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
              <select class="form-select form-select-sm" style="max-width:110px"
                data-r4lc-model="data.items.${i}.target" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
                ${['_self','_blank'].map(t => `<option value="${t}" ${item.target===t?'selected':''}>${t}</option>`).join('')}
              </select>
            </div>
          </div>
        </div>
      </div>`;
    }

    // ---------- Plugin Admin API -----------------------------------------------
    window.BuilderPlugins[TYPE] = {
        label: 'Carosello Loghi',

        renderEditor(sec, block) {
            const data  = ensureData(block);
            const items = data.items;
            const o     = data.options;

            const tiles = items.map((it, i) => renderItemTile(it, i, sec, block)).join('');
            const heightMode = (o.sizeMode !== 'box');

            return `
      <div class="border rounded p-2 bg-white">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
          <button type="button" class="btn btn-sm btn-soft"
            data-r4lc-action="pick-media" data-r4lc-multiple="1"
            data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            <i class="bi bi-images me-1"></i> Aggiungi da Archivio
          </button>
          <span class="small text-muted">Selezione multipla supportata.</span>
          <span class="ms-auto small text-muted">Totale loghi: <strong>${items.length}</strong></span>
        </div>

        <div class="row g-2 mb-3">
          ${tiles || '<div class="col-12 small text-muted">Nessun logo.</div>'}
        </div>

        <details class="fieldset collapsible" open>
          <summary><i class="bi bi-sliders me-1"></i> Opzioni carosello</summary>
          <div class="pt-2 row g-2 align-items-end">
            <div class="col-6 col-md-3">
              <label class="small">Modalità dimensioni</label>
              <select class="form-select form-select-sm"
                data-r4lc-model="data.options.sizeMode" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
                <option value="height" ${o.sizeMode!=='box'?'selected':''}>Altezza fissa</option>
                <option value="box" ${o.sizeMode==='box'?'selected':''}>Box L×H</option>
              </select>
            </div>
            <div class="col-6 col-md-3 ${heightMode ? '' : 'd-none'}" data-r4lc-show="mode:height">
              <label class="small">Altezza (px)</label>
              <input type="number" min="24" max="1024" class="form-control form-control-sm"
                value="${o.height}" data-r4lc-model="data.options.height"
                data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            </div>
            <div class="col-6 col-md-3 ${heightMode ? 'd-none' : ''}" data-r4lc-show="mode:box">
              <label class="small">Larghezza box (px)</label>
              <input type="number" min="24" max="2048" class="form-control form-control-sm"
                value="${o.boxWidth}" data-r4lc-model="data.options.boxWidth"
                data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            </div>
            <div class="col-6 col-md-3 ${heightMode ? 'd-none' : ''}" data-r4lc-show="mode:box">
              <label class="small">Altezza box (px)</label>
              <input type="number" min="24" max="2048" class="form-control form-control-sm"
                value="${o.boxHeight}" data-r4lc-model="data.options.boxHeight"
                data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            </div>

            <div class="col-6 col-md-3">
              <label class="small">Gap (px)</label>
              <input type="number" min="0" max="200" class="form-control form-control-sm"
                value="${o.gap}" data-r4lc-model="data.options.gap"
                data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            </div>
            <div class="col-6 col-md-3">
              <label class="small">Velocità (px/s)</label>
              <input type="number" min="1" max="400" class="form-control form-control-sm"
                value="${o.speed}" data-r4lc-model="data.options.speed"
                data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
            </div>
            <div class="col-6 col-md-3">
              <label class="small">Direzione</label>
              <select class="form-select form-select-sm"
                data-r4lc-model="data.options.direction" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
                ${['ltr','rtl'].map(v => `<option value="${v}" ${o.direction===v?'selected':''}>${v.toUpperCase()}</option>`).join('')}
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label class="small">Pausa hover</label>
              <select class="form-select form-select-sm"
                data-r4lc-model="data.options.pauseOnHover" data-r4lc-sec="${sec.id}" data-r4lc-bid="${block.id}">
                <option value="1" ${o.pauseOnHover ? 'selected':''}>Sì</option>
                <option value="0" ${!o.pauseOnHover ? 'selected':''}>No</option>
              </select>
            </div>
          </div>
        </details>

        <div class="mt-2 d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="renderBuilder()">
            <i class="bi bi-arrow-repeat me-1"></i> Aggiorna anteprima
          </button>
        </div>
      </div>`;
        },

        // piccola anteprima
        renderView(block) {
            const d = ensureData(block);
            return `<div class="text-muted small">Carosello loghi • ${d.items.length} elementi</div>`;
        }
    };

    // ---------- Eventi delegati -------------------------------------------------
    // Azioni click: pick/move/remove
    document.addEventListener('click', async (e) => {
        const el = e.target.closest('[data-r4lc-action]');
        if (!el) return;

        const secId = el.getAttribute('data-r4lc-sec');
        const bid   = el.getAttribute('data-r4lc-bid');
        const action= el.getAttribute('data-r4lc-action');

        // API dal builder host
        const ctx = (typeof window.findBlockById === 'function') ? window.findBlockById(bid) : null;
        if (!ctx || !ctx.blk) return;
        const sec = ctx.sec, block = ctx.blk;
        const data = ensureData(block);

        if (action === 'pick-media') {
            if (typeof window.openMediaPicker !== 'function') {
                alert('Media Picker non disponibile in questa pagina.');
                return;
            }
            const picked = await window.openMediaPicker({ multiple: true });
            if (!picked || !picked.length) return;

            picked.forEach(it => {
                const src = pickSafeImage(it);
                if (!src) return;
                data.items.push({
                    src,
                    alt: it.alt || it.title || '',
                    url: '',
                    target: '_self'
                });
            });

            window.updatePluginField(secId, bid, 'data.items', data.items);
            window.renderBuilder();
            return;
        }

        const idx = parseInt(el.getAttribute('data-index'), 10);
        if (Number.isInteger(idx)) {
            if (action === 'remove') {
                data.items.splice(idx, 1);
            } else if (action === 'move-up' && idx > 0) {
                const t = data.items[idx]; data.items[idx] = data.items[idx-1]; data.items[idx-1] = t;
            } else if (action === 'move-down' && idx < data.items.length - 1) {
                const t = data.items[idx]; data.items[idx] = data.items[idx+1]; data.items[idx+1] = t;
            } else {
                return;
            }
            window.updatePluginField(secId, bid, 'data.items', data.items);
            window.renderBuilder();
        }
    });

    // Binding generale dei campi
    document.addEventListener('input', (e) => {
        const el = e.target.closest('[data-r4lc-model]');
        if (!el) return;
        const path = el.getAttribute('data-r4lc-model');
        const secId= el.getAttribute('data-r4lc-sec');
        const bid  = el.getAttribute('data-r4lc-bid');

        let val = el.value;
        if (val === '1' || val === '0') val = (val === '1');
        if (el.type === 'number') {
            const n = parseInt(val,10); if (!Number.isNaN(n)) val = n;
        }
        window.updatePluginField(secId, bid, path, val);

        // se cambia la modalità, ri-render per mostrare/nascondere i controlli
        if (path === 'data.options.sizeMode') {
            window.renderBuilder();
        }
        // aggiorna live l'anteprima front (se wired)
        try { document.dispatchEvent(new CustomEvent('r4lc:refresh')); } catch(_){}
    });

    document.addEventListener('change', (e) => {
        const el = e.target.closest('[data-r4lc-model]');
        if (!el) return;
        // normalizza select boolean
        if (el.getAttribute('data-r4lc-model') === 'data.options.pauseOnHover') {
            const secId= el.getAttribute('data-r4lc-sec');
            const bid  = el.getAttribute('data-r4lc-bid');
            const v = (el.value === '1');
            window.updatePluginField(secId, bid, 'data.options.pauseOnHover', v);
            try { document.dispatchEvent(new CustomEvent('r4lc:refresh')); } catch(_){}
        }
    });

    // segnala pronti
    try { window.dispatchEvent(new Event('plugins:ready')); } catch(_){}
    console.info('[r4-logos-carousel-v2] admin pronto');
})();
