// public/pb/blockStyle.js

// Spaziatura base in px per margini/padding
const SPACING_MAP = {
    0: 0,
    1: 4,
    2: 8,
    3: 16,
    4: 24,
    5: 32,
};

export function getDefaultBlockStyle() {
    return {
        col: 12,          // 1..12 -> col-md-*
        height: 'auto',   // 'auto' | 'vh-50' | 'vh-100'
        marginTop: 0,     // 0..5
        marginBottom: 3,  // 0..5
        padding: 3,       // 0..5
        bgColor: '',      // hex o vuoto
        border: 'none',   // 'none' | 'light' | 'strong'
        borderRadius: 'md', // 'none' | 'sm' | 'md' | 'lg' | 'xl'
    };
}

export function normalizeBlockStyle(style) {
    const def = getDefaultBlockStyle();
    if (!style || typeof style !== 'object') {
        return { ...def };
    }
    return { ...def, ...style };
}

// Applica colonne + margini al contenitore col-*
export function applyBlockStyleToColumn(colEl, style) {
    const st  = normalizeBlockStyle(style);
    const col = Math.min(12, Math.max(1, parseInt(st.col, 10) || 12));

    colEl.className = `col-12 col-md-${col}`;

    const mt = SPACING_MAP[st.marginTop]    ?? SPACING_MAP[0];
    const mb = SPACING_MAP[st.marginBottom] ?? SPACING_MAP[3];

    colEl.style.marginTop    = mt + 'px';
    colEl.style.marginBottom = mb + 'px';
}

// Applica altezza, padding, bg, bordo al box del blocco
export function applyBlockStyleToBlock(blockEl, style) {
    const st = normalizeBlockStyle(style);

    const pad = SPACING_MAP[st.padding] ?? SPACING_MAP[3];
    blockEl.style.padding = pad + 'px';

    if (st.bgColor) {
        blockEl.style.backgroundColor = st.bgColor;
    } else {
        blockEl.style.backgroundColor = '';
    }

    if (st.height === 'vh-100') {
        blockEl.style.minHeight = '100vh';
    } else if (st.height === 'vh-50') {
        blockEl.style.minHeight = '50vh';
    } else {
        blockEl.style.minHeight = '';
    }

    if (st.border === 'none') {
        blockEl.style.border = 'none';
    } else if (st.border === 'light') {
        blockEl.style.border = '1px solid rgba(0,0,0,0.08)';
    } else if (st.border === 'strong') {
        blockEl.style.border = '2px solid rgba(0,0,0,0.35)';
    }

    let radius = '';
    switch (st.borderRadius) {
        case 'none': radius = '0'; break;
        case 'sm':   radius = '4px'; break;
        case 'md':   radius = '8px'; break;
        case 'lg':   radius = '16px'; break;
        case 'xl':   radius = '24px'; break;
        default:     radius = '8px';
    }
    blockEl.style.borderRadius = radius;
}

// Pannellino <details> con le impostazioni stile
export function renderBlockStylePanel(ctx) {
    const { section, block, state, rerender } = ctx;
    const style = normalizeBlockStyle(block.style);

    // assicuriamo lo style normalizzato nello state
    if (state && typeof state.updateBlockStyle === 'function') {
        state.updateBlockStyle(section.id, block.id, style);
    }

    const panel = document.createElement('details');
    panel.className = 'pb-style-panel mt-2';

    const summary = document.createElement('summary');
    summary.className = 'small text-muted';
    summary.innerHTML = '<i class="bi bi-sliders me-1"></i>Stili blocco';
    panel.appendChild(summary);

    const inner = document.createElement('div');
    inner.className = 'mt-2 row g-2';

    // --- Colonne (Bootstrap) ---
    const colWrap = document.createElement('div');
    colWrap.className = 'col-6 col-md-3';
    const colLabel = document.createElement('label');
    colLabel.className = 'form-label small mb-1';
    colLabel.textContent = 'Colonne (lg)';
    const colSelect = document.createElement('select');
    colSelect.className = 'form-select form-select-sm';
    colSelect.innerHTML = `
        <option value="12">12 / 12</option>
        <option value="9">9 / 12</option>
        <option value="8">8 / 12</option>
        <option value="6">6 / 12</option>
        <option value="4">4 / 12</option>
        <option value="3">3 / 12</option>
    `;
    colSelect.value = String(style.col ?? 12);
    colSelect.addEventListener('change', () => {
        const v = parseInt(colSelect.value, 10) || 12;
        state.updateBlockStyle(section.id, block.id, { col: v });
        rerender && rerender();
    });
    colWrap.appendChild(colLabel);
    colWrap.appendChild(colSelect);
    inner.appendChild(colWrap);

    // --- Altezza ---
    const hWrap = document.createElement('div');
    hWrap.className = 'col-6 col-md-3';
    const hLabel = document.createElement('label');
    hLabel.className = 'form-label small mb-1';
    hLabel.textContent = 'Altezza';
    const hSelect = document.createElement('select');
    hSelect.className = 'form-select form-select-sm';
    hSelect.innerHTML = `
        <option value="auto">Auto</option>
        <option value="vh-50">Metà schermo</option>
        <option value="vh-100">Tutto schermo</option>
    `;
    hSelect.value = style.height || 'auto';
    hSelect.addEventListener('change', () => {
        state.updateBlockStyle(section.id, block.id, { height: hSelect.value });
        rerender && rerender();
    });
    hWrap.appendChild(hLabel);
    hWrap.appendChild(hSelect);
    inner.appendChild(hWrap);

    // --- Margini sopra/sotto ---
    const mtWrap = document.createElement('div');
    mtWrap.className = 'col-6 col-md-3';
    const mtLabel = document.createElement('label');
    mtLabel.className = 'form-label small mb-1';
    mtLabel.textContent = 'Margine sopra';
    const mtSelect = document.createElement('select');
    mtSelect.className = 'form-select form-select-sm';
    mtSelect.innerHTML = `
        <option value="0">0</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
    `;
    mtSelect.value = String(style.marginTop ?? 0);
    mtSelect.addEventListener('change', () => {
        const v = parseInt(mtSelect.value, 10) || 0;
        state.updateBlockStyle(section.id, block.id, { marginTop: v });
        rerender && rerender();
    });
    mtWrap.appendChild(mtLabel);
    mtWrap.appendChild(mtSelect);
    inner.appendChild(mtWrap);

    const mbWrap = document.createElement('div');
    mbWrap.className = 'col-6 col-md-3';
    const mbLabel = document.createElement('label');
    mbLabel.className = 'form-label small mb-1';
    mbLabel.textContent = 'Margine sotto';
    const mbSelect = document.createElement('select');
    mbSelect.className = 'form-select form-select-sm';
    mbSelect.innerHTML = mtSelect.innerHTML;
    mbSelect.value = String(style.marginBottom ?? 3);
    mbSelect.addEventListener('change', () => {
        const v = parseInt(mbSelect.value, 10) || 3;
        state.updateBlockStyle(section.id, block.id, { marginBottom: v });
        rerender && rerender();
    });
    mbWrap.appendChild(mbLabel);
    mbWrap.appendChild(mbSelect);
    inner.appendChild(mbWrap);

    // --- Padding interno ---
    const padWrap = document.createElement('div');
    padWrap.className = 'col-6 col-md-3';
    const padLabel = document.createElement('label');
    padLabel.className = 'form-label small mb-1';
    padLabel.textContent = 'Padding interno';
    const padSelect = document.createElement('select');
    padSelect.className = 'form-select form-select-sm';
    padSelect.innerHTML = mtSelect.innerHTML;
    padSelect.value = String(style.padding ?? 3);
    padSelect.addEventListener('change', () => {
        const v = parseInt(padSelect.value, 10) || 3;
        state.updateBlockStyle(section.id, block.id, { padding: v });
        rerender && rerender();
    });
    padWrap.appendChild(padLabel);
    padWrap.appendChild(padSelect);
    inner.appendChild(padWrap);

    // --- Colore sfondo ---
    const bgWrap = document.createElement('div');
    bgWrap.className = 'col-6 col-md-3';
    const bgLabel = document.createElement('label');
    bgLabel.className = 'form-label small mb-1';
    bgLabel.textContent = 'Colore sfondo';
    const bgInput = document.createElement('input');
    bgInput.type = 'color';
    bgInput.className = 'form-control form-control-color form-control-sm w-100';
    bgInput.value = style.bgColor || '#ffffff';
    bgInput.addEventListener('input', () => {
        state.updateBlockStyle(section.id, block.id, { bgColor: bgInput.value });
        rerender && rerender();
    });
    bgWrap.appendChild(bgLabel);
    bgWrap.appendChild(bgInput);
    inner.appendChild(bgWrap);

    // --- Bordo ---
    const bWrap = document.createElement('div');
    bWrap.className = 'col-6 col-md-3';
    const bLabel = document.createElement('label');
    bLabel.className = 'form-label small mb-1';
    bLabel.textContent = 'Bordo';
    const bSelect = document.createElement('select');
    bSelect.className = 'form-select form-select-sm';
    bSelect.innerHTML = `
        <option value="none">Nessuno</option>
        <option value="light">Sottile</option>
        <option value="strong">Marcato</option>
    `;
    bSelect.value = style.border || 'none';
    bSelect.addEventListener('change', () => {
        state.updateBlockStyle(section.id, block.id, { border: bSelect.value });
        rerender && rerender();
    });
    bWrap.appendChild(bLabel);
    bWrap.appendChild(bSelect);
    inner.appendChild(bWrap);

    // --- Raggio bordi ---
    const rWrap = document.createElement('div');
    rWrap.className = 'col-6 col-md-3';
    const rLabel = document.createElement('label');
    rLabel.className = 'form-label small mb-1';
    rLabel.textContent = 'Raggio bordi';
    const rSelect = document.createElement('select');
    rSelect.className = 'form-select form-select-sm';
    rSelect.innerHTML = `
        <option value="none">Angoli vivi</option>
        <option value="sm">Leggermente arrotondati</option>
        <option value="md">Arrotondati</option>
        <option value="lg">Molto arrotondati</option>
        <option value="xl">Pillola</option>
    `;
    rSelect.value = style.borderRadius || 'md';
    rSelect.addEventListener('change', () => {
        state.updateBlockStyle(section.id, block.id, { borderRadius: rSelect.value });
        rerender && rerender();
    });
    rWrap.appendChild(rLabel);
    rWrap.appendChild(rSelect);
    inner.appendChild(rWrap);

    panel.appendChild(inner);
    return panel;
}
