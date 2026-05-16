// public/pb/index.js
import { createState } from './state.js';
import { renderBuilder } from './render.js';
import { initBuilderSidebar } from './sidebar.js';

(function () {
    const container       = document.getElementById('builderContainer');
    const addSecBtn       = document.getElementById('pbAddSectionBtn');
    const addComponentBtn = document.getElementById('pbAddComponentBtn');
    const previewBtn      = document.getElementById('pbTogglePreview');
    const form            = document.getElementById('pageForm');
    const hidden          = document.getElementById('contentJson');

    const componentsModalEl = document.getElementById('pbComponentsModal');
    const componentsListEl  = document.getElementById('pbComponentsList');
    const componentSearchEl = document.getElementById('pbComponentSearch');
    const componentCategoryEl = document.getElementById('pbComponentCategory');
    const componentSearchBtn  = document.getElementById('pbComponentSearchBtn');

    if (!container) return;

    const initial = Array.isArray(window.__PB_CONTENT__) ? window.__PB_CONTENT__ : [];
    const state   = createState(initial);

    window.__PB_STATE__ = state;

    let previewMode = false;
    let componentsModal = null;

    function updatePreviewButtonUI() {
        if (!previewBtn) return;

        if (previewMode) {
            previewBtn.classList.remove('btn-outline-secondary');
            previewBtn.classList.add('btn-primary');
            previewBtn.innerHTML = '<i class="bi bi-eye-slash me-1"></i> Esci Anteprima';
        } else {
            previewBtn.classList.add('btn-outline-secondary');
            previewBtn.classList.remove('btn-primary');
            previewBtn.innerHTML = '<i class="bi bi-eye me-1"></i> Anteprima';
        }
    }

    function redraw() {
        renderBuilder(container, state, { previewMode });
    }

    function saveToForm() {
        if (hidden) {
            hidden.value = JSON.stringify(state.get());
        }
    }

    function uid(prefix = 'id') {
        const rnd = Math.random().toString(36).slice(2, 10);
        return `${prefix}_${Date.now()}_${rnd}`;
    }

    function ensureSectionExists() {
        const current = Array.isArray(state.get()) ? state.get() : [];

        if (current.length > 0) {
            return current;
        }

        if (typeof state.addSection === 'function') {
            state.addSection();
            return Array.isArray(state.get()) ? state.get() : [];
        }

        const fallback = [
            {
                id: uid('sec'),
                blocks: [],
            },
        ];

        if (typeof state.set === 'function') {
            state.set(fallback);
            return fallback;
        }

        return fallback;
    }

    function buildDefaultPropsFromSchema(schema) {
        const props = {};

        (Array.isArray(schema) ? schema : []).forEach((field) => {
            if (!field || !field.name) return;

            if (field.type === 'repeater') {
                props[field.name] = Array.isArray(field.default) ? field.default : [];
                return;
            }

            if (field.type === 'group') {
                props[field.name] =
                    typeof field.default === 'object' && field.default !== null
                        ? field.default
                        : {};
                return;
            }

            props[field.name] = field.default ?? '';
        });

        return props;
    }

    function createComponentBlock(component) {
        const schema = Array.isArray(component.schema) ? component.schema : [];
        const props = buildDefaultPropsFromSchema(schema);

        return {
            id: uid('blk'),
            type: 'component',
            component_id: component.id,
            component_key: component.key || '',
            component_name: component.name || '',
            component_schema: schema,
            columns: 12,
            props,
            style: {},
        };
    }

    function mergeComponentBlockWithCatalog(block, component) {
        const schema = Array.isArray(component.schema) ? component.schema : [];
        const currentProps = block.props && typeof block.props === 'object' ? block.props : {};
        const defaultProps = buildDefaultPropsFromSchema(schema);

        return {
            ...block,
            component_id: component.id,
            component_key: component.key || '',
            component_name: component.name || '',
            component_schema: schema,
            props: {
                ...defaultProps,
                ...currentProps,
            },
        };
    }

    function syncExistingComponentBlocks(components) {
        const rows = Array.isArray(state.get()) ? state.get() : [];
        const map = new Map(
            (Array.isArray(components) ? components : []).map((c) => [Number(c.id), c])
        );

        let changed = false;

        const nextRows = rows.map((row) => {
            const blocks = Array.isArray(row.blocks) ? row.blocks : [];
            const nextBlocks = blocks.map((block) => {
                if (!block || block.type !== 'component' || !block.component_id) {
                    return block;
                }

                const component = map.get(Number(block.component_id));
                if (!component) {
                    return block;
                }

                const merged = mergeComponentBlockWithCatalog(block, component);

                const before = JSON.stringify(block);
                const after = JSON.stringify(merged);

                if (before !== after) {
                    changed = true;
                }

                return merged;
            });

            return {
                ...row,
                blocks: nextBlocks,
            };
        });

        if (changed && typeof state.set === 'function') {
            state.set(nextRows);
            saveToForm();
        }
    }

    async function fetchComponents({ q = '', activeOnly = true } = {}) {
        if (!window.PB_COMPONENTS_URL) return [];

        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (activeOnly) params.set('active_only', '1');

        const url = `${window.PB_COMPONENTS_URL}${params.toString() ? `?${params.toString()}` : ''}`;

        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const items = await res.json();
        return Array.isArray(items) ? items : [];
    }

    function insertComponentIntoBuilder(component) {
        const rows = ensureSectionExists();

        if (!Array.isArray(rows) || !rows.length) {
            return;
        }

        const firstSectionId = rows[0]?.id;
        if (!firstSectionId) {
            return;
        }

        if (typeof state.addBlock === 'function') {
            state.addBlock(firstSectionId, 'component', createComponentBlock(component));
        } else {
            return;
        }

        saveToForm();
        redraw();

        if (componentsModal) {
            componentsModal.hide();
        }
    }

    function renderComponentsList(items) {
        if (!componentsListEl) return;

        if (!Array.isArray(items) || !items.length) {
            componentsListEl.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-light border mb-0">
                        Nessun componente trovato.
                    </div>
                </div>
            `;
            return;
        }

        componentsListEl.innerHTML = items.map((item) => {
            const name = escapeHtml(item.name || '');
            const key = escapeHtml(item.key || '');
            const category = escapeHtml(item.category || '');
            const description = escapeHtml(item.description || '');
            const preview = item.preview_html && String(item.preview_html).trim() !== ''
                ? item.preview_html
                : `<div class="text-muted small">Anteprima non disponibile</div>`;

            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <div class="fw-semibold">${name}</div>
                                ${category ? `<div class="small text-muted">${category}</div>` : ''}
                                ${key ? `<div class="small"><code>${key}</code></div>` : ''}
                            </div>

                            <div class="border rounded bg-light p-2 mb-3 flex-grow-1" style="min-height:120px;">
                                ${preview}
                            </div>

                            ${description ? `<p class="small text-muted mb-3">${description}</p>` : ''}

                            <div class="mt-auto">
                                <button type="button"
                                        class="btn btn-primary btn-sm w-100"
                                        data-role="pb-insert-component"
                                        data-component-id="${item.id}">
                                    <i class="bi bi-plus-circle me-1"></i> Inserisci
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        componentsListEl.querySelectorAll('[data-role="pb-insert-component"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-component-id'));
                const component = items.find((x) => Number(x.id) === id);
                if (!component) return;
                insertComponentIntoBuilder(component);
            });
        });
    }

    async function loadComponents() {
        if (!componentsListEl || !window.PB_COMPONENTS_URL) return;

        componentsListEl.innerHTML = `
            <div class="col-12 text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Caricamento componenti…
            </div>
        `;

        const q = componentSearchEl?.value?.trim() || '';
        const category = componentCategoryEl?.value?.trim() || '';

        try {
            let items = await fetchComponents({ q, activeOnly: true });

            if (category) {
                const c = category.toLowerCase();
                items = items.filter((item) => String(item.category || '').toLowerCase().includes(c));
            }

            renderComponentsList(items);
        } catch (err) {
            console.error('Errore caricamento componenti:', err);

            componentsListEl.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        Impossibile caricare la libreria componenti.
                    </div>
                </div>
            `;
        }
    }

    function openComponentsModal() {
        if (!componentsModalEl || !window.bootstrap?.Modal) return;

        if (!componentsModal) {
            componentsModal = new window.bootstrap.Modal(componentsModalEl);
        }

        componentsModal.show();
        loadComponents();
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    addSecBtn?.addEventListener('click', () => {
        state.addSection();
        redraw();
    });

    addComponentBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        openComponentsModal();
    });

    componentSearchBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        loadComponents();
    });

    componentSearchEl?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadComponents();
        }
    });

    componentCategoryEl?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadComponents();
        }
    });

    previewBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        previewMode = !previewMode;
        updatePreviewButtonUI();
        redraw();
    });

    form?.addEventListener('submit', () => {
        saveToForm();
    });

    updatePreviewButtonUI();

    initBuilderSidebar({
        state,
        redraw,
        saveToForm,
        openComponentsModal,
    });

    (async function boot() {
        try {
            const components = await fetchComponents({ activeOnly: true });
            syncExistingComponentBlocks(components);
        } catch (err) {
            console.error('Errore sync componenti esistenti:', err);
        }

        redraw();
    })();
})();
