// public/pb/blocks/spacer.js

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
 * Blocco "spazio / sfondo".
 *
 * block.spacer = {
 *   height: number (px),
 *   backgroundType: 'none'|'color'|'gradient'|'image',
 *   bgColor: '#f8f9fa',
 *   gradient: { from, to, angle },
 *   bgImage: { src, full, position, size, repeat, quality },
 *   overlay: { enabled, color, opacity (0..1) },
 *   bgAttachment: 'scroll' | 'fixed', // per parallax
 *   parallax: boolean,                // alias booleano
 *   borderRadius: number|string       // raggio bordi
 * }
 */
export function renderSpacerBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = (typeof ctx.rerender === 'function') ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';

    // === STYLE GENERICO (compatibilità con altri blocchi) ====================
    const style = {
        ...(block.style && typeof block.style === 'object' ? block.style : {}),
        ...(block.data && block.data.style && typeof block.data.style === 'object' ? block.data.style : {}),
    };

    // === ANIMAZIONE (opzionale, stessa struttura degli altri blocchi) ========
    const animation = (block.animation && typeof block.animation === 'object')
        ? {
            name: block.animation.name || 'none',
            duration: typeof block.animation.duration === 'number' ? block.animation.duration : 600,
            delay: typeof block.animation.delay === 'number' ? block.animation.delay : 0,
        }
        : { name: 'none', duration: 600, delay: 0 };

    // === DATI SPACER =========================================================
    const defaultSpacer = {
        height: 80,
        backgroundType: 'none', // none | color | gradient | image
        bgColor: '#f8f9fa',
        gradient: {
            from: '#0d6efd',
            to: '#6610f2',
            angle: 135,
        },
        bgImage: {
            src: '',
            full: '',
            position: 'center center',
            size: 'cover',       // cover | contain | auto
            repeat: 'no-repeat', // no-repeat | repeat | repeat-x | repeat-y
            quality: 'thumb',    // thumb | 25 | 59 | 75 | full
        },
        overlay: {
            enabled: false,
            color: '#000000',
            opacity: 0.35,       // 0..1
        },
        bgAttachment: 'scroll',  // scroll | fixed
        parallax: false,
        borderRadius: 0,
    };

    const srcSpacer = (block.spacer && typeof block.spacer === 'object')
        ? block.spacer
        : {};

    const spacer = {
        ...defaultSpacer,
        ...srcSpacer,
        gradient: {
            ...defaultSpacer.gradient,
            ...(srcSpacer.gradient || {}),
        },
        bgImage: {
            ...defaultSpacer.bgImage,
            ...(srcSpacer.bgImage || {}),
        },
        overlay: {
            ...defaultSpacer.overlay,
            ...(srcSpacer.overlay || {}),
        },
    };

    // Normalizza qualità
    spacer.bgImage.quality = spacer.bgImage.quality || 'thumb';

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
            spacer: { ...spacer },
            style:  { ...style },
            animation: animData,
            ...extra,
        });
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Spazio / Sfondo';
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
            if (confirm('Eliminare questo blocco di spazio?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        }));
    }

    // === PREVIEW ============================================================
    const preview = document.createElement('div');
    preview.className = 'pb-preview mt-2';

    const spacerBox = document.createElement('div');
    spacerBox.className = 'pb-spacer-box position-relative d-flex align-items-center justify-content-center';

    const overlayEl = document.createElement('div');
    overlayEl.className = 'pb-spacer-overlay position-absolute top-0 start-0 w-100 h-100';
    spacerBox.appendChild(overlayEl);

    const labelEl = document.createElement('div');
    labelEl.className = 'pb-spacer-label position-relative text-muted small text-center px-2';
    spacerBox.appendChild(labelEl);

    preview.appendChild(spacerBox);

    // Helper: url da usare nel preview in base alla qualità
    const resolveBgUrlForPreview = () => {
        const q = spacer.bgImage.quality || 'thumb';
        // Nota: in admin preview usiamo src; se "full" e full esiste, mostriamo full.
        if (q === 'full') return spacer.bgImage.full || spacer.bgImage.src || '';
        return spacer.bgImage.src || spacer.bgImage.full || '';
    };

    // Applica style generico (minHeight, background, overlay, radius, ecc.)
    const applySpacerStyle = () => {
        const h = parseInt(spacer.height ?? 0, 10);
        const safeH = !isNaN(h) && h > 0 ? h : 40;
        spacerBox.style.minHeight = `${safeH}px`;

        const q = spacer.bgImage.quality || 'thumb';
        labelEl.textContent = `Spazio / Sfondo – altezza ${safeH}px` + (spacer.backgroundType === 'image' ? ` – qualità ${q}` : '');

        // reset base
        spacerBox.style.backgroundColor = '';
        spacerBox.style.backgroundImage = '';
        spacerBox.style.backgroundPosition = '';
        spacerBox.style.backgroundSize = '';
        spacerBox.style.backgroundRepeat = '';
        spacerBox.style.backgroundAttachment = '';

        // Background in base al tipo
        const bgType = spacer.backgroundType || 'none';
        const bgColor = spacer.bgColor || '';

        if (bgType === 'color') {
            spacerBox.style.backgroundColor = bgColor || '#f8f9fa';
        } else if (bgType === 'gradient') {
            const from = spacer.gradient.from || '#0d6efd';
            const to   = spacer.gradient.to || '#6610f2';
            const ang  = parseInt(spacer.gradient.angle ?? 135, 10);
            const safeAngle = isNaN(ang) ? 135 : ang;
            spacerBox.style.backgroundImage = `linear-gradient(${safeAngle}deg, ${from}, ${to})`;
        } else if (bgType === 'image') {
            const url = resolveBgUrlForPreview();
            if (url) {
                spacerBox.style.backgroundImage    = `url('${url}')`;
                spacerBox.style.backgroundPosition = spacer.bgImage.position || 'center center';
                spacerBox.style.backgroundSize     = spacer.bgImage.size || 'cover';
                spacerBox.style.backgroundRepeat   = spacer.bgImage.repeat || 'no-repeat';

                if (bgColor) {
                    spacerBox.style.backgroundColor = bgColor;
                }

                // Effetto parallax: background fisso se richiesto
                const att = (spacer.bgAttachment === 'fixed' || spacer.parallax)
                    ? 'fixed'
                    : 'scroll';
                spacerBox.style.backgroundAttachment = att;
            } else {
                spacerBox.style.backgroundColor = bgColor || '#f8f9fa';
            }
        } else {
            spacerBox.style.backgroundColor = 'transparent';
        }

        // Overlay
        if (spacer.overlay && spacer.overlay.enabled) {
            overlayEl.style.display = 'block';
            overlayEl.style.backgroundColor = spacer.overlay.color || '#000000';
            const op = typeof spacer.overlay.opacity === 'number'
                ? Math.max(0, Math.min(spacer.overlay.opacity, 1))
                : 0.35;
            overlayEl.style.opacity = String(op);
        } else {
            overlayEl.style.display = 'none';
        }

        // Border radius (usa sia spacer.borderRadius che style.borderRadius)
        let rRaw = (typeof spacer.borderRadius !== 'undefined' && spacer.borderRadius !== null && spacer.borderRadius !== '')
            ? spacer.borderRadius
            : (style.borderRadius ?? '');

        let rCss = '';
        if (typeof rRaw === 'number') {
            if (rRaw > 0) rCss = `${rRaw}px`;
        } else if (typeof rRaw === 'string') {
            const trimmed = rRaw.trim();
            if (trimmed !== '' && trimmed !== '0') {
                if (/^\d+$/.test(trimmed)) {
                    rCss = `${parseInt(trimmed, 10)}px`;
                } else {
                    rCss = trimmed;
                }
            }
        }

        spacerBox.style.borderRadius = rCss;
        overlayEl.style.borderRadius = rCss;
    };

    // === PANNELLO IMPOSTAZIONI SPACER =======================================
    const settings = document.createElement('div');
    settings.className = 'mt-3';

    settings.innerHTML = `
        <div class="mb-1 small text-muted">
            Impostazioni spazio / sfondo
        </div>

        <div class="row g-2 mb-2">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Altezza interna (px)</label>
                <input type="number" min="0" max="2000"
                       class="form-control form-control-sm pb-spacer-h"
                       placeholder="es. 80">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Tipo sfondo</label>
                <select class="form-select form-select-sm pb-spacer-bgtype">
                    <option value="none">Nessuno</option>
                    <option value="color">Colore pieno</option>
                    <option value="gradient">Gradiente</option>
                    <option value="image">Immagine</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mb-2 pb-spacer-bgcolor-wrap d-none">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Colore sfondo</label>
                <input type="color"
                       class="form-control form-control-color form-control-sm pb-spacer-bgcolor">
            </div>
        </div>

        <div class="row g-2 mb-2 pb-spacer-gradient-wrap d-none">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Colore 1</label>
                <input type="color"
                       class="form-control form-control-color form-control-sm pb-spacer-grad-from">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Colore 2</label>
                <input type="color"
                       class="form-control form-control-color form-control-sm pb-spacer-grad-to">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Angolo (°)</label>
                <input type="number" min="0" max="360"
                       class="form-control form-control-sm pb-spacer-grad-angle">
            </div>
        </div>

        <div class="row g-2 mb-2 pb-spacer-image-wrap d-none">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-0 d-block">Immagine di sfondo</label>
                <button type="button"
                        class="btn btn-sm btn-outline-primary pb-spacer-img-pick">
                    <i class="bi bi-image"></i> Scegli immagine
                </button>
                <div class="small text-muted mt-1 pb-spacer-img-info"></div>
            </div>

            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Qualità immagine</label>
                <select class="form-select form-select-sm pb-spacer-img-quality">
                    <option value="thumb">Thumb</option>
                    <option value="25">25%</option>
                    <option value="59">59%</option>
                    <option value="75">75%</option>
                    <option value="full">Full</option>
                </select>
            </div>

            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Posizione</label>
                <select class="form-select form-select-sm pb-spacer-img-pos">
                    <option value="center center">Centro</option>
                    <option value="top center">Sopra</option>
                    <option value="bottom center">Sotto</option>
                    <option value="center left">Sinistra</option>
                    <option value="center right">Destra</option>
                    <option value="top left">Sopra / sinistra</option>
                    <option value="top right">Sopra / destra</option>
                    <option value="bottom left">Sotto / sinistra</option>
                    <option value="bottom right">Sotto / destra</option>
                </select>
            </div>

            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Adattamento</label>
                <select class="form-select form-select-sm pb-spacer-img-size">
                    <option value="cover">Riempi (cover)</option>
                    <option value="contain">Contieni (contain)</option>
                    <option value="auto">Auto</option>
                </select>
            </div>

            <div class="col-12 col-md-4">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input pb-spacer-img-parallax" type="checkbox"
                           id="spacerImgParallax_${block.id}">
                    <label class="form-check-label small" for="spacerImgParallax_${block.id}">
                        Effetto parallax (immagine fissa)
                    </label>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-2 pb-spacer-radius-wrap">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Raggio bordi (px)</label>
                <input type="number" min="0" max="200"
                       class="form-control form-control-sm pb-spacer-radius">
            </div>
        </div>

        <div class="row g-2 mb-2 align-items-center pb-spacer-overlay-wrap">
            <div class="col-12 col-md-3">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input pb-spacer-ov-enabled" type="checkbox"
                           id="spacerOvSwitch_${block.id}">
                    <label class="form-check-label small" for="spacerOvSwitch_${block.id}">
                        Overlay sopra lo sfondo
                    </label>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Colore overlay</label>
                <input type="color"
                       class="form-control form-control-color form-control-sm pb-spacer-ov-color">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Opacità overlay</label>
                <input type="range" min="0" max="100"
                       class="form-range pb-spacer-ov-opacity">
                <div class="small text-muted text-end pb-spacer-ov-opacity-label"></div>
            </div>
        </div>
    `;

    const hInp           = settings.querySelector('.pb-spacer-h');
    const bgTypeSel      = settings.querySelector('.pb-spacer-bgtype');
    const bgColorWrap    = settings.querySelector('.pb-spacer-bgcolor-wrap');
    const bgColorInp     = settings.querySelector('.pb-spacer-bgcolor');
    const gradWrap       = settings.querySelector('.pb-spacer-gradient-wrap');
    const gradFromInp    = settings.querySelector('.pb-spacer-grad-from');
    const gradToInp      = settings.querySelector('.pb-spacer-grad-to');
    const gradAngleInp   = settings.querySelector('.pb-spacer-grad-angle');
    const imgWrap        = settings.querySelector('.pb-spacer-image-wrap');
    const imgPickBtn     = settings.querySelector('.pb-spacer-img-pick');
    const imgInfo        = settings.querySelector('.pb-spacer-img-info');
    const imgQualitySel  = settings.querySelector('.pb-spacer-img-quality');
    const imgPosSel      = settings.querySelector('.pb-spacer-img-pos');
    const imgSizeSel     = settings.querySelector('.pb-spacer-img-size');
    const imgParallaxInp = settings.querySelector('.pb-spacer-img-parallax');
    const radiusWrap     = settings.querySelector('.pb-spacer-radius-wrap');
    const radiusInp      = settings.querySelector('.pb-spacer-radius');
    const ovEnabledInp   = settings.querySelector('.pb-spacer-ov-enabled');
    const ovColorInp     = settings.querySelector('.pb-spacer-ov-color');
    const ovOpacityInp   = settings.querySelector('.pb-spacer-ov-opacity');
    const ovOpacityLbl   = settings.querySelector('.pb-spacer-ov-opacity-label');

    const refreshSettingsUI = () => {
        if (hInp) hInp.value = spacer.height ?? 80;
        if (bgTypeSel) bgTypeSel.value = spacer.backgroundType || 'none';

        const bgType = spacer.backgroundType || 'none';

        if (bgColorWrap) bgColorWrap.classList.toggle('d-none', !(bgType === 'color' || bgType === 'image'));
        if (gradWrap)    gradWrap.classList.toggle('d-none', bgType !== 'gradient');
        if (imgWrap)     imgWrap.classList.toggle('d-none', bgType !== 'image');
        if (radiusWrap)  radiusWrap.classList.remove('d-none');

        if (bgColorInp) {
            bgColorInp.value = spacer.bgColor || '#f8f9fa';
        }

        if (gradFromInp) gradFromInp.value = spacer.gradient.from || '#0d6efd';
        if (gradToInp)   gradToInp.value   = spacer.gradient.to   || '#6610f2';
        if (gradAngleInp) {
            gradAngleInp.value = spacer.gradient.angle ?? 135;
        }

        if (imgInfo) {
            const q = spacer.bgImage.quality || 'thumb';
            imgInfo.textContent = spacer.bgImage.src
                ? `${spacer.bgImage.src} (qualità: ${q})`
                : 'Nessuna immagine selezionata';
        }

        if (imgQualitySel) imgQualitySel.value = spacer.bgImage.quality || 'thumb';
        if (imgPosSel)     imgPosSel.value     = spacer.bgImage.position || 'center center';
        if (imgSizeSel)    imgSizeSel.value    = spacer.bgImage.size || 'cover';
        if (imgParallaxInp) imgParallaxInp.checked =
            (spacer.bgAttachment === 'fixed' || spacer.parallax);

        // Radius: leggi da spacer.borderRadius oppure style.borderRadius
        const rRaw = (typeof spacer.borderRadius !== 'undefined' && spacer.borderRadius !== null && spacer.borderRadius !== '')
            ? spacer.borderRadius
            : (style.borderRadius ?? 0);

        let rNum = 0;
        if (typeof rRaw === 'number') {
            rNum = rRaw;
        } else if (typeof rRaw === 'string' && rRaw.trim() !== '') {
            const m = rRaw.match(/^(\d+)/);
            if (m) rNum = parseInt(m[1], 10);
        }
        if (radiusInp) radiusInp.value = rNum || 0;

        if (ovEnabledInp) ovEnabledInp.checked = !!(spacer.overlay && spacer.overlay.enabled);
        if (ovColorInp)   ovColorInp.value     = spacer.overlay.color || '#000000';
        if (ovOpacityInp) {
            const pct = Math.round(
                Math.max(0, Math.min(
                    typeof spacer.overlay.opacity === 'number' ? spacer.overlay.opacity : 0.35,
                    1
                )) * 100
            );
            ovOpacityInp.value = pct;
            if (ovOpacityLbl) ovOpacityLbl.textContent = pct + '%';
        }
    };

    const syncSpacerFromInputs = () => {
        if (hInp) {
            const v = parseInt(hInp.value || String(spacer.height || 80), 10);
            spacer.height = Number.isNaN(v) ? 80 : Math.max(0, Math.min(v, 2000));
            hInp.value = spacer.height;
        }

        if (bgTypeSel) {
            spacer.backgroundType = bgTypeSel.value || 'none';
        }

        if (bgColorInp) {
            spacer.bgColor = bgColorInp.value || '#f8f9fa';
        }

        if (gradFromInp) {
            spacer.gradient.from = gradFromInp.value || '#0d6efd';
        }
        if (gradToInp) {
            spacer.gradient.to   = gradToInp.value || '#6610f2';
        }
        if (gradAngleInp) {
            const v = parseInt(gradAngleInp.value || String(spacer.gradient.angle || 135), 10);
            spacer.gradient.angle = Number.isNaN(v) ? 135 : Math.max(0, Math.min(v, 360));
            gradAngleInp.value = spacer.gradient.angle;
        }

        if (imgQualitySel) {
            spacer.bgImage.quality = imgQualitySel.value || 'thumb';
        }
        if (imgPosSel) {
            spacer.bgImage.position = imgPosSel.value || 'center center';
        }
        if (imgSizeSel) {
            spacer.bgImage.size = imgSizeSel.value || 'cover';
        }
        if (imgParallaxInp) {
            const enabled = !!imgParallaxInp.checked;
            spacer.bgAttachment = enabled ? 'fixed' : 'scroll';
            spacer.parallax     = enabled;
        }

        // Radius: salviamo sia in spacer.borderRadius sia in style.borderRadius
        if (radiusInp) {
            const v = parseInt(radiusInp.value || '0', 10);
            const safe = Number.isNaN(v) ? 0 : Math.max(0, Math.min(v, 200));
            spacer.borderRadius = safe;
            radiusInp.value = safe;
            style.borderRadius = safe; // così il renderer usa lo stesso valore come gli altri blocchi
        }

        if (ovEnabledInp) {
            spacer.overlay.enabled = !!ovEnabledInp.checked;
        }
        if (ovColorInp) {
            spacer.overlay.color = ovColorInp.value || '#000000';
        }
        if (ovOpacityInp) {
            const v = parseInt(ovOpacityInp.value || '35', 10);
            const pct = Number.isNaN(v) ? 35 : Math.max(0, Math.min(v, 100));
            spacer.overlay.opacity = pct / 100;
            ovOpacityInp.value = pct;
            if (ovOpacityLbl) ovOpacityLbl.textContent = pct + '%';
        }

        refreshSettingsUI();
        applySpacerStyle();
        saveBlock();
    };

    if (!previewMode) {
        // Eventi
        hInp           && hInp.addEventListener('input',  syncSpacerFromInputs);
        bgTypeSel      && bgTypeSel.addEventListener('change', syncSpacerFromInputs);
        bgColorInp     && bgColorInp.addEventListener('input', syncSpacerFromInputs);
        gradFromInp    && gradFromInp.addEventListener('input', syncSpacerFromInputs);
        gradToInp      && gradToInp.addEventListener('input', syncSpacerFromInputs);
        gradAngleInp   && gradAngleInp.addEventListener('input', syncSpacerFromInputs);
        imgQualitySel  && imgQualitySel.addEventListener('change', syncSpacerFromInputs);
        imgPosSel      && imgPosSel.addEventListener('change', syncSpacerFromInputs);
        imgSizeSel     && imgSizeSel.addEventListener('change', syncSpacerFromInputs);
        imgParallaxInp && imgParallaxInp.addEventListener('change', syncSpacerFromInputs);
        radiusInp      && radiusInp.addEventListener('input', syncSpacerFromInputs);
        ovEnabledInp   && ovEnabledInp.addEventListener('change', syncSpacerFromInputs);
        ovColorInp     && ovColorInp.addEventListener('input',  syncSpacerFromInputs);
        ovOpacityInp   && ovOpacityInp.addEventListener('input', syncSpacerFromInputs);

        imgPickBtn && imgPickBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openImagePicker((url) => {
                if (!url) return;
                spacer.bgImage.src  = url;
                spacer.bgImage.full = url;

                // se non impostata, resta thumb
                spacer.bgImage.quality = spacer.bgImage.quality || 'thumb';

                refreshSettingsUI();
                applySpacerStyle();
                saveBlock();
            }, { mode: 'image' });
        });
    }

    // === APPLICA STILI INIZIALI =============================================
    refreshSettingsUI();
    applySpacerStyle();

    // === ASSEMBLA CARD ======================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    if (!previewMode) {
        body.appendChild(settings);
    }

    container.appendChild(body);
}
