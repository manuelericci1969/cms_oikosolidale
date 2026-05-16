export function initFloatingPanels(editor) {
    const stylesContainer = document.getElementById('gjs-styles');
    const traitsContainer = document.getElementById('gjs-traits');

    if (!stylesContainer || !traitsContainer) {
        console.warn('Floating panels: container styles/traits non trovati.');
        return null;
    }

    const STORAGE_KEY = 'r4_v3_floating_panels_v1';

    const state = loadState();

    const panels = {
        styles: createPanel({
            id: 'r4-floating-styles',
            title: 'Stili',
            x: state.styles?.x ?? window.innerWidth - 430,
            y: state.styles?.y ?? 120,
            width: state.styles?.width ?? 360,
            height: state.styles?.height ?? 420,
            minimized: !!state.styles?.minimized,
            visible: state.styles?.visible !== false,
            bodyNode: stylesContainer
        }),
        traits: createPanel({
            id: 'r4-floating-traits',
            title: 'Proprietà',
            x: state.traits?.x ?? window.innerWidth - 410,
            y: state.traits?.y ?? 570,
            width: state.traits?.width ?? 340,
            height: state.traits?.height ?? 300,
            minimized: !!state.traits?.minimized,
            visible: state.traits?.visible !== false,
            bodyNode: traitsContainer
        })
    };

    let zCounter = 3000;

    Object.values(panels).forEach((panel) => {
        bringToFront(panel);
        bindPanel(panel);
        panel.sync();
    });

    function bringToFront(panel) {
        zCounter += 1;
        panel.root.style.zIndex = String(zCounter);
    }

    function saveAll() {
        const next = {
            styles: panels.styles.serialize(),
            traits: panels.traits.serialize()
        };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        } catch (e) {
            console.warn('Floating panels: impossibile salvare stato', e);
        }
    }

    function loadState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return {};
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function clampPanel(panel) {
        const margin = 12;
        const rect = panel.root.getBoundingClientRect();
        const maxX = Math.max(margin, window.innerWidth - rect.width - margin);
        const maxY = Math.max(margin, window.innerHeight - 56);

        panel.x = Math.max(margin, Math.min(panel.x, maxX));
        panel.y = Math.max(margin, Math.min(panel.y, maxY));

        panel.root.style.left = `${panel.x}px`;
        panel.root.style.top = `${panel.y}px`;
    }

    function bindPanel(panel) {
        const header = panel.header;
        const closeBtn = panel.closeBtn;
        const minBtn = panel.minBtn;
        const dockBtn = panel.dockBtn;

        let drag = null;

        panel.root.addEventListener('mousedown', () => {
            bringToFront(panel);
        });

        header.addEventListener('mousedown', (event) => {
            if (
                event.target.closest('.r4-floating-panel__actions') ||
                event.target.closest('button')
            ) {
                return;
            }

            bringToFront(panel);

            const rect = panel.root.getBoundingClientRect();
            drag = {
                startX: event.clientX,
                startY: event.clientY,
                originX: rect.left,
                originY: rect.top
            };

            document.body.classList.add('r4-floating-panels-dragging');
            event.preventDefault();
        });

        window.addEventListener('mousemove', (event) => {
            if (!drag) return;

            const dx = event.clientX - drag.startX;
            const dy = event.clientY - drag.startY;

            panel.x = drag.originX + dx;
            panel.y = drag.originY + dy;

            clampPanel(panel);
        });

        window.addEventListener('mouseup', () => {
            if (!drag) return;
            drag = null;
            document.body.classList.remove('r4-floating-panels-dragging');
            saveAll();
        });

        closeBtn.addEventListener('click', () => {
            panel.visible = false;
            panel.sync();
            saveAll();
        });

        minBtn.addEventListener('click', () => {
            panel.minimized = !panel.minimized;
            panel.sync();
            saveAll();
        });

        dockBtn.addEventListener('click', () => {
            panel.x = window.innerWidth - panel.root.offsetWidth - 18;
            panel.y = panel.kind === 'styles' ? 120 : 570;
            clampPanel(panel);
            saveAll();
        });

        panel.root.addEventListener('transitionend', () => {
            clampPanel(panel);
        });
    }

    function createPanel(config) {
        const root = document.createElement('div');
        root.className = 'r4-floating-panel';
        root.id = config.id;
        root.innerHTML = `
            <div class="r4-floating-panel__header">
                <div class="r4-floating-panel__title">${escapeHtml(config.title)}</div>
                <div class="r4-floating-panel__actions">
                    <button type="button" class="r4-floating-panel__btn" data-action="dock" title="Aggancia a destra">↗</button>
                    <button type="button" class="r4-floating-panel__btn" data-action="min" title="Riduci">—</button>
                    <button type="button" class="r4-floating-panel__btn r4-floating-panel__btn--close" data-action="close" title="Chiudi">×</button>
                </div>
            </div>
            <div class="r4-floating-panel__body"></div>
        `;

        document.body.appendChild(root);

        const body = root.querySelector('.r4-floating-panel__body');
        const header = root.querySelector('.r4-floating-panel__header');
        const closeBtn = root.querySelector('[data-action="close"]');
        const minBtn = root.querySelector('[data-action="min"]');
        const dockBtn = root.querySelector('[data-action="dock"]');

        body.appendChild(config.bodyNode);

        const panel = {
            kind: config.id.includes('styles') ? 'styles' : 'traits',
            root,
            body,
            header,
            closeBtn,
            minBtn,
            dockBtn,
            x: config.x,
            y: config.y,
            width: config.width,
            height: config.height,
            visible: config.visible,
            minimized: config.minimized,
            sync() {
                root.style.left = `${this.x}px`;
                root.style.top = `${this.y}px`;
                root.style.width = `${this.width}px`;
                root.style.height = this.minimized ? 'auto' : `${this.height}px`;

                root.classList.toggle('is-hidden', !this.visible);
                root.classList.toggle('is-minimized', !!this.minimized);

                body.style.display = this.minimized ? 'none' : '';
            },
            serialize() {
                return {
                    x: this.x,
                    y: this.y,
                    width: this.width,
                    height: this.height,
                    visible: this.visible,
                    minimized: this.minimized
                };
            }
        };

        return panel;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function showPanel(name) {
        const panel = panels[name];
        if (!panel) return;
        panel.visible = true;
        panel.minimized = false;
        panel.sync();
        bringToFront(panel);
        clampPanel(panel);
        saveAll();
    }

    function focusPanel(name) {
        const panel = panels[name];
        if (!panel) return;
        showPanel(name);
    }

    function refreshLayout() {
        Object.values(panels).forEach((panel) => {
            clampPanel(panel);
        });
    }

    window.addEventListener('resize', refreshLayout);

    editor.on('component:selected', () => {
        focusPanel('styles');
        focusPanel('traits');
    });

    return {
        panels,
        showPanel,
        focusPanel,
        refreshLayout
    };
}
