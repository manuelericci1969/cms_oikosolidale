// public/pb/render.js

import { renderBlock } from './blocks/index.js';

function classForRowAlign(align) {
    switch (align) {
        case 'center': return 'justify-content-center';
        case 'end': return 'justify-content-end';
        case 'between': return 'justify-content-between';
        case 'around': return 'justify-content-around';
        case 'evenly': return 'justify-content-evenly';
        default: return '';
    }
}

export function renderBuilder(root, state, options = {}) {
    const previewMode = !!options.previewMode;
    const sections = state.get() || [];

    root.innerHTML = '';

    if (!sections.length) {
        const empty = document.createElement('div');
        empty.className = 'pb-empty text-center text-muted py-4';
        empty.innerHTML = `
            <p class="mb-2">Non ci sono sezioni.</p>
            <p class="mb-0">Usa il pulsante <strong>Aggiungi sezione</strong> in alto per iniziare.</p>
        `;
        root.appendChild(empty);
        return;
    }

    sections.forEach((section, index) => {
        const secEl = document.createElement('section');
        secEl.className = 'pb-section card mb-3';

        // Header sezione
        const header = document.createElement('div');
        header.className = 'card-header d-flex align-items-center justify-content-between pb-section-header';

        const left = document.createElement('div');
        left.className = 'd-flex align-items-center gap-2';
        left.innerHTML = `
            <span class="badge bg-primary-subtle text-primary-emphasis">
                Sezione ${index + 1}
            </span>
        `;
        header.appendChild(left);

        const right = document.createElement('div');
        right.className = 'd-flex align-items-center gap-1';

        if (!previewMode) {
            // Allineamento riga
            const alignSel = document.createElement('select');
            alignSel.className = 'form-select form-select-sm';
            alignSel.style.width = 'auto';
            alignSel.innerHTML = `
                <option value="start">Allinea a sinistra</option>
                <option value="center">Centro</option>
                <option value="end">Destra</option>
                <option value="between">Distribuito (between)</option>
                <option value="around">Distribuito (around)</option>
                <option value="evenly">Distribuito (evenly)</option>
            `;
            alignSel.value = section.rowAlign || 'start';
            alignSel.addEventListener('change', () => {
                state.updateSection(section.id, { rowAlign: alignSel.value });
                renderBuilder(root, state, { previewMode });
            });
            right.appendChild(alignSel);

            // Sposta su/giù
            const upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'btn btn-sm btn-light border';
            upBtn.title = 'Sposta sezione su';
            upBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
            upBtn.addEventListener('click', (e) => {
                e.preventDefault();
                state.moveSection(section.id, -1);
                renderBuilder(root, state, { previewMode });
            });
            right.appendChild(upBtn);

            const downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'btn btn-sm btn-light border';
            downBtn.title = 'Sposta sezione giù';
            downBtn.innerHTML = '<i class="bi bi-arrow-down"></i>';
            downBtn.addEventListener('click', (e) => {
                e.preventDefault();
                state.moveSection(section.id, +1);
                renderBuilder(root, state, { previewMode });
            });
            right.appendChild(downBtn);

            // Elimina sezione
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn btn-sm btn-outline-danger';
            delBtn.title = 'Elimina sezione';
            delBtn.innerHTML = '<i class="bi bi-trash"></i>';
            delBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Eliminare questa intera sezione?')) {
                    state.removeSection(section.id);
                    renderBuilder(root, state, { previewMode });
                }
            });
            right.appendChild(delBtn);
        }

        header.appendChild(right);
        secEl.appendChild(header);

        // Corpo sezione
        const body = document.createElement('div');
        body.className = 'card-body';

        const row = document.createElement('div');
        row.className = 'row g-3 pb-section-row';
        const alignCls = classForRowAlign(section.rowAlign || 'start');
        if (alignCls) row.classList.add(alignCls);

        const rerender = () => renderBuilder(root, state, { previewMode });

        // blocchi in modo sicuro (anche se manca section.blocks)
        const blocks = Array.isArray(section.blocks) ? section.blocks : [];

        blocks.forEach((block, bIndex) => {
            const cols = Math.min(
                12,
                Math.max(1, parseInt(block.columns || 12, 10) || 12),
            );

            const col = document.createElement('div');
            col.className = `col-md-${cols} pb-block-col`;

            const card = document.createElement('div');
            card.className = 'pb-block card h-100';
            card.dataset.blockId = block.id;

            renderBlock({
                container: card,
                section,
                block,
                index: bIndex,
                state,
                previewMode,
                rerender,
            });

            col.appendChild(card);
            row.appendChild(col);
        });

        body.appendChild(row);

        if (!blocks.length && !previewMode) {
            const empty = document.createElement('div');
            empty.className = 'text-center text-muted small py-2';
            empty.textContent = 'Nessun blocco in questa sezione.';
            body.appendChild(empty);
        }

        if (!previewMode) {
            const addRow = document.createElement('div');
            addRow.className = 'mt-3 d-flex align-items-center gap-2 flex-wrap';

            const label = document.createElement('span');
            label.className = 'small text-muted';
            label.textContent = 'Aggiungi blocco:';
            addRow.appendChild(label);

            const select = document.createElement('select');
            select.className = 'form-select form-select-sm w-auto';
            select.innerHTML = `
                <option value="">Seleziona…</option>
                <option value="richtext">Testo / Rich Text</option>
                <option value="image">Immagine</option>
                <option value="gallery">Galleria immagini</option>
                <option value="video">Video</option>
                <option value="logo_carousel">Carosello loghi</option>
            `;
            select.addEventListener('change', () => {
                const type = select.value;
                if (!type) return;
                state.addBlock(section.id, type);
                renderBuilder(root, state, { previewMode });
            });

            addRow.appendChild(select);

            body.appendChild(addRow);
        }

        secEl.appendChild(body);
        root.appendChild(secEl);
    });
}
