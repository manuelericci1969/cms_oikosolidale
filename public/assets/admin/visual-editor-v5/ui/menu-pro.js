(function () {
    'use strict';

    var STYLE_ID = 'r4v5-menu-pro-style';
    var ROOT_READY_CLASS = 'r4v5-menu-pro-ready';
    var CATEGORY_ALL = '__all__';
    var selectedCategory = CATEGORY_ALL;

    var tabMeta = {
        widgets: { icon: '▦', title: 'Widget', hint: 'Blocchi e sezioni pronte' },
        inspector: { icon: '⚙', title: 'Inspector', hint: 'Modifica elemento selezionato' },
        page: { icon: '▣', title: 'Pagina', hint: 'Layout, sfondo e visibilità' },
        seo: { icon: '◎', title: 'SEO', hint: 'Titoli e meta dati' }
    };

    var categoryDescriptions = {
        Base: 'Elementi essenziali per costruire contenuti semplici.',
        Layout: 'Strutture, colonne e contenitori della pagina.',
        Marketing: 'Blocchi orientati a conversione e comunicazione.',
        Media: 'Immagini, gallery, slider e contenuti visuali.',
        Contenuti: 'Testi editoriali, articoli, quote e badge.',
        'Sezioni Pro': 'Sezioni landing professionali già composte.',
        'Sezioni Pro Extra': 'Blocchi avanzati: pricing, team, portfolio, contatti.',
        'Liste Pro': 'Elenchi puntati, check list e liste card.',
        'Preset Landing': 'Pagine preassemblate da usare come base.'
    };

    function qs(selector, context) {
        return (context || document).querySelector(selector);
    }

    function qsa(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function ensureStyle() {
        if (document.getElementById(STYLE_ID)) return;

        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = [
            '.r4v5-menu-pro-ready .r4v5-left-title{display:flex;align-items:center;justify-content:center;gap:8px;padding:13px 12px 9px;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#dbeafe}',
            '.r4v5-menu-pro-ready .r4v5-left-title::before{content:"";width:8px;height:8px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.12)}',
            '.r4v5-menu-pro-ready .r4v5-left-tabs{grid-template-columns:repeat(4,minmax(0,1fr));gap:0;background:#020617}',
            '.r4v5-menu-pro-ready .r4v5-left-tab{min-height:54px;padding:7px 4px 8px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;line-height:1.05}',
            '.r4v5-left-tab .r4v5-menu-tab-icon{font-size:15px;line-height:1;color:#93c5fd}',
            '.r4v5-left-tab .r4v5-menu-tab-text{font-size:10px;font-weight:950;color:inherit}',
            '.r4v5-left-tab .r4v5-menu-tab-hint{display:none}',
            '.r4v5-menu-pro-tools{display:grid;gap:10px;margin-bottom:12px}',
            '.r4v5-menu-pro-guide{padding:12px;border:1px solid rgba(59,130,246,.28);border-radius:14px;background:linear-gradient(135deg,rgba(13,110,253,.16),rgba(56,189,248,.08));color:#dbeafe}',
            '.r4v5-menu-pro-guide strong{display:block;font-size:12px;font-weight:950;color:#fff;margin-bottom:4px}',
            '.r4v5-menu-pro-guide span{display:block;font-size:11px;line-height:1.45;color:#bfdbfe}',
            '.r4v5-menu-pro-category-title{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8}',
            '.r4v5-menu-pro-count{font-size:10px;color:#cbd5e1;font-weight:900;text-transform:none;letter-spacing:0}',
            '.r4v5-menu-pro-categories{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;overflow:hidden;border:1px solid rgba(148,163,184,.14);background:#060b1a;scrollbar-width:thin}',
            '.r4v5-menu-pro-chip{position:relative;min-width:0;border:0;border-right:1px solid rgba(148,163,184,.12);border-bottom:1px solid rgba(148,163,184,.10);border-radius:0;background:#060b1a;color:#cbd5e1;padding:10px 5px 11px;font-size:10px;font-weight:950;line-height:1;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;letter-spacing:-.01em}',
            '.r4v5-menu-pro-chip:nth-child(2n){border-right:0}',
            '.r4v5-menu-pro-chip:after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:3px;background:transparent}',
            '.r4v5-menu-pro-chip:hover{background:#0f172a;color:#fff}',
            '.r4v5-menu-pro-chip.is-active{background:#101827;color:#fff;box-shadow:none}',
            '.r4v5-menu-pro-chip.is-active:after{background:#0d6efd}',
            '.r4v5-menu-pro-empty{display:none;margin-top:12px;padding:13px;border:1px dashed rgba(148,163,184,.32);border-radius:14px;color:#94a3b8;font-size:12px;line-height:1.5;text-align:center}',
            '.r4v5-menu-pro-empty.is-visible{display:block}',
            '.r4v5-menu-pro-ready .gjs-block-category.r4v5-menu-hidden,.r4v5-menu-pro-ready .gjs-block.r4v5-menu-hidden{display:none!important}',
            '.r4v5-menu-pro-ready .gjs-block-category .gjs-title{display:flex!important;align-items:center;justify-content:space-between;gap:8px;border-radius:10px;padding:9px 10px!important;background:#0f172a!important;border:1px solid rgba(148,163,184,.16)}',
            '.r4v5-menu-pro-ready .gjs-block-category .gjs-title::after{content:attr(data-r4v5-menu-desc);font-size:9px;font-weight:700;color:#64748b;text-align:right;max-width:125px;line-height:1.2;text-transform:none;letter-spacing:0}',
            '.r4v5-toolbar-group{display:flex;align-items:stretch;border-left:1px solid rgba(148,163,184,.16)}',
            '.r4v5-toolbar-group-label{display:flex;align-items:center;padding:0 8px;background:#020617;color:#64748b;font-size:9px;font-weight:950;letter-spacing:.08em;text-transform:uppercase;writing-mode:vertical-rl;transform:rotate(180deg);border-right:1px solid rgba(148,163,184,.10)}',
            '.r4v5-toolbar-group-items{display:flex;align-items:stretch;gap:0}',
            '.r4v5-toolbar-group .r4v5-btn{border-left:0}',
            '.r4v5-toolbar-group[data-r4v5-toolbar-group="Salvataggio"] .r4v5-toolbar-group-label{color:#93c5fd}',
            '.r4v5-toolbar-group[data-r4v5-toolbar-group="Salvataggio"]{box-shadow:inset 1px 0 0 rgba(13,110,253,.35)}',
            '.r4v5-menu-pro-panel-note{margin:0 0 12px;padding:10px 11px;border-radius:13px;background:#0f172a;border:1px solid rgba(148,163,184,.18);font-size:11px;line-height:1.45;color:#94a3b8}',
            '@media (max-width:1200px){.r4v5-toolbar-group-label{display:none}.r4v5-toolbar-group{border-left:1px solid rgba(148,163,184,.14)}}',
            '@media (max-width:860px){.r4v5-menu-pro-ready .r4v5-left-tab{min-height:48px}.r4v5-toolbar-group-label{display:none}}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function root() {
        return document.getElementById('r4EditorV5') || qs('.r4v5-editor');
    }

    function enhanceTabs() {
        qsa('[data-r4v5-left-tab]').forEach(function (button) {
            if (button.dataset.r4v5MenuEnhanced === '1') return;
            var key = button.dataset.r4v5LeftTab;
            var meta = tabMeta[key] || { icon: '•', title: button.textContent.trim(), hint: '' };
            button.dataset.r4v5MenuEnhanced = '1';
            button.title = meta.hint || meta.title;
            button.innerHTML = '<span class="r4v5-menu-tab-icon" aria-hidden="true">' + meta.icon + '</span><span class="r4v5-menu-tab-text">' + meta.title + '</span><span class="r4v5-menu-tab-hint">' + meta.hint + '</span>';
        });
    }

    function getToolbarGroupName(el) {
        if (!el || !el.matches || !el.matches('.r4v5-btn')) return null;
        var text = (el.textContent || '').trim().toLowerCase();
        var href = el.getAttribute('href') || '';
        var command = el.dataset.r4v5Command || '';

        if (el.dataset.r4v5SubmitStatus) return 'Salvataggio';
        if (el.dataset.r4v5Device) return 'Preview';
        if (command === 'undo' || command === 'redo') return 'Cronologia';
        if (command === 'media' || command === 'code') return 'Strumenti';
        if (el.hasAttribute('data-r4v5-toggle-right')) return 'Inspector';
        if (href || text.indexOf('dashboard') >= 0 || text.indexOf('esci') >= 0 || text.indexOf('fallback') >= 0 || text.indexOf('anteprima') >= 0) return 'Navigazione';
        return 'Altro';
    }

    function getOrCreateToolbarGroup(actions, name) {
        var group = qs('.r4v5-toolbar-group[data-r4v5-toolbar-group="' + name + '"]', actions);
        if (group) return group;

        group = document.createElement('div');
        group.className = 'r4v5-toolbar-group';
        group.dataset.r4v5ToolbarGroup = name;
        group.title = name;
        group.innerHTML = '<span class="r4v5-toolbar-group-label" aria-hidden="true">' + name + '</span><div class="r4v5-toolbar-group-items"></div>';
        actions.appendChild(group);
        return group;
    }

    function groupToolbar() {
        var actions = qs('.r4v5-actions');
        if (!actions) return;

        qsa(':scope > .r4v5-btn', actions).forEach(function (el) {
            var name = getToolbarGroupName(el);
            if (!name) return;
            var group = getOrCreateToolbarGroup(actions, name);
            var items = qs('.r4v5-toolbar-group-items', group);
            items.appendChild(el);
        });
    }

    function readCategoryName(category) {
        var title = qs('.gjs-title', category);
        return title ? (title.textContent || '').trim() : '';
    }

    function getCategories() {
        return qsa('.gjs-block-category').map(function (category) {
            return { el: category, name: readCategoryName(category) };
        }).filter(function (item) { return item.name; });
    }

    function ensureWidgetTools() {
        var panel = qs('[data-r4v5-left-panel="widgets"]');
        if (!panel || qs('.r4v5-menu-pro-tools', panel)) return;

        var search = qs('.r4v5-search', panel);
        var tools = document.createElement('div');
        tools.className = 'r4v5-menu-pro-tools';
        tools.innerHTML = [
            '<div class="r4v5-menu-pro-guide"><strong>Menu Pro</strong><span>Cerca, filtra per categoria e inserisci i widget senza perdere tempo nel pannello.</span></div>',
            '<div class="r4v5-menu-pro-category-title"><span>Categorie</span><span class="r4v5-menu-pro-count" data-r4v5-menu-count>0 widget</span></div>',
            '<div class="r4v5-menu-pro-categories" data-r4v5-menu-categories></div>',
            '<div class="r4v5-menu-pro-empty" data-r4v5-menu-empty>Nessun widget trovato. Prova a cambiare ricerca o categoria.</div>'
        ].join('');

        if (search) panel.insertBefore(tools, search);
        else panel.insertBefore(tools, panel.firstChild);

        if (search && !search.dataset.r4v5MenuBound) {
            search.dataset.r4v5MenuBound = '1';
            search.addEventListener('input', applyWidgetFilters);
            search.placeholder = 'Cerca widget, sezioni, liste...';
        }
    }

    function ensurePanelNotes() {
        var notes = {
            inspector: 'Seleziona un elemento nel canvas per modificarne contenuto, stile, animazioni e proprietà.',
            page: 'Qui gestisci impostazioni generali: larghezza, gutter, sfondo, visibilità e homepage.',
            seo: 'Compila title, description e keyword solo dopo aver definito contenuto e obiettivo della pagina.'
        };

        Object.keys(notes).forEach(function (key) {
            var panel = qs('[data-r4v5-left-panel="' + key + '"]');
            if (!panel || qs('.r4v5-menu-pro-panel-note', panel)) return;
            var note = document.createElement('p');
            note.className = 'r4v5-menu-pro-panel-note';
            note.textContent = notes[key];
            var first = qs('.r4v5-panel-title', panel) || panel.firstChild;
            if (first && first.nextSibling) panel.insertBefore(note, first.nextSibling);
            else panel.insertBefore(note, panel.firstChild);
        });
    }

    function rebuildCategoryChips() {
        ensureWidgetTools();
        var holder = qs('[data-r4v5-menu-categories]');
        if (!holder) return;

        var categories = getCategories();
        var names = categories.map(function (item) { return item.name; });
        var currentSignature = holder.dataset.signature || '';
        var nextSignature = names.join('|');
        if (currentSignature === nextSignature) return;

        holder.dataset.signature = nextSignature;
        holder.innerHTML = '';

        var all = document.createElement('button');
        all.type = 'button';
        all.className = 'r4v5-menu-pro-chip';
        all.dataset.r4v5MenuCategory = CATEGORY_ALL;
        all.textContent = 'Tutte';
        holder.appendChild(all);

        names.forEach(function (name) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'r4v5-menu-pro-chip';
            chip.dataset.r4v5MenuCategory = name;
            chip.textContent = name;
            holder.appendChild(chip);
        });

        holder.addEventListener('click', function (event) {
            var chip = event.target.closest('[data-r4v5-menu-category]');
            if (!chip) return;
            selectedCategory = chip.dataset.r4v5MenuCategory || CATEGORY_ALL;
            applyWidgetFilters();
        });

        applyWidgetFilters();
    }

    function decorateCategoryTitles() {
        getCategories().forEach(function (item) {
            var title = qs('.gjs-title', item.el);
            if (!title) return;
            title.dataset.r4v5MenuDesc = categoryDescriptions[item.name] || 'Widget disponibili';
        });
    }

    function applyWidgetFilters() {
        var search = qs('[data-r4v5-left-panel="widgets"] .r4v5-search');
        var query = search ? String(search.value || '').trim().toLowerCase() : '';
        var visibleBlocks = 0;

        qsa('[data-r4v5-menu-category]').forEach(function (chip) {
            chip.classList.toggle('is-active', (chip.dataset.r4v5MenuCategory || CATEGORY_ALL) === selectedCategory);
        });

        getCategories().forEach(function (category) {
            var categoryMatches = selectedCategory === CATEGORY_ALL || category.name === selectedCategory;
            var categoryVisibleBlocks = 0;

            qsa('.gjs-block', category.el).forEach(function (block) {
                var label = (block.textContent || '').trim().toLowerCase();
                var matches = categoryMatches && (!query || label.indexOf(query) >= 0 || category.name.toLowerCase().indexOf(query) >= 0);
                block.classList.toggle('r4v5-menu-hidden', !matches);
                if (matches) {
                    categoryVisibleBlocks += 1;
                    visibleBlocks += 1;
                }
            });

            category.el.classList.toggle('r4v5-menu-hidden', categoryVisibleBlocks === 0);
        });

        var count = qs('[data-r4v5-menu-count]');
        if (count) count.textContent = visibleBlocks + (visibleBlocks === 1 ? ' widget' : ' widget');

        var empty = qs('[data-r4v5-menu-empty]');
        if (empty) empty.classList.toggle('is-visible', visibleBlocks === 0);
    }

    function observeDynamicUi() {
        var target = qs('.r4v5-editor') || document.body;
        if (!target || target.dataset.r4v5MenuObserver === '1') return;
        target.dataset.r4v5MenuObserver = '1';

        var scheduled = false;
        var observer = new MutationObserver(function () {
            if (scheduled) return;
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                groupToolbar();
                rebuildCategoryChips();
                decorateCategoryTitles();
                applyWidgetFilters();
            });
        });
        observer.observe(target, { childList: true, subtree: true });
    }

    function boot() {
        ensureStyle();
        var editorRoot = root();
        if (editorRoot) editorRoot.classList.add(ROOT_READY_CLASS);
        enhanceTabs();
        groupToolbar();
        ensureWidgetTools();
        ensurePanelNotes();
        rebuildCategoryChips();
        decorateCategoryTitles();
        observeDynamicUi();

        window.setTimeout(function () {
            groupToolbar();
            rebuildCategoryChips();
            decorateCategoryTitles();
            applyWidgetFilters();
        }, 250);

        window.setTimeout(function () {
            groupToolbar();
            rebuildCategoryChips();
            decorateCategoryTitles();
            applyWidgetFilters();
        }, 900);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
