(function () {
    'use strict';

    const registry = window.R4EditorV5Registry;
    if (!registry) return;

    const icons = {
        list: '<svg viewBox="0 0 24 24"><path d="M9 7h11M9 12h11M9 17h11"/><circle cx="5" cy="7" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="17" r="1"/></svg>',
        check: '<svg viewBox="0 0 24 24"><path d="M8 12l3 3 5-6"/><circle cx="12" cy="12" r="9"/></svg>',
        article: '<svg viewBox="0 0 24 24"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>',
        quote: '<svg viewBox="0 0 24 24"><path d="M9 7H5v6h4v4H5M19 7h-4v6h4v4h-4"/></svg>',
        badge: '<svg viewBox="0 0 24 24"><path d="M12 3l3 4 5 1-3 4 .5 5-5.5-2-5.5 2 .5-5-3-4 5-1 3-4z"/></svg>'
    };

    registry.registerWidget({
        key: 'r4v5-pro-bullet-list',
        label: 'Elenco puntato',
        category: 'Contenuti',
        order: 10,
        media: icons.list,
        content: '<ul class="r4v5-pro-list-basic"><li>Primo punto elenco modificabile</li><li>Secondo punto elenco con testo descrittivo</li><li>Terzo punto elenco utile per contenuti editoriali</li></ul>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-number-list',
        label: 'Elenco numerato',
        category: 'Contenuti',
        order: 20,
        media: icons.list,
        content: '<ol class="r4v5-pro-list-basic"><li>Prima fase del processo</li><li>Seconda fase operativa</li><li>Terza fase di verifica e consegna</li></ol>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-check-list',
        label: 'Lista check avanzata',
        category: 'Contenuti',
        order: 30,
        media: icons.check,
        content: '<ul class="r4v5-pro-check-list" role="list"><li><span class="r4v5-pro-check" aria-hidden="true">✓</span><div><strong>Vantaggio principale</strong><p>Descrivi in modo chiaro il beneficio per il cliente o per il progetto.</p></div></li><li><span class="r4v5-pro-check" aria-hidden="true">✓</span><div><strong>Secondo vantaggio</strong><p>Aggiungi una spiegazione sintetica, concreta e orientata al valore.</p></div></li><li><span class="r4v5-pro-check" aria-hidden="true">✓</span><div><strong>Supporto continuo</strong><p>Indica un elemento di fiducia, assistenza o garanzia post-lancio.</p></div></li></ul>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-article-block',
        label: 'Articolo / testo lungo',
        category: 'Contenuti',
        order: 40,
        media: icons.article,
        content: '<section class="r4v5-pro-section"><article class="r4v5-pro-article"><span class="r4v5-pro-pill">Approfondimento</span><h2>Titolo articolo o contenuto editoriale</h2><p>Questo blocco è pensato per testi lunghi, pagine informative, landing SEO e contenuti editoriali. Puoi modificarlo dall’Inspector testo e arricchirlo con link, immagini o liste.</p><p>Usalo per spiegare un servizio, descrivere un processo, raccontare un progetto o costruire una sezione SEO più completa.</p></article></section>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-blockquote',
        label: 'Citazione / quote',
        category: 'Contenuti',
        order: 50,
        media: icons.quote,
        content: '<blockquote class="r4v5-pro-quote">“Una citazione forte aiuta a fissare un concetto chiave e rende la pagina più autorevole e memorabile.”</blockquote>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-badge',
        label: 'Badge / label',
        category: 'Contenuti',
        order: 60,
        media: icons.badge,
        content: '<span class="r4v5-pro-pill">Nuovo servizio</span>'
    });
})();
