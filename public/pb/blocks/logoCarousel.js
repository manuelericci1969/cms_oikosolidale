// public/pb/blocks/logoCarousel.js

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
 * Blocco "Carosello loghi"
 *
 * block.logoCarousel = {
 *   items: [{ id, src, full, alt, link, target, message }],
 *   options: {
 *     visible, logoWidth, logoHeight, gap, speed, pauseOnHover
 *   }
 * }
 */
export function renderLogoCarouselBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = typeof ctx.rerender === 'function' ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';

    // === DATI ===============================================================
    const data    = (block.logoCarousel && typeof block.logoCarousel === 'object')
        ? block.logoCarousel
        : {};
    const items   = Array.isArray(data.items) ? data.items.slice() : [];
    const options = (data.options && typeof data.options === 'object') ? data.options : {};

    if (typeof options.visible !== 'number') options.visible = 5;
    if (typeof options.logoWidth !== 'number') options.logoWidth = 140;
    if (typeof options.logoHeight !== 'number') options.logoHeight = 80;
    if (typeof options.gap !== 'number') options.gap = 32;
    if (typeof options.speed !== 'number') options.speed = 40;           // px/sec
    if (typeof options.pauseOnHover !== 'boolean') options.pauseOnHover = true;

    function saveBlock(extra) {
        state.updateBlock(section.id, block.id, Object.assign({
            type: 'logo_carousel',
            columns: block.columns || 12,
            logoCarousel: {
                items: items.map(function (it) {
                    return Object.assign({}, it);
                }),
                options: Object.assign({}, options),
            },
        }, extra || {}));
    }

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Carosello loghi';
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
            if (confirm('Eliminare questo blocco loghi?')) {
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

        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small py-3 text-center';
            empty.innerHTML = `
                <i class="bi bi-card-image me-1"></i>
                Nessun logo aggiunto.
            `;
            preview.appendChild(empty);
            return;
        }

        const row = document.createElement('div');
        row.className = 'd-flex flex-wrap align-items-center gap-2';

        const w = options.logoWidth || 140;
        const h = options.logoHeight || 80;

        for (let i = 0; i < items.length; i++) {
            const it = items[i];
            const box = document.createElement('div');
            box.className = 'border rounded bg-light d-flex align-items-center justify-content-center';
            box.style.width = w + 'px';
            box.style.height = h + 'px';

            if (it && it.src) {
                const img = document.createElement('img');
                img.src = it.src;
                img.alt = it.alt || '';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                img.style.objectFit = 'contain';
                img.style.filter = 'grayscale(1)';
                img.style.opacity = '0.6';
                box.appendChild(img);
            } else {
                const span = document.createElement('span');
                span.className = 'small text-muted';
                span.textContent = 'Logo';
                box.appendChild(span);
            }

            row.appendChild(box);
        }

        preview.appendChild(row);
    }

    renderPreview();

    // === CONFIGURAZIONE (solo in modalità edit) =============================
    const config = document.createElement('div');
    config.className = 'mt-3';

    if (!previewMode) {
        // Header + pulsante "Aggiungi logo"
        const topRow = document.createElement('div');
        topRow.className = 'd-flex align-items-center justify-content-between mb-2';

        const title = document.createElement('span');
        title.className = 'small text-muted';
        title.textContent = 'Loghi nel carosello';
        topRow.appendChild(title);

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-sm btn-outline-primary';
        addBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Aggiungi logo';
        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            items.push({
                id: 'logo_' + Date.now() + '_' + Math.random().toString(36).slice(2),
                src: '',
                full: '',
                alt: '',
                link: '',
                target: '_self',
                message: '',   // 🔹 testo alert se non c’è link
            });
            saveBlock();
            renderItemsList();
            renderPreview();
        });
        topRow.appendChild(addBtn);

        config.appendChild(topRow);

        const listEl = document.createElement('div');
        config.appendChild(listEl);

        function renderItemsList() {
            listEl.innerHTML = '';

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'text-muted small';
                empty.textContent = 'Nessun logo. Usa "Aggiungi logo".';
                listEl.appendChild(empty);
                return;
            }

            items.forEach((it, index) => {
                const row = document.createElement('div');
                row.className = 'border rounded p-2 mb-2 d-flex align-items-center gap-2 flex-wrap';

                // Thumb / scelta immagine
                const thumbBtn = document.createElement('button');
                thumbBtn.type = 'button';
                thumbBtn.className = 'btn btn-sm btn-outline-secondary';
                if (it.src) {
                    thumbBtn.innerHTML =
                        '<img src="' + it.src + '" alt="' + (it.alt || '') + '" style="width:48px;height:48px;object-fit:contain;">';
                } else {
                    thumbBtn.innerHTML = '<i class="bi bi-image"></i>';
                }
                thumbBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    openImagePicker((url, item) => {
                        if (!url) return;
                        it.src  = (item && (item.thumb || item.url)) || url;
                        it.full = (item && item.variants && (item.variants.full || item.variants['75'] || item.variants['59'] || item.variants['25'])) || url;
                        if (!it.alt && item) {
                            it.alt = item.alt || item.title || item.original_name || '';
                        }
                        saveBlock();
                        renderItemsList();
                        renderPreview();
                    });
                });
                row.appendChild(thumbBtn);

                // Dati testo + link
                const textCol = document.createElement('div');
                textCol.className = 'flex-grow-1';

                const altInput = document.createElement('input');
                altInput.type = 'text';
                altInput.className = 'form-control form-control-sm mb-1';
                altInput.placeholder = 'Titolo / alt';
                altInput.value = it.alt || '';
                altInput.addEventListener('input', () => {
                    it.alt = altInput.value;
                    saveBlock();
                    renderPreview();
                });
                textCol.appendChild(altInput);

                // 🔹 Testo alert (se il link è vuoto)
                const msgInput = document.createElement('input');
                msgInput.type = 'text';
                msgInput.className = 'form-control form-control-sm mb-1';
                msgInput.placeholder = 'Testo alert (se il link è vuoto)';
                msgInput.value = it.message || '';
                msgInput.addEventListener('input', () => {
                    it.message = msgInput.value;
                    saveBlock();
                });
                textCol.appendChild(msgInput);

                const linkGroup = document.createElement('div');
                linkGroup.className = 'input-group input-group-sm';

                const span = document.createElement('span');
                span.className = 'input-group-text';
                span.textContent = 'Link';
                linkGroup.appendChild(span);

                const linkInput = document.createElement('input');
                linkInput.type = 'text';
                linkInput.className = 'form-control';
                linkInput.placeholder = 'https://...';
                linkInput.value = it.link || '';
                linkInput.addEventListener('input', () => {
                    it.link = linkInput.value;
                    saveBlock();
                });
                linkGroup.appendChild(linkInput);

                const targetSel = document.createElement('select');
                targetSel.className = 'form-select';
                targetSel.style.maxWidth = '130px';
                targetSel.innerHTML = `
                    <option value="_self">Stessa scheda</option>
                    <option value="_blank">Nuova scheda</option>
                `;
                targetSel.value = it.target === '_blank' ? '_blank' : '_self';
                targetSel.addEventListener('change', () => {
                    it.target = targetSel.value || '_self';
                    saveBlock();
                });
                linkGroup.appendChild(targetSel);

                textCol.appendChild(linkGroup);
                row.appendChild(textCol);

                // Pulsanti su/giù/elimina
                const btnCol = document.createElement('div');
                btnCol.className = 'd-flex flex-column gap-1';

                const upBtn = createIconBtn('bi bi-arrow-up', 'Su', () => {
                    if (index === 0) return;
                    const tmp = items[index - 1];
                    items[index - 1] = items[index];
                    items[index] = tmp;
                    saveBlock();
                    renderItemsList();
                    renderPreview();
                });
                const downBtn = createIconBtn('bi bi-arrow-down', 'Giù', () => {
                    if (index === items.length - 1) return;
                    const tmp = items[index + 1];
                    items[index + 1] = items[index];
                    items[index] = tmp;
                    saveBlock();
                    renderItemsList();
                    renderPreview();
                });
                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'btn btn-sm btn-outline-danger';
                delBtn.title = 'Elimina logo';
                delBtn.innerHTML = '<i class="bi bi-trash"></i>';
                delBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!confirm('Eliminare questo logo?')) return;
                    items.splice(index, 1);
                    saveBlock();
                    renderItemsList();
                    renderPreview();
                });

                btnCol.appendChild(upBtn);
                btnCol.appendChild(downBtn);
                btnCol.appendChild(delBtn);

                row.appendChild(btnCol);

                listEl.appendChild(row);
            });
        }

        renderItemsList();

        // Opzioni generali carosello
        const opts = document.createElement('div');
        opts.className = 'mt-3';

        opts.innerHTML = `
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0">Loghi visibili</label>
                    <input type="number" min="1" max="10" class="form-control form-control-sm pb-lc-visible">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0">Larghezza logo (px)</label>
                    <input type="number" min="20" max="600" class="form-control form-control-sm pb-lc-w">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0">Altezza logo (px)</label>
                    <input type="number" min="20" max="400" class="form-control form-control-sm pb-lc-h">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0">Gap orizzontale (px)</label>
                    <input type="number" min="0" max="200" class="form-control form-control-sm pb-lc-gap">
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0">Velocità (px/sec)</label>
                    <input type="number" min="5" max="300" class="form-control form-control-sm pb-lc-speed">
                </div>
                <div class="col-6 col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input pb-lc-pause" type="checkbox" id="pbLcPause">
                        <label class="form-check-label small" for="pbLcPause">Pausa al passaggio</label>
                    </div>
                </div>
            </div>
        `;

        config.appendChild(opts);

        const visibleInp = opts.querySelector('.pb-lc-visible');
        const wInp       = opts.querySelector('.pb-lc-w');
        const hInp       = opts.querySelector('.pb-lc-h');
        const gapInp     = opts.querySelector('.pb-lc-gap');
        const speedInp   = opts.querySelector('.pb-lc-speed');
        const pauseChk   = opts.querySelector('.pb-lc-pause');

        visibleInp.value = options.visible;
        wInp.value       = options.logoWidth;
        hInp.value       = options.logoHeight;
        gapInp.value     = options.gap;
        speedInp.value   = options.speed;
        pauseChk.checked = !!options.pauseOnHover;

        visibleInp.addEventListener('input', () => {
            let v = parseInt(visibleInp.value || '0', 10);
            if (isNaN(v) || v < 1) v = 1;
            if (v > 10) v = 10;
            options.visible = v;
            visibleInp.value = String(v);
            saveBlock();
        });

        wInp.addEventListener('input', () => {
            let v = parseInt(wInp.value || '0', 10);
            if (isNaN(v) || v < 20) v = 20;
            if (v > 600) v = 600;
            options.logoWidth = v;
            wInp.value = String(v);
            saveBlock();
            renderPreview();
        });

        hInp.addEventListener('input', () => {
            let v = parseInt(hInp.value || '0', 10);
            if (isNaN(v) || v < 20) v = 20;
            if (v > 400) v = 400;
            options.logoHeight = v;
            hInp.value = String(v);
            saveBlock();
            renderPreview();
        });

        gapInp.addEventListener('input', () => {
            let v = parseInt(gapInp.value || '0', 10);
            if (isNaN(v) || v < 0) v = 0;
            if (v > 200) v = 200;
            options.gap = v;
            gapInp.value = String(v);
            saveBlock();
        });

        speedInp.addEventListener('input', () => {
            let v = parseInt(speedInp.value || '0', 10);
            if (isNaN(v) || v < 5) v = 5;
            if (v > 300) v = 300;
            options.speed = v;
            speedInp.value = String(v);
            saveBlock();
        });

        pauseChk.addEventListener('change', () => {
            options.pauseOnHover = !!pauseChk.checked;
            saveBlock();
        });
    }

    // === ASSEMBLA CARD ======================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    if (!previewMode) {
        body.appendChild(config);
    }

    container.appendChild(body);
}
