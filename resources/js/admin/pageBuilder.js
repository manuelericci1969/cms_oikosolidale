// resources/js/admin/pageBuilder.js
// Editor interno (contenteditable) + Page Builder
import '../../../public/assets/page-builder.css';

import * as bootstrap from 'bootstrap';
window.bootstrap = window.bootstrap || bootstrap;

(() => {
    'use strict';

    // ======================
    // Registry / Plugin API
    // ======================
    const BUILTIN_TYPES = ['text','image','gallery','carousel','video'];
    const BlockRegistry = new Map();

    // helper per path tipo "a.b.c"
    const deepSet = (obj, path, value) => {
        const parts = String(path).split('.');
        let ref = obj;
        while (parts.length > 1) {
            const k = parts.shift();
            if (typeof ref[k] !== 'object' || ref[k] === null) ref[k] = {};
            ref = ref[k];
        }
        ref[parts[0]] = value;
    };

    const API = {
        registerBlock(def){
            if (!def?.type) return;
            BlockRegistry.set(def.type, def);
        },
        addFilter(name, cb){
            (window.__pbFilters ||= {})[name] = [ ...(window.__pbFilters[name]||[]), cb ];
        },
        applyFilters(name, value, ...args){
            const fns = (window.__pbFilters||{})[name] || [];
            return fns.reduce((v, fn) => fn(v, ...args), value);
        },
        utils: {
            upload: (file, onProgress) => xhrUpload(file, onProgress),
            toast: (msg, type) => showToast(msg, type),
            rerender: () => renderBuilder(),
            setBlockValue: (blockId, path, value) => pbUpdate(blockId, path, value),
            getBlock: (blockId) => pbGet(blockId),
            addSection: (...args) => addSection(...args),
            addBlock: (...args) => addBlock(...args),
        }
    };

    // ===========
    // Stato base
    // ===========
    const initialTag = document.getElementById('builderInitial');
    let initial = [];
    try { initial = JSON.parse(initialTag?.textContent || '[]'); } catch { initial = []; }
    window.builderData = Array.isArray(initial) ? initial : [];

    const form = document.getElementById('pageForm');
    const uploadUrl = form?.dataset?.uploadUrl || '';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ========
    // Helpers
    // ========
    const uid = (p='id') => `${p}_${Date.now()}_${Math.floor(Math.random()*10000)}`;
    const q = (sel, ctx=document) => ctx.querySelector(sel);
    const qa = (sel, ctx=document) => Array.prototype.slice.call(ctx.querySelectorAll(sel));
    const cloneDeep = (obj) => JSON.parse(JSON.stringify(obj));

    const ensureShape = () => {
        if (!Array.isArray(window.builderData)) window.builderData = [];
        window.builderData = window.builderData.map(s => ({
            id: s.id || uid('sec'),
            blocks: Array.isArray(s.blocks) ? s.blocks : []
        }));
    };
    const findSection = id => window.builderData.find(s => s.id === id);
    const findBlock = (sid, bid) => {
        const s = findSection(sid); if (!s) return null;
        return (s.blocks || []).find(b => b.id === bid) || null;
    };
    const findBlockById = (bid) => {
        for (const sec of window.builderData){
            const blk = (sec.blocks||[]).find(b => b.id === bid);
            if (blk) return { sec, blk };
        }
        return null;
    };
    const getBlockById = (bid) => (findBlockById(bid)||{}).blk || null;

    // ===========
    // Notifiche
    // ===========
    function showToast(text, type=''){
        const host = q('#toastArea'); if (!host) return;
        const el = document.createElement('div');
        el.className = 'upload-toast ' + (type||'');
        el.textContent = text;
        host.appendChild(el);
        setTimeout(()=> { el.style.opacity='0'; el.style.transform='translateY(8px)'; }, 2500);
        setTimeout(()=> host.removeChild(el), 3000);
    }
    function setOverlay(show, msg=null, pct=null){
        const ov = q('#uploadOverlay'), bar = q('#uploadProgress'), label = q('#uploadMessage');
        if (!ov) return;
        if (show) ov.classList.add('show'); else ov.classList.remove('show');
        if (msg && label) label.textContent = msg;
        if (pct!=null && bar){ bar.style.width = Math.max(0,Math.min(100, pct)) + '%'; }
    }

    // ==========================
    // Upload con progress (XHR)
    // ==========================
    function parseUploadResponse(xhr){
        const ct = (xhr.getResponseHeader('Content-Type')||'').toLowerCase();
        let data = null;

        if (xhr.response && typeof xhr.response === 'object') {
            data = xhr.response;
        } else if (xhr.responseText) {
            try { data = JSON.parse(xhr.responseText); } catch(e){}
        }

        const url =
            data?.url ||
            data?.path ||
            data?.location ||
            data?.file?.url ||
            data?.data?.url ||
            null;

        const thumb =
            data?.thumb ||
            data?.thumbnail ||
            data?.file?.thumb ||
            url || null;

        return { data, url, thumb, ct };
    }
    function xhrUpload(file, onProgress){
        return new Promise((resolve, reject) => {
            if (!uploadUrl) return reject(new Error('Upload URL mancante'));
            const formData = new FormData();
            formData.append('file', file, file.name);
            if (csrf) formData.append('_token', csrf);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl, true);
            xhr.responseType = 'json';
            xhr.setRequestHeader('Accept','application/json');

            xhr.upload.onprogress = function(ev){
                if (ev.lengthComputable && typeof onProgress === 'function'){
                    const p = (ev.loaded / ev.total) * 100;
                    onProgress(p, ev);
                }
            };

            xhr.onload = function(){
                const { data, url, thumb, ct } = parseUploadResponse(xhr);

                if (xhr.status >= 200 && xhr.status < 300){
                    if (url){
                        resolve({ url, thumb, raw:data });
                    } else {
                        console.warn('Upload OK ma payload inatteso', {status:xhr.status, ct, data, text:xhr.responseText});
                        reject(new Error('Upload riuscito ma risposta senza URL (atteso JSON { url, thumb })'));
                    }
                } else {
                    const msg = data?.message || `Upload fallito (${xhr.status})`;
                    reject(new Error(msg));
                }
            };

            xhr.onerror = function(){ reject(new Error('Errore di rete durante l’upload')); };
            xhr.send(formData);
        });
    }

    // =======================================================
    // ===== R4 Editor — vanilla contenteditable, zero deps ==
    // =======================================================
    const r4Editors = new Map(); // bid -> { host, editable, cleanup }

    function insertImageAtCursor(editable, url){
        const img = document.createElement('img');
        img.src = url; img.alt = '';
        const sel = window.getSelection();
        if (sel && sel.rangeCount){
            const range = sel.getRangeAt(0);
            range.deleteContents();
            range.insertNode(img);
            range.setStartAfter(img);
            range.setEndAfter(img);
            sel.removeAllRanges();
            sel.addRange(range);
        } else {
            editable.appendChild(img);
        }
        editable.focus();
    }

    async function insertImageUpload(file, editable){
        try{
            setOverlay(true, 'Caricamento immagine…', 0);
            const { url } = await xhrUpload(file, p => setOverlay(true, `Caricamento immagine… ${p.toFixed(0)}%`, p));
            setOverlay(false);
            insertImageAtCursor(editable, url);
            showToast('Immagine inserita', 'success');
        }catch(e){
            setOverlay(false);
            showToast('Errore upload: ' + (e?.message||e), 'error');
        }
    }

    function sanitizeEditable(node){
        const ALLOWED_TAGS = ['P','BR','B','I','U','S','STRONG','EM','A','UL','OL','LI','H1','H2','H3','IMG','BLOCKQUOTE','CODE','PRE','SPAN'];
        const ALLOWED_ATTR = { 'A':['href','target','rel'], 'IMG':['src','alt','title'], 'SPAN':[] };
        const walker = document.createTreeWalker(node, NodeFilter.SHOW_ELEMENT, null);
        const toRemove = [];
        while (walker.nextNode()){
            const el = walker.currentNode;
            if (!ALLOWED_TAGS.includes(el.tagName)){
                const parent = el.parentNode;
                while (el.firstChild) parent.insertBefore(el.firstChild, el);
                toRemove.push(el);
            } else {
                [...el.attributes].forEach(attr=>{
                    const keep = (ALLOWED_ATTR[el.tagName]||[]).includes(attr.name.toLowerCase());
                    if (!keep) el.removeAttribute(attr.name);
                });
                if (el.tagName==='A'){
                    const href = el.getAttribute('href')||'';
                    if (!/^\w+:\/\//.test(href) && !href.startsWith('#') && !href.startsWith('/')) el.removeAttribute('href');
                    else { el.setAttribute('rel','noopener noreferrer'); el.setAttribute('target','_blank'); }
                }
            }
        }
        toRemove.forEach(n=> n.remove());
    }

    function initR4Editors(){
        document.querySelectorAll('.r4-editor[data-bid]').forEach(host=>{
            const bid = host.dataset.bid;
            if (r4Editors.has(bid)) return;

            const blk = getBlockById(bid);
            const initialHtml = blk?.content || '';

            host.innerHTML = `
        <div class="r4-toolbar">
          <select class="r4-block">
            <option value="P">Paragrafo</option>
            <option value="H1">H1</option>
            <option value="H2">H2</option>
            <option value="H3">H3</option>
          </select>
          <button type="button" data-cmd="bold" title="Grassetto"><i class="bi bi-type-bold"></i></button>
          <button type="button" data-cmd="italic" title="Corsivo"><i class="bi bi-type-italic"></i></button>
          <button type="button" data-cmd="underline" title="Sottolineato"><i class="bi bi-type-underline"></i></button>
          <button type="button" data-cmd="strikeThrough" title="Barrato"><i class="bi bi-type-strikethrough"></i></button>
          <button type="button" data-cmd="insertOrderedList" title="Lista numerata"><i class="bi bi-list-ol"></i></button>
          <button type="button" data-cmd="insertUnorderedList" title="Lista puntata"><i class="bi bi-list-ul"></i></button>
          <button type="button" data-cmd="justifyLeft" title="Allinea a sinistra"><i class="bi bi-text-left"></i></button>
          <button type="button" data-cmd="justifyCenter" title="Centra"><i class="bi bi-text-center"></i></button>
          <button type="button" data-cmd="justifyRight" title="Allinea a destra"><i class="bi bi-text-right"></i></button>
          <button type="button" data-action="link" title="Inserisci link"><i class="bi bi-link-45deg"></i></button>
          <button type="button" data-action="image" title="Inserisci immagine"><i class="bi bi-image"></i></button>
          <button type="button" data-action="clean" title="Pulisci formattazione"><i class="bi bi-eraser"></i></button>
        </div>
        <div class="r4-editable" contenteditable="true"></div>
      `;

            const editable = host.querySelector('.r4-editable');
            editable.innerHTML = initialHtml || '<p><br></p>';

            // Toolbar
            host.addEventListener('click', (ev)=>{
                const btn = ev.target.closest('button');
                if (!btn) return;
                const cmd = btn.dataset.cmd;
                const act = btn.dataset.action;
                if (cmd){
                    document.execCommand(cmd, false, null);
                    editable.focus();
                } else if (act === 'link'){
                    const url = prompt('URL (https://)');
                    if (url){
                        let u = url.trim();
                        if (!/^\w+:\/\//.test(u)) u = 'https://' + u;
                        document.execCommand('createLink', false, u);
                    }
                    editable.focus();
                } else if (act === 'image'){
                    const input = document.createElement('input');
                    input.type = 'file'; input.accept = 'image/*';
                    input.onchange = () => { const f = input.files?.[0]; if (f) insertImageUpload(f, editable); };
                    input.click();
                } else if (act === 'clean'){
                    document.execCommand('removeFormat', false, null);
                    sanitizeEditable(editable);
                }
            });

            // Block type
            host.querySelector('select.r4-block')?.addEventListener('change', (e)=>{
                const tag = e.target.value;
                document.execCommand('formatBlock', false, tag);
                editable.focus();
            });

            // Salvataggio live
            const onInput = () => {
                const b = getBlockById(bid);
                if (b && b.type==='text') b.content = editable.innerHTML;
            };
            editable.addEventListener('input', onInput);

            // Paste
            editable.addEventListener('paste', (ev)=>{
                const items = ev.clipboardData?.items || [];
                const fileItem = Array.from(items).find(i => i.kind==='file' && i.type.startsWith('image/'));
                if (fileItem){
                    ev.preventDefault();
                    insertImageUpload(fileItem.getAsFile(), editable);
                } else {
                    setTimeout(()=> sanitizeEditable(editable), 0);
                }
            });

            // Drag&drop immagini
            editable.addEventListener('drop', (ev)=>{
                const files = ev.dataTransfer?.files || [];
                if (files.length){
                    ev.preventDefault();
                    const f = files[0];
                    if (f.type.startsWith('image/')) insertImageUpload(f, editable);
                }
            });

            function cleanup(){ editable.removeEventListener('input', onInput); }
            r4Editors.set(bid, { host, editable, cleanup });
        });
    }

    function collectR4Editors(){
        r4Editors.forEach(({editable}, bid)=>{
            const b = getBlockById(bid);
            if (b && b.type==='text') b.content = editable.innerHTML;
        });
    }

    function destroyR4Editors(){
        r4Editors.forEach(({cleanup})=> cleanup && cleanup());
        r4Editors.clear();
    }

    // ==========================
    // Sezioni (Blocchi) — CRUD
    // ==========================
    window.addSection = function(){
        window.builderData.push({ id: uid('sec'), blocks: [] });
        renderBuilder();
    };
    window.removeSection = function(secId){
        if (!confirm('Eliminare questo Blocco?')) return;
        window.builderData = window.builderData.filter(s => s.id !== secId);
        renderBuilder();
    };
    window.duplicateSection = function(secId){
        const src = findSection(secId); if (!src) return;
        const copy = cloneDeep(src);
        copy.id = uid('sec');
        (copy.blocks||[]).forEach(b => b.id = uid('block'));
        window.builderData.push(copy);
        renderBuilder();
    };

    // collapse helpers
    window.toggleSection = function(secId){
        const row = q(`[data-sec="${secId}"]`);
        if (!row) return;
        const btn = row.querySelector('[data-bs-toggle="collapse"]');
        if (btn){ btn.click(); }
    };
    function collapseAll(expand){
        qa('.pb-body.collapse').forEach(el=>{
            const isShown = el.classList.contains('show');
            if (expand && !isShown) new bootstrap.Collapse(el).show();
            if (!expand && isShown) new bootstrap.Collapse(el).hide();
        });
    }

    // ==========================
    // Blocchi interni
    // ==========================
    window.addBlock = function(secId, cols=12, type='text'){
        const sec = findSection(secId); if (!sec) return;
        const blk = {
            id: uid('block'),
            columns: parseInt(cols),
            type,
            animation: { name:'none', duration:600, delay:0 },
        };

        if (type === 'text'){
            blk.content = '';
        } else if (type === 'image'){
            blk.image = {
                src:'', full:'', alt:'', caption:'',
                quality:'thumb',
                options:{ heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center' },
                fx: { parallax:false, parallaxMode:'y', parallaxStrength:20, parallaxPerspective:800, ripple:false, rippleRadius:60, rippleDuration:1200, rippleThrottle:120 }
            };
        } else if (type === 'gallery'){
            blk.gallery = []; blk.galleryQuality = 'thumb';
        } else if (type === 'video'){
            blk.video = { url:'', provider:'', id:'' };
        } else if (type === 'carousel'){
            blk.carousel = { items: [], options: { autoplay:true, interval:5000, indicators:true, controls:true, heightMode:'auto', heightPx:450, objectFit:'cover', quality:'thumb' } };
        }

        const def = BlockRegistry.get(type);
        if (def?.defaults) Object.assign(blk, cloneDeep(def.defaults));

        sec.blocks.push(blk);
        renderBuilder();
    };
    window.removeBlock = function(secId, blockId){
        const sec = findSection(secId); if (!sec) return;
        if (!confirm('Eliminare questo elemento?')) return;
        sec.blocks = (sec.blocks || []).filter(b => b.id !== blockId);
        renderBuilder();
    };
    window.duplicateBlock = function(secId, blockId){
        const sec = findSection(secId); if (!sec) return;
        const src = sec.blocks.find(b => b.id === blockId); if (!src) return;
        const copy = cloneDeep(src); copy.id = uid('block');
        sec.blocks.push(copy);
        renderBuilder();
    };
    window.changeColumns = (secId, blockId, v) => {
        const b = findBlock(secId, blockId); if (b){ b.columns = parseInt(v)||12; renderBuilder(); }
    };
    window.changeType = (secId, blockId, t) => {
        const b = findBlock(secId, blockId); if (!b) return;
        b.type = t;
        const def = BlockRegistry.get(t);
        if (def?.defaults) Object.assign(b, cloneDeep(def.defaults));
        renderBuilder();
    };
    window.updateAnim = (secId, blockId, k, v) => {
        const b = findBlock(secId, blockId); if (!b) return;
        b.animation = b.animation || { name:'none', duration:600, delay:0 };
        b.animation[k] = (k==='duration'||k==='delay') ? (parseInt(v)||0) : v;
    };

    // Upload multiplo helper
    async function uploadFiles(files){
        const out=[]; let processed = 0; const total = files.length;
        setOverlay(true, 'Preparazione…', 0);

        for (const file of files){
            await xhrUpload(file, (p)=>{
                const overall = (processed/total)*100 + (p/100)*(100/total);
                setOverlay(true, `Caricamento ${processed+1}/${total}… ${overall.toFixed(0)}%`, overall);
            }).then(d=>{
                out.push(d);
                processed++;
                setOverlay(true, `File ${processed}/${total} caricato`, (processed/total)*100);
            }).catch(err=>{
                setOverlay(false);
                showToast('Errore upload: ' + err.message, 'error');
                throw err;
            });
        }

        setOverlay(false);
        showToast(total>1 ? 'Immagini caricate' : 'Immagine caricata', 'success');
        return out;
    }

    window.pickImage = async function(secId, blockId){
        const input=document.createElement('input');
        input.type='file'; input.accept='image/*';
        input.onchange=async ()=>{
            try{
                const [d]=await uploadFiles(input.files);
                const b=findBlock(secId,blockId); if(!b) return;
                b.image ||= {};
                b.image.src = d.thumb || d.url;
                b.image.full = d.url;
                renderBuilder();
            }catch(e){}
        };
        input.click();
    };

    window.pickGallery = async function(secId, blockId, target='gallery'){
        const input=document.createElement('input');
        input.type='file'; input.accept='image/*'; input.multiple=true;
        input.onchange=async ()=>{
            try{
                const datas=await uploadFiles(input.files);
                const b=findBlock(secId,blockId); if(!b) return;
                if (target==='gallery'){
                    b.gallery ||= [];
                    datas.forEach(d => b.gallery.push({ src:d.thumb||d.url, full:d.url, alt:'' }));
                } else {
                    b.carousel ||= { items: [], options: {} };
                    b.carousel.items ||= [];
                    datas.forEach(d => b.carousel.items.push({ src:d.thumb||d.url, full:d.url, alt:'' }));
                }
                renderBuilder();
            }catch(e){}
        };
        input.click();
    };
    window.removeGalleryItem = (secId, blockId, idx, target='gallery') => {
        const b=findBlock(secId,blockId); if(!b) return;
        const arr= target==='gallery' ? (b.gallery ||= []) : ((b.carousel ||= {items:[]}).items ||= []);
        if(Array.isArray(arr)){ arr.splice(idx,1); renderBuilder(); }
    };

    // ======
    // Video
    // ======
    const parseVideoUrl = (url) => {
        url=String(url||'').trim(); if(!url) return {provider:'',id:''};
        const yt = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_\-]{6,})/);
        if (yt) return {provider:'youtube', id:yt[1]};
        const vm = url.match(/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/);
        if (vm) return {provider:'vimeo', id:vm[1]};
        return {provider:'', id:''};
    };
    window.updateVideoUrl = (secId, blockId, el) => {
        const b=findBlock(secId, blockId); if(!b) return;
        const url=el.value; const info=parseVideoUrl(url);
        b.video = { url, provider:info.provider, id:info.id };
        renderBuilder();
    };

    // ==========
    // Immagini
    // ==========
    window.updateImageField = (secId, blockId, path, value) => {
        const b=findBlock(secId, blockId); if (!b) return;
        deepSet(b, path, value);
    };
    window.updateImageOption = (secId, blockId, key, value) => {
        const b=findBlock(secId, blockId); if (!b) return;
        b.image ||= {};
        b.image.options ||= { heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center' };
        if (key === 'heightPx') b.image.options[key] = Math.max(50, parseInt(value)||450);
        else b.image.options[key] = value;
        renderBuilder();
    };
    window.updateImageFx = (secId, blockId, key, value) => {
        const b=findBlock(secId, blockId); if (!b) return;
        b.image ||= {};
        b.image.fx ||= {
            parallax:false, parallaxMode:'y', parallaxStrength:20, parallaxPerspective:800,
            ripple:false, rippleRadius:60, rippleDuration:1200, rippleThrottle:120
        };
        const intKeys = ['parallaxStrength','parallaxPerspective','rippleRadius','rippleDuration','rippleThrottle'];
        if (intKeys.includes(key)) b.image.fx[key] = Math.max(0, parseInt(value)||0);
        else if (key==='parallax' || key==='ripple') b.image.fx[key] = (value===true || value==='1' || value===1);
        else b.image.fx[key] = value;
    };
    window.updateImageCustomPos = (secId, blockId) => {
        const xSel = document.getElementById('posX_' + blockId);
        const ySel = document.getElementById('posY_' + blockId);
        const x = xSel ? parseInt(xSel.value) : 50;
        const y = ySel ? parseInt(ySel.value) : 50;
        const pos = `${Math.max(0,Math.min(100,x))}% ${Math.max(0,Math.min(100,y))}%`;
        window.updateImageOption(secId, blockId, 'objectPosition', pos);
    };
    window.onPresetPositionChange = (secId, blockId, el) => {
        const val = el.value;
        if (val === '__custom__'){ window.updateImageCustomPos(secId, blockId); }
        else { window.updateImageOption(secId, blockId, 'objectPosition', val); }
    };

    // ==========================
    // Drag & Drop sezioni
    // ==========================
    let dragIndex = null;
    function attachDnD(){
        qa('.pb-section').forEach((el, idx) => {
            el.setAttribute('draggable', 'true');
            el.addEventListener('dragstart', (e)=>{ dragIndex = idx; e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain', idx); });
            el.addEventListener('dragover', (e)=>{ e.preventDefault(); el.classList.add('pb-drag-over'); e.dataTransfer.dropEffect='move'; });
            el.addEventListener('dragleave', ()=> el.classList.remove('pb-drag-over'));
            el.addEventListener('drop', (e)=>{
                e.preventDefault(); el.classList.remove('pb-drag-over');
                const from = dragIndex, to = idx;
                if (from===null || from===to) return;
                const arr = window.builderData;
                arr.splice(to, 0, arr.splice(from,1)[0]);
                dragIndex=null;
                renderBuilder();
            });
        });
    }

    // ==========================
    // Palette (header)
    // ==========================
    let headerPluginsInjected = false;

    function headerPluginButtonsHtml(){
        return Array.from(BlockRegistry.values())
            .filter(d => !BUILTIN_TYPES.includes(d.type))
            .map(d => `<button type="button" class="btn btn-outline-primary" data-action="add-item" data-type="${d.type}">
        <i class="${d.icon||'bi bi-puzzle'} me-1"></i> ${d.label||d.type}
      </button>`).join('');
    }

    function sectionPluginButtonsHtml(secId){
        return Array.from(BlockRegistry.values())
            .filter(d => !BUILTIN_TYPES.includes(d.type))
            .map(d => `<button type="button" class="btn btn-outline-primary" onclick="addBlock('${secId}',12,'${d.type}')">
        <i class="${d.icon||'bi bi-puzzle'} me-1"></i> ${d.label||d.type}
      </button>`).join('');
    }

    function bindPalette(){
        const header = document.querySelector('.card-header .palette');
        if (!header) return;

        if (!headerPluginsInjected) {
            const btns = headerPluginButtonsHtml();
            if (btns) header.insertAdjacentHTML('beforeend', btns);
            headerPluginsInjected = true;
        }

        header.querySelector('[data-action="add-section"]')?.addEventListener('click', ()=> addSection());
        header.querySelectorAll('[data-action="add-item"]').forEach(btn=>{
            btn.addEventListener('click', ()=>{
                if (!window.builderData.length) addSection();
                const last = window.builderData[window.builderData.length-1];
                addBlock(last.id, 12, btn.dataset.type);
            });
        });
    }

    // ==========================
    // UI builders
    // ==========================
    function blockToolbar(sec, block){
        return `
<div class="pb-toolbar">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <label class="small mb-0 me-1">Tipo</label>
    <select class="form-select form-select-sm w-auto" onchange="changeType('${sec.id}','${block.id}', this.value)">
      <option value="text" ${block.type==='text'?'selected':''}>Testo</option>
      <option value="image" ${block.type==='image'?'selected':''}>Immagine</option>
      <option value="gallery" ${block.type==='gallery'?'selected':''}>Galleria</option>
      <option value="carousel" ${block.type==='carousel'?'selected':''}>Carosello</option>
      <option value="video" ${block.type==='video'?'selected':''}>Video</option>
      ${Array.from(BlockRegistry.values()).filter(d=>!BUILTIN_TYPES.includes(d.type)).map(d=>`<option value="${d.type}" ${block.type===d.type?'selected':''}>${d.label||d.type}</option>`).join('')}
    </select>

    <label class="small mb-0 ms-2 me-1">Colonne</label>
    <select class="form-select form-select-sm w-auto" onchange="changeColumns('${sec.id}','${block.id}', this.value)">
      ${[1,2,3,4,5,6,7,8,9,10,11,12].map(n=>`<option value="${n}" ${Number(block.columns)===n?'selected':''}>Col-${n}</option>`).join('')}
    </select>

    <label class="small mb-0 ms-2 me-1">Animazione</label>
    <select class="form-select form-select-sm w-auto" onchange="updateAnim('${sec.id}','${block.id}','name', this.value)">
      ${['none','fade','slide-up','slide-left','zoom','flip'].map(a=>`<option value="${a}" ${block.animation?.name===a?'selected':''}>${a}</option>`).join('')}
    </select>
    <input type="number" class="form-control form-control-sm w-auto" min="0" step="50" value="${block.animation?.duration??600}" title="Durata (ms)" onchange="updateAnim('${sec.id}','${block.id}','duration', this.value)">
    <input type="number" class="form-control form-control-sm w-auto" min="0" step="50" value="${block.animation?.delay??0}"     title="Ritardo (ms)" onchange="updateAnim('${sec.id}','${block.id}','delay', this.value)">
  </div>

  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" title="Duplica" onclick="duplicateBlock('${sec.id}','${block.id}')">
      <i class="bi bi-files"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger" title="Elimina" onclick="removeBlock('${sec.id}','${block.id}')">
      <i class="bi bi-trash"></i>
    </button>
  </div>
</div>`;
    }

    function renderBlockContent(sec, block){
        // 1) plugin first
        const def = BlockRegistry.get(block.type);
        if (def?.renderEditor) {
            return def.renderEditor({ sec, block });
        }

        // 2) built-in editors
        if (block.type === 'text'){
            return `<div class="r4-editor" data-bid="${block.id}"></div>`;
        }

        if (block.type === 'image'){
            const im = block.image || {};
            const src = im.src || '';
            const alt = im.alt || '';
            const caption = im.caption || '';
            const quality = im.quality || 'thumb';
            const opt = im.options || { heightMode:'auto', heightPx:450, objectFit:'cover', objectPosition:'center center' };
            const isFixed = (opt.heightMode || 'auto') === 'fixed';
            const fit = opt.objectFit || 'cover';
            const currentPos = (opt.objectPosition || 'center center').trim();

            const fx = im.fx || {
                parallax:false, parallaxMode:'y', parallaxStrength:20, parallaxPerspective:800,
                ripple:false, rippleRadius:60, rippleDuration:1200, rippleThrottle:120
            };

            const presets = ['center center','top left','top center','top right','center left','center right','bottom left','bottom center','bottom right'];
            const isPreset = presets.includes(currentPos);
            const percMatch = currentPos.match(/^\s*(\d{1,3})%\s+(\d{1,3})%\s*$/);
            let xPerc = 50, yPerc = 50;
            if (percMatch){
                xPerc = Math.max(0, Math.min(100, parseInt(percMatch[1])));
                yPerc = Math.max(0, Math.min(100, parseInt(percMatch[2])));
            }
            const percentOptions = Array.from({length:21}, (_,i)=> i*5);
            const imgStyle = `width:100%;${isFixed?`height:${opt.heightPx||450}px;`:''}object-fit:${fit};object-position:${currentPos};`;

            return `
<div>
  <div class="mb-2">
    ${src ? `<img src="${src}" class="img-fluid mb-2" style="${imgStyle}">`
                : `<div class="small-muted mb-2"><i class="bi bi-image me-1"></i> Nessuna immagine</div>`}
    <div class="d-flex flex-wrap gap-2 mb-2">
      <button class="btn btn-sm btn-soft" type="button" onclick="pickImage('${sec.id}','${block.id}')">
        <i class="bi bi-upload me-1"></i> Carica
      </button>
      <input type="text" class="form-control form-control-sm" placeholder="Oppure incolla URL"
             value="${src}" oninput="updateImageField('${sec.id}','${block.id}','image.src', this.value)">
    </div>

    <div class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="small">Altezza</label>
        <select class="form-select form-select-sm" onchange="updateImageOption('${sec.id}','${block.id}','heightMode', this.value)">
          <option value="auto" ${opt.heightMode==='auto'?'selected':''}>Auto</option>
          <option value="fixed" ${opt.heightMode==='fixed'?'selected':''}>Fissa (px)</option>
        </select>
      </div>
      <div class="col-auto">
        <label class="small">Px</label>
        <input type="number" class="form-control form-control-sm" min="50" step="10" ${isFixed?'':'disabled'}
               value="${opt.heightPx||450}" onchange="updateImageOption('${sec.id}','${block.id}','heightPx', this.value)">
      </div>
      <div class="col-auto">
        <label class="small">Object-fit</label>
        <select class="form-select form-select-sm" onchange="updateImageOption('${sec.id}','${block.id}','objectFit', this.value)">
          ${['cover','contain','fill','none','scale-down'].map(v=>`<option value="${v}" ${fit===v?'selected':''}>${v}</option>`).join('')}
        </select>
      </div>

      <div class="col-auto">
        <label class="small">Posizione (preset)</label>
        <select class="form-select form-select-sm" onchange="onPresetPositionChange('${sec.id}','${block.id}', this)">
          ${presets.map(v => `<option value="${v}" ${isPreset && currentPos===v?'selected':''}>${v}</option>`).join('')}
          <option value="__custom__" ${!isPreset?'selected':''}>Personalizzata (percentuale)</option>
        </select>
      </div>

      <div class="col-auto">
        <label class="small">X%</label>
        <select id="posX_${block.id}" class="form-select form-select-sm" onchange="updateImageCustomPos('${sec.id}','${block.id}')">
          ${percentOptions.map(v => `<option value="${v}" ${v===xPerc?'selected':''}>${v}%</option>`).join('')}
        </select>
      </div>
      <div class="col-auto">
        <label class="small">Y%</label>
        <select id="posY_${block.id}" class="form-select form-select-sm" onchange="updateImageCustomPos('${sec.id}','${block.id}')">
          ${percentOptions.map(v => `<option value="${v}" ${v===yPerc?'selected':''}>${v}%</option>`).join('')}
        </select>
      </div>

      <div class="col-auto">
        <label class="small">Qualità</label>
        <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','image.quality', this.value)">
          ${['thumb','25','59','75','full'].map(q=>`<option value="${q}" ${quality===q?'selected':''}>${q}</option>`).join('')}
        </select>
      </div>
    </div>

    <div class="row g-2 mt-2">
      <div class="col-md-6">
        <fieldset class="fieldset">
          <legend><i class="bi bi-layers-half me-1"></i> Effetto Parallax</legend>
          <div class="row g-2 align-items-end">
            <div class="col-6">
              <label class="small">Parallax</label>
              <select class="form-select form-select-sm" onchange="updateImageFx('${sec.id}','${block.id}','parallax', this.value)">
                <option value="0" ${!fx.parallax?'selected':''}>Off</option>
                <option value="1" ${fx.parallax?'selected':''}>On</option>
              </select>
            </div>
            <div class="col-6">
              <label class="small">Modalità</label>
              <select class="form-select form-select-sm" onchange="updateImageFx('${sec.id}','${block.id}','parallaxMode', this.value)">
                ${['y','xy','tilt'].map(m=>`<option value="${m}" ${fx.parallaxMode===m?'selected':''}>${m.toUpperCase()}</option>`).join('')}
              </select>
            </div>
            <div class="col-6">
              <label class="small">Intensità (px)</label>
              <input type="number" class="form-control form-control-sm" min="0" step="1"
                     value="${fx.parallaxStrength||20}" onchange="updateImageFx('${sec.id}','${block.id}','parallaxStrength', this.value)">
            </div>
            <div class="col-6">
              <label class="small">Perspective (tilt)</label>
              <input type="number" class="form-control form-control-sm" min="100" step="50"
                     value="${fx.parallaxPerspective||800}" onchange="updateImageFx('${sec.id}','${block.id}','parallaxPerspective', this.value)">
            </div>
          </div>
        </fieldset>
      </div>

      <div class="col-md-6">
        <fieldset class="fieldset">
          <legend><i class="bi bi-droplet-half me-1"></i> Effetto Onde (Ripple)</legend>
          <div class="row g-2 align-items-end">
            <div class="col-6">
              <label class="small">Ripple</label>
              <select class="form-select form-select-sm" onchange="updateImageFx('${sec.id}','${block.id}','ripple', this.value)">
                <option value="0" ${!fx.ripple?'selected':''}>Off</option>
                <option value="1" ${fx.ripple?'selected':''}>On</option>
              </select>
            </div>
            <div class="col-6">
              <label class="small">Raggio base (px)</label>
              <input type="number" class="form-control form-control-sm" min="12" step="2"
                     value="${fx.rippleRadius||60}" onchange="updateImageFx('${sec.id}','${block.id}','rippleRadius', this.value)">
            </div>
            <div class="col-6">
              <label class="small">Durata (ms)</label>
              <input type="number" class="form-control form-control-sm" min="200" step="50"
                     value="${fx.rippleDuration||1200}" onchange="updateImageFx('${sec.id}','${block.id}','rippleDuration', this.value)">
            </div>
            <div class="col-6">
              <label class="small">Throttle hover (ms)</label>
              <input type="number" class="form-control form-control-sm" min="0" step="10"
                     value="${fx.rippleThrottle||120}" onchange="updateImageFx('${sec.id}','${block.id}','rippleThrottle', this.value)">
            </div>
          </div>
        </fieldset>
      </div>
    </div>
  </div>

  <input type="text" class="form-control form-control-sm mb-2" placeholder="Alt"
         value="${alt}" oninput="updateImageField('${sec.id}','${block.id}','image.alt', this.value)">
  <input type="text" class="form-control form-control-sm" placeholder="Didascalia"
         value="${caption}" oninput="updateImageField('${sec.id}','${block.id}','image.caption', this.value)">
</div>`;
        }

        if (block.type === 'gallery'){
            const items = Array.isArray(block.gallery) ? block.gallery : [];
            const ql = block.galleryQuality || 'thumb';
            const thumbs = items.map((it,i)=>`
  <div class="me-2 mb-2 d-inline-block text-center">
    <img src="${it.src}" class="thumb" alt="">
    <button type="button" class="btn btn-sm btn-outline-danger d-block mt-1 w-100" onclick="removeGalleryItem('${sec.id}','${block.id}', ${i})">
      <i class="bi bi-trash me-1"></i> Rimuovi
    </button>
  </div>`).join('');
            return `
<div>
  <div class="mb-2">${thumbs || '<div class="small-muted"><i class="bi bi-images me-1"></i> Nessuna immagine</div>'}</div>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <button type="button" class="btn btn-sm btn-soft" onclick="pickGallery('${sec.id}','${block.id}','gallery')">
      <i class="bi bi-upload me-1"></i> Aggiungi immagini
    </button>
    <label class="small mb-0 ms-1">Qualità</label>
    <select class="form-select form-select-sm w-auto" onchange="updateImageField('${sec.id}','${block.id}','galleryQuality', this.value)">
      ${['thumb','25','59','75','full'].map(v=>`<option value="${v}" ${ql===v?'selected':''}>${v}</option>`).join('')}
    </select>
  </div>
</div>`;
        }

        if (block.type === 'carousel'){
            const items = (block.carousel?.items) || [];
            const opt = block.carousel?.options || {};
            const hMode = opt.heightMode || 'auto';
            const hPx = opt.heightPx ?? 450;
            const fit = opt.objectFit || 'cover';
            const ql = opt.quality || 'thumb';
            const autoplay = !!(opt.autoplay ?? true);
            const interval = opt.interval ?? 5000;
            const indicators = !!(opt.indicators ?? true);
            const controls = !!(opt.controls ?? true);

            const thumbs = items.map((it,i)=>`
  <div class="me-2 mb-2 d-inline-block text-center">
    <img src="${it.src}" class="thumb" alt="">
    <button type="button" class="btn btn-sm btn-outline-danger d-block mt-1 w-100" onclick="removeGalleryItem('${sec.id}','${block.id}', ${i}, 'carousel')">
      <i class="bi bi-trash me-1"></i> Rimuovi
    </button>
  </div>`).join('');

            return `
<div>
  <div class="mb-2">${thumbs || '<div class="small-muted"><i class="bi bi-collection me-1"></i> Nessuna immagine</div>'}</div>
  <div class="row g-2 align-items-end">
    <div class="col-auto">
      <button type="button" class="btn btn-sm btn-soft" onclick="pickGallery('${sec.id}','${block.id}','carousel')">
        <i class="bi bi-upload me-1"></i> Aggiungi immagini
      </button>
    </div>
    <div class="col-auto">
      <label class="small">Qualità</label>
      <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','carousel.options.quality', this.value)">
        ${['thumb','25','59','75','full'].map(v=>`<option value="${v}" ${ql===v?'selected':''}>${v}</option>`).join('')}
      </select>
    </div>
    <div class="col-auto">
      <label class="small">Altezza</label>
      <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','carousel.options.heightMode', this.value)">
        <option value="auto" ${hMode==='auto'?'selected':''}>Auto</option>
        <option value="fixed" ${hMode==='fixed'?'selected':''}>Fissa (px)</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="small">Px</label>
      <input type="number" class="form-control form-control-sm" min="100" step="10" ${hMode==='fixed'?'':'disabled'}
             value="${hPx}" oninput="updateImageField('${sec.id}','${block.id}','carousel.options.heightPx', this.value)">
    </div>
    <div class="col-auto">
      <label class="small">Object-fit</label>
      <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','carousel.options.objectFit', this.value)">
        ${['cover','contain'].map(v=>`<option value="${v}" ${fit===v?'selected':''}>${v}</option>`).join('')}
      </select>
    </div>
    <div class="col-auto">
      <label class="small">Autoplay</label>
      <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','carousel.options.autoplay', this.value==='1')">
        <option value="1" ${autoplay?'selected':''}>Sì</option>
        <option value="0" ${!autoplay?'selected':''}>No</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="small">Interval (ms)</label>
      <input type="number" class="form-control form-control-sm" min="1000" step="500"
             value="${interval}" oninput="updateImageField('${sec.id}','${block.id}','carousel.options.interval', this.value)">
    </div>
    <div class="col-auto">
      <label class="small">Indicatori</label>
      <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','carousel.options.indicators', this.value==='1')">
        <option value="1" ${indicators?'selected':''}>Sì</option>
        <option value="0" ${!indicators?'selected':''}>No</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="small">Controlli</label>
      <select class="form-select form-select-sm" onchange="updateImageField('${sec.id}','${block.id}','carousel.options.controls', this.value==='1')">
        <option value="1" ${controls?'selected':''}>Sì</option>
        <option value="0" ${!controls?'selected':''}>No</option>
      </select>
    </div>
  </div>
</div>`;
        }

        if (block.type === 'video'){
            const url = block.video?.url || '';
            const provider = block.video?.provider || '';
            const vid = block.video?.id || '';
            const preview =
                (provider==='youtube' && vid) ? `<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/${vid}" allowfullscreen></iframe></div>` :
                    (provider==='vimeo'   && vid) ? `<div class="ratio ratio-16x9"><iframe src="https://player.vimeo.com/video/${vid}" allowfullscreen></iframe></div>` :
                        (url ? `<div class="small-muted"><i class="bi bi-exclamation-circle me-1"></i> URL non riconosciuto (YouTube/Vimeo)</div>` : '');
            return `
<div>
  <input type="text" class="form-control form-control-sm mb-2" placeholder="URL YouTube o Vimeo"
         value="${url}" oninput="updateVideoUrl('${sec.id}','${block.id}', this)">
  ${preview}
</div>`;
        }

        return '<div class="text-muted">Tipo non supportato.</div>';
    }

    function attachBlockUpdateHelpers(){
        qa('.pb-block').forEach(node => {
            const bid = node.getAttribute('data-bid') || node.querySelector('[data-bid]')?.getAttribute('data-bid');
            if (!bid) return;
            node.__update = (path, value) => {
                const found = findBlockById(bid);
                if (!found) return;
                deepSet(found.blk, path, value);
                renderBuilder();
            };
        });
    }

    function renderBuilder(){
        ensureShape();
        const el = document.getElementById('builderContainer');

        // 1) sincronizza e smonta editor esistenti
        collectR4Editors();
        destroyR4Editors();

        if (!window.builderData.length){
            el.innerHTML = `<div class="text-center py-5 text-muted">Nessun <strong>Blocco</strong>. Usa “Aggiungi Blocco”.</div>`;
            initR4Editors();
            bindPalette();
            return;
        }

        // 2) HTML
        let html = '';
        window.builderData.forEach((sec, idx) => {
            const collapseId = `secbody_${sec.id}`;
            html += `
<div class="pb-section" data-sec="${sec.id}">
  <div class="pb-head">
    <div class="pb-title">
      <i class="bi bi-grip-vertical drag-handle" title="Trascina per riordinare i Blocchi"></i>
      <span>Blocco ${idx+1}</span>
    </div>
    <div class="pb-actions">
      <button type="button" class="btn btn-sm btn-outline-secondary" title="Comprimi / Espandi" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
        <i class="bi bi-chevron-down caret"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary" title="Duplica" onclick="duplicateSection('${sec.id}')">
        <i class="bi bi-files"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger" title="Elimina" onclick="removeSection('${sec.id}')">
        <i class="bi bi-trash"></i>
      </button>
    </div>
  </div>

  <div id="${collapseId}" class="pb-body collapse show">
    <div class="palette mb-3">
      <button type="button" class="btn btn-soft" onclick="addBlock('${sec.id}',12,'text')">
        <i class="bi bi-type me-1"></i> Testo
      </button>
      <button type="button" class="btn btn-outline-primary" onclick="addBlock('${sec.id}',6,'image')">
        <i class="bi bi-image me-1"></i> Immagine
      </button>
      <button type="button" class="btn btn-outline-primary" onclick="addBlock('${sec.id}',4,'gallery')">
        <i class="bi bi-images me-1"></i> Galleria
      </button>
      <button type="button" class="btn btn-outline-primary" onclick="addBlock('${sec.id}',12,'carousel')">
        <i class="bi bi-collection me-1"></i> Carosello
      </button>
      <button type="button" class="btn btn-outline-primary" onclick="addBlock('${sec.id}',6,'video')">
        <i class="bi bi-camera-video me-1"></i> Video
      </button>
      ${sectionPluginButtonsHtml(sec.id)}
    </div>

    <div class="row g-3">
      ${(sec.blocks||[]).map(b => `
        <div class="col-md-${Number(b.columns)||12}">
          <div class="pb-block" data-bid="${b.id}">
            ${blockToolbar(sec, b)}
            ${renderBlockContent(sec, b)}
          </div>
        </div>`).join('')}
    </div>
  </div>
</div>`;
        });

        el.innerHTML = html;

        // 3) DnD e palette
        attachDnD();
        bindPalette();

        // 4) helper __update per plugin
        attachBlockUpdateHelpers();

        // 5) Inizializza editor interni
        initR4Editors();
    }

    // ================
    // API globali p/ plugin HTML (inline handlers)
    // ================
    function pbUpdate(blockId, path, value){
        const found = findBlockById(blockId);
        if (!found) return;
        deepSet(found.blk, path, value);
        renderBuilder();
    }
    function pbGet(blockId){
        const found = findBlockById(blockId);
        return found?.blk ? cloneDeep(found.blk) : null;
    }
    window.pbUpdate = pbUpdate;
    window.pbGet = pbGet;

    // ==========
    // Salvataggio
    // ==========
    form?.addEventListener('submit', function(){
        collectR4Editors();
        q('#contentJson').value = JSON.stringify(window.builderData || []);
    });

    // ==========
    // Init
    // ==========
    document.addEventListener('DOMContentLoaded', function () {
        console.info('[PB] pageBuilder.js avviato');

        const safeRender = () => {
            try { renderBuilder(); }
            catch (e) {
                console.error('[PB] render error:', e);
                const el = document.getElementById('builderContainer');
                if (el) el.innerHTML = `<div class="text-danger">Errore JS durante il render: ${e?.message||e}</div>`;
            }
        };

        // 1) RENDER SUBITO
        safeRender();

        // 2) Bind sidebar / pulsanti
        q('#btnAddSection')?.addEventListener('click', addSection);
        q('#btnCollapseAll')?.addEventListener('click', ()=> collapseAll(false));
        q('#btnExpandAll')?.addEventListener('click', ()=> collapseAll(true));
        q('#fabAdd')?.addEventListener('click', addSection);

        // 3) Settings responsive
        const settingsCol = q('#settingsCol');
        const settingsContent = q('#settingsContent');
        const settingsHost = q('.settings-content-host');
        const settingsToggle = q('#settingsToggle');
        const gearFab = q('#pbGearFab');
        const offcanvas = q('#settingsOffcanvas');
        const backdrop = q('#settingsBackdrop');

        function isDesktop(){ return window.matchMedia('(min-width: 992px)').matches; }
        function setCollapsedDesktop(flag){
            if (!isDesktop()) return;
            settingsCol.classList.toggle('collapsed', !!flag);
            localStorage.setItem('pb_settings_collapsed', flag ? '1' : '0');
        }
        const saved = localStorage.getItem('pb_settings_collapsed');
        if (saved === '1') setCollapsedDesktop(true);

        settingsToggle?.addEventListener('click', () => {
            setCollapsedDesktop(!settingsCol.classList.contains('collapsed'));
        });

        function openSettingsMobile(){
            if (isDesktop()) return;
            offcanvas.appendChild(settingsContent);
            offcanvas.classList.add('show');
            backdrop.classList.add('show');
            offcanvas.setAttribute('aria-hidden','false');
        }
        function closeSettingsMobile(){
            if (isDesktop()) return;
            settingsHost.appendChild(settingsContent);
            offcanvas.classList.remove('show');
            backdrop.classList.remove('show');
            offcanvas.setAttribute('aria-hidden','true');
        }
        gearFab?.addEventListener('click', openSettingsMobile);
        backdrop?.addEventListener('click', closeSettingsMobile);

        window.addEventListener('resize', () => {
            if (isDesktop()){
                if (!settingsHost.contains(settingsContent)) settingsHost.appendChild(settingsContent);
                closeSettingsMobile();
            } else {
                settingsCol.classList.remove('collapsed');
            }
        });

        // 4) Carica i plugin admin (opzionale)
        import('./plugins-entries.js')
            .then(m => m?.default ? m.default(API) : null)
            .then(() => {
                headerPluginsInjected = false;
                safeRender();
            })
            .catch(e => {
                console.warn('Plugin admin non caricati:', e);
            });
    });

})();
