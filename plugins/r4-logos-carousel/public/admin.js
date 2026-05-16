// /public/plugins/r4-logos-carousel/admin.js  (v1.3.2)
(function () {
    const TYPE = 'plugin:r4-logos-carousel';
    window.BuilderPlugins = window.BuilderPlugins || {};

    const esc  = s => String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const bool = v => v===true || v==='1' || v===1 || String(v).toLowerCase()==='true';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const API_PICKER  = (window.R4ADMIN && window.R4ADMIN.mediaPickerUrl) || '/admin/media/picker';
    const API_BROWSE  = (window.R4ADMIN && window.R4ADMIN.mediaBrowseUrl) || '/admin/media/browse?images_only=1';
    const API_UPLOAD  = (window.R4ADMIN && window.R4ADMIN.mediaUploadUrl) || '/admin/media';

    // =========== PREVIEW FALLBACK ===========
    // Se view.js non fosse caricato in admin, definisco un mount minimale coerente con le var CSS --r4lc-*
    if (!window.BuilderPlugins[TYPE] || !window.BuilderPlugins[TYPE].mount) {
        const renderHTML = (data) => {
            const d = (data && data.data) ? data.data : (data || {});
            const logos = Array.isArray(d.logos) ? d.logos : [];
            if (!logos.length) return `<div class="alert alert-secondary small mb-0">Nessun logo selezionato.</div>`;

            const visible = Number.isFinite(+d.visible) ? +d.visible : 5;
            const gap     = Number.isFinite(+d.gap)     ? +d.gap     : 24;
            const itemW   = Number.isFinite(+d.itemW)   ? `--r4lc-item-w:${+d.itemW}px;` : '';
            const itemH   = Number.isFinite(+d.itemH)   ? `--r4lc-item-h:${+d.itemH}px;` : '';
            const dir     = (d.dir==='rtl' ? 'rtl' : 'ltr');
            const autoplay= bool(d.autoplay ?? true);
            const quality = (['thumb','25','59','75','full'].includes(d.quality) ? d.quality : '59');

            const items = logos.map(it=>{
                const per = `${Number.isFinite(+it.w)?`--r4lc-item-w:${+it.w}px;`:''}${Number.isFinite(+it.h)?`--r4lc-item-h:${+it.h}px;`:''}`;
                const src = (it.variants?.[quality]) || it.src || it.thumb || it.full || '';
                const img = `<img src="${esc(src)}" alt="${esc(it.alt||'')}" loading="lazy" decoding="async">`;
                const cap = it.desc ? `<small class="r4lc-caption">${esc(it.desc)}</small>` : '';
                const hasUrl = !!it.url;
                const start = hasUrl ? `<a class="r4lc-item" style="${per}" href="${esc(it.url)}" target="_blank" rel="noopener nofollow">`
                    : `<div class="r4lc-item" style="${per}">`;
                const end   = hasUrl ? `</a>` : `</div>`;
                return `${start}<span class="r4lc-img">${img}</span>${cap}${end}`;
            }).join('');

            return `
<div class="r4lc" data-dir="${dir}" style="--r4lc-visible:${visible};--r4lc-gap:${gap}px;${itemW}${itemH};${autoplay?'--r4lc-play:running;':'--r4lc-play:paused;'}">
  <div class="r4lc-viewport">
    <div class="r4lc-track" data-clone-source>${items}</div>
  </div>
</div>`;
        };

        window.BuilderPlugins[TYPE] = Object.assign(window.BuilderPlugins[TYPE]||{}, {
            mount(el, data){ el.innerHTML = renderHTML(data); }
        });
    }

    // =========== MEDIA PICKER =============
    const LogosPicker = (function(){
        let modal, onConfirm, multi=true, selected = new Map();
        let page=1, q='';

        function ensureStyles(){
            if (document.getElementById('r4lp_css')) return;
            const css = `
.r4lp-card{position:relative;border:1px solid var(--bs-border-color);border-radius:.5rem;padding:.5rem;}
.r4lp-card.sel{outline:2px solid var(--bs-primary); outline-offset:2px; border-color:transparent;}
.r4lp-check{position:absolute;top:.35rem;right:.35rem;width:24px;height:24px;border-radius:50%;
  background:#fff;border:1px solid #c9c9c9;display:flex;align-items:center;justify-content:center;
  font-size:14px;line-height:1}
.r4lp-card.sel .r4lp-check{background:var(--bs-success);border-color:var(--bs-success);color:#fff}
.r4lp-thumb{aspect-ratio:1/1;background:#f6f7f9;border-radius:.35rem;overflow:hidden;display:flex;align-items:center;justify-content:center}
.r4lp-thumb img{max-width:100%;max-height:100%;object-fit:contain}
`;
            const s = document.createElement('style'); s.id='r4lp_css'; s.textContent=css; document.head.appendChild(s);
        }

        function ensureModal(){
            if (modal) return modal;
            ensureStyles();
            const tpl = `
<div class="modal fade" id="r4LogosPicker" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Seleziona loghi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#r4lp_tab_lib">Libreria</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#r4lp_tab_up">Carica</button></li>
        </ul>
        <div class="tab-content border border-top-0 p-3">
          <div class="tab-pane fade show active" id="r4lp_tab_lib" role="tabpanel">
            <div class="d-flex gap-2 mb-2">
              <input id="r4lp_q" class="form-control form-control-sm" placeholder="Cerca...">
              <button id="r4lp_search" class="btn btn-sm btn-primary">Cerca</button>
            </div>
            <div id="r4lp_grid" class="row g-2"></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <button id="r4lp_prev" class="btn btn-sm btn-outline-secondary">&laquo; Precedente</button>
              <small class="text-muted" id="r4lp_info"></small>
              <button id="r4lp_next" class="btn btn-sm btn-outline-secondary">Successivo &raquo;</button>
            </div>
          </div>
          <div class="tab-pane fade" id="r4lp_tab_up" role="tabpanel">
            <form id="r4lp_form" class="vstack gap-2">
              <input id="r4lp_file" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="form-control form-control-sm" required>
              <div class="row g-2">
                <div class="col-md-6"><input id="r4lp_title" class="form-control form-control-sm" placeholder="Titolo (opz.)"></div>
                <div class="col-md-6"><input id="r4lp_alt"   class="form-control form-control-sm" placeholder="Testo alternativo (opz.)"></div>
              </div>
              <div class="d-flex justify-content-end">
                <button id="r4lp_upload" class="btn btn-sm btn-success" type="submit">Carica immagine</button>
              </div>
              <div id="r4lp_upmsg" class="small text-muted"></div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="me-auto small text-muted" id="r4lp_count">0 selezionati</div>
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annulla</button>
        <button id="r4lp_confirm" class="btn btn-sm btn-success" disabled>Usa selezione</button>
      </div>
    </div>
  </div>
</div>`;
            document.body.insertAdjacentHTML('beforeend', tpl);

            modal = new bootstrap.Modal(document.getElementById('r4LogosPicker'));
            const countEl = document.getElementById('r4lp_count');
            const btnConfirm = document.getElementById('r4lp_confirm');

            const updateCount = () => {
                const n = selected.size;
                countEl.textContent = `${n} selezionat${n===1?'o':'i'}`;
                btnConfirm.disabled = n === 0;
            };

            document.getElementById('r4lp_search').onclick = ()=>{ q = document.getElementById('r4lp_q').value||''; page = 1; load().then(updateCount); };
            document.getElementById('r4lp_prev').onclick   = ()=>{ if(page>1){ page--; load().then(updateCount); } };
            document.getElementById('r4lp_next').onclick   = ()=>{ page++; load().then(updateCount); };
            document.getElementById('r4lp_confirm').onclick= ()=>{
                onConfirm && onConfirm(Array.from(selected.values()));
                modal.hide();
            };

            // Upload
            document.getElementById('r4lp_form').addEventListener('submit', async (e)=>{
                e.preventDefault();
                const f = document.getElementById('r4lp_file').files[0];
                if (!f) return;
                const fd = new FormData();
                fd.append('file', f);
                fd.append('title', document.getElementById('r4lp_title').value||'');
                fd.append('alt',   document.getElementById('r4lp_alt').value||'');
                const msg = document.getElementById('r4lp_upmsg');
                msg.textContent = 'Caricamento...';
                try{
                    const res = await fetch(API_UPLOAD, { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' }, body: fd });
                    if (!res.ok) throw new Error('Upload fallito');
                    const j = await res.json();
                    const item = { id: j.id, url: j.url, thumb: j.thumb||j.url, variants: j.variants||{}, alt:'', title:'' };
                    selected.set(String(item.id), item);
                    msg.textContent = 'Caricata ✓ (già selezionata).';
                    updateCount();
                }catch(err){ msg.textContent = 'Errore: ' + (err.message||err); }
            });

            function card(item){
                const id = String(item.id);
                const isSel = selected.has(id);
                return `
<div class="col-6 col-md-3 col-lg-2">
  <div class="r4lp-card ${isSel?'sel':''}" data-id="${esc(id)}" data-action="pick">
    <span class="r4lp-check">${isSel?'<i class="bi bi-check"></i>':''}</span>
    <div class="r4lp-thumb"><img src="${esc(item.thumb || item.url)}" alt="${esc(item.alt||'')}"></div>
    <div class="small text-truncate mt-1" title="${esc(item.title||item.original_name||'')}">
      ${esc(item.title||item.original_name||'')}
    </div>
  </div>
</div>`;
            }

            async function load(){
                let url = `${API_BROWSE}${API_BROWSE.includes('?')?'&':'?'}per_page=24&page=${page}&q=${encodeURIComponent(q)}`;
                let res = await fetch(url, { headers:{'Accept':'application/json'} });
                let js  = res.ok ? await res.json() : null;
                let items = js?.items || js?.data || [];
                let total = js?.pagination?.total ?? js?.total ?? 0;
                let pageN = js?.pagination?.current_page ?? js?.page ?? page;
                let lastN = js?.pagination?.last_page ?? js?.last_page ?? 1;

                if (!Array.isArray(items) || !items.length){
                    url = `${API_PICKER}?per=24&page=${page}&q=${encodeURIComponent(q)}`;
                    res = await fetch(url, { headers:{'Accept':'application/json'} });
                    js  = await res.json();
                    items = js.items || [];
                    total = js.total || 0;
                    pageN = js.page || 1;
                    lastN = js.last_page || 1;
                }

                const grid= document.getElementById('r4lp_grid');
                const info= document.getElementById('r4lp_info');
                grid.innerHTML = items.map(card).join('');
                info.textContent = `Pagina ${pageN} di ${lastN} – ${total} elementi`;

                grid.querySelectorAll('[data-action="pick"]').forEach(cardEl=>{
                    cardEl.onclick = ()=>{
                        const id = String(cardEl.getAttribute('data-id'));
                        if (multi){
                            if (selected.has(id)) selected.delete(id);
                            else {
                                const item = items.find(x=>String(x.id)===id);
                                if (item) selected.set(id, item);
                            }
                        } else {
                            selected.clear();
                            const item = items.find(x=>String(x.id)===id);
                            if (item) selected.set(id, item);
                        }
                        cardEl.classList.toggle('sel');
                        const chk = cardEl.querySelector('.r4lp-check');
                        chk.innerHTML = cardEl.classList.contains('sel') ? '<i class="bi bi-check"></i>' : '';
                        const n = selected.size;
                        document.getElementById('r4lp_count').textContent = `${n} selezionat${n===1?'o':'i'}`;
                        document.getElementById('r4lp_confirm').disabled = n === 0;
                    };
                });
                return true;
            }

            ensureModal.load = load;
            return modal;
        }

        return {
            open({ multiple=true }={}, cb){
                multi = !!multiple; onConfirm = cb; selected.clear();
                const m = ensureModal();
                m.show();
                ensureModal.load?.().then(()=> {
                    const n = selected.size;
                    document.getElementById('r4lp_count').textContent = `${n} selezionat${n===1?'o':'i'}`;
                    document.getElementById('r4lp_confirm').disabled = n === 0;
                });
            }
        };
    })();

    // =========== TABELLA LOGHI ===========
    function rowsLogos(d, sec, block){
        const logos = Array.isArray(d.logos) ? d.logos : [];
        if (!logos.length) return `<div class="text-muted small">Nessun logo. Aggiungi dal pulsante qui sotto.</div>`;
        return `
<div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th>Anteprima</th>
        <th>Alt</th>
        <th>Link</th>
        <th>Descrizione</th>
        <th style="width:100px">Larg. (px)</th>
        <th style="width:100px">Alt. (px)</th>
        <th class="text-end">Azioni</th>
      </tr>
    </thead>
    <tbody>
      ${logos.map((it, i)=>`
        <tr>
          <td style="width:96px">
            <img src="${esc((it.variants?.thumb)||it.src||it.thumb||it.full||'')}" alt="" style="height:44px;max-width:92px;object-fit:contain">
          </td>
          <td><input type="text" class="form-control form-control-sm"
                value="${esc(it.alt||'')}"
                oninput="updatePluginField('${sec.id}','${block.id}','data.logos.${i}.alt', this.value);"></td>
          <td><input type="url" class="form-control form-control-sm" placeholder="https://…"
                value="${esc(it.url||'')}"
                oninput="updatePluginField('${sec.id}','${block.id}','data.logos.${i}.url', this.value);"></td>
          <td><input type="text" class="form-control form-control-sm" placeholder="Breve descrizione…"
                value="${esc(it.desc||'')}"
                oninput="updatePluginField('${sec.id}','${block.id}','data.logos.${i}.desc', this.value);"></td>
          <td><input type="number" min="1" class="form-control form-control-sm"
                value="${esc(it.w ?? '')}"
                oninput="updatePluginField('${sec.id}','${block.id}','data.logos.${i}.w', this.value ? Number(this.value) : null);refreshPreview('${block.id}')"></td>
          <td><input type="number" min="1" class="form-control form-control-sm"
                value="${esc(it.h ?? '')}"
                oninput="updatePluginField('${sec.id}','${block.id}','data.logos.${i}.h', this.value ? Number(this.value) : null);refreshPreview('${block.id}')"></td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary" onclick="R4LC_Admin.move('${sec.id}','${block.id}',${i},-1)">&uarr;</button>
              <button type="button" class="btn btn-outline-secondary" onclick="R4LC_Admin.move('${sec.id}','${block.id}',${i},+1)">&darr;</button>
              <button type="button" class="btn btn-outline-danger" onclick="R4LC_Admin.remove('${sec.id}','${block.id}',${i})">Rimuovi</button>
            </div>
          </td>
        </tr>
      `).join('')}
    </tbody>
  </table>
</div>
`;
    }

    // =========== MUTATORI ===========
    window.R4LC_Admin = {
        add(secId, blockId){
            LogosPicker.open({ multiple:true }, (items)=>{
                const mapped = items.map(m=>{
                    const v = m.variants || {};
                    return {
                        id: m.id,
                        variants: v,
                        src: v['59'] || m.thumb || m.url,
                        full: v['full'] || m.url,
                        alt: m.alt || m.title || m.original_name || '',
                        url: '',
                        desc: '',
                        w: null, h: null
                    };
                });

                const payload = readPayload(blockId);
                const cur = Array.isArray(payload.logos) ? payload.logos : [];
                const next = cur.concat(mapped);

                updatePluginField(secId, blockId, 'data.logos', next);
                writePayload(blockId, { ...payload, logos: next });
            });
        },

        remove(secId, blockId, idx){
            const payload = readPayload(blockId);
            const cur = Array.isArray(payload.logos) ? payload.logos.slice() : [];
            if (idx < 0 || idx >= cur.length) return;

            cur.splice(idx, 1);

            updatePluginField(secId, blockId, 'data.logos', cur);
            writePayload(blockId, { ...payload, logos: cur });
        },

        move(secId, blockId, idx, dir){
            const payload = readPayload(blockId);
            const cur = Array.isArray(payload.logos) ? payload.logos.slice() : [];
            const j = idx + dir;
            if (idx < 0 || j < 0 || idx >= cur.length || j >= cur.length) return;

            const [x] = cur.splice(idx, 1);
            cur.splice(j, 0, x);

            updatePluginField(secId, blockId, 'data.logos', cur);
            writePayload(blockId, { ...payload, logos: cur });
        }
    };

    // =========== EDITOR UI ===========
    window.BuilderPlugins[TYPE] = Object.assign(window.BuilderPlugins[TYPE]||{}, {
        label: 'Carosello Loghi',
        renderEditor(sec, block){
            const d = block.data || (block.data = {});
            if (!('visible' in d))  d.visible = 5;
            if (!('gap' in d))      d.gap = 24;
            if (!('speed' in d))    d.speed = 35;
            if (!('dir' in d))      d.dir = 'ltr';
            if (!('autoplay' in d)) d.autoplay = true;
            if (!('quality' in d))  d.quality = '59';
            if (!('itemW' in d))    d.itemW = null;
            if (!('itemH' in d))    d.itemH = null;
            if (!Array.isArray(d.logos)) d.logos = [];

            const dirOptions = [['ltr','Sinistra→Destra'],['rtl','Destra→Sinistra']]
                .map(([v,lab])=>`<option value="${v}" ${d.dir===v?'selected':''}>${lab}</option>`).join('');
            const qOptions = ['thumb','25','59','75','full']
                .map(q=>`<option value="${q}" ${d.quality===q?'selected':''}>${q}</option>`).join('');

            return `
<div class="row g-3">
  <div class="col-md-2">
    <label class="small">Visibili</label>
    <input type="number" min="1" max="8" step="1" class="form-control form-control-sm"
           value="${esc(d.visible)}"
           oninput="updatePluginField('${sec.id}','${block.id}','data.visible', Number(this.value)||5);refreshPreview('${block.id}')">
  </div>
  <div class="col-md-2">
    <label class="small">Gap (px)</label>
    <input type="number" min="0" max="64" step="1" class="form-control form-control-sm"
           value="${esc(d.gap)}"
           oninput="updatePluginField('${sec.id}','${block.id}','data.gap', Number(this.value)||24);refreshPreview('${block.id}')">
  </div>
  <div class="col-md-2">
    <label class="small">Velocità (px/s)</label>
    <input type="number" min="10" max="200" step="1" class="form-control form-control-sm"
           value="${esc(d.speed)}"
           oninput="updatePluginField('${sec.id}','${block.id}','data.speed', Number(this.value)||35);refreshPreview('${block.id}')">
  </div>
  <div class="col-md-2">
    <label class="small">Direzione</label>
    <select class="form-select form-select-sm"
            onchange="updatePluginField('${sec.id}','${block.id}','data.dir', this.value);refreshPreview('${block.id}')">
      ${dirOptions}
    </select>
  </div>
  <div class="col-md-2">
    <label class="small">Qualità</label>
    <select class="form-select form-select-sm"
            onchange="updatePluginField('${sec.id}','${block.id}','data.quality', this.value);refreshPreview('${block.id}')">
      ${qOptions}
    </select>
  </div>
  <div class="col-md-1">
    <label class="small">Max W</label>
    <input type="number" min="1" class="form-control form-control-sm"
           value="${esc(d.itemW ?? '')}"
           placeholder="px"
           oninput="updatePluginField('${sec.id}','${block.id}','data.itemW', this.value?Number(this.value):null);refreshPreview('${block.id}')">
  </div>
  <div class="col-md-1">
    <label class="small">Max H</label>
    <input type="number" min="1" class="form-control form-control-sm"
           value="${esc(d.itemH ?? '')}"
           placeholder="px"
           oninput="updatePluginField('${sec.id}','${block.id}','data.itemH', this.value?Number(this.value):null);refreshPreview('${block.id}')">
  </div>

  <div class="col-12">
    <div class="form-check">
      <input id="autoplay_${block.id}" class="form-check-input" type="checkbox" ${bool(d.autoplay)?'checked':''}
             onchange="updatePluginField('${sec.id}','${block.id}','data.autoplay', this.checked);refreshPreview('${block.id}')">
      <label class="form-check-label" for="autoplay_${block.id}">Autoplay</label>
    </div>
  </div>

  <div class="col-12">
    <hr class="my-2">
    <div class="d-flex justify-content-between align-items-center">
      <strong>Loghi</strong>
      <button type="button" class="btn btn-sm btn-primary" onclick="R4LC_Admin.add('${sec.id}','${block.id}')">
        <i class="bi bi-image me-1"></i> Aggiungi dal Media
      </button>
    </div>
    <div class="mt-2">
      ${rowsLogos(d, sec, block)}
    </div>
  </div>
</div>
`;
        }
    });

    // =========== PREVIEW OBSERVER ===========
    function safeParse(json, fb={}){ try { return JSON.parse(json); } catch { return fb; } }
    const cssEscape = window.CSS && CSS.escape ? CSS.escape : (s)=>String(s).replace(/"/g,'\\"');

    function hostEl(blockId){
        return document.querySelector(`.pb-plugin[data-type="${TYPE}"][data-block-id="${cssEscape(blockId)}"]`);
    }
    function readPayload(blockId){
        const el = hostEl(blockId); if (!el) return {};
        return safeParse(el.getAttribute('data-payload') || '{}', {});
    }
    function writePayload(blockId, data){
        const el = hostEl(blockId); if (!el) return;
        el.setAttribute('data-payload', JSON.stringify(data));
        window.BuilderPlugins?.[TYPE]?.mount?.(el, data);
        document.dispatchEvent(new Event('r4lc:refresh')); // per sicurezza, riallinea i layout
    }

    function refreshPreview(blockId){
        const host = hostEl(blockId);
        if (!host) return;
        const data = safeParse(host.getAttribute('data-payload') || '{}');
        window.BuilderPlugins[TYPE]?.mount(host, data);
        document.dispatchEvent(new Event('r4lc:refresh'));
    }

    function observePreviews(){
        const sel = `.pb-plugin[data-type="${TYPE}"]`;
        document.querySelectorAll(sel).forEach((el)=>{
            const data = safeParse(el.getAttribute('data-payload') || '{}');
            window.BuilderPlugins[TYPE]?.mount(el, data);
        });

        const mo = new MutationObserver((muts)=>{
            muts.forEach(m=>{
                if (m.type==='attributes' && m.attributeName==='data-payload' && m.target.matches(sel)){
                    const el = m.target;
                    const data = safeParse(el.getAttribute('data-payload') || '{}');
                    window.BuilderPlugins[TYPE]?.mount(el, data);
                    document.dispatchEvent(new Event('r4lc:refresh'));
                }
            });
        });
        mo.observe(document.documentElement, {attributes:true, attributeFilter:['data-payload'], subtree:true});
    }

    if (document.readyState==='loading') document.addEventListener('DOMContentLoaded', observePreviews);
    else observePreviews();
})();
