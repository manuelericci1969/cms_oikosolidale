// public/pb/blocks/carousel.js
// Carosello immagini (Page Builder) — preview "reale" come in produzione (Bootstrap Carousel)

import { openImagePicker, getMediaUrlByQuality } from '../mediaPicker.js';

let __pbCarouselCssInjected = false;

function ensureCarouselPreviewCss() {
    if (__pbCarouselCssInjected) return;
    __pbCarouselCssInjected = true;

    const style = document.createElement('style');
    style.id = 'pb-carousel-preview-css';
    style.textContent = `
        /* Preview builder: stesso comportamento del frontend */
        .pb-carousel .carousel-item img{
            width:100%;
            object-fit:var(--pb-of,cover);
            object-position:var(--pb-op,center center);
        }
        .pb-carousel.pb-carousel-fixed .carousel-item img{
            height:var(--pb-ch,450px);
        }

        /* Full-bleed come frontend */
        .pb-fullbleed{
            width:100vw;
            max-width:100vw;
            margin-left:calc(50% - 50vw);
            margin-right:calc(50% - 50vw);
        }

        .pb-car-layout{ overflow:hidden; }

        /* 3x3 picker: evidenzia attivo */
        .pb-style-panel .pb-op-btn.btn-primary{
            color:#fff !important;
        }
    `;
    document.head.appendChild(style);
}

function createIconBtn(iconClass, title, onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-light border';
    btn.title = title;
    btn.innerHTML = `<i class="${iconClass}"></i>`;
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        onClick();
    });
    return btn;
}

function clamp(n, min, max) {
    n = parseInt(String(n || ''), 10);
    if (isNaN(n)) n = min;
    return Math.max(min, Math.min(max, n));
}

function normalizeBoxTRBL(v, def = 0) {
    const x = (v && typeof v === 'object') ? v : {};
    return {
        t: clamp(x.t ?? def, 0, 400),
        r: clamp(x.r ?? def, 0, 400),
        b: clamp(x.b ?? def, 0, 400),
        l: clamp(x.l ?? def, 0, 400),
    };
}

function initBootstrapCarousel(rootEl, options) {
    if (!rootEl) return;
    if (!window.bootstrap || !window.bootstrap.Carousel) return;

    try {
        const interval = options.autoplay ? clamp(options.interval, 1000, 20000) : false;

        // getOrCreateInstance è disponibile in BS 5.2+
        const inst = window.bootstrap.Carousel.getOrCreateInstance(rootEl, {
            interval,
            ride: options.autoplay ? 'carousel' : false,
            pause: options.autoplay ? 'hover' : false,
            wrap: true,
            touch: true,
        });

        if (options.autoplay) inst.cycle();
        else inst.pause();
    } catch (e) {
        console.warn('Bootstrap Carousel init error', e);
    }
}

// Usa varianti salvate se presenti (thumb/q25/q59/q75/full), fallback su src/full
function pickUrl(it, q) {
    if (!it || typeof it !== 'object') return '';
    const full = it.full || it.src || '';
    const src = it.src || full || '';
    const thumb = it.thumb || '';

    if (!q) return src || full || thumb || '';
    if (q === 'full') return full || src || thumb || '';
    if (q === 'thumb') return thumb || src || full || '';
    if (q === '25') return it.q25 || it['25'] || src || full || thumb || '';
    if (q === '59') return it.q59 || it['59'] || src || full || thumb || '';
    if (q === '75') return it.q75 || it['75'] || src || full || thumb || '';
    return src || full || thumb || '';
}

// ===== Object-position UX ====================================================

function parseObjectPosition(pos) {
    // Ritorna { x, y } in percentuale 0..100
    const def = { x: 50, y: 50 };
    pos = String(pos || '').trim().toLowerCase();
    if (!pos) return def;

    // percentuali tipo "30% 70%"
    const perc = pos.match(/(-?\d+(?:\.\d+)?)%\s+(-?\d+(?:\.\d+)?)%/);
    if (perc) {
        const x = Math.max(0, Math.min(100, parseFloat(perc[1])));
        const y = Math.max(0, Math.min(100, parseFloat(perc[2])));
        return { x, y };
    }

    // keyword: left/center/right e top/center/bottom
    const parts = pos.split(/\s+/).filter(Boolean);
    const mapX = { left: 0, center: 50, right: 100 };
    const mapY = { top: 0, center: 50, bottom: 100 };

    let x = null, y = null;

    if (parts.length === 1 && parts[0] === 'center') return def;

    for (const p of parts) {
        if (p in mapX) x = mapX[p];
        if (p in mapY) y = mapY[p];
    }

    if (x === null && y === null) return def;
    if (x === null) x = 50;
    if (y === null) y = 50;

    return { x, y };
}

function toPercentPosition(x, y) {
    const xx = Math.max(0, Math.min(100, Math.round(Number(x))));
    const yy = Math.max(0, Math.min(100, Math.round(Number(y))));
    return `${xx}% ${yy}%`;
}

function nearestAnchor(v) {
    // aggancia a 0 / 50 / 100 se “vicino”
    const n = Number(v);
    if (Math.abs(n - 0) <= 4) return 0;
    if (Math.abs(n - 50) <= 4) return 50;
    if (Math.abs(n - 100) <= 4) return 100;
    return null;
}

// ============================================================================

export function renderCarouselBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender = (typeof ctx.rerender === 'function') ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';
    ensureCarouselPreviewCss();

    // === STYLE DATA =========================================================
    const style = {
        ...(block.style && typeof block.style === 'object' ? block.style : {}),
        ...(block.data && block.data.style && typeof block.data.style === 'object' ? block.data.style : {}),
    };

    // Defaults layout/padding (usati in frontend da pb_layout_css + pb_block_box_css)
    style.widthMode = ['container', 'full', 'custom'].includes(style.widthMode) ? style.widthMode : 'container';
    if (style.widthMode === 'custom') {
        if (typeof style.maxWidth !== 'number') style.maxWidth = 1140;
        style.maxWidth = clamp(style.maxWidth, 320, 2400);
    }

    style.padding = normalizeBoxTRBL(style.padding, 0);
    style.margin  = normalizeBoxTRBL(style.margin, 0);

    // In full-bleed, azzera margini laterali outer (evita offset strani)
    if (style.widthMode === 'full') {
        style.margin.l = 0;
        style.margin.r = 0;
    }

    // === ANIMATION DATA (frontend) ==========================================
    const animation = (block.animation && typeof block.animation === 'object')
        ? {
            name: block.animation.name || 'none',
            duration: typeof block.animation.duration === 'number' ? block.animation.duration : 600,
            delay: typeof block.animation.delay === 'number' ? block.animation.delay : 0,
        }
        : { name: 'none', duration: 600, delay: 0 };

    // === CAROUSEL DATA ======================================================
    const car = (block.carousel && typeof block.carousel === 'object') ? block.carousel : {};
    const items = Array.isArray(car.items) ? car.items.map(x => ({ ...x })) : [];

    const options = {
        autoplay: car.options?.autoplay ?? true,
        interval: typeof car.options?.interval === 'number' ? car.options.interval : 5000,
        indicators: car.options?.indicators ?? true,
        controls: car.options?.controls ?? true,
        heightMode: ['auto', 'fixed'].includes(car.options?.heightMode) ? car.options.heightMode : 'auto',
        heightPx: typeof car.options?.heightPx === 'number' ? car.options.heightPx : 450,
        objectFit: ['cover', 'contain', 'fill', 'none', 'scale-down'].includes(car.options?.objectFit) ? car.options.objectFit : 'cover',
        objectPosition: (typeof car.options?.objectPosition === 'string' && car.options.objectPosition.trim() !== '')
            ? car.options.objectPosition.trim()
            : 'center center',
        quality: ['thumb', '25', '59', '75', 'full'].includes(car.options?.quality) ? car.options.quality : 'thumb',
    };

    const saveBlock = (extra = {}) => {
        const animData = animation.name && animation.name !== 'none'
            ? { name: animation.name, duration: animation.duration, delay: animation.delay }
            : null;

        state.updateBlock(section.id, block.id, {
            carousel: {
                items: items.map(it => ({ ...it })),
                options: { ...options },
            },
            style: { ...style },
            animation: animData,
            ...extra,
        });
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Carosello immagini';
    toolbar.appendChild(label);

    if (!previewMode) {
        toolbar.appendChild(createIconBtn('bi bi-arrow-up', 'Sposta blocco su', () => {
            state.moveBlock(section.id, block.id, -1);
            rerender && rerender();
        }));
        toolbar.appendChild(createIconBtn('bi bi-arrow-down', 'Sposta blocco giù', () => {
            state.moveBlock(section.id, block.id, +1);
            rerender && rerender();
        }));
        toolbar.appendChild(createIconBtn('bi bi-files', 'Duplica blocco', () => {
            state.duplicateBlock(section.id, block.id);
            rerender && rerender();
        }));
        toolbar.appendChild(createIconBtn('bi bi-trash', 'Elimina blocco', () => {
            if (confirm('Eliminare questo carosello?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        }));

        const sep = document.createElement('span');
        sep.className = 'small-muted ms-2 me-1';
        sep.textContent = '|';
        toolbar.appendChild(sep);

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-sm btn-outline-primary';
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Aggiungi slide';
        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openImagePicker((url, item) => {
                if (!url) return;

                const base = (item && (item.url || item.thumb)) ? (item.url || item.thumb) : url;

                // salviamo tutte le varianti (robusto per backend diversi)
                const full = getMediaUrlByQuality(item, 'full', base) || base;
                const thumb = getMediaUrlByQuality(item, 'thumb', base) || '';
                const q25 = getMediaUrlByQuality(item, '25', base) || '';
                const q59 = getMediaUrlByQuality(item, '59', base) || '';
                const q75 = getMediaUrlByQuality(item, '75', base) || '';

                items.push({
                    src: url,     // url ritornato dal picker
                    full,
                    thumb,
                    q25,
                    q59,
                    q75,
                    alt: '',
                });

                saveBlock();
                rerender && rerender();
            }, { mode: 'image', quality: options.quality || null });
        });
        toolbar.appendChild(addBtn);
    }

    // === PREVIEW (RENDER COME FRONTEND) =====================================
    const preview = document.createElement('div');
    preview.className = 'pb-preview mt-2';

    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'text-muted small py-3 text-center';
        empty.innerHTML = `<i class="bi bi-images me-1"></i> Nessuna slide nel carosello.`;
        preview.appendChild(empty);
    } else {
        const cid = `pb_car_${block.id || Math.random().toString(36).slice(2)}`;

        const hMode = options.heightMode;
        const hPx = clamp(options.heightPx, 100, 1200);
        const fit = options.objectFit;

        // objectPosition può essere "center center" oppure percentuali -> CSS lo accetta
        const pos = options.objectPosition || 'center center';
        const q = options.quality;

        const cClass = `pb-carousel carousel slide${hMode === 'fixed' ? ' pb-carousel-fixed' : ''}`;
        const cStyle = hMode === 'fixed'
            ? `--pb-ch:${hPx}px;--pb-of:${fit};--pb-op:${pos};`
            : `--pb-of:${fit};--pb-op:${pos};`;

        // Layout wrapper (simula pb-outer/pb-box: widthMode + padding)
        const layoutWrap = document.createElement('div');
        layoutWrap.className = 'pb-car-layout';
        if (style.widthMode === 'full') layoutWrap.classList.add('pb-fullbleed');

        // padding (in produzione lo fa pb_block_box_css sul pb-box)
        layoutWrap.style.padding = `${style.padding.t}px ${style.padding.r}px ${style.padding.b}px ${style.padding.l}px`;

        // custom max-width (in produzione lo fa pb_layout_css sul pb-outer)
        if (style.widthMode === 'custom') {
            const mw = clamp(style.maxWidth, 320, 2400);
            layoutWrap.style.maxWidth = mw + 'px';
            layoutWrap.style.marginLeft = 'auto';
            layoutWrap.style.marginRight = 'auto';
        }

        const root = document.createElement('div');
        root.id = cid;
        root.className = cClass;
        root.setAttribute('data-bs-interval', String(clamp(options.interval, 1000, 20000)));
        if (options.autoplay) root.setAttribute('data-bs-ride', 'carousel');

        if (options.indicators) {
            const ind = document.createElement('div');
            ind.className = 'carousel-indicators';
            items.forEach((_, i) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.setAttribute('data-bs-target', `#${cid}`);
                b.setAttribute('data-bs-slide-to', String(i));
                if (i === 0) {
                    b.className = 'active';
                    b.setAttribute('aria-current', 'true');
                }
                b.setAttribute('aria-label', `Slide ${i + 1}`);
                ind.appendChild(b);
            });
            root.appendChild(ind);
        }

        const inner = document.createElement('div');
        inner.className = 'carousel-inner';

        items.forEach((it, i) => {
            const itemEl = document.createElement('div');
            itemEl.className = `carousel-item${i === 0 ? ' active' : ''}`;

            const img = document.createElement('img');
            img.className = 'd-block w-100';
            img.alt = it.alt || '';
            img.loading = 'lazy';
            img.decoding = 'async';
            img.src = pickUrl(it, q) || it.src || it.full || '';

            itemEl.appendChild(img);
            inner.appendChild(itemEl);
        });

        root.appendChild(inner);

        if (options.controls) {
            const prev = document.createElement('button');
            prev.className = 'carousel-control-prev';
            prev.type = 'button';
            prev.setAttribute('data-bs-target', `#${cid}`);
            prev.setAttribute('data-bs-slide', 'prev');
            prev.innerHTML = `
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            `;

            const next = document.createElement('button');
            next.className = 'carousel-control-next';
            next.type = 'button';
            next.setAttribute('data-bs-target', `#${cid}`);
            next.setAttribute('data-bs-slide', 'next');
            next.innerHTML = `
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            `;

            root.appendChild(prev);
            root.appendChild(next);
        }

        root.style.cssText = cStyle;
        layoutWrap.appendChild(root);
        preview.appendChild(layoutWrap);

        initBootstrapCarousel(root, options);
    }

    // === OPTIONS PANEL ======================================================
    const optWrap = document.createElement('details');
    optWrap.className = 'pb-style-panel mt-2';
    optWrap.open = true;

    const optSum = document.createElement('summary');
    optSum.innerHTML = '<i class="bi bi-sliders me-1"></i> Impostazioni carosello';
    optWrap.appendChild(optSum);

    const optInner = document.createElement('div');
    optInner.className = 'mt-2';
    optInner.innerHTML = `
        <div class="row g-2">

            <div class="col-12 col-md-6">
                <label class="form-label small mb-0">Larghezza blocco</label>
                <select class="form-select form-select-sm pb-car-wmode">
                    <option value="container">Con margini (container)</option>
                    <option value="full">Tutto schermo (full-bleed)</option>
                    <option value="custom">Custom (max-width)</option>
                </select>
            </div>

            <div class="col-6 col-md-3 pb-car-maxw-wrap">
                <label class="form-label small mb-0">Max width (px)</label>
                <input type="number" min="320" max="2400" step="10"
                       class="form-control form-control-sm pb-car-maxw">
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Padding L/R (px)</label>
                <input type="number" min="0" max="400" step="1"
                       class="form-control form-control-sm pb-car-gutter"
                       placeholder="es: 0 / 24 / 48">
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Qualità output</label>
                <select class="form-select form-select-sm pb-car-q">
                    <option value="thumb">Thumb</option>
                    <option value="25">25%</option>
                    <option value="59">59%</option>
                    <option value="75">75%</option>
                    <option value="full">Full</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Autoplay</label>
                <select class="form-select form-select-sm pb-car-autoplay">
                    <option value="1">Sì</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Intervallo (ms)</label>
                <input type="number" min="1000" max="20000" step="250"
                       class="form-control form-control-sm pb-car-interval">
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Altezza</label>
                <select class="form-select form-select-sm pb-car-hmode">
                    <option value="auto">Auto</option>
                    <option value="fixed">Fissa</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Altezza px</label>
                <input type="number" min="100" max="1200" step="10"
                       class="form-control form-control-sm pb-car-hpx">
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Object fit</label>
                <select class="form-select form-select-sm pb-car-fit">
                    <option value="cover">Cover</option>
                    <option value="contain">Contain</option>
                    <option value="fill">Fill</option>
                    <option value="none">None</option>
                    <option value="scale-down">Scale-down</option>
                </select>
            </div>

            <!-- Object position guidato -->
            <div class="col-12 col-md-6">
                <label class="form-label small mb-1">Posizionamento immagine</label>

                <div class="d-grid" style="grid-template-columns:repeat(3,1fr);gap:.25rem">
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="0" data-y="0">↖</button>
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="50" data-y="0">↑</button>
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="100" data-y="0">↗</button>

                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="0" data-y="50">←</button>
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="50" data-y="50">•</button>
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="100" data-y="50">→</button>

                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="0" data-y="100">↙</button>
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="50" data-y="100">↓</button>
                    <button type="button" class="btn btn-sm btn-light border pb-op-btn" data-x="100" data-y="100">↘</button>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <label class="form-label small mb-0">Orizzontale (X)</label>
                        <input type="range" min="0" max="100" step="1" class="form-range pb-car-pos-x">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-0">Verticale (Y)</label>
                        <input type="range" min="0" max="100" step="1" class="form-range pb-car-pos-y">
                    </div>
                </div>

                <div class="small text-muted mt-1">
                    Valore: <code class="pb-car-pos-out"></code>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-2 pb-car-pos-adv-toggle">Avanzato</button>
                </div>

                <div class="mt-2 d-none pb-car-pos-adv">
                    <input type="text" class="form-control form-control-sm pb-car-pos-raw"
                           placeholder="es: center center, top center, 50% 20%">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Indicatori</label>
                <select class="form-select form-select-sm pb-car-ind">
                    <option value="1">Sì</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Controlli</label>
                <select class="form-select form-select-sm pb-car-ctrl">
                    <option value="1">Sì</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>

        <div class="small text-muted mt-2">
            Nota: nella modale media picker puoi scegliere anche la qualità “di inserimento” (url restituito).
            Qui invece scegli la qualità che verrà usata in output sul sito (frontend).
        </div>
    `;
    optWrap.appendChild(optInner);

    // selectors layout
    const wModeSel = optInner.querySelector('.pb-car-wmode');
    const maxwWrap = optInner.querySelector('.pb-car-maxw-wrap');
    const maxwInp  = optInner.querySelector('.pb-car-maxw');
    const gutInp   = optInner.querySelector('.pb-car-gutter');

    // selectors carousel
    const qSel = optInner.querySelector('.pb-car-q');
    const apSel = optInner.querySelector('.pb-car-autoplay');
    const intInp = optInner.querySelector('.pb-car-interval');
    const hmSel = optInner.querySelector('.pb-car-hmode');
    const hpInp = optInner.querySelector('.pb-car-hpx');
    const fitSel = optInner.querySelector('.pb-car-fit');
    const indSel = optInner.querySelector('.pb-car-ind');
    const ctrSel = optInner.querySelector('.pb-car-ctrl');

    // object-position selectors
    const opBtns = Array.from(optInner.querySelectorAll('.pb-op-btn'));
    const posX = optInner.querySelector('.pb-car-pos-x');
    const posY = optInner.querySelector('.pb-car-pos-y');
    const posOut = optInner.querySelector('.pb-car-pos-out');
    const advToggle = optInner.querySelector('.pb-car-pos-adv-toggle');
    const advBox = optInner.querySelector('.pb-car-pos-adv');
    const posRaw = optInner.querySelector('.pb-car-pos-raw');

    // init values
    wModeSel.value = style.widthMode || 'container';
    maxwInp.value  = String(clamp(style.maxWidth || 1140, 320, 2400));
    gutInp.value   = String(clamp(style.padding?.l ?? 0, 0, 400));

    qSel.value = options.quality;
    apSel.value = options.autoplay ? '1' : '0';
    intInp.value = String(clamp(options.interval, 1000, 20000));
    hmSel.value = options.heightMode;
    hpInp.value = String(clamp(options.heightPx, 100, 1200));
    fitSel.value = options.objectFit;
    indSel.value = options.indicators ? '1' : '0';
    ctrSel.value = options.controls ? '1' : '0';

    // init object-position from saved string
    const p0 = parseObjectPosition(options.objectPosition || 'center center');
    posX.value = String(p0.x);
    posY.value = String(p0.y);
    const pStr0 = toPercentPosition(p0.x, p0.y);
    posOut.textContent = pStr0;
    posRaw.value = options.objectPosition || pStr0;

    function refreshMaxwVisibility() {
        const isCustom = (wModeSel.value === 'custom');
        if (maxwWrap) maxwWrap.style.display = isCustom ? '' : 'none';
    }

    function refreshOpActive() {
        const ax = nearestAnchor(posX.value);
        const ay = nearestAnchor(posY.value);
        opBtns.forEach(b => b.classList.remove('btn-primary'));
        if (ax === null || ay === null) return;

        const hit = opBtns.find(b => Number(b.dataset.x) === ax && Number(b.dataset.y) === ay);
        if (hit) hit.classList.add('btn-primary');
    }

    refreshMaxwVisibility();
    refreshOpActive();

    // disable in preview mode
    if (previewMode) {
        [
            wModeSel, maxwInp, gutInp,
            qSel, apSel, intInp, hmSel, hpInp, fitSel, indSel, ctrSel,
            posX, posY, advToggle, posRaw,
            ...opBtns
        ].forEach(el => { if (el) el.disabled = true; });
    }

    // sync layout + other options (NON object-position)
    const sync = () => {
        // width mode + max width
        const wm = wModeSel.value;
        style.widthMode = ['container','full','custom'].includes(wm) ? wm : 'container';

        if (style.widthMode === 'custom') {
            style.maxWidth = clamp(maxwInp.value, 320, 2400);
        }

        // padding L/R (margini visivi interni)
        const gut = clamp(gutInp.value, 0, 400);
        style.padding = normalizeBoxTRBL(style.padding, 0);
        style.padding.l = gut;
        style.padding.r = gut;

        // se full-bleed, azzera margini laterali outer (per sicurezza)
        style.margin = normalizeBoxTRBL(style.margin, 0);
        if (style.widthMode === 'full') {
            style.margin.l = 0;
            style.margin.r = 0;
        }

        // carousel options
        options.quality = qSel.value;
        options.autoplay = apSel.value === '1';
        options.interval = clamp(intInp.value, 1000, 20000);
        options.heightMode = hmSel.value;
        options.heightPx = clamp(hpInp.value, 100, 1200);
        options.objectFit = fitSel.value;

        options.indicators = indSel.value === '1';
        options.controls = ctrSel.value === '1';

        refreshMaxwVisibility();
        saveBlock();
        rerender && rerender();
    };

    // object-position apply
    const applyPosFromXY = () => {
        const x = clamp(posX.value, 0, 100);
        const y = clamp(posY.value, 0, 100);
        const str = toPercentPosition(x, y);

        options.objectPosition = str;  // salviamo in percentuale: sempre valido in CSS
        posOut.textContent = str;
        posRaw.value = str;

        refreshOpActive();
        saveBlock();
        rerender && rerender();
    };

    if (!previewMode) {
        // layout
        wModeSel.addEventListener('change', sync);
        maxwInp.addEventListener('input', sync);
        gutInp.addEventListener('input', sync);

        // carousel
        qSel.addEventListener('change', sync);
        apSel.addEventListener('change', sync);
        intInp.addEventListener('input', sync);
        hmSel.addEventListener('change', sync);
        hpInp.addEventListener('input', sync);
        fitSel.addEventListener('change', sync);
        indSel.addEventListener('change', sync);
        ctrSel.addEventListener('change', sync);

        // object-position: slider
        posX.addEventListener('input', applyPosFromXY);
        posY.addEventListener('input', applyPosFromXY);

        // object-position: 3x3 preset
        opBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                posX.value = btn.dataset.x;
                posY.value = btn.dataset.y;
                applyPosFromXY();
            });
        });

        // advanced toggle
        advToggle.addEventListener('click', (e) => {
            e.preventDefault();
            advBox.classList.toggle('d-none');
        });

        // advanced raw input (accetta anche "top center" ecc.)
        posRaw.addEventListener('input', () => {
            const p = parseObjectPosition(posRaw.value);
            posX.value = String(p.x);
            posY.value = String(p.y);
            applyPosFromXY();
        });
    }

    // === SLIDE MANAGER (solo edit mode) =====================================
    const manager = document.createElement('div');
    manager.className = 'mt-2';

    if (!previewMode && items.length) {
        const row = document.createElement('div');
        row.className = 'row g-2';

        items.forEach((it, idx) => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-lg-4';

            const card = document.createElement('div');
            card.className = 'border rounded small h-100 d-flex flex-column';

            const imgWrap = document.createElement('div');
            imgWrap.className = 'ratio ratio-16x9 bg-light d-flex align-items-center justify-content-center';

            const prevUrl = pickUrl(it, 'thumb') || it.src || it.full || '';
            if (prevUrl) {
                const img = document.createElement('img');
                img.src = prevUrl;
                img.alt = it.alt || '';
                img.style.objectFit = 'cover';
                img.style.width = '100%';
                img.style.height = '100%';
                imgWrap.appendChild(img);
            } else {
                imgWrap.innerHTML = '<span class="text-muted"><i class="bi bi-image"></i></span>';
            }

            card.appendChild(imgWrap);

            const body = document.createElement('div');
            body.className = 'p-2 d-flex flex-column gap-1';

            const altInput = document.createElement('input');
            altInput.type = 'text';
            altInput.className = 'form-control form-control-sm';
            altInput.placeholder = 'Alt slide';
            altInput.value = it.alt || '';
            altInput.addEventListener('input', () => {
                items[idx].alt = altInput.value;
                saveBlock();
            });
            body.appendChild(altInput);

            const actions = document.createElement('div');
            actions.className = 'd-flex flex-wrap gap-1 justify-content-between align-items-center';

            const moveLeft = document.createElement('button');
            moveLeft.type = 'button';
            moveLeft.className = 'btn btn-sm btn-light border';
            moveLeft.title = 'Sposta indietro';
            moveLeft.innerHTML = '<i class="bi bi-arrow-left"></i>';
            moveLeft.disabled = (idx === 0);
            moveLeft.addEventListener('click', (e) => {
                e.preventDefault();
                if (idx === 0) return;
                const [el] = items.splice(idx, 1);
                items.splice(idx - 1, 0, el);
                saveBlock();
                rerender && rerender();
            });

            const moveRight = document.createElement('button');
            moveRight.type = 'button';
            moveRight.className = 'btn btn-sm btn-light border';
            moveRight.title = 'Sposta avanti';
            moveRight.innerHTML = '<i class="bi bi-arrow-right"></i>';
            moveRight.disabled = (idx === items.length - 1);
            moveRight.addEventListener('click', (e) => {
                e.preventDefault();
                if (idx === items.length - 1) return;
                const [el] = items.splice(idx, 1);
                items.splice(idx + 1, 0, el);
                saveBlock();
                rerender && rerender();
            });

            const replaceBtn = document.createElement('button');
            replaceBtn.type = 'button';
            replaceBtn.className = 'btn btn-sm btn-outline-secondary';
            replaceBtn.title = 'Cambia immagine';
            replaceBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
            replaceBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openImagePicker((url, item) => {
                    if (!url) return;

                    const base = (item && (item.url || item.thumb)) ? (item.url || item.thumb) : url;

                    const full = getMediaUrlByQuality(item, 'full', base) || base;
                    const thumb = getMediaUrlByQuality(item, 'thumb', base) || '';
                    const q25 = getMediaUrlByQuality(item, '25', base) || '';
                    const q59 = getMediaUrlByQuality(item, '59', base) || '';
                    const q75 = getMediaUrlByQuality(item, '75', base) || '';

                    items[idx] = {
                        ...items[idx],
                        src: url,
                        full,
                        thumb,
                        q25,
                        q59,
                        q75,
                    };

                    saveBlock();
                    rerender && rerender();
                }, { mode: 'image', quality: options.quality || null });
            });

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn btn-sm btn-outline-danger';
            delBtn.title = 'Rimuovi slide';
            delBtn.innerHTML = '<i class="bi bi-trash"></i>';
            delBtn.addEventListener('click', (e) => {
                e.preventDefault();
                items.splice(idx, 1);
                saveBlock();
                rerender && rerender();
            });

            actions.appendChild(moveLeft);
            actions.appendChild(moveRight);
            actions.appendChild(replaceBtn);
            actions.appendChild(delBtn);

            body.appendChild(actions);
            card.appendChild(body);

            col.appendChild(card);
            row.appendChild(col);
        });

        manager.appendChild(row);
    }

    // === ASSEMBLA ===========================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    body.appendChild(optWrap);
    body.appendChild(manager);

    container.appendChild(body);
}
