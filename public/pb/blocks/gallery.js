// public/pb/blocks/gallery.js

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
 * Render del blocco Galleria immagini.
 * Usa block.gallery (array di {src, full, alt}) e block.galleryQuality.
 */
export function renderGalleryBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = (typeof ctx.rerender === 'function') ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';

    // === STYLE DATA =========================================================
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

    // === GALLERY DATA =======================================================
    const items = Array.isArray(block.gallery) ? block.gallery.slice() : [];
    let galleryQuality = block.galleryQuality || 'thumb';

    const saveBlock = (extra = {}) => {
        const animData = animation.name && animation.name !== 'none'
            ? {
                name: animation.name,
                duration: animation.duration,
                delay: animation.delay,
            }
            : null;

        state.updateBlock(section.id, block.id, {
            gallery: items.map((it) => ({ ...it })),
            galleryQuality,
            style: { ...style },
            animation: animData,
            ...extra,
        });
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar pb-gallery-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Galleria immagini';
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
            if (confirm('Eliminare questa galleria?')) {
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
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Aggiungi immagine';
        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openImagePicker((url) => {
                if (!url) return;
                items.push({
                    src: url,
                    full: url,
                    alt: '',
                });
                saveBlock();
                rerender && rerender();
            });
        });
        toolbar.appendChild(addBtn);
    }

    // === PREVIEW GRID =======================================================
    const preview = document.createElement('div');
    preview.className = 'pb-preview mt-2';

    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'text-muted small py-3 text-center';
        empty.innerHTML = `
            <i class="bi bi-images me-1"></i>
            Nessuna immagine nella galleria.
        `;
        preview.appendChild(empty);
    } else {
        const row = document.createElement('div');
        row.className = 'row g-2';

        items.forEach((item, idx) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'border rounded small h-100 d-flex flex-column';

            const imgWrap = document.createElement('div');
            imgWrap.className = 'ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center';

            if (item.src) {
                const img = document.createElement('img');
                img.src = item.src;
                img.alt = item.alt || '';
                img.style.objectFit = 'cover';
                img.style.width = '100%';
                img.style.height = '100%';
                imgWrap.innerHTML = '';
                imgWrap.appendChild(img);
            } else {
                imgWrap.innerHTML = '<span class="text-muted"><i class="bi bi-image"></i></span>';
            }

            card.appendChild(imgWrap);

            if (!previewMode) {
                const body = document.createElement('div');
                body.className = 'p-2 d-flex flex-column gap-1';

                const altInput = document.createElement('input');
                altInput.type = 'text';
                altInput.className = 'form-control form-control-sm';
                altInput.placeholder = 'Alt immagine';
                altInput.value = item.alt || '';
                altInput.addEventListener('input', () => {
                    items[idx].alt = altInput.value;
                    saveBlock();
                });
                body.appendChild(altInput);

                const actions = document.createElement('div');
                actions.className = 'd-flex justify-content-between align-items-center gap-1';

                const moveLeft = document.createElement('button');
                moveLeft.type = 'button';
                moveLeft.className = 'btn btn-sm btn-light border';
                moveLeft.title = 'Sposta a sinistra';
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
                actions.appendChild(moveLeft);

                const moveRight = document.createElement('button');
                moveRight.type = 'button';
                moveRight.className = 'btn btn-sm btn-light border';
                moveRight.title = 'Sposta a destra';
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
                actions.appendChild(moveRight);

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'btn btn-sm btn-outline-danger';
                delBtn.title = 'Rimuovi';
                delBtn.innerHTML = '<i class="bi bi-trash"></i>';
                delBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    items.splice(idx, 1);
                    saveBlock();
                    rerender && rerender();
                });
                actions.appendChild(delBtn);

                body.appendChild(actions);
                card.appendChild(body);
            }

            col.appendChild(card);
            row.appendChild(col);
        });

        preview.appendChild(row);
    }

    // Qualità per l'output frontend (usata in page_renderer.blade.php)
    const qualityWrap = document.createElement('div');
    qualityWrap.className = 'mt-2';

    qualityWrap.innerHTML = `
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Qualità immagini</label>
                <select class="form-select form-select-sm pb-gallery-quality">
                    <option value="thumb">Thumb</option>
                    <option value="25">25%</option>
                    <option value="59">59%</option>
                    <option value="75">75%</option>
                    <option value="full">Full</option>
                </select>
            </div>
        </div>
    `;

    const qualitySel = qualityWrap.querySelector('.pb-gallery-quality');
    if (qualitySel) qualitySel.value = galleryQuality || 'thumb';

    if (!previewMode) {
        qualitySel && qualitySel.addEventListener('change', () => {
            galleryQuality = qualitySel.value || 'thumb';
            saveBlock();
        });
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

        animation.name = name;
        animation.duration = duration;
        animation.delay = delay;

        saveBlock();
    };

    if (!previewMode) {
        colSel && colSel.addEventListener('change', () => {
            const c = parseInt(colSel.value || '12', 10);
            const safe = (!isNaN(c) && c >= 1 && c <= 12) ? c : 12;
            style.col = String(safe);
            applyStyleToDom();
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
        animDurInp && animDurInp.addEventListener('input', syncAnimation);
        animDelInp && animDelInp.addEventListener('input', syncAnimation);
    }

    applyStyleToDom();

    // === ASSEMBLA CARD ======================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    body.appendChild(qualityWrap);
    body.appendChild(stylePanel);

    container.appendChild(body);
}
