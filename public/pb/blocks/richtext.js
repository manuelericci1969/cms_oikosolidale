// public/pb/blocks/richtext.js

import { openImagePicker } from '../mediaPicker.js';

/**
 * Ritorna la lista di font disponibili per il rich text.
 * Se window.PB_FONTS è valorizzato da Blade (typography settings), usiamo quello.
 */
function getAvailableFonts() {
    const fromWindow = (window.PB_FONTS && Array.isArray(window.PB_FONTS)) ? window.PB_FONTS : null;
    if (fromWindow && fromWindow.length) return fromWindow;
    return [
        'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins',
        'Playfair Display','Merriweather','Source Sans 3','Raleway',
        'Nunito','Oswald','PT Serif','Work Sans','Rubik',
        'Arial','Verdana','Times New Roman','Georgia','Tahoma','Trebuchet MS','Courier New',
    ];
}

/**
 * Helper per bottoni toolbar del rich text.
 */
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
 * Render del blocco Rich Text.
 * ctx = { container, section, block, index, state, rerender, previewMode }
 */
export function renderRichTextBlock(ctx) {
    const { container, section, block, state } = ctx;
    const rerender    = (typeof ctx.rerender === 'function') ? ctx.rerender : null;
    const previewMode = !!ctx.previewMode;

    // Pulisci contenitore
    container.innerHTML = '';

    // === STYLE DATA =========================================================
    const style = {
        ...(block.style && typeof block.style === 'object' ? block.style : {}),
        ...(block.data && block.data.style && typeof block.data.style === 'object' ? block.data.style : {}),
    };

    // === EDITOR (contenteditable + HTML) ====================================
    const editor = document.createElement('div');
    editor.className = 'pb-richtext-editor';
    editor.dataset.path = 'content';
    editor.contentEditable = previewMode ? 'false' : 'true';

    const html = (typeof block.html === 'string')
        ? block.html
        : (block.data && typeof block.data.html === 'string'
            ? block.data.html
            : '<p>Scrivi qui…</p>');

    editor.innerHTML = html;

    // 🔹 Nuovo: editor HTML di base (textarea) + stato modalità
    let htmlEditor = null;
    let isHtmlMode = false;

    const getCurrentHtml = () => {
        if (!previewMode && isHtmlMode && htmlEditor) {
            return htmlEditor.value;
        }
        return editor.innerHTML;
    };

    const syncStateContent = () => {
        state.updateBlock(section.id, block.id, {
            html: getCurrentHtml(),
            style: { ...style },
        });
    };

    if (!previewMode) {
        editor.addEventListener('input', syncStateContent);
        editor.addEventListener('blur', syncStateContent);
    }

    const exec = (cmd, value = null) => {
        // In modalità HTML non applichiamo comandi visuali
        if (previewMode || isHtmlMode) return;
        editor.focus();
        document.execCommand(cmd, false, value);
        syncStateContent();
    };

    // === TOOLBAR ============================================================
    const toolbar = document.createElement('div');
    toolbar.className = 'pb-toolbar pb-richtext-toolbar';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted';
    label.textContent = 'Testo';
    toolbar.appendChild(label);

    if (!previewMode) {
        // spostamento / duplicazione / eliminazione blocco
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
            if (confirm('Eliminare questo blocco di testo?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        }));

        const sep = document.createElement('span');
        sep.className = 'small-muted ms-2 me-1';
        sep.textContent = '|';
        toolbar.appendChild(sep);

        // Formattazione base
        toolbar.appendChild(createIconBtn('bi bi-type-bold', 'Grassetto', () => exec('bold')));
        toolbar.appendChild(createIconBtn('bi bi-type-italic', 'Corsivo', () => exec('italic')));
        toolbar.appendChild(createIconBtn('bi bi-type-underline', 'Sottolineato', () => exec('underline')));
        toolbar.appendChild(createIconBtn('bi bi-type-strikethrough', 'Barrato', () => exec('strikeThrough')));

        // Elenchi
        toolbar.appendChild(createIconBtn('bi bi-list-ul', 'Elenco puntato', () => exec('insertUnorderedList')));
        toolbar.appendChild(createIconBtn('bi bi-list-ol', 'Elenco numerato', () => exec('insertOrderedList')));

        // Allineamento
        toolbar.appendChild(createIconBtn('bi bi-text-left', 'Allinea a sinistra', () => exec('justifyLeft')));
        toolbar.appendChild(createIconBtn('bi bi-text-center', 'Allinea al centro', () => exec('justifyCenter')));
        toolbar.appendChild(createIconBtn('bi bi-text-right', 'Allinea a destra', () => exec('justifyRight')));

        // 🔹 Da qui in poi su NUOVA RIGA (grazie a .pb-toolbar-break in CSS)
        const brk = document.createElement('span');
        brk.className = 'pb-toolbar-break';
        toolbar.appendChild(brk);

        // Selettore stile paragrafo/titolo
        const formatSelect = document.createElement('select');
        formatSelect.className = 'form-select form-select-sm';
        formatSelect.style.width = 'auto';
        formatSelect.innerHTML = `
            <option value="">Stile</option>
            <option value="P">Paragrafo</option>
            <option value="H1">Titolo 1</option>
            <option value="H2">Titolo 2</option>
            <option value="H3">Titolo 3</option>
        `;
        formatSelect.addEventListener('change', () => {
            const val = formatSelect.value;
            if (val) {
                exec('formatBlock', val);
                formatSelect.value = '';
            }
        });
        toolbar.appendChild(formatSelect);

        // Selettore font
        const fonts = getAvailableFonts();
        const fontSelect = document.createElement('select');
        fontSelect.className = 'form-select form-select-sm';
        fontSelect.style.width = 'auto';

        let fontOptionsHtml = '<option value="">Font</option>';
        fonts.forEach((f) => {
            const labelF = String(f);
            const valueF = String(f);
            fontOptionsHtml += `<option value="${valueF.replace(/"/g, '&quot;')}">${labelF}</option>`;
        });
        fontSelect.innerHTML = fontOptionsHtml;

        fontSelect.addEventListener('change', () => {
            const val = fontSelect.value;
            if (val) {
                exec('fontName', val);
                fontSelect.value = '';
            }
        });
        toolbar.appendChild(fontSelect);

        // Dimensione font (1..7)
        const sizeSelect = document.createElement('select');
        sizeSelect.className = 'form-select form-select-sm';
        sizeSelect.style.width = 'auto';
        sizeSelect.innerHTML = `
            <option value="">Dimensione</option>
            <option value="2">Piccolo</option>
            <option value="3">Normale</option>
            <option value="4">Grande</option>
            <option value="5">Molto grande</option>
        `;
        sizeSelect.addEventListener('change', () => {
            const val = sizeSelect.value;
            if (val) {
                exec('fontSize', val);
                sizeSelect.value = '';
            }
        });
        toolbar.appendChild(sizeSelect);

        // Colori selezione testo
        const textColor = document.createElement('input');
        textColor.type = 'color';
        textColor.className = 'form-control form-control-color form-control-sm';
        textColor.title = 'Colore testo (selezione)';
        textColor.addEventListener('input', () => {
            exec('foreColor', textColor.value);
        });
        toolbar.appendChild(textColor);

        const bgColorSel = document.createElement('input');
        bgColorSel.type = 'color';
        bgColorSel.className = 'form-control form-control-color form-control-sm';
        bgColorSel.title = 'Evidenziazione testo (selezione)';
        bgColorSel.addEventListener('input', () => {
            exec('hiliteColor', bgColorSel.value);
        });
        toolbar.appendChild(bgColorSel);

        // Link
        toolbar.appendChild(createIconBtn('bi bi-link-45deg', 'Inserisci/Modifica link', () => {
            const url = prompt('URL del link (es. https://...)', 'https://');
            if (url) {
                exec('createLink', url);
            }
        }));
        toolbar.appendChild(createIconBtn('bi bi-link-45deg', 'Rimuovi link', () => {
            exec('unlink');
        }));

        // Immagine dal Media Picker
        toolbar.appendChild(createIconBtn('bi bi-image', 'Inserisci immagine', () => {
            openImagePicker((url) => {
                if (url) {
                    exec('insertImage', url);
                }
            });
        }));

        // Rimuovi formattazione selezione
        toolbar.appendChild(createIconBtn('bi bi-eraser', 'Rimuovi formattazione', () => {
            exec('removeFormat');
        }));

        // 🔹 Nuovo: toggle editor HTML / WYSIWYG
        toolbar.appendChild(createIconBtn('bi bi-code-slash', 'Mostra/Nascondi HTML', () => {
            if (!htmlEditor) return;

            if (!isHtmlMode) {
                // passo a modalità HTML
                htmlEditor.value = editor.innerHTML;
                editor.style.display = 'none';
                htmlEditor.style.display = 'block';
                isHtmlMode = true;
            } else {
                // torno a modalità visuale
                editor.innerHTML = htmlEditor.value;
                editor.style.display = 'block';
                htmlEditor.style.display = 'none';
                isHtmlMode = false;
            }
            syncStateContent();
        }));
    }

    // === PREVIEW WRAPPER ====================================================
    const preview = document.createElement('div');
    preview.className = 'pb-preview';
    preview.appendChild(editor);

    // 🔹 Nuovo: creazione textarea HTML (solo in edit mode)
    if (!previewMode) {
        htmlEditor = document.createElement('textarea');
        htmlEditor.className = 'pb-richtext-html form-control form-control-sm mt-2';
        htmlEditor.style.fontFamily = 'monospace';
        htmlEditor.style.minHeight = '160px';
        htmlEditor.style.display = 'none'; // nascosto di default (WYSIWYG attivo)
        htmlEditor.value = html;
        htmlEditor.addEventListener('input', syncStateContent);
        htmlEditor.addEventListener('blur', syncStateContent);
        preview.appendChild(htmlEditor);
    }

    // === PANNELLO STILI BLOCCO =============================================
    const stylePanel = document.createElement('details');
    stylePanel.className = 'pb-style-panel mt-1';

    const summary = document.createElement('summary');
    summary.innerHTML = '<i class="bi bi-sliders me-1"></i> Stili blocco';
    stylePanel.appendChild(summary);

    const inner = document.createElement('div');
    inner.className = 'mt-2';

    // 🔹 Colonne: select vuoto, lo popoliamo 1..12 via JS
    inner.innerHTML = `
    <div class="row g-2 mb-2">
        <div class="col-6 col-md-4">
            <label class="form-label small mb-0">Larghezza (colonne)</label>
            <select class="form-select form-select-sm pb-style-col"></select>
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

    <div class="row g-2">
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
    </div>`;

    stylePanel.appendChild(inner);

    // === HOOK ELEMENTI STILE ===============================================
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

    // 🔹 Popola select colonne da 1 a 12
    if (colSel) {
        colSel.innerHTML = '';
        for (let i = 1; i <= 12; i++) {
            const opt = document.createElement('option');
            opt.value = String(i);
            let label = String(i);
            if (i === 12) label = '12 (intera)';
            if (i === 6)  label = '6 (½)';
            if (i === 4)  label = '4 (⅓)';
            if (i === 3)  label = '3 (¼)';
            opt.textContent = label;
            colSel.appendChild(opt);
        }
    }

    // Animazione blocco (frontend)
    const animNameSel = inner.querySelector('.pb-anim-name');
    const animDurInp  = inner.querySelector('.pb-anim-dur');
    const animDelInp  = inner.querySelector('.pb-anim-del');

    const anim = (block.animation && typeof block.animation === 'object')
        ? { ...block.animation }
        : { name: 'none', duration: 600, delay: 0 };

    if (animNameSel) animNameSel.value = anim.name || 'none';
    if (animDurInp)  animDurInp.value  = (typeof anim.duration === 'number' ? anim.duration : 600);
    if (animDelInp)  animDelInp.value  = (typeof anim.delay === 'number' ? anim.delay : 0);

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
        state.updateBlock(section.id, block.id, {
            style: { ...style },
            html: getCurrentHtml(),
        });
        applyStyleToDom();
    };

    const syncAnimation = () => {
        if (!animNameSel) return;

        const name = animNameSel.value || 'none';

        let duration = 600;
        if (animDurInp) {
            const v = parseInt(animDurInp.value || '600', 10);
            duration = isNaN(v) ? 600 : Math.min(Math.max(v, 100), 5000);
        }

        let delay = 0;
        if (animDelInp) {
            const v = parseInt(animDelInp.value || '0', 10);
            delay = isNaN(v) ? 0 : Math.min(Math.max(v, 0), 5000);
        }

        const animData = { name, duration, delay };

        state.updateBlock(section.id, block.id, {
            style: { ...style },
            html: getCurrentHtml(),
            animation: name === 'none' ? null : animData,
        });
    };

    if (!previewMode) {
        colSel && colSel.addEventListener('change', () => {
            const c = parseInt(colSel.value || '12', 10);
            const safe = (!isNaN(c) && c >= 1 && c <= 12) ? c : 12;
            style.col = String(safe);
            state.updateBlock(section.id, block.id, {
                columns: safe,
                style: { ...style },
                html: getCurrentHtml(),
            });
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

    const body = document.createElement('div');
    body.className = 'card-body';

    body.appendChild(toolbar);
    body.appendChild(preview);
    body.appendChild(stylePanel);

    container.appendChild(body);
}
