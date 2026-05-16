// public/pb/blocks/video.js

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

function extractYouTubeId(value) {
    const v = (value || '').trim();
    if (!v) return '';
    // ID "nudo"
    if (/^[a-zA-Z0-9_-]{11}$/.test(v)) return v;

    let m = v.match(/[?&]v=([^&#]+)/);
    if (m && m[1]) return m[1];

    m = v.match(/youtu\.be\/([^?&#]+)/);
    if (m && m[1]) return m[1];

    m = v.match(/\/embed\/([^?&#]+)/);
    if (m && m[1]) return m[1];

    // fallback: magari è già l'ID
    return v;
}

function extractVimeoId(value) {
    const v = (value || '').trim();
    if (!v) return '';
    if (/^\d+$/.test(v)) return v;

    const m = v.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (m && m[1]) return m[1];

    return v;
}

function ratioClassFromAspect(aspect) {
    switch (aspect) {
        case '21 / 9': return 'ratio-21x9';
        case '4 / 3':  return 'ratio-4x3';
        case '1 / 1':  return 'ratio-1x1';
        default:       return 'ratio-16x9';
    }
}

/**
 * Blocco Video
 * Usa block.video = { provider, id, url, options:{aspect,poster,objectFit,autoplay,controls,loop,muted,playsinline} }
 * + block.style + block.animation (come gli altri).
 */
export function renderVideoBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = (typeof ctx.rerender === 'function') ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';

    // === STYLE DATA =========================================================
    const style = {
        ...(block.style && typeof block.style === 'object' ? block.style : {}),
        ...(block.data && block.data.style && typeof block.data.style === 'object' ? block.data.style : {}),
    };

    // === ANIMATION DATA =====================================================
    const animation = (block.animation && typeof block.animation === 'object')
        ? {
            name: block.animation.name || 'none',
            duration: typeof block.animation.duration === 'number' ? block.animation.duration : 600,
            delay: typeof block.animation.delay === 'number' ? block.animation.delay : 0,
        }
        : { name: 'none', duration: 600, delay: 0 };

    // === VIDEO DATA =========================================================
    const video = (block.video && typeof block.video === 'object')
        ? { ...block.video }
        : {
            provider: 'youtube', // youtube | vimeo | file
            id: '',
            url: '',
            options: {},
        };

    video.provider = video.provider || 'youtube';
    video.id  = video.id  || '';
    video.url = video.url || '';

    const options = (video.options && typeof video.options === 'object') ? video.options : {};
    video.options = options;

    options.aspect      = options.aspect      || '16 / 9';
    options.poster      = options.poster      || '';
    options.autoplay    = !!options.autoplay;
    options.controls    = options.controls !== undefined ? !!options.controls : true;
    options.loop        = !!options.loop;
    options.muted       = !!options.muted;
    options.playsinline = options.playsinline !== undefined ? !!options.playsinline : true;
    // 🔹 NUOVO: adattamento video (object-fit)
    options.objectFit   = options.objectFit || 'contain'; // contain | cover | fill

    const saveBlock = (extra = {}) => {
        const animData = animation.name && animation.name !== 'none'
            ? {
                name: animation.name,
                duration: animation.duration,
                delay: animation.delay,
            }
            : null;

        state.updateBlock(section.id, block.id, {
            video: {
                provider: video.provider,
                id: video.id,
                url: video.url,
                options: { ...options },
            },
            style: { ...style },
            animation: animData,
            ...extra,
        });
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar pb-video-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Video';
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
            if (confirm('Eliminare questo blocco video?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        }));
    }

    // === PREVIEW ============================================================
    const preview = document.createElement('div');
    preview.className = 'pb-preview mt-2';

    function renderPreview() {
        preview.innerHTML = '';

        const hasYouTube = video.provider === 'youtube' && !!video.id;
        const hasVimeo   = video.provider === 'vimeo'   && !!video.id;
        const hasFile    = video.provider === 'file'    && !!video.url;

        if (!hasYouTube && !hasVimeo && !hasFile) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small py-3 text-center';
            empty.innerHTML = `
                <i class="bi bi-play-btn me-1"></i>
                Nessun video configurato.
            `;
            preview.appendChild(empty);
            return;
        }

        const ratioClass = ratioClassFromAspect(options.aspect || '16 / 9');
        const wrap = document.createElement('div');
        wrap.className = 'ratio ' + ratioClass;

        let inner = null;

        if (hasYouTube) {
            inner = document.createElement('iframe');
            inner.src = 'https://www.youtube.com/embed/' + encodeURIComponent(video.id);
            inner.title = 'Video YouTube';
            inner.loading = 'lazy';
            inner.allow =
                'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            inner.allowFullscreen = true;
        } else if (hasVimeo) {
            inner = document.createElement('iframe');
            inner.src = 'https://player.vimeo.com/video/' + encodeURIComponent(video.id);
            inner.title = 'Video Vimeo';
            inner.loading = 'lazy';
            inner.allow = 'autoplay; fullscreen; picture-in-picture';
            inner.allowFullscreen = true;
        } else if (hasFile) {
            inner = document.createElement('video');
            inner.src = video.url;
            inner.controls = !!options.controls;
            inner.loop = !!options.loop;
            inner.muted = !!options.muted || !!options.autoplay;
            inner.autoplay = !!options.autoplay;
            inner.playsInline = !!options.playsinline;
            if (options.poster) {
                inner.poster = options.poster;
            }
            // 🔹 Applica object-fit anche in anteprima
            inner.style.objectFit = options.objectFit || 'contain';
        }

        if (inner) {
            inner.style.width = '100%';
            inner.style.height = '100%';
            wrap.appendChild(inner);
        }

        preview.appendChild(wrap);
    }

    renderPreview();

    // === CONFIGURAZIONE VIDEO ==============================================
    const config = document.createElement('div');
    config.className = 'mt-3';

    const uid = block.id || Math.random().toString(36).slice(2);

    config.innerHTML = `
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-0">Tipo sorgente</label>
                <select class="form-select form-select-sm pb-video-provider">
                    <option value="youtube">YouTube</option>
                    <option value="vimeo">Vimeo</option>
                    <option value="file">File / URL diretto</option>
                </select>
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label small mb-0">URL o ID video</label>
                <div class="input-group input-group-sm">
                    <input type="text"
                           class="form-control pb-video-url"
                           placeholder="https://... oppure ID video">
                    <button class="btn btn-outline-secondary pb-video-url-btn" type="button">
                        <i class="bi bi-collection-play"></i>
                    </button>
                </div>
                <div class="form-text small text-muted">
                    Per file interni puoi usare il pulsante a destra per scegliere dai media.
                </div>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Aspect ratio</label>
                <select class="form-select form-select-sm pb-video-aspect">
                    <option value="16 / 9">16:9</option>
                    <option value="21 / 9">21:9</option>
                    <option value="4 / 3">4:3</option>
                    <option value="1 / 1">1:1</option>
                </select>
            </div>
            <div class="col-6 col-md-9">
                <label class="form-label small mb-0">Poster (immagine di copertina)</label>
                <div class="input-group input-group-sm">
                    <input type="text"
                           class="form-control pb-video-poster"
                           placeholder="URL immagine poster">
                    <button class="btn btn-outline-secondary pb-video-poster-btn" type="button">
                        <i class="bi bi-image"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 🔹 Nuovo: adattamento video (object-fit) -->
        <div class="row g-2 mt-2">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-0">Adattamento video</label>
                <select class="form-select form-select-sm pb-video-fit">
                    <option value="contain">Contieni (tutto visibile)</option>
                    <option value="cover">Riempi (può tagliare)</option>
                    <option value="fill">Fill (può deformare)</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-6 col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input pb-video-autoplay" type="checkbox" id="pbVideoAutoplay_${uid}">
                    <label class="form-check-label small" for="pbVideoAutoplay_${uid}">
                        Autoplay (muto)
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input pb-video-controls" type="checkbox" id="pbVideoControls_${uid}">
                    <label class="form-check-label small" for="pbVideoControls_${uid}">
                        Mostra controlli
                    </label>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input pb-video-loop" type="checkbox" id="pbVideoLoop_${uid}">
                    <label class="form-check-label small" for="pbVideoLoop_${uid}">
                        Loop
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input pb-video-muted" type="checkbox" id="pbVideoMuted_${uid}">
                    <label class="form-check-label small" for="pbVideoMuted_${uid}">
                        Forza muto
                    </label>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input pb-video-inline" type="checkbox" id="pbVideoInline_${uid}">
                    <label class="form-check-label small" for="pbVideoInline_${uid}">
                        Riproduzione inline
                    </label>
                </div>
            </div>
        </div>
    `;

    const providerSel  = config.querySelector('.pb-video-provider');
    const urlInput     = config.querySelector('.pb-video-url');
    const urlBtn       = config.querySelector('.pb-video-url-btn');
    const aspectSel    = config.querySelector('.pb-video-aspect');
    const posterInput  = config.querySelector('.pb-video-poster');
    const posterBtn    = config.querySelector('.pb-video-poster-btn');
    const fitSel       = config.querySelector('.pb-video-fit');
    const autoplayChk  = config.querySelector('.pb-video-autoplay');
    const controlsChk  = config.querySelector('.pb-video-controls');
    const loopChk      = config.querySelector('.pb-video-loop');
    const mutedChk     = config.querySelector('.pb-video-muted');
    const inlineChk    = config.querySelector('.pb-video-inline');

    if (providerSel) providerSel.value = video.provider || 'youtube';

    if (urlInput) {
        if (video.provider === 'file') {
            urlInput.value = video.url || '';
        } else {
            // per youtube/vimeo mostro l'URL originale se c'è, altrimenti l'ID
            urlInput.value = video.url || video.id || '';
        }
    }

    if (aspectSel) aspectSel.value = options.aspect || '16 / 9';
    if (posterInput) posterInput.value = options.poster || '';
    if (fitSel) fitSel.value = options.objectFit || 'contain';
    if (autoplayChk) autoplayChk.checked = !!options.autoplay;
    if (controlsChk) controlsChk.checked = !!options.controls;
    if (loopChk)     loopChk.checked     = !!options.loop;
    if (mutedChk)    mutedChk.checked    = !!options.muted;
    if (inlineChk)   inlineChk.checked   = !!options.playsinline;

    function applyUrlFromInput() {
        if (!urlInput) return;
        const val = (urlInput.value || '').trim();

        if (!val) {
            video.id = '';
            video.url = '';
        } else if (video.provider === 'youtube') {
            const id = extractYouTubeId(val);
            video.id = id;
            video.url = val;
        } else if (video.provider === 'vimeo') {
            const id = extractVimeoId(val);
            video.id = id;
            video.url = val;
        } else {
            // file / URL diretto
            video.id = '';
            video.url = val;
        }

        saveBlock();
        renderPreview();
    }

    if (!previewMode) {
        providerSel && providerSel.addEventListener('change', () => {
            video.provider = providerSel.value || 'youtube';
            applyUrlFromInput();
        });

        urlInput && urlInput.addEventListener('change', applyUrlFromInput);
        urlInput && urlInput.addEventListener('blur', applyUrlFromInput);

        // Bottone "scegli dai media" per URL video (file interni)
        urlBtn && urlBtn.addEventListener('click', (e) => {
            e.preventDefault();

            // se non è già file, lo forzo
            if (providerSel && providerSel.value !== 'file') {
                providerSel.value = 'file';
                video.provider = 'file';
            }

            openImagePicker((url, item) => {
                if (!url) return;
                // se hai aggiornato mediaPicker per restituire anche "mime",
                // qui puoi filtrare ulteriormente se serve
                if (urlInput) {
                    urlInput.value = url;
                }
                applyUrlFromInput();
            });
        });

        aspectSel && aspectSel.addEventListener('change', () => {
            options.aspect = aspectSel.value || '16 / 9';
            saveBlock();
            renderPreview();
        });

        // 🔹 cambio adattamento (object-fit)
        fitSel && fitSel.addEventListener('change', () => {
            options.objectFit = fitSel.value || 'contain';
            saveBlock();
            renderPreview();
        });

        posterBtn && posterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openImagePicker((url) => {
                if (!url) return;
                options.poster = url;
                if (posterInput) posterInput.value = url;
                saveBlock();
                renderPreview();
            });
        });

        posterInput && posterInput.addEventListener('change', () => {
            options.poster = posterInput.value || '';
            saveBlock();
            renderPreview();
        });

        autoplayChk && autoplayChk.addEventListener('change', () => {
            options.autoplay = !!autoplayChk.checked;
            saveBlock();
            renderPreview();
        });
        controlsChk && controlsChk.addEventListener('change', () => {
            options.controls = !!controlsChk.checked;
            saveBlock();
            renderPreview();
        });
        loopChk && loopChk.addEventListener('change', () => {
            options.loop = !!loopChk.checked;
            saveBlock();
            renderPreview();
        });
        mutedChk && mutedChk.addEventListener('change', () => {
            options.muted = !!mutedChk.checked;
            saveBlock();
            renderPreview();
        });
        inlineChk && inlineChk.addEventListener('change', () => {
            options.playsinline = !!inlineChk.checked;
            saveBlock();
            renderPreview();
        });
    }

    // === PANNELLO STILI BLOCCO + ANIMAZIONE =================================
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
        animDurInp  && animDurInp.addEventListener('input', syncAnimation);
        animDelInp  && animDelInp.addEventListener('input', syncAnimation);
    }

    applyStyleToDom();

    // === ASSEMBLA CARD ======================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    body.appendChild(config);
    body.appendChild(stylePanel);

    container.appendChild(body);
}
