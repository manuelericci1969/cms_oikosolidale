// public/pb/blocks/alert.js

import { renderBlockStylePanel } from '../blockStyle.js';
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
 * Blocco "Alert / Promozione / Offerta"
 *
 * block.alert:
 * {
 *   variant: 'info|success|warning|danger|primary|secondary|light|dark|custom',
 *   title: '',
 *   text: '',
 *   badge: '',
 *   icon: 'bi bi-megaphone',
 *   showIcon: true,
 *   dismissible: false,
 *   small: '',
 *   cta: { label: '', url: '', target: '_self|_blank' },
 *   bgColor: '',
 *   textColor: '',
 *   borderColor: '',
 *   image: { src:'', full:'', alt:'' },
 *   popup: {
 *     enabled: false,
 *     showEvery: 'always|once',
 *     startAt: '',   // datetime-local
 *     endAt: '',
 *     widthPx: 480,
 *     overlayOpacity: 0.55,
 *     autoCloseSeconds: 0,
 *     delaySeconds: 0,
 *     triggerOnScroll: false,
 *     triggerScrollPercent: 50,
 *     fadeEnabled: true,
 *     fadeSeconds: 2
 *   }
 * }
 */
export function renderAlertBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = typeof ctx.rerender === 'function' ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    container.innerHTML = '';

    const defaultAlert = {
        variant: 'info',
        title: '',
        text: '',
        badge: '',
        icon: 'bi bi-megaphone',
        showIcon: true,
        dismissible: false,
        small: '',
        cta: {
            label: '',
            url: '',
            target: '_self',
        },
        bgColor: '',
        textColor: '',
        borderColor: '',
        image: {
            src: '',
            full: '',
            alt: '',
        },
        popup: {
            enabled: false,
            showEvery: 'always',      // always | once
            startAt: '',
            endAt: '',
            widthPx: 480,
            overlayOpacity: 0.55,     // 0..0.9
            autoCloseSeconds: 0,      // 0 = mai

            // Ritardo / scroll
            delaySeconds: 0,          // 0 = nessun ritardo
            triggerOnScroll: false,   // true = apri allo scroll
            triggerScrollPercent: 50, // percentuale di scroll pagina (0–100)

            // Fade-in
            fadeEnabled: true,        // true = fade-in, false = senza animazione
            fadeSeconds: 2,           // durata fade-in in secondi
        },
    };

    let rawAlert = {};
    if (block && block.alert && typeof block.alert === 'object') {
        rawAlert = block.alert;
    } else if (block && block.data && block.data.alert && typeof block.data.alert === 'object') {
        rawAlert = block.data.alert;
    }

    const alertData = {
        ...defaultAlert,
        ...rawAlert,
        cta: {
            ...defaultAlert.cta,
            ...(rawAlert && typeof rawAlert.cta === 'object' ? rawAlert.cta : {}),
        },
        image: {
            ...defaultAlert.image,
            ...(rawAlert && typeof rawAlert.image === 'object' ? rawAlert.image : {}),
        },
        popup: {
            ...defaultAlert.popup,
            ...(rawAlert && typeof rawAlert.popup === 'object' ? rawAlert.popup : {}),
        },
    };

    const saveBlock = () => {
        state.updateBlock(section.id, block.id, {
            alert: JSON.parse(JSON.stringify(alertData)),
        });
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar d-flex align-items-center gap-2 flex-wrap';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.innerHTML = '<i class="bi bi-megaphone me-1"></i> Alert / Promozione';
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
            if (confirm('Eliminare questo alert?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        }));
    }

    // === PREVIEW ============================================================
    const preview = document.createElement('div');
    preview.className = 'pb-alert-preview mt-2';

    function renderPreview() {
        preview.innerHTML = '';

        let variant = alertData.variant || 'info';
        const allowed = ['primary','secondary','success','danger','warning','info','light','dark','custom'];
        if (!allowed.includes(variant)) variant = 'info';

        let classes = 'alert d-flex align-items-center mb-0';
        if (variant !== 'custom') {
            classes += ` alert-${variant}`;
        } else {
            classes += ' alert-light';
        }
        if (alertData.dismissible && !previewMode) {
            classes += ' alert-dismissible';
        }

        const wrapper = document.createElement('div');
        wrapper.className = classes;

        if (variant === 'custom') {
            if (alertData.bgColor)     wrapper.style.backgroundColor = alertData.bgColor;
            if (alertData.textColor)   wrapper.style.color = alertData.textColor;
            if (alertData.borderColor) wrapper.style.borderColor = alertData.borderColor;
        }

        if (alertData.showIcon) {
            const iconBox = document.createElement('div');
            iconBox.className = 'me-2 fs-4 flex-shrink-0';
            const i = document.createElement('i');
            i.className = alertData.icon || 'bi bi-megaphone';
            iconBox.appendChild(i);
            wrapper.appendChild(iconBox);
        }

        const contentBox = document.createElement('div');
        contentBox.className = 'flex-grow-1';

        // Immagine (se presente)
        if (alertData.image && alertData.image.src) {
            const imgWrap = document.createElement('div');
            imgWrap.className = 'mb-2 text-center';
            const img = document.createElement('img');
            img.src = alertData.image.src;
            img.alt = alertData.image.alt || '';
            img.className = 'img-fluid rounded';
            imgWrap.appendChild(img);
            contentBox.appendChild(imgWrap);
        }

        if (alertData.badge) {
            const badgeWrap = document.createElement('div');
            badgeWrap.className = 'mb-1';
            const badgeEl = document.createElement('span');
            badgeEl.className = 'badge rounded-pill text-bg-warning me-1';
            badgeEl.textContent = alertData.badge;
            badgeWrap.appendChild(badgeEl);
            contentBox.appendChild(badgeWrap);
        }

        if (alertData.title) {
            const t = document.createElement('div');
            t.className = 'fw-semibold mb-1';
            t.textContent = alertData.title;
            contentBox.appendChild(t);
        }

        if (alertData.text) {
            const p = document.createElement('div');
            p.className = 'mb-1';
            p.textContent = alertData.text;
            contentBox.appendChild(p);
        }

        if (alertData.small) {
            const s = document.createElement('div');
            s.className = 'small text-muted';
            s.textContent = alertData.small;
            contentBox.appendChild(s);
        }

        if (alertData.cta.url && alertData.cta.label) {
            const ctaWrap = document.createElement('div');
            ctaWrap.className = 'mt-2';
            const btn = document.createElement('a');
            btn.className = 'btn btn-sm btn-outline-dark';
            btn.href = alertData.cta.url;
            btn.target = alertData.cta.target || '_self';
            btn.textContent = alertData.cta.label;
            ctaWrap.appendChild(btn);
            contentBox.appendChild(ctaWrap);
        }

        wrapper.appendChild(contentBox);

        if (alertData.dismissible && !previewMode) {
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close ms-2';
            closeBtn.setAttribute('aria-label', 'Chiudi');
            closeBtn.disabled = true; // solo anteprima
            wrapper.appendChild(closeBtn);
        }

        preview.appendChild(wrapper);
    }

    renderPreview();

    // === FORM (solo edit) ===================================================
    let formRow = null;

    if (!previewMode) {
        formRow = document.createElement('div');
        formRow.className = 'row g-2 mt-3';

        // ---------------- MAIN (contenuti + immagine) -----------------------
        const colMain = document.createElement('div');
        colMain.className = 'col-12 col-md-7';

        // Titolo
        const tGroup = document.createElement('div');
        tGroup.className = 'mb-2';
        const tLabel = document.createElement('label');
        tLabel.className = 'form-label small mb-1';
        tLabel.textContent = 'Titolo';
        const tInput = document.createElement('input');
        tInput.type = 'text';
        tInput.className = 'form-control form-control-sm';
        tInput.value = alertData.title || '';
        tInput.addEventListener('input', () => {
            alertData.title = tInput.value;
            saveBlock();
            renderPreview();
        });
        tGroup.appendChild(tLabel);
        tGroup.appendChild(tInput);
        colMain.appendChild(tGroup);

        // Testo principale
        const txtGroup = document.createElement('div');
        txtGroup.className = 'mb-2';
        const txtLabel = document.createElement('label');
        txtLabel.className = 'form-label small mb-1';
        txtLabel.textContent = 'Testo principale';
        const txtArea = document.createElement('textarea');
        txtArea.className = 'form-control form-control-sm';
        txtArea.rows = 3;
        txtArea.value = alertData.text || '';
        txtArea.addEventListener('input', () => {
            alertData.text = txtArea.value;
            saveBlock();
            renderPreview();
        });
        txtGroup.appendChild(txtLabel);
        txtGroup.appendChild(txtArea);
        colMain.appendChild(txtGroup);

        // Nota piccola
        const smallGroup = document.createElement('div');
        smallGroup.className = 'mb-2';
        const smallLabel = document.createElement('label');
        smallLabel.className = 'form-label small mb-1';
        smallLabel.textContent = 'Nota piccola (es. termini e condizioni)';
        const smallInput = document.createElement('input');
        smallInput.type = 'text';
        smallInput.className = 'form-control form-control-sm';
        smallInput.value = alertData.small || '';
        smallInput.addEventListener('input', () => {
            alertData.small = smallInput.value;
            saveBlock();
            renderPreview();
        });
        smallGroup.appendChild(smallLabel);
        smallGroup.appendChild(smallInput);
        colMain.appendChild(smallGroup);

        // Immagine
        const imgGroup = document.createElement('div');
        imgGroup.className = 'mb-2';
        const imgLabel = document.createElement('label');
        imgLabel.className = 'form-label small mb-1';
        imgLabel.textContent = 'Immagine (opzionale)';
        const imgPreviewWrap = document.createElement('div');
        imgPreviewWrap.className = 'border rounded p-2 d-flex align-items-center gap-2 mb-1 bg-light';

        function refreshImgPreview() {
            imgPreviewWrap.innerHTML = '';
            if (alertData.image && alertData.image.src) {
                const img = document.createElement('img');
                img.src = alertData.image.src;
                img.alt = alertData.image.alt || '';
                img.style.maxWidth = '80px';
                img.style.height = 'auto';
                img.className = 'rounded';
                const span = document.createElement('span');
                span.className = 'small text-muted';
                span.textContent = alertData.image.alt || alertData.image.src;
                imgPreviewWrap.appendChild(img);
                imgPreviewWrap.appendChild(span);
            } else {
                const span = document.createElement('span');
                span.className = 'small text-muted';
                span.textContent = 'Nessuna immagine selezionata.';
                imgPreviewWrap.appendChild(span);
            }
        }

        refreshImgPreview();

        const imgButtonsRow = document.createElement('div');
        imgButtonsRow.className = 'd-flex align-items-center gap-2 mb-1';

        const imgPickBtn = document.createElement('button');
        imgPickBtn.type = 'button';
        imgPickBtn.className = 'btn btn-sm btn-outline-primary';
        imgPickBtn.innerHTML = '<i class="bi bi-image me-1"></i> Seleziona immagine';
        imgPickBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openImagePicker((url) => {
                if (!url) return;
                alertData.image = alertData.image || {};
                alertData.image.src = url;
                alertData.image.full = url;
                saveBlock();
                renderPreview();
                refreshImgPreview();
            });
        });

        const imgRemoveBtn = document.createElement('button');
        imgRemoveBtn.type = 'button';
        imgRemoveBtn.className = 'btn btn-sm btn-outline-danger';
        imgRemoveBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Rimuovi immagine';
        imgRemoveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            alertData.image = { src:'', full:'', alt:'' };
            saveBlock();
            renderPreview();
            refreshImgPreview();
            imgAltInput.value = '';
        });

        imgButtonsRow.appendChild(imgPickBtn);
        imgButtonsRow.appendChild(imgRemoveBtn);

        const imgAltInput = document.createElement('input');
        imgAltInput.type = 'text';
        imgAltInput.className = 'form-control form-control-sm';
        imgAltInput.placeholder = 'Testo alternativo immagine (alt)';
        imgAltInput.value = (alertData.image && alertData.image.alt) || '';
        imgAltInput.addEventListener('input', () => {
            alertData.image = alertData.image || {};
            alertData.image.alt = imgAltInput.value;
            saveBlock();
            renderPreview();
            refreshImgPreview();
        });

        imgGroup.appendChild(imgLabel);
        imgGroup.appendChild(imgPreviewWrap);
        imgGroup.appendChild(imgButtonsRow);
        imgGroup.appendChild(imgAltInput);
        colMain.appendChild(imgGroup);

        formRow.appendChild(colMain);

        // ---------------- OPZIONI (stile, CTA, popup) ------------------------
        const colOpt = document.createElement('div');
        colOpt.className = 'col-12 col-md-5';

        // Variante
        const vGroup = document.createElement('div');
        vGroup.className = 'mb-2';
        const vLabel = document.createElement('label');
        vLabel.className = 'form-label small mb-1';
        vLabel.textContent = 'Stile';
        const vSelect = document.createElement('select');
        vSelect.className = 'form-select form-select-sm';
        vSelect.innerHTML = `
            <option value="info">Info (azzurro)</option>
            <option value="success">Success (verde)</option>
            <option value="warning">Warning (giallo)</option>
            <option value="danger">Danger (rosso)</option>
            <option value="primary">Primary</option>
            <option value="secondary">Secondary</option>
            <option value="light">Light</option>
            <option value="dark">Dark</option>
            <option value="custom">Personalizzato</option>
        `;
        vSelect.value = alertData.variant || 'info';
        vSelect.addEventListener('change', () => {
            alertData.variant = vSelect.value || 'info';
            saveBlock();
            renderPreview();
            syncCustomColorsVisibility();
        });
        vGroup.appendChild(vLabel);
        vGroup.appendChild(vSelect);
        colOpt.appendChild(vGroup);

        // Badge
        const badgeGroup = document.createElement('div');
        badgeGroup.className = 'mb-2';
        const badgeLabel = document.createElement('label');
        badgeLabel.className = 'form-label small mb-1';
        badgeLabel.textContent = 'Badge (es. -20% fino a domenica)';
        const badgeInput = document.createElement('input');
        badgeInput.type = 'text';
        badgeInput.className = 'form-control form-control-sm';
        badgeInput.value = alertData.badge || '';
        badgeInput.addEventListener('input', () => {
            alertData.badge = badgeInput.value;
            saveBlock();
            renderPreview();
        });
        badgeGroup.appendChild(badgeLabel);
        badgeGroup.appendChild(badgeInput);
        colOpt.appendChild(badgeGroup);

        // Icona + toggle
        const iconGroup = document.createElement('div');
        iconGroup.className = 'mb-2';
        const iconLabel = document.createElement('label');
        iconLabel.className = 'form-label small mb-1';
        iconLabel.textContent = 'Icona (classe Bootstrap Icons)';
        const iconRow = document.createElement('div');
        iconRow.className = 'd-flex align-items-center gap-2';
        const iconInput = document.createElement('input');
        iconInput.type = 'text';
        iconInput.className = 'form-control form-control-sm';
        iconInput.placeholder = 'es. bi bi-megaphone';
        iconInput.value = alertData.icon || 'bi bi-megaphone';
        iconInput.addEventListener('input', () => {
            alertData.icon = iconInput.value || 'bi bi-megaphone';
            saveBlock();
            renderPreview();
        });
        const iconCheckWrap = document.createElement('div');
        iconCheckWrap.className = 'form-check form-switch mb-0';
        const iconCheck = document.createElement('input');
        iconCheck.type = 'checkbox';
        iconCheck.className = 'form-check-input';
        iconCheck.checked = !!alertData.showIcon;
        iconCheck.addEventListener('change', () => {
            alertData.showIcon = !!iconCheck.checked;
            saveBlock();
            renderPreview();
        });
        const iconCheckLbl = document.createElement('label');
        iconCheckLbl.className = 'form-check-label small';
        iconCheckLbl.textContent = 'Mostra icona';
        iconCheckWrap.appendChild(iconCheck);
        iconCheckWrap.appendChild(iconCheckLbl);
        iconRow.appendChild(iconInput);
        iconRow.appendChild(iconCheckWrap);
        iconGroup.appendChild(iconLabel);
        iconGroup.appendChild(iconRow);
        colOpt.appendChild(iconGroup);

        // CTA
        const ctaGroup = document.createElement('div');
        ctaGroup.className = 'mb-2';
        const ctaLabelEl = document.createElement('label');
        ctaLabelEl.className = 'form-label small mb-1';
        ctaLabelEl.textContent = 'Pulsante (CTA)';
        const ctaLabelInput = document.createElement('input');
        ctaLabelInput.type = 'text';
        ctaLabelInput.className = 'form-control form-control-sm mb-1';
        ctaLabelInput.placeholder = 'Testo pulsante (es. Scopri di più)';
        ctaLabelInput.value = alertData.cta.label || '';
        ctaLabelInput.addEventListener('input', () => {
            alertData.cta.label = ctaLabelInput.value;
            saveBlock();
            renderPreview();
        });
        const ctaUrlInput = document.createElement('input');
        ctaUrlInput.type = 'text';
        ctaUrlInput.className = 'form-control form-control-sm mb-1';
        ctaUrlInput.placeholder = 'URL destinazione';
        ctaUrlInput.value = alertData.cta.url || '';
        ctaUrlInput.addEventListener('input', () => {
            alertData.cta.url = ctaUrlInput.value;
            saveBlock();
            renderPreview();
        });
        const ctaTargetSelect = document.createElement('select');
        ctaTargetSelect.className = 'form-select form-select-sm';
        ctaTargetSelect.innerHTML = `
            <option value="_self">Apri nella stessa scheda</option>
            <option value="_blank">Apri in nuova scheda</option>
        `;
        ctaTargetSelect.value = alertData.cta.target || '_self';
        ctaTargetSelect.addEventListener('change', () => {
            alertData.cta.target = ctaTargetSelect.value || '_self';
            saveBlock();
            renderPreview();
        });

        ctaGroup.appendChild(ctaLabelEl);
        ctaGroup.appendChild(ctaLabelInput);
        ctaGroup.appendChild(ctaUrlInput);
        ctaGroup.appendChild(ctaTargetSelect);
        colOpt.appendChild(ctaGroup);

        // Dismissible inline
        const dismGroup = document.createElement('div');
        dismGroup.className = 'mb-2';
        const dismCheckWrap = document.createElement('div');
        dismCheckWrap.className = 'form-check form-switch';
        const dismCheck = document.createElement('input');
        dismCheck.type = 'checkbox';
        dismCheck.className = 'form-check-input';
        dismCheck.checked = !!alertData.dismissible;
        dismCheck.addEventListener('change', () => {
            alertData.dismissible = !!dismCheck.checked;
            saveBlock();
            renderPreview();
        });
        const dismLabel = document.createElement('label');
        dismLabel.className = 'form-check-label small';
        dismLabel.textContent = 'Mostra pulsante di chiusura (solo versione inline)';
        dismCheckWrap.appendChild(dismCheck);
        dismCheckWrap.appendChild(dismLabel);
        dismGroup.appendChild(dismCheckWrap);
        colOpt.appendChild(dismGroup);

        // Colori custom
        const customColors = document.createElement('div');
        customColors.className = 'border rounded p-2 bg-light mb-2';
        customColors.innerHTML = `
            <div class="small text-muted mb-2">
                Colori personalizzati (solo se stile = "Personalizzato")
            </div>
            <div class="row g-2">
                <div class="col-4">
                    <label class="form-label small mb-1">Sfondo</label>
                    <input type="color"
                           class="form-control form-control-color form-control-sm w-100 pb-alert-bg">
                </div>
                <div class="col-4">
                    <label class="form-label small mb-1">Testo</label>
                    <input type="color"
                           class="form-control form-control-color form-control-sm w-100 pb-alert-text">
                </div>
                <div class="col-4">
                    <label class="form-label small mb-1">Bordo</label>
                    <input type="color"
                           class="form-control form-control-color form-control-sm w-100 pb-alert-border">
                </div>
            </div>
        `;
        const bgInp   = customColors.querySelector('.pb-alert-bg');
        const txtInp  = customColors.querySelector('.pb-alert-text');
        const bordInp = customColors.querySelector('.pb-alert-border');

        if (bgInp)   bgInp.value   = alertData.bgColor     || '#ffffff';
        if (txtInp)  txtInp.value  = alertData.textColor   || '#000000';
        if (bordInp) bordInp.value = alertData.borderColor || '#000000';

        bgInp && bgInp.addEventListener('input', () => {
            alertData.bgColor = bgInp.value;
            saveBlock();
            renderPreview();
        });
        txtInp && txtInp.addEventListener('input', () => {
            alertData.textColor = txtInp.value;
            saveBlock();
            renderPreview();
        });
        bordInp && bordInp.addEventListener('input', () => {
            alertData.borderColor = bordInp.value;
            saveBlock();
            renderPreview();
        });

        colOpt.appendChild(customColors);

        function syncCustomColorsVisibility() {
            const isCustom = (alertData.variant === 'custom');
            customColors.style.display = isCustom ? 'block' : 'none';
        }
        syncCustomColorsVisibility();

        // ---------------- POPUP SETTINGS ------------------------------------
        const popupGroup = document.createElement('div');
        popupGroup.className = 'mt-3';

        popupGroup.innerHTML = `
            <div class="form-check form-switch mb-2">
                <input class="form-check-input pb-popup-enabled" type="checkbox" id="pbPopupEnabled_${block.id}">
                <label class="form-check-label small" for="pbPopupEnabled_${block.id}">
                    Mostra come popup (oscurando la pagina)
                </label>
            </div>
            <div class="border rounded p-2 bg-light pb-popup-options">
                <div class="small text-muted mb-2">
                    Il popup appare sopra la pagina, con sfondo scuro.
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Frequenza</label>
                        <select class="form-select form-select-sm pb-popup-show-every">
                            <option value="always">Ad ogni apertura</option>
                            <option value="once">Solo la prima volta (per browser)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Larghezza (px)</label>
                        <input type="number" min="240" max="900" step="10"
                               class="form-control form-control-sm pb-popup-width">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Data inizio</label>
                        <input type="datetime-local" class="form-control form-control-sm pb-popup-start">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Data fine</label>
                        <input type="datetime-local" class="form-control form-control-sm pb-popup-end">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Auto chiusura (sec)</label>
                        <input type="number" min="0" max="600" step="1"
                               class="form-control form-control-sm pb-popup-autoclose">
                        <div class="form-text small">0 = non si chiude da solo</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Oscuramento sfondo</label>
                        <input type="range" min="0" max="90" step="5"
                               class="form-range pb-popup-overlay">
                        <div class="small text-muted mt-1 pb-popup-overlay-label"></div>
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Ritardo apertura (sec)</label>
                        <input type="number" min="0" max="600" step="1"
                               class="form-control form-control-sm pb-popup-delay">
                        <div class="form-text small">
                            0 = mostra subito quando le altre condizioni sono soddisfatte.
                            Se usato con "Attiva su scroll", il conteggio parte dopo lo scroll.
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Attiva su scroll</label>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input pb-popup-scroll-toggle" type="checkbox" id="pbPopupScroll_${block.id}">
                            <label class="form-check-label small" for="pbPopupScroll_${block.id}">
                                Apri quando l'utente ha scrollato la pagina
                            </label>
                        </div>
                        <input type="range" min="0" max="100" step="5"
                               class="form-range pb-popup-scroll-percent">
                        <div class="small text-muted pb-popup-scroll-percent-label"></div>
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input pb-popup-fade-toggle" type="checkbox" id="pbPopupFade_${block.id}">
                            <label class="form-check-label small" for="pbPopupFade_${block.id}">
                                Animazione fade-in
                            </label>
                        </div>
                        <div class="form-text small">
                            Se disattivato l'alert appare subito senza animazione.
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Durata fade-in (sec)</label>
                        <input type="number" min="0" max="10" step="0.5"
                               class="form-control form-control-sm pb-popup-fade-seconds">
                        <div class="form-text small">
                            0 = nessun fade (apparizione istantanea).
                        </div>
                    </div>
                </div>
            </div>
        `;

        const popupEnabledInput   = popupGroup.querySelector('.pb-popup-enabled');
        const popupOptionsWrap    = popupGroup.querySelector('.pb-popup-options');
        const popupShowEveryInput = popupGroup.querySelector('.pb-popup-show-every');
        const popupWidthInput     = popupGroup.querySelector('.pb-popup-width');
        const popupStartInput     = popupGroup.querySelector('.pb-popup-start');
        const popupEndInput       = popupGroup.querySelector('.pb-popup-end');
        const popupAutoCloseInput = popupGroup.querySelector('.pb-popup-autoclose');
        const popupOverlayRange   = popupGroup.querySelector('.pb-popup-overlay');
        const popupOverlayLabel   = popupGroup.querySelector('.pb-popup-overlay-label');
        const popupDelayInput         = popupGroup.querySelector('.pb-popup-delay');
        const popupScrollToggle       = popupGroup.querySelector('.pb-popup-scroll-toggle');
        const popupScrollPercent      = popupGroup.querySelector('.pb-popup-scroll-percent');
        const popupScrollPercentLabel = popupGroup.querySelector('.pb-popup-scroll-percent-label');
        const popupFadeToggle         = popupGroup.querySelector('.pb-popup-fade-toggle');
        const popupFadeSeconds        = popupGroup.querySelector('.pb-popup-fade-seconds');

        popupEnabledInput.checked        = !!alertData.popup.enabled;
        popupShowEveryInput.value        = alertData.popup.showEvery || 'always';
        popupWidthInput.value            = alertData.popup.widthPx || 480;
        popupStartInput.value            = alertData.popup.startAt || '';
        popupEndInput.value              = alertData.popup.endAt || '';
        popupAutoCloseInput.value        = alertData.popup.autoCloseSeconds || 0;
        const overlayPerc = Math.round((alertData.popup.overlayOpacity ?? 0.55) * 100);
        popupOverlayRange.value          = String(Math.min(90, Math.max(0, overlayPerc)));
        popupOverlayLabel.textContent    = `Oscuramento: ${popupOverlayRange.value}%`;

        // Ritardo
        let delay = parseInt(alertData.popup.delaySeconds ?? 0, 10);
        if (isNaN(delay) || delay < 0) delay = 0;
        if (delay > 600) delay = 600;
        popupDelayInput.value = String(delay);

        // Scroll
        const scrollOn = !!alertData.popup.triggerOnScroll;
        popupScrollToggle.checked = scrollOn;

        let scrollPerc = parseInt(alertData.popup.triggerScrollPercent ?? 50, 10);
        if (isNaN(scrollPerc)) scrollPerc = 50;
        if (scrollPerc < 0) scrollPerc = 0;
        if (scrollPerc > 100) scrollPerc = 100;
        popupScrollPercent.value = String(scrollPerc);
        popupScrollPercentLabel.textContent =
            `Mostra dopo circa il ${scrollPerc}% di scroll della pagina`;

        // Fade
        const fadeOn = alertData.popup.fadeEnabled !== false; // default true
        popupFadeToggle.checked = fadeOn;

        let fadeSec = parseFloat(alertData.popup.fadeSeconds ?? 2);
        if (isNaN(fadeSec) || fadeSec < 0) fadeSec = 0;
        if (fadeSec > 10) fadeSec = 10;
        popupFadeSeconds.value = String(fadeSec);

        const syncPopupVisibility = () => {
            popupOptionsWrap.style.display = popupEnabledInput.checked ? 'block' : 'none';
        };
        syncPopupVisibility();

        popupEnabledInput.addEventListener('change', () => {
            alertData.popup.enabled = !!popupEnabledInput.checked;
            saveBlock();
            syncPopupVisibility();
        });

        popupShowEveryInput.addEventListener('change', () => {
            alertData.popup.showEvery = popupShowEveryInput.value || 'always';
            saveBlock();
        });

        popupWidthInput.addEventListener('input', () => {
            let v = parseInt(popupWidthInput.value || '480', 10);
            if (isNaN(v)) v = 480;
            if (v < 240) v = 240;
            if (v > 900) v = 900;
            popupWidthInput.value = v;
            alertData.popup.widthPx = v;
            saveBlock();
        });

        popupStartInput.addEventListener('change', () => {
            alertData.popup.startAt = popupStartInput.value || '';
            saveBlock();
        });
        popupEndInput.addEventListener('change', () => {
            alertData.popup.endAt = popupEndInput.value || '';
            saveBlock();
        });

        popupAutoCloseInput.addEventListener('input', () => {
            let v = parseInt(popupAutoCloseInput.value || '0', 10);
            if (isNaN(v) || v < 0) v = 0;
            if (v > 600) v = 600;
            popupAutoCloseInput.value = v;
            alertData.popup.autoCloseSeconds = v;
            saveBlock();
        });

        popupOverlayRange.addEventListener('input', () => {
            const perc = Math.min(90, Math.max(0, parseInt(popupOverlayRange.value || '55', 10)));
            popupOverlayRange.value = String(perc);
            popupOverlayLabel.textContent = `Oscuramento: ${perc}%`;
            alertData.popup.overlayOpacity = perc / 100;
            saveBlock();
        });

        popupDelayInput.addEventListener('input', () => {
            let v = parseInt(popupDelayInput.value || '0', 10);
            if (isNaN(v) || v < 0) v = 0;
            if (v > 600) v = 600;
            popupDelayInput.value = String(v);
            alertData.popup.delaySeconds = v;
            saveBlock();
        });

        popupScrollToggle.addEventListener('change', () => {
            alertData.popup.triggerOnScroll = !!popupScrollToggle.checked;
            saveBlock();
        });

        popupScrollPercent.addEventListener('input', () => {
            let v = parseInt(popupScrollPercent.value || '50', 10);
            if (isNaN(v)) v = 50;
            if (v < 0) v = 0;
            if (v > 100) v = 100;
            popupScrollPercent.value = String(v);
            popupScrollPercentLabel.textContent =
                `Mostra dopo circa il ${v}% di scroll della pagina`;
            alertData.popup.triggerScrollPercent = v;
            saveBlock();
        });

        popupFadeToggle.addEventListener('change', () => {
            alertData.popup.fadeEnabled = !!popupFadeToggle.checked;
            saveBlock();
        });

        popupFadeSeconds.addEventListener('input', () => {
            let v = parseFloat(popupFadeSeconds.value || '0');
            if (isNaN(v) || v < 0) v = 0;
            if (v > 10) v = 10;
            popupFadeSeconds.value = String(v);
            alertData.popup.fadeSeconds = v;
            saveBlock();
        });

        colOpt.appendChild(popupGroup);
        formRow.appendChild(colOpt);
    }

    // === ASSEMBLA CARD ======================================================
    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    if (formRow) body.appendChild(formRow);

    if (!previewMode) {
        const stylePanel = renderBlockStylePanel({
            section,
            block,
            state,
            rerender,
        });
        body.appendChild(stylePanel);
    }

    container.appendChild(body);
}
