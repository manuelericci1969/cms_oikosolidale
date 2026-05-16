// public/pb/sidebar.js

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function makeButton({ title, subtitle = '', icon = 'bi bi-plus-square', onClick }) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pb-sidebar-item';
    btn.innerHTML = `
        <span class="pb-sidebar-item-icon"><i class="${escapeHtml(icon)}"></i></span>
        <span class="pb-sidebar-item-text">
            <strong>${escapeHtml(title)}</strong>
            ${subtitle ? `<small>${escapeHtml(subtitle)}</small>` : ''}
        </span>
    `;
    btn.addEventListener('click', (event) => {
        event.preventDefault();
        if (typeof onClick === 'function') onClick();
    });
    return btn;
}

function firstSectionId(state) {
    const rows = Array.isArray(state.get()) ? state.get() : [];
    if (rows.length && rows[0]?.id) return rows[0].id;

    if (typeof state.addSection === 'function') {
        const created = state.addSection();
        if (created) return created;
    }

    const nextRows = Array.isArray(state.get()) ? state.get() : [];
    return nextRows[0]?.id || null;
}

function insertBlock(state, type, data = {}, redraw, saveToForm) {
    const sectionId = firstSectionId(state);
    if (!sectionId || typeof state.addBlock !== 'function') return;

    state.addBlock(sectionId, type, data);

    if (typeof saveToForm === 'function') saveToForm();
    if (typeof redraw === 'function') redraw();
}

function insertRichText(state, html, redraw, saveToForm) {
    insertBlock(state, 'richtext', { html }, redraw, saveToForm);
}

function setActiveTab(root, tabName) {
    root.querySelectorAll('[data-pb-sidebar-tab]').forEach((btn) => {
        btn.classList.toggle('is-active', btn.dataset.pbSidebarTab === tabName);
    });

    root.querySelectorAll('[data-pb-sidebar-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.pbSidebarPanel !== tabName;
    });
}

function movePageSettingsIntoSidebar(pagePanel) {
    const existingSettings = document.getElementById('settingsContent');

    if (existingSettings) {
        existingSettings.classList.add('pb-sidebar-page-settings');
        existingSettings.style.display = 'block';
        pagePanel.appendChild(existingSettings);
        return;
    }

    const empty = document.createElement('div');
    empty.className = 'alert alert-light border small mb-0';
    empty.textContent = 'Impostazioni pagina non trovate in questa vista.';
    pagePanel.appendChild(empty);
}

function buildElementsPanel(panel, ctx) {
    const { state, redraw, saveToForm } = ctx;

    const groups = [
        {
            title: 'Layout',
            items: [
                {
                    title: 'Contenitore',
                    subtitle: 'Sezione vuota',
                    icon: 'bi bi-bounding-box',
                    onClick: () => {
                        if (typeof state.addSection === 'function') {
                            state.addSection();
                            saveToForm?.();
                            redraw?.();
                        }
                    },
                },
                {
                    title: 'Griglia',
                    subtitle: '2 colonne',
                    icon: 'bi bi-grid-3x3-gap',
                    onClick: () => {
                        const sectionId = firstSectionId(state);
                        if (!sectionId) return;
                        state.addBlock(sectionId, 'richtext', { columns: 6, html: '<h3>Colonna 1</h3><p>Testo della prima colonna…</p>' });
                        state.addBlock(sectionId, 'richtext', { columns: 6, html: '<h3>Colonna 2</h3><p>Testo della seconda colonna…</p>' });
                        saveToForm?.();
                        redraw?.();
                    },
                },
            ],
        },
        {
            title: 'Base',
            items: [
                {
                    title: 'Titolo',
                    subtitle: 'Heading H2',
                    icon: 'bi bi-type-h2',
                    onClick: () => insertRichText(state, '<h2>Nuovo titolo</h2>', redraw, saveToForm),
                },
                {
                    title: 'Editor di testo',
                    subtitle: 'Rich text',
                    icon: 'bi bi-text-paragraph',
                    onClick: () => insertRichText(state, '<p>Scrivi qui il tuo testo…</p>', redraw, saveToForm),
                },
                {
                    title: 'Immagine',
                    subtitle: 'Media picker',
                    icon: 'bi bi-image',
                    onClick: () => insertBlock(state, 'image', {}, redraw, saveToForm),
                },
                {
                    title: 'Pulsante',
                    subtitle: 'CTA/link',
                    icon: 'bi bi-cursor',
                    onClick: () => insertRichText(
                        state,
                        '<p><a class="btn btn-primary" href="#">Testo pulsante</a></p>',
                        redraw,
                        saveToForm,
                    ),
                },
                {
                    title: 'Divisore',
                    subtitle: 'Linea orizzontale',
                    icon: 'bi bi-hr',
                    onClick: () => insertRichText(state, '<hr>', redraw, saveToForm),
                },
                {
                    title: 'Distanziatore',
                    subtitle: 'Spazio verticale',
                    icon: 'bi bi-arrows-vertical',
                    onClick: () => insertRichText(state, '<div style="height:48px"></div>', redraw, saveToForm),
                },
                {
                    title: 'Video',
                    subtitle: 'URL YouTube/Vimeo',
                    icon: 'bi bi-play-btn',
                    onClick: () => insertBlock(state, 'video', {}, redraw, saveToForm),
                },
                {
                    title: 'Google Maps',
                    subtitle: 'Embed mappa',
                    icon: 'bi bi-geo-alt',
                    onClick: () => insertRichText(
                        state,
                        '<div class="ratio ratio-16x9"><iframe src="https://www.google.com/maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>',
                        redraw,
                        saveToForm,
                    ),
                },
            ],
        },
    ];

    groups.forEach((group) => {
        const section = document.createElement('section');
        section.className = 'pb-sidebar-group';
        section.innerHTML = `<h4>${escapeHtml(group.title)}</h4>`;

        const grid = document.createElement('div');
        grid.className = 'pb-sidebar-grid';

        group.items.forEach((item) => grid.appendChild(makeButton(item)));
        section.appendChild(grid);
        panel.appendChild(section);
    });
}

function buildWidgetsPanel(panel, ctx) {
    const { state, redraw, saveToForm, openComponentsModal } = ctx;

    const search = document.createElement('div');
    search.className = 'pb-sidebar-search';
    search.innerHTML = '<i class="bi bi-search"></i><input type="search" placeholder="Cerca widget…" aria-label="Cerca widget">';
    panel.appendChild(search);

    const library = makeButton({
        title: 'Libreria componenti',
        subtitle: 'Widget salvati nel CMS',
        icon: 'bi bi-box-seam',
        onClick: () => {
            if (typeof openComponentsModal === 'function') openComponentsModal();
        },
    });
    panel.appendChild(library);

    const group = document.createElement('section');
    group.className = 'pb-sidebar-group';
    group.innerHTML = '<h4>Preconfigurati</h4>';

    const grid = document.createElement('div');
    grid.className = 'pb-sidebar-grid';

    const widgets = [
        {
            title: 'Hero',
            subtitle: 'Titolo + testo + CTA',
            icon: 'bi bi-stars',
            html: '<section class="py-5"><p class="text-uppercase small text-muted mb-2">Eyebrow</p><h1>Hero title della pagina</h1><p class="lead">Descrizione breve della proposta di valore.</p><p><a class="btn btn-primary" href="#">Call to action</a></p></section>',
        },
        {
            title: 'CTA Finale',
            subtitle: 'Invito all’azione',
            icon: 'bi bi-megaphone',
            html: '<section class="p-4 rounded bg-light text-center"><h2>Pronto a iniziare?</h2><p>Inserisci qui il messaggio finale della pagina.</p><p><a class="btn btn-primary" href="#">Contattaci</a></p></section>',
        },
        {
            title: 'FAQ',
            subtitle: 'Domande frequenti',
            icon: 'bi bi-question-circle',
            html: '<section><h2>Domande frequenti</h2><h3>Domanda 1</h3><p>Risposta alla prima domanda.</p><h3>Domanda 2</h3><p>Risposta alla seconda domanda.</p></section>',
        },
        {
            title: 'Stats',
            subtitle: 'Numeri in evidenza',
            icon: 'bi bi-bar-chart',
            html: '<div class="row text-center"><div class="col-md-4"><h2>10+</h2><p>Progetti</p></div><div class="col-md-4"><h2>24/7</h2><p>Supporto</p></div><div class="col-md-4"><h2>100%</h2><p>Personalizzato</p></div></div>',
        },
        {
            title: 'Gallery',
            subtitle: 'Galleria immagini',
            icon: 'bi bi-images',
            onClick: () => insertBlock(state, 'gallery', {}, redraw, saveToForm),
        },
        {
            title: 'Loghi',
            subtitle: 'Carosello loghi',
            icon: 'bi bi-sliders',
            onClick: () => insertBlock(state, 'logo_carousel', {}, redraw, saveToForm),
        },
    ];

    widgets.forEach((widget) => {
        grid.appendChild(makeButton({
            title: widget.title,
            subtitle: widget.subtitle,
            icon: widget.icon,
            onClick: widget.onClick || (() => insertRichText(state, widget.html, redraw, saveToForm)),
        }));
    });

    group.appendChild(grid);
    panel.appendChild(group);
}

export function initBuilderSidebar(ctx = {}) {
    const { state } = ctx;
    const builderContainer = document.getElementById('builderContainer');
    if (!builderContainer || !state) return;

    const builderCard = builderContainer.closest('.card') || builderContainer;
    if (!builderCard || builderCard.closest('.pb-v4-shell')) return;

    document.body.classList.add('pb-v4-sidebar-enhanced');

    const shell = document.createElement('div');
    shell.className = 'pb-v4-shell';

    const sidebar = document.createElement('aside');
    sidebar.className = 'pb-v4-sidebar';
    sidebar.innerHTML = `
        <div class="pb-sidebar-title">Elementi</div>
        <div class="pb-sidebar-tabs" role="tablist">
            <button type="button" data-pb-sidebar-tab="widgets">Widget</button>
            <button type="button" data-pb-sidebar-tab="page">Pagina</button>
            <button type="button" data-pb-sidebar-tab="elements">Elementi</button>
        </div>
        <div class="pb-sidebar-panels">
            <div data-pb-sidebar-panel="widgets"></div>
            <div data-pb-sidebar-panel="page" hidden></div>
            <div data-pb-sidebar-panel="elements" hidden></div>
        </div>
    `;

    const canvas = document.createElement('div');
    canvas.className = 'pb-v4-canvas';

    builderCard.parentNode.insertBefore(shell, builderCard);
    shell.appendChild(sidebar);
    shell.appendChild(canvas);
    canvas.appendChild(builderCard);

    const widgetsPanel = sidebar.querySelector('[data-pb-sidebar-panel="widgets"]');
    const pagePanel = sidebar.querySelector('[data-pb-sidebar-panel="page"]');
    const elementsPanel = sidebar.querySelector('[data-pb-sidebar-panel="elements"]');

    if (widgetsPanel) buildWidgetsPanel(widgetsPanel, ctx);
    if (pagePanel) movePageSettingsIntoSidebar(pagePanel);
    if (elementsPanel) buildElementsPanel(elementsPanel, ctx);

    sidebar.querySelectorAll('[data-pb-sidebar-tab]').forEach((btn) => {
        btn.addEventListener('click', () => setActiveTab(sidebar, btn.dataset.pbSidebarTab));
    });

    setActiveTab(sidebar, 'widgets');
}
