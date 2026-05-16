// public/pb/blocks/component.js

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function debounce(fn, wait = 300) {
    let t = null;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

async function pickImage() {
    const pickerUrl =
        window.R4ADMIN?.mediaBrowseUrl ||
        window.PB_MEDIA_PICKER_URL ||
        window.R4ADMIN?.mediaPickerUrl ||
        '/admin/media/browse';

    let openPicker = window.openImagePicker;
    let getUrlByQuality = window.getMediaUrlByQuality;

    try {
        if (typeof openPicker !== 'function') {
            const mod = await import(`/pb/mediaPicker.js?v=${Date.now()}`);
            openPicker = mod.openImagePicker;
            getUrlByQuality = mod.getMediaUrlByQuality;

            if (typeof openPicker === 'function') {
                window.openImagePicker = openPicker;
            }
            if (typeof getUrlByQuality === 'function') {
                window.getMediaUrlByQuality = getUrlByQuality;
            }
        }
    } catch (err) {
        console.error('Errore caricamento mediaPicker.js', err);
    }

    if (typeof openPicker !== 'function') {
        alert('Media picker non disponibile.');
        return null;
    }

    const picked = await openPicker({
        pickerUrl,
        mode: 'image',
        quality: 'full',
    });

    if (!picked) return null;

    const fallback =
        picked.url ||
        picked.src ||
        picked.full ||
        picked.original ||
        picked.original_url ||
        picked.thumb ||
        '';

    if (typeof getUrlByQuality === 'function') {
        return getUrlByQuality(picked, 'full', fallback) || fallback || null;
    }

    return fallback || null;
}

function getFieldValue(block, field) {
    const props = block.props && typeof block.props === 'object' ? block.props : {};
    const current = props[field.name];

    if (current !== undefined && current !== null) {
        return current;
    }

    if (field.default !== undefined) {
        return field.default;
    }

    return '';
}

function buildInputForField({ field, value, onChange }) {
    const type = String(field.type || 'text').toLowerCase();

    if (type === 'select') {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';

        const options = Array.isArray(field.options) ? field.options : [];
        options.forEach((opt) => {
            const optionEl = document.createElement('option');

            if (typeof opt === 'object' && opt !== null) {
                optionEl.value = String(opt.value ?? '');
                optionEl.textContent = String(opt.label ?? opt.value ?? '');
            } else {
                optionEl.value = String(opt);
                optionEl.textContent = String(opt);
            }

            if (String(value ?? '') === optionEl.value) {
                optionEl.selected = true;
            }

            select.appendChild(optionEl);
        });

        select.addEventListener('change', () => onChange(select.value));
        return select;
    }

    if (type === 'textarea' || type === 'richtext') {
        const textarea = document.createElement('textarea');
        textarea.className = 'form-control form-control-sm';
        textarea.rows = type === 'richtext' ? 6 : 4;
        textarea.value = String(value ?? '');

        const debounced = debounce(() => onChange(textarea.value), 350);
        textarea.addEventListener('input', debounced);

        return textarea;
    }

    if (type === 'image') {
        const wrap = document.createElement('div');
        wrap.className = 'border rounded p-2 bg-light';

        const preview = document.createElement('div');
        preview.className = 'mb-2 text-center';

        const renderPreview = (src) => {
            preview.innerHTML = '';

            if (src) {
                const img = document.createElement('img');
                img.src = String(src);
                img.alt = field.label || field.name || 'image';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '140px';
                img.className = 'img-fluid rounded border';
                preview.appendChild(img);
            } else {
                const empty = document.createElement('div');
                empty.className = 'small text-muted';
                empty.textContent = 'Nessuna immagine selezionata';
                preview.appendChild(empty);
            }
        };

        renderPreview(value);

        const actions = document.createElement('div');
        actions.className = 'd-flex gap-2 flex-wrap';

        const pickBtn = document.createElement('button');
        pickBtn.type = 'button';
        pickBtn.className = 'btn btn-sm btn-outline-primary';
        pickBtn.innerHTML = '<i class="bi bi-image me-1"></i>Scegli';

        pickBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const pickedUrl = await pickImage();
            if (!pickedUrl) return;
            renderPreview(pickedUrl);
            onChange(pickedUrl);
        });

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-sm btn-outline-danger';
        clearBtn.innerHTML = '<i class="bi bi-x-lg me-1"></i>Rimuovi';

        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            renderPreview('');
            onChange('');
        });

        actions.appendChild(pickBtn);
        actions.appendChild(clearBtn);

        wrap.appendChild(preview);
        wrap.appendChild(actions);

        return wrap;
    }

    if (type === 'color') {
        const input = document.createElement('input');
        input.type = 'color';
        input.className = 'form-control form-control-color';
        input.value = String(value || '#000000');
        input.addEventListener('change', () => onChange(input.value));
        return input;
    }

    const input = document.createElement('input');
    input.type = (type === 'url') ? 'url' : 'text';
    input.className = 'form-control form-control-sm';
    input.value = String(value ?? '');
    input.addEventListener('change', () => onChange(input.value));

    return input;
}

function renderPropsEditor({ mount, block, section, state, rerender }) {
    const schema = Array.isArray(block.component_schema) ? block.component_schema : [];

    if (!schema.length) {
        const empty = document.createElement('div');
        empty.className = 'small text-muted';
        empty.textContent = 'Questo componente non espone proprietà editabili.';
        mount.appendChild(empty);
        return;
    }

    const grid = document.createElement('div');
    grid.className = 'row g-3';

    schema.forEach((field) => {
        if (!field || !field.name) return;

        const type = String(field.type || 'text').toLowerCase();

        if (type === 'repeater' || type === 'group') {
            const col = document.createElement('div');
            col.className = 'col-12';

            col.innerHTML = `
                <div class="border rounded p-2 bg-light">
                    <div class="fw-semibold">${esc(field.label || field.name)}</div>
                    <div class="small text-muted">
                        Tipo <code>${esc(type)}</code> non ancora gestito nel builder.
                    </div>
                </div>
            `;

            grid.appendChild(col);
            return;
        }

        const col = document.createElement('div');
        col.className = (type === 'textarea' || type === 'richtext') ? 'col-12' : 'col-md-6';

        const label = document.createElement('label');
        label.className = 'form-label small fw-semibold';
        label.textContent = field.label || field.name;

        const currentValue = getFieldValue(block, field);

        const control = buildInputForField({
            field,
            value: currentValue,
            onChange: (nextValue) => {
                const nextProps = {
                    ...(block.props && typeof block.props === 'object' ? block.props : {}),
                    [field.name]: nextValue,
                };

                state.updateBlock(section.id, block.id, {
                    props: nextProps,
                });

                rerender && rerender();
            },
        });

        col.appendChild(label);
        col.appendChild(control);

        if (field.help) {
            const help = document.createElement('div');
            help.className = 'form-text';
            help.textContent = String(field.help);
            col.appendChild(help);
        }

        grid.appendChild(col);
    });

    mount.appendChild(grid);
}

export function renderComponentBlock(ctx) {
    const { container, block, section, state, rerender, previewMode } = ctx;

    container.innerHTML = '';

    const body = document.createElement('div');
    body.className = 'card-body';

    const header = document.createElement('div');
    header.className = 'd-flex align-items-center justify-content-between mb-3';

    const left = document.createElement('div');
    left.innerHTML = `
        <div class="d-flex flex-column">
            <span class="badge bg-dark-subtle text-dark-emphasis mb-1">Componente</span>
            <strong>${esc(block.component_name || 'Componente senza nome')}</strong>
            <small class="text-muted"><code>${esc(block.component_key || '')}</code></small>
        </div>
    `;
    header.appendChild(left);

    const right = document.createElement('div');
    right.className = 'd-flex align-items-center gap-1';

    if (!previewMode) {
        const up = document.createElement('button');
        up.type = 'button';
        up.className = 'btn btn-sm btn-light border';
        up.title = 'Sposta blocco su';
        up.innerHTML = '<i class="bi bi-arrow-up"></i>';
        up.addEventListener('click', (e) => {
            e.preventDefault();
            state.moveBlock(section.id, block.id, -1);
            rerender && rerender();
        });
        right.appendChild(up);

        const down = document.createElement('button');
        down.type = 'button';
        down.className = 'btn btn-sm btn-light border';
        down.title = 'Sposta blocco giù';
        down.innerHTML = '<i class="bi bi-arrow-down"></i>';
        down.addEventListener('click', (e) => {
            e.preventDefault();
            state.moveBlock(section.id, block.id, +1);
            rerender && rerender();
        });
        right.appendChild(down);

        const duplicate = document.createElement('button');
        duplicate.type = 'button';
        duplicate.className = 'btn btn-sm btn-light border';
        duplicate.title = 'Duplica blocco';
        duplicate.innerHTML = '<i class="bi bi-copy"></i>';
        duplicate.addEventListener('click', (e) => {
            e.preventDefault();
            state.duplicateBlock(section.id, block.id);
            rerender && rerender();
        });
        right.appendChild(duplicate);

        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-sm btn-outline-danger';
        del.title = 'Elimina blocco';
        del.innerHTML = '<i class="bi bi-trash"></i>';
        del.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Eliminare questo componente?')) {
                state.removeBlock(section.id, block.id);
                rerender && rerender();
            }
        });
        right.appendChild(del);
    }

    header.appendChild(right);
    body.appendChild(header);

    const info = document.createElement('div');
    info.className = 'small text-muted mb-3';
    info.innerHTML = `
        <div><strong>ID componente:</strong> ${esc(block.component_id ?? '')}</div>
        <div><strong>Colonne:</strong> ${esc(block.columns ?? 12)}/12</div>
    `;
    body.appendChild(info);

    const previewBox = document.createElement('div');
    previewBox.className = 'border rounded bg-light p-3';

    const propsCount = block.props && typeof block.props === 'object'
        ? Object.keys(block.props).length
        : 0;

    previewBox.innerHTML = `
        <div class="fw-semibold mb-1">${esc(block.component_name || 'Componente')}</div>
        <div class="small text-muted mb-2">
            Questo blocco verrà renderizzato nel frontend tramite il template del componente registrato.
        </div>
        <div class="small">
            <span class="badge bg-secondary-subtle text-secondary-emphasis">props: ${propsCount}</span>
        </div>
    `;
    body.appendChild(previewBox);

    if (!previewMode) {
        const propsWrap = document.createElement('div');
        propsWrap.className = 'mt-3 pt-3 border-top';

        const propsTitle = document.createElement('div');
        propsTitle.className = 'fw-semibold mb-2';
        propsTitle.textContent = 'Proprietà componente';
        propsWrap.appendChild(propsTitle);

        renderPropsEditor({
            mount: propsWrap,
            block,
            section,
            state,
            rerender,
        });

        body.appendChild(propsWrap);

        const footer = document.createElement('div');
        footer.className = 'mt-3 pt-3 border-top d-flex align-items-center gap-2 flex-wrap';

        const colsLabel = document.createElement('label');
        colsLabel.className = 'small text-muted';
        colsLabel.textContent = 'Larghezza blocco:';
        footer.appendChild(colsLabel);

        const colsSelect = document.createElement('select');
        colsSelect.className = 'form-select form-select-sm';
        colsSelect.style.width = 'auto';

        for (let i = 1; i <= 12; i++) {
            const opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = `${i}/12`;
            if (Number(block.columns || 12) === i) {
                opt.selected = true;
            }
            colsSelect.appendChild(opt);
        }

        colsSelect.addEventListener('change', () => {
            state.updateBlock(section.id, block.id, {
                columns: parseInt(colsSelect.value, 10) || 12,
            });
            rerender && rerender();
        });

        footer.appendChild(colsSelect);
        body.appendChild(footer);
    }

    container.appendChild(body);
}
