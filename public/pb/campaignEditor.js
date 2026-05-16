// public/pb/campaignEditor.js
import { openImagePicker } from './mediaPicker.js';

/**
 * Font disponibili (come nel Page Builder)
 */
function getAvailableFonts() {
    const fromWindow = (window.PB_FONTS && Array.isArray(window.PB_FONTS))
        ? window.PB_FONTS
        : null;
    if (fromWindow && fromWindow.length) return fromWindow;

    return [
        'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins',
        'Playfair Display','Merriweather','Source Sans 3','Raleway',
        'Nunito','Oswald','PT Serif','Work Sans','Rubik',
        'Arial','Verdana','Times New Roman','Georgia','Tahoma','Trebuchet MS','Courier New',
    ];
}

/**
 * Helper per bottoni toolbar
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
 * Pannellino per ridimensionare le immagini nell'editor campagna
 */
function initImageResizePanel(editor, onChange) {
    if (!editor) return;

    let panel = null;
    let rangeEl = null;
    let inputEl = null;
    let presetBtns = null;
    let resetBtn = null;
    let currentImg = null;

    function clamp(value, min, max) {
        value = parseInt(value, 10);
        if (isNaN(value)) return 100;
        return Math.max(min, Math.min(max, value));
    }

    function ensurePanel() {
        if (panel) return;

        panel = document.createElement('div');
        panel.className = 'pb-img-panel bg-white border shadow-sm p-2';

        panel.innerHTML = `
            <div class="small text-muted mb-1">
                Larghezza immagine (10–100%)
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <input type="range" min="10" max="100" value="100"
                       data-role="pb-img-range" class="form-range" />
                <div class="d-flex align-items-center gap-1">
                    <input type="number" min="10" max="100" value="100"
                           class="form-control form-control-sm"
                           style="width:70px"
                           data-role="pb-img-input">
                    <span>%</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-1">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-preset="25">25%</button>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-preset="50">50%</button>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-preset="75">75%</button>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-preset="100">100%</button>
                <button type="button"
                        class="btn btn-link btn-sm text-danger ms-auto"
                        data-role="pb-img-reset">
                    Reset
                </button>
            </div>
        `;

        document.body.appendChild(panel);

        rangeEl    = panel.querySelector('[data-role="pb-img-range"]');
        inputEl    = panel.querySelector('[data-role="pb-img-input"]');
        presetBtns = panel.querySelectorAll('[data-preset]');
        resetBtn   = panel.querySelector('[data-role="pb-img-reset"]');

        function applyWidth(pct) {
            if (!currentImg) return;
            const v = clamp(pct, 10, 100);
            currentImg.style.width  = v + '%';
            currentImg.style.height = 'auto';
            rangeEl.value = v;
            inputEl.value = v;
            if (typeof onChange === 'function') {
                onChange();
            }
        }

        rangeEl.addEventListener('input', () => applyWidth(rangeEl.value));
        inputEl.addEventListener('change', () => applyWidth(inputEl.value));

        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                applyWidth(btn.dataset.preset);
            });
        });

        resetBtn.addEventListener('click', () => {
            if (!currentImg) return;
            currentImg.style.width  = '';
            currentImg.style.height = '';
            rangeEl.value = 100;
            inputEl.value = 100;
            if (typeof onChange === 'function') {
                onChange();
            }
        });
    }

    function showPanel(img) {
        ensurePanel();

        if (currentImg && currentImg !== img) {
            currentImg.classList.remove('pb-img-selected');
        }

        currentImg = img;
        currentImg.classList.add('pb-img-selected');

        let initial = 100;
        if (img.style.width && img.style.width.endsWith('%')) {
            initial = clamp(parseInt(img.style.width, 10), 10, 100);
        }
        rangeEl.value = initial;
        inputEl.value = initial;

        panel.classList.add('pb-img-panel--visible');

        const rect      = img.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect();

        let top = rect.top - panelRect.height - 8;
        if (top < 8) {
            top = rect.bottom + 8;
        }

        let left = rect.left;
        const maxLeft = window.innerWidth - panelRect.width - 8;
        if (left > maxLeft) left = maxLeft;

        panel.style.top  = (top + window.scrollY) + 'px';
        panel.style.left = (left + window.scrollX) + 'px';
    }

    function hidePanel() {
        if (currentImg) {
            currentImg.classList.remove('pb-img-selected');
            currentImg = null;
        }
        if (panel) {
            panel.classList.remove('pb-img-panel--visible');
        }
    }

    // Click dentro l'editor: se clicchi un'immagine → pannello
    editor.addEventListener('click', (e) => {
        const img = e.target.closest('img');
        if (!img || !editor.contains(img)) {
            hidePanel();
            return;
        }
        showPanel(img);
    });

    // Click fuori: chiude il pannello
    document.addEventListener('click', (e) => {
        if (!panel) return;
        if (panel.contains(e.target)) return;
        if (editor.contains(e.target)) return;
        hidePanel();
    });
}

/**
 * Inizializza l'editor HTML della campagna
 */
function initCampaignHtmlEditor() {
    const textarea   = document.getElementById('campaignHtmlTextarea');
    const editor     = document.getElementById('campaignRichtextEditor');
    const htmlEditor = document.getElementById('campaignHtmlSource');
    const toolbar    = document.getElementById('campaignRichtextToolbar');

    if (!textarea || !editor || !htmlEditor || !toolbar) return;

    const initial = (textarea.value && textarea.value.trim() !== '')
        ? textarea.value
        : '<p>Scrivi qui…</p>';

    editor.innerHTML = initial;
    htmlEditor.value = initial;

    let isHtmlMode = false;

    const getCurrentHtml = () => (isHtmlMode ? htmlEditor.value : editor.innerHTML);

    const syncTextarea = () => {
        textarea.value = getCurrentHtml();
    };

    editor.addEventListener('input', syncTextarea);
    editor.addEventListener('blur', syncTextarea);
    htmlEditor.addEventListener('input', syncTextarea);
    htmlEditor.addEventListener('blur', syncTextarea);

    const exec = (cmd, value = null) => {
        if (isHtmlMode) return; // in modalità HTML non applichiamo comandi visuali
        editor.focus();
        document.execCommand(cmd, false, value);
        syncTextarea();
    };

    // ==== TOOLBAR ==========================================================
    toolbar.innerHTML = '';

    const label = document.createElement('span');
    label.className = 'badge bg-light text-muted me-2';
    label.textContent = 'Contenuto campagna';
    toolbar.appendChild(label);

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

    // Separatore / nuova “riga” toolbar
    const brk = document.createElement('span');
    brk.className = 'pb-toolbar-break';
    toolbar.appendChild(brk);

    // Stile paragrafo / heading
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

    // Font
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

    // Dimensione font
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

    // Colori testo / evidenziazione
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
        }, { mode: 'image' });
    }));

    // Rimuovi formattazione
    toolbar.appendChild(createIconBtn('bi bi-eraser', 'Rimuovi formattazione', () => {
        exec('removeFormat');
    }));

    // Toggle HTML / WYSIWYG
    toolbar.appendChild(createIconBtn('bi bi-code-slash', 'Mostra/nascondi HTML', () => {
        if (!isHtmlMode) {
            htmlEditor.value = editor.innerHTML;
            editor.style.display = 'none';
            htmlEditor.classList.remove('d-none');
            isHtmlMode = true;
        } else {
            editor.innerHTML = htmlEditor.value;
            editor.style.display = '';
            htmlEditor.classList.add('d-none');
            isHtmlMode = false;
        }
        syncTextarea();
    }));

    // Inizializza pannello di resize immagini
    initImageResizePanel(editor, syncTextarea);
}

document.addEventListener('DOMContentLoaded', initCampaignHtmlEditor);
