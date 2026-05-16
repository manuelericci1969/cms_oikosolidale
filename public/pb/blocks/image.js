// public/pb/blocks/image.js

import { openImagePicker } from '../mediaPicker.js';

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

/**
 * Render del blocco Immagine.
 * ctx = { container, section, block, index, state, rerender, previewMode }
 */
export function renderImageBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = (typeof ctx.rerender === 'function') ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';

    // === STYLE DATA (blocchi) ===============================================
    const style = {
        ...(block.style && typeof block.style === 'object' ? block.style : {}),
        ...(block.data && block.data.style && typeof block.data.style === 'object' ? block.data.style : {}),
    };

    // === ANIMATION DATA (frontend) ==========================================
    const animation = (block.animation && typeof block.animation === 'object')
        ? {
            name: block.animation.name || 'none',
            duration: typeof block.animation.duration === 'number' ? block.animation.duration : 600,
            delay: typeof block.animation.delay === 'number' ? block.animation.delay : 0,
        }
        : { name: 'none', duration: 600, delay: 0 };

    // === IMAGE DATA =========================================================
    const image = (block.image && typeof block.image === 'object')
        ? { ...block.image }
        : {
            src: '',
            full: '',
            alt: '',
            caption: '',
            quality: 'thumb',
        };

    image.src     = image.src     || '';
    image.full    = image.full    || image.src || '';
    image.alt     = image.alt     || '';
    image.caption = image.caption || '';
    image.quality = image.quality || 'thumb';

    // --- OPTIONS (height / fit / align) -------------------------------------
    const defaultOptions = {
        heightMode: 'auto',      // auto | fixed | ratio
        heightPx: 450,           // usato se fixed
        aspectRatio: '16 / 9',   // usato se ratio
        objectFit: 'cover',      // cover | contain | fill | none | scale-down
        align: 'center',         // left | center | right
    };

    image.options = (image.options && typeof image.options === 'object')
        ? { ...defaultOptions, ...image.options }
        : { ...defaultOptions };

    // --- BORDER (solo radius per ora, compatibile con renderer PHP) ---------
    const defaultBorder = { w: 0, s: 'solid', c: '#000000', r: 0 };
    image.border = (image.border && typeof image.border === 'object')
        ? { ...defaultBorder, ...image.border }
        : { ...defaultBorder };

    // === SAVE BLOCK =========================================================
    const saveBlock = (extra = {}) => {
        const animData = animation.name && animation.name !== 'none'
            ? {
                name: animation.name,
                duration: animation.duration,
                delay: animation.delay,
            }
            : null;

        state.updateBlock(section.id, block.id, {
            image: { ...image },
            style: { ...style },
            animation: animData,
            ...extra,
        });
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar pb-image-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Immagine';
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
            if (confirm('Eliminare questo blocco immagine?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        }));

        const sep = document.createElement('span');
        sep.className = 'small-muted ms-2 me-1';
        sep.textContent = '|';
        toolbar.appendChild(sep);

        const pickBtn = document.createElement('button');
        pickBtn.type = 'button';
        pickBtn.className = 'btn btn-sm btn-outline-primary';
        pickBtn.innerHTML = `<i class="bi bi-image"></i> ${image.src ? 'Cambia immagine' : 'Scegli immagine'}`;
        pickBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openImagePicker((url) => {
                if (!url) return;
                image.src  = url;
                image.full = url;
                saveBlock();
                rerender && rerender();
            });
        });
        toolbar.appendChild(pickBtn);
    }

    // === PREVIEW ============================================================
    const preview = document.createElement('div');
    preview.className = 'pb-preview mt-2';

    let figure = null;
    let imgEl  = null;

    if (image.src) {
        figure = document.createElement('figure');
        figure.className = 'm-0 text-center';

        imgEl = document.createElement('img');
        imgEl.src = image.src;
        imgEl.alt = image.alt || '';
        imgEl.className = 'img-fluid';
        figure.appendChild(imgEl);

        if (image.caption) {
            const capEl = document.createElement('figcaption');
            capEl.className = 'small text-muted mt-1';
            capEl.textContent = image.caption;
            figure.appendChild(capEl);
        }

        preview.appendChild(figure);
    } else {
        const empty = document.createElement('div');
        empty.className = 'text-muted small py-3 text-center';
        empty.innerHTML = `
            <i class="bi bi-image me-1"></i>
            Nessuna immagine selezionata.
        `;
        preview.appendChild(empty);
    }

    // === META IMMAGINE (alt, didascalia, qualità) ===========================
    const meta = document.createElement('div');
    meta.className = 'mt-3';

    meta.innerHTML = `
        <div class="row g-2">
            <div class="col-12 col-md-6">
                <label class="form-label small mb-0">Testo alternativo (alt)</label>
                <input type="text" class="form-control form-control-sm pb-img-alt">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label small mb-0">Didascalia</label>
                <input type="text" class="form-control form-control-sm pb-img-caption">
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Qualità</label>
                <select class="form-select form-select-sm pb-img-quality">
                    <option value="thumb">Thumb</option>
                    <option value="25">25%</option>
                    <option value="59">59%</option>
                    <option value="75">75%</option>
                    <option value="full">Full</option>
                </select>
            </div>
        </div>
    `;

    const altInp     = meta.querySelector('.pb-img-alt');
    const capInp     = meta.querySelector('.pb-img-caption');
    const qualitySel = meta.querySelector('.pb-img-quality');

    if (altInp)     altInp.value     = image.alt || '';
    if (capInp)     capInp.value     = image.caption || '';
    if (qualitySel) qualitySel.value = image.quality || 'thumb';

    if (!previewMode) {
        altInp && altInp.addEventListener('input', () => {
            image.alt = altInp.value;
            saveBlock();
        });
        capInp && capInp.addEventListener('input', () => {
            image.caption = capInp.value;
            saveBlock();
        });
        qualitySel && qualitySel.addEventListener('change', () => {
            image.quality = qualitySel.value || 'thumb';
            saveBlock();
        });
    }

    // === STILI IMMAGINE (height / fit / radius) =============================
    const imgSettings = document.createElement('div');
    imgSettings.className = 'mt-3';

    imgSettings.innerHTML = `
        <div class="mb-1 small text-muted">
            Stili immagine (frontend)
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Altezza immagine</label>
                <select class="form-select form-select-sm pb-img-hmode">
                    <option value="auto">Adatta al contenuto</option>
                    <option value="fixed">Altezza fissa</option>
                    <option value="ratio">Rapporto fisso</option>
                </select>
            </div>
            <div class="col-6 col-md-4 pb-img-hpx-wrap d-none">
                <label class="form-label small mb-0">Altezza (px)</label>
                <input type="number" min="50" max="2000"
                       class="form-control form-control-sm pb-img-hpx"
                       placeholder="es. 450">
            </div>
            <div class="col-6 col-md-4 pb-img-ar-wrap d-none">
                <label class="form-label small mb-0">Rapporto</label>
                <select class="form-select form-select-sm pb-img-ar">
                    <option value="16 / 9">16 : 9</option>
                    <option value="4 / 3">4 : 3</option>
                    <option value="3 / 2">3 : 2</option>
                    <option value="1 / 1">1 : 1</option>
                </select>
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Adattamento</label>
                <select class="form-select form-select-sm pb-img-fit">
                    <option value="cover">Riempi &amp; ritaglia (cover)</option>
                    <option value="contain">Contieni (contain)</option>
                    <option value="fill">Deforma (fill)</option>
                    <option value="none">Nessuno</option>
                    <option value="scale-down">Riduci (scale-down)</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Allineamento</label>
                <select class="form-select form-select-sm pb-img-align">
                    <option value="center">Centro</option>
                    <option value="left">Sinistra</option>
                    <option value="right">Destra</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Raggio immagine</label>
                <input type="text" class="form-control form-control-sm pb-img-radius"
                       placeholder="es. 12px">
            </div>
        </div>
    `;

    const hModeSel   = imgSettings.querySelector('.pb-img-hmode');
    const hPxWrap    = imgSettings.querySelector('.pb-img-hpx-wrap');
    const hPxInp     = imgSettings.querySelector('.pb-img-hpx');
    const arWrap     = imgSettings.querySelector('.pb-img-ar-wrap');
    const arSel      = imgSettings.querySelector('.pb-img-ar');
    const fitSel     = imgSettings.querySelector('.pb-img-fit');
    const alignSel   = imgSettings.querySelector('.pb-img-align');
    const radiusInp  = imgSettings.querySelector('.pb-img-radius');

    const refreshImgSettingsUI = () => {
        const opt = image.options || defaultOptions;
        const border = image.border || defaultBorder;

        const hMode = opt.heightMode || 'auto';
        if (hModeSel) hModeSel.value = hMode;

        if (hPxInp) {
            hPxInp.value = opt.heightPx != null ? opt.heightPx : 450;
        }
        if (arSel) {
            arSel.value = opt.aspectRatio || '16 / 9';
        }
        if (fitSel) {
            fitSel.value = opt.objectFit || 'cover';
        }
        if (alignSel) {
            alignSel.value = opt.align || 'center';
        }
        if (radiusInp) {
            const r = border.r || 0;
            radiusInp.value = r ? `${r}px` : '';
        }

        if (hPxWrap) hPxWrap.classList.toggle('d-none', hMode !== 'fixed');
        if (arWrap)  arWrap.classList.toggle('d-none', hMode !== 'ratio');
    };

    const applyImageLayoutToPreview = () => {
        if (!imgEl) return;

        const opt    = image.options || defaultOptions;
        const border = image.border || defaultBorder;

        const hMode = opt.heightMode || 'auto';
        const hPx   = parseInt(opt.heightPx ?? 0, 10);
        const fit   = opt.objectFit || 'cover';
        const align = opt.align || 'center';

        imgEl.style.width = '100%';

        if (hMode === 'fixed' && hPx > 0) {
            imgEl.style.height = `${hPx}px`;
            imgEl.style.objectFit = fit;
        } else {
            imgEl.style.height = '';
            // per "ratio" nel frontend ci pensa il wrapper; qui basta mostrare il fit
            imgEl.style.objectFit = (hMode === 'auto') ? '' : fit;
        }

        const r = border.r || 0;
        imgEl.style.borderRadius = r ? `${r}px` : '';

        if (figure) {
            if (align === 'left') figure.style.textAlign = 'left';
            else if (align === 'right') figure.style.textAlign = 'right';
            else figure.style.textAlign = 'center';
        }
    };

    const syncImageOptions = () => {
        const opt = image.options || (image.options = { ...defaultOptions });

        if (hModeSel) {
            opt.heightMode = hModeSel.value || 'auto';
        }
        if (hPxInp) {
            const v = parseInt(hPxInp.value || String(opt.heightPx || 450), 10);
            opt.heightPx = Number.isNaN(v) ? 450 : Math.max(50, Math.min(v, 2000));
            hPxInp.value = opt.heightPx;
        }
        if (arSel) {
            opt.aspectRatio = arSel.value || '16 / 9';
        }
        if (fitSel) {
            opt.objectFit = fitSel.value || 'cover';
        }
        if (alignSel) {
            opt.align = alignSel.value || 'center';
        }

        if (radiusInp) {
            const raw = (radiusInp.value || '').trim();
            const m = raw.match(/(\d+)/);
            const r = m ? parseInt(m[1], 10) : 0;
            image.border = image.border && typeof image.border === 'object'
                ? image.border
                : { ...defaultBorder };
            image.border.r = Number.isNaN(r) ? 0 : Math.max(0, r);
            radiusInp.value = image.border.r ? `${image.border.r}px` : '';
        }

        refreshImgSettingsUI();
        applyImageLayoutToPreview();
        saveBlock();
    };

    if (!previewMode) {
        hModeSel && hModeSel.addEventListener('change', syncImageOptions);
        hPxInp   && hPxInp.addEventListener('input', syncImageOptions);
        arSel    && arSel.addEventListener('change', syncImageOptions);
        fitSel   && fitSel.addEventListener('change', syncImageOptions);
        alignSel && alignSel.addEventListener('change', syncImageOptions);
        radiusInp&& radiusInp.addEventListener('input', syncImageOptions);
    }

    // === PANNELLO STILI BLOCCO + ANIMAZIONE ================================
    const stylePanel = document.createElement('details');
    stylePanel.className = 'pb-style-panel mt-3';

    const summary = document.createElement('summary');
    summary.innerHTML = '<i class="bi bi-sliders me-1"></i> Stili blocco';
    stylePanel.appendChild(summary);

    const inner = document.createElement('div');
    inner.className = 'mt-2';

    inner.innerHTML = `
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Larghezza (colonne)</label>
                <select class="form-select form-select-sm pb-style-col">
                    <option value="12">12 / intera</option>
                    <option value="10">10</option>
                    <option value="8">8</option>
                    <option value="6">6 (½)</option>
                    <option value="4">4 (⅓)</option>
                    <option value="3">3 (¼)</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Altezza minima</label>
                <select class="form-select form-select-sm pb-style-minh">
                    <option value="">Default</option>
                    <option value="300px">300px</option>
                    <option value="400px">400px</option>
                    <option value="600px">600px</option>
                    <option value="100vh">Schermo intero (100vh)</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Margine sopra</label>
                <input type="text" class="form-control form-control-sm pb-style-mt" placeholder="es. 1.5rem">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Margine sotto</label>
                <input type="text" class="form-control form-control-sm pb-style-mb" placeholder="es. 1.5rem">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Padding X</label>
                <input type="text" class="form-control form-control-sm pb-style-px" placeholder="es. 1.5rem">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Padding Y</label>
                <input type="text" class="form-control form-control-sm pb-style-py" placeholder="es. 1.5rem">
            </div>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Sfondo blocco</label>
                <input type="color" class="form-control form-control-color form-control-sm pb-style-bg">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Colore bordo</label>
                <input type="color" class="form-control form-control-color form-control-sm pb-style-bc">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0">Spessore bordo</label>
                <input type="text" class="form-control form-control-sm pb-style-bw" placeholder="es. 1px">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0">Raggio bordo</label>
                <input type="text" class="form-control form-control-sm pb-style-br" placeholder="es. 8px">
            </div>
        </div>

        <hr class="mt-3 mb-2">

        <div class="mb-1 small text-muted">
            Animazione blocco (frontend)
        </div>
        <div class="row g-2 align-items-center">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Tipo animazione</label>
                <select class="form-select form-select-sm pb-anim-name">
                    <option value="none">Nessuna</option>
                    <option value="fade">Fade</option>
                    <option value="slide-up">Slide up</option>
                    <option value="slide-left">Slide left</option>
                    <option value="zoom">Zoom</option>
                    <option value="flip">Flip</option>
                </select>
            </div>
            <div class="col-3 col-md-4">
                <label class="form-label small mb-0">Durata (ms)</label>
                <input type="number" min="100" max="5000" step="50"
                       class="form-control form-control-sm pb-anim-dur"
                       placeholder="600">
            </div>
            <div class="col-3 col-md-4">
                <label class="form-label small mb-0">Delay (ms)</label>
                <input type="number" min="0" max="5000" step="50"
                       class="form-control form-control-sm pb-anim-del"
                       placeholder="0">
            </div>
        </div>
    `;
    stylePanel.appendChild(inner);

    const colSel  = inner.querySelector('.pb-style-col');
    const minHSel = inner.querySelector('.pb-style-minh');
    const mtInp   = inner.querySelector('.pb-style-mt');
    const mbInp   = inner.querySelector('.pb-style-mb');
    const pxInp   = inner.querySelector('.pb-style-px');
    const pyInp   = inner.querySelector('.pb-style-py');
    const bgInp   = inner.querySelector('.pb-style-bg');
    const bcInp   = inner.querySelector('.pb-style-bc');
    const bwInp   = inner.querySelector('.pb-style-bw');
    const brInp   = inner.querySelector('.pb-style-br');

    const animNameSel = inner.querySelector('.pb-anim-name');
    const animDurInp  = inner.querySelector('.pb-anim-dur');
    const animDelInp  = inner.querySelector('.pb-anim-del');

    const initialCols = block.columns || parseInt(style.col || '12', 10) || 12;
    if (colSel)  colSel.value  = String(initialCols);
    if (minHSel) minHSel.value = style.minHeight || '';
    if (mtInp)   mtInp.value   = style.marginTop || '';
    if (mbInp)   mbInp.value   = style.marginBottom || '';
    if (pxInp)   pxInp.value   = style.paddingX || '';
    if (pyInp)   pyInp.value   = style.paddingY || '';
    if (bgInp && style.bgColor)     bgInp.value = style.bgColor;
    if (bcInp && style.borderColor) bcInp.value = style.borderColor;
    if (bwInp)   bwInp.value   = style.borderWidth || '';
    if (brInp)   brInp.value   = style.borderRadius || '';

    if (animNameSel) animNameSel.value = animation.name || 'none';
    if (animDurInp)  animDurInp.value  = animation.duration;
    if (animDelInp)  animDelInp.value  = animation.delay;

    const applyStyleToDom = () => {
        const s = style || {};

        preview.style.minHeight = s.minHeight || '';
        preview.style.marginTop    = s.marginTop || '';
        preview.style.marginBottom = s.marginBottom || '';

        preview.style.paddingTop    = s.paddingY || '';
        preview.style.paddingBottom = s.paddingY || '';
        preview.style.paddingLeft   = s.paddingX || '';
        preview.style.paddingRight  = s.paddingX || '';

        preview.style.backgroundColor = s.bgColor || '';

        if (s.borderWidth) {
            preview.style.borderWidth = s.borderWidth;
            preview.style.borderStyle = 'solid';
        } else {
            preview.style.borderWidth = '';
            preview.style.borderStyle = '';
        }
        preview.style.borderColor  = s.borderColor || '';
        preview.style.borderRadius = s.borderRadius || '';
    };

    const syncStyle = () => {
        applyStyleToDom();
        applyImageLayoutToPreview();
        saveBlock();
    };

    const syncAnimation = () => {
        if (!animNameSel) return;

        const name = animNameSel.value || 'none';

        let duration = animation.duration;
        if (animDurInp) {
            const v = parseInt(animDurInp.value || String(duration), 10);
            duration = isNaN(v) ? 600 : Math.min(Math.max(v, 100), 5000);
            animDurInp.value = duration;
        }

        let delay = animation.delay;
        if (animDelInp) {
            const v = parseInt(animDelInp.value || String(delay), 10);
            delay = isNaN(v) ? 0 : Math.min(Math.max(v, 0), 5000);
            animDelInp.value = delay;
        }

        animation.name    = name;
        animation.duration = duration;
        animation.delay    = delay;

        saveBlock();
    };

    if (!previewMode) {
        colSel && colSel.addEventListener('change', () => {
            const c = parseInt(colSel.value || '12', 10);
            const safe = (!isNaN(c) && c >= 1 && c <= 12) ? c : 12;
            style.col = String(safe);
            applyStyleToDom();
            applyImageLayoutToPreview();
            saveBlock({ columns: safe });
        });
        minHSel && minHSel.addEventListener('change', () => {
            style.minHeight = minHSel.value || '';
            syncStyle();
        });
        mtInp && mtInp.addEventListener('input', () => {
            style.marginTop = mtInp.value;
            syncStyle();
        });
        mbInp && mbInp.addEventListener('input', () => {
            style.marginBottom = mbInp.value;
            syncStyle();
        });
        pxInp && pxInp.addEventListener('input', () => {
            style.paddingX = pxInp.value;
            syncStyle();
        });
        pyInp && pyInp.addEventListener('input', () => {
            style.paddingY = pyInp.value;
            syncStyle();
        });
        bgInp && bgInp.addEventListener('input', () => {
            style.bgColor = bgInp.value;
            syncStyle();
        });
        bcInp && bcInp.addEventListener('input', () => {
            style.borderColor = bcInp.value;
            syncStyle();
        });
        bwInp && bwInp.addEventListener('input', () => {
            style.borderWidth = bwInp.value;
            syncStyle();
        });
        brInp && brInp.addEventListener('input', () => {
            style.borderRadius = brInp.value;
            syncStyle();
        });

        animNameSel && animNameSel.addEventListener('change', syncAnimation);
        animDurInp  && animDurInp.addEventListener('input', syncAnimation);
        animDelInp  && animDelInp.addEventListener('input', syncAnimation);
    }

    // === APPLICA STILI INIZIALI =============================================
    refreshImgSettingsUI();
    applyStyleToDom();
    applyImageLayoutToPreview();

    // === ASSEMBLA CARD ======================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    body.appendChild(meta);
    body.appendChild(imgSettings);
    body.appendChild(stylePanel);

    container.appendChild(body);
}
