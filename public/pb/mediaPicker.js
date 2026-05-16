// public/pb/mediaPicker.js

let imagePickerInstance = null;

/**
 * URL principale dell'item.
 */
function getItemUrl(it) {
    return (it?.url || it?.full || it?.src || it?.thumb || '') || '';
}

/**
 * Estensione da URL.
 */
function getExtFromUrl(url) {
    if (!url) return '';
    const clean = String(url).split('?')[0];
    const m = clean.match(/\.([a-z0-9]+)$/i);
    return m ? m[1].toLowerCase() : '';
}

/**
 * Mime/type generico.
 */
function getItemMime(it) {
    return String(it?.mime || it?.mime_type || it?.type || '').toLowerCase();
}

function isVideoItem(it) {
    const mime = getItemMime(it);
    if (mime.startsWith('video/')) return true;

    const ext = getExtFromUrl(getItemUrl(it));
    return ['mp4', 'webm', 'ogv', 'ogg', 'm4v', 'mov', 'avi', 'mkv'].includes(ext);
}

function isImageItem(it) {
    const mime = getItemMime(it);
    if (mime.startsWith('image/')) return true;

    const ext = getExtFromUrl(getItemUrl(it));
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'].includes(ext);
}

/**
 * Compatibile con V2/V3.
 * Ritorna l'URL in base alla qualità richiesta.
 */
export function getMediaUrlByQuality(item, quality = 'full', fallback = '') {
    if (!item || typeof item !== 'object') return fallback || '';

    const q = String(quality || 'full').toLowerCase();

    if (q === 'thumb') {
        return item.thumb || item.thumbnail || item.url || item.full || item.src || fallback || '';
    }

    if (q === '25') {
        return item.q25 || item['25'] || item.url_25 || item.url || item.full || item.src || fallback || '';
    }

    if (q === '59') {
        return item.q59 || item['59'] || item.url_59 || item.url || item.full || item.src || fallback || '';
    }

    if (q === '75') {
        return item.q75 || item['75'] || item.url_75 || item.url || item.full || item.src || fallback || '';
    }

    return item.full || item.url || item.src || item.thumb || fallback || '';
}

/**
 * API retrocompatibile:
 *
 * VECCHIO STILE V2:
 * openImagePicker((url, item) => { ... }, { mode: 'image' })
 *
 * NUOVO STILE:
 * const picked = await openImagePicker({ mode: 'image', quality: 'full' })
 */
export function openImagePicker(arg1, options = {}) {
    const isLegacyCallback = typeof arg1 === 'function';
    const config = isLegacyCallback ? (options || {}) : (arg1 || {});

    const endpoint =
        config.pickerUrl ||
        window.PB_MEDIA_PICKER_URL ||
        (window.R4ADMIN && window.R4ADMIN.mediaPickerUrl) ||
        (window.R4ADMIN && window.R4ADMIN.mediaBrowseUrl) ||
        '/admin/media/browse';

    const mode = config.mode || 'image';

    // fallback manuale
    if (!endpoint || !window.bootstrap || !window.bootstrap.Modal) {
        const direct = prompt(
            mode === 'video' ? 'URL video (es. https://...)' : 'URL immagine (es. https://...)',
            'https://'
        );

        if (!direct) {
            return isLegacyCallback ? undefined : Promise.resolve(null);
        }

        const fakeItem = { url: direct, full: direct, src: direct };

        if (isLegacyCallback) {
            arg1(direct, fakeItem);
            return;
        }

        return Promise.resolve(fakeItem);
    }

    if (!imagePickerInstance) {
        imagePickerInstance = createImagePicker(endpoint);
    } else {
        imagePickerInstance.endpoint = endpoint;
    }

    imagePickerInstance.page = 1;
    imagePickerInstance.q = '';
    imagePickerInstance.mode = mode;

    if (isLegacyCallback) {
        imagePickerInstance.onPick = arg1;
        imagePickerInstance.resolve = null;
        imagePickerInstance.load();
        imagePickerInstance.modal.show();
        return;
    }

    return new Promise((resolve) => {
        imagePickerInstance.onPick = null;
        imagePickerInstance.resolve = resolve;
        imagePickerInstance.load();
        imagePickerInstance.modal.show();
    });
}

function createImagePicker(endpoint) {
    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.id = 'pbImagePicker';
    modalEl.tabIndex = -1;
    modalEl.innerHTML = `
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-images me-2"></i>
                        <span class="pbIpTitleText">Seleziona immagine</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-8">
                            <input type="search" class="form-control" id="pbIpSearch" placeholder="Cerca per nome/titolo…">
                        </div>
                        <div class="col-md-4 text-end">
                            <div id="pbIpCounter" class="text-muted small"></div>
                        </div>
                    </div>
                    <div id="pbIpGrid" class="row g-3"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" id="pbIpPrev" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-chevron-left"></i> Prec
                        </button>
                        <button type="button" id="pbIpNext" class="btn btn-outline-secondary btn-sm">
                            Succ <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modalEl);

    const modal       = new window.bootstrap.Modal(modalEl);
    const grid        = modalEl.querySelector('#pbIpGrid');
    const searchInput = modalEl.querySelector('#pbIpSearch');
    const counterEl   = modalEl.querySelector('#pbIpCounter');
    const prevBtn     = modalEl.querySelector('#pbIpPrev');
    const nextBtn     = modalEl.querySelector('#pbIpNext');
    const titleTextEl = modalEl.querySelector('.pbIpTitleText');
    const titleIconEl = modalEl.querySelector('.modal-title i');

    const picker = {
        modal,
        endpoint,
        page: 1,
        lastPage: 1,
        q: '',
        items: [],
        mode: 'image',
        onPick: null,
        resolve: null,

        async load() {
            const params = new URLSearchParams({
                page: String(this.page),
                per: '24',
            });

            if (this.q) params.set('q', this.q);
            params.set('pb_mode', this.mode);

            try {
                const res = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (!res.ok) {
                    console.error('Errore caricamento media picker', res.status);
                    this.render({ items: [], page: 1, last_page: 1 });
                    return;
                }

                const json = await res.json();
                this.render(json);
            } catch (e) {
                console.error('Errore fetch media picker', e);
                this.render({ items: [], page: 1, last_page: 1 });
            }
        },

        render(json) {
            let items = Array.isArray(json.items) ? json.items : [];
            const page = Number(json.page || 1);
            const lastPage = Number(json.last_page || 1);

            this.page = page;
            this.lastPage = lastPage;

            if (this.mode === 'video') {
                if (titleIconEl) titleIconEl.className = 'bi bi-film me-2';
                if (titleTextEl) titleTextEl.textContent = 'Seleziona video';
            } else if (this.mode === 'image') {
                if (titleIconEl) titleIconEl.className = 'bi bi-images me-2';
                if (titleTextEl) titleTextEl.textContent = 'Seleziona immagine';
            } else {
                if (titleIconEl) titleIconEl.className = 'bi bi-collection me-2';
                if (titleTextEl) titleTextEl.textContent = 'Seleziona media';
            }

            if (this.mode === 'image') {
                items = items.filter(isImageItem);
            } else if (this.mode === 'video') {
                items = items.filter(isVideoItem);
            }

            this.items = items;
            grid.innerHTML = '';

            if (!items.length) {
                grid.innerHTML = `
                    <div class="col-12 text-center text-muted py-4">
                        <i class="bi bi-inbox"></i>
                        Nessun elemento trovato
                    </div>
                `;
            } else {
                items.forEach((it, idx) => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3';

                    const url = getItemUrl(it);
                    const title = it.title || it.original_name || '';
                    const isVid = isVideoItem(it);

                    let mediaHtml = '';
                    if (!isVid) {
                        mediaHtml = `
                            <img src="${it.thumb || url}"
                                 alt="${it.alt || ''}"
                                 class="pb-image-picker-thumb mb-2"
                                 style="width:100%;height:110px;object-fit:cover;border-radius:.5rem;">
                        `;
                    } else {
                        if (it.thumb) {
                            mediaHtml = `
                                <img src="${it.thumb}"
                                     alt="${it.alt || ''}"
                                     class="pb-image-picker-thumb mb-2"
                                     style="width:100%;height:110px;object-fit:cover;border-radius:.5rem;">
                            `;
                        } else {
                            mediaHtml = `
                                <div class="d-flex align-items-center justify-content-center mb-2"
                                     style="height:110px;border:1px dashed #dee2e6;border-radius:.5rem;">
                                    <i class="bi bi-film fs-2 text-muted"></i>
                                </div>
                            `;
                        }
                    }

                    const btnLabel = this.mode === 'video' ? 'Scegli video' : 'Scegli';

                    col.innerHTML = `
                        <div class="card h-100 border-0">
                            ${mediaHtml}
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-pb-ip-index="${idx}">
                                <i class="bi bi-check2-circle me-1"></i> ${btnLabel}
                            </button>
                            <div class="small text-truncate mt-1" title="${title}">
                                ${title}
                            </div>
                        </div>
                    `;

                    grid.appendChild(col);
                });
            }

            prevBtn.disabled = (page <= 1);
            nextBtn.disabled = (page >= lastPage);

            if (items.length) {
                const from = (page - 1) * 24 + 1;
                const to = Math.min(page * 24, from - 1 + items.length);
                const label = this.mode === 'video' ? 'video' : 'elementi';
                counterEl.textContent = `Mostrati ${from}-${to} di ${items.length} ${label}`;
            } else {
                counterEl.textContent = '';
            }
        }
    };

    searchInput.addEventListener('input', () => {
        picker.q = searchInput.value.trim();
        picker.page = 1;
        picker.load();
    });

    prevBtn.addEventListener('click', () => {
        if (picker.page > 1) {
            picker.page -= 1;
            picker.load();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (picker.page < picker.lastPage) {
            picker.page += 1;
            picker.load();
        }
    });

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-pb-ip-index]');
        if (!btn) return;

        const idx = parseInt(btn.getAttribute('data-pb-ip-index'), 10);
        const item = picker.items[idx];
        if (!item) return;

        const url = getItemUrl(item);

        if (typeof picker.onPick === 'function') {
            picker.onPick(url, item);
        }

        if (typeof picker.resolve === 'function') {
            picker.resolve(item);
        }

        picker.modal.hide();
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        if (typeof picker.resolve === 'function') {
            picker.resolve(null);
            picker.resolve = null;
        }
    });

    return picker;
}
