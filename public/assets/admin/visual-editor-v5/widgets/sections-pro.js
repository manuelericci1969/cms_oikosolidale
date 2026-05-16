(function () {
    'use strict';

    const registry = window.R4EditorV5Registry;
    if (!registry) return;

    const imgHero = 'https://placehold.co/900x700?text=Hero+Image';
    const imgCard = 'https://placehold.co/700x420?text=Servizio';
    const imgVertical = 'https://placehold.co/760x860?text=Consulenza';

    const icons = {
        hero: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h7M7 14h4M16 15l2-2 2 2"/></svg>',
        services: '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" rx="2"/><rect x="13" y="4" width="7" height="7" rx="2"/><rect x="4" y="13" width="7" height="7" rx="2"/><rect x="13" y="13" width="7" height="7" rx="2"/></svg>',
        why: '<svg viewBox="0 0 24 24"><path d="M8 12l3 3 5-6"/><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/></svg>',
        process: '<svg viewBox="0 0 24 24"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/><path d="M7 12h3M14 12h3"/></svg>',
        faq: '<svg viewBox="0 0 24 24"><path d="M12 18h.01M9.5 9a2.5 2.5 0 115 0c0 2-2.5 2-2.5 4"/><circle cx="12" cy="12" r="9"/></svg>',
        cta: '<svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="3"/><path d="M8 12h7M13 9l3 3-3 3"/></svg>'
    };

    registry.registerWidget({
        key: 'r4v5-pro-hero',
        label: 'Hero Pro',
        category: 'Sezioni Pro',
        order: 10,
        media: icons.hero,
        content: '<section class="r4v5-pro-hero" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true"><div class="r4v5-pro-inner r4v5-pro-hero-grid"><div><span class="r4v5-pro-eyebrow">🚀 Software, Web & App</span><h1 class="r4v5-pro-title r4v5-pro-hero-title">Trasformiamo la tua idea <span class="r4v5-pro-accent">in una soluzione digitale</span></h1><p class="r4v5-pro-text">Sviluppiamo siti web professionali, software su misura, app mobile e strategie digitali per aziende, hotel, professionisti e attività locali.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Richiedi consulenza</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-ghost">Scopri i servizi</a></div><div class="r4v5-pro-trust"><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Nessun costo nascosto</span><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Prima consulenza gratuita</span><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Supporto post-lancio</span></div></div><div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="' + imgHero + '" alt="Team al lavoro su una soluzione digitale" width="900" height="700" loading="eager"></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-services',
        label: 'Servizi Pro',
        category: 'Sezioni Pro',
        order: 20,
        media: icons.services,
        content: '<section class="r4v5-pro-section"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">I nostri servizi</span><h2 class="r4v5-pro-title">Tutto il digitale che ti serve sotto un unico tetto</h2><p class="r4v5-pro-text">Dalle landing page ai gestionali complessi: sviluppiamo soluzioni digitali su misura per far crescere la tua attività.</p></header><div class="r4v5-pro-grid r4v5-pro-grid-4"><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+Web" alt="Siti web professionali" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">WEB</div><h3 class="r4v5-pro-card-title">Siti Web Professionali</h3><p class="r4v5-pro-card-text">Siti aziendali, landing page e portali SEO, mobile-first e orientati alla conversione.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+CRM" alt="Software gestionali" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">CRM</div><h3 class="r4v5-pro-card-title">Software & Gestionali</h3><p class="r4v5-pro-card-text">CRM, dashboard, backoffice e piattaforme costruite intorno ai tuoi processi reali.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+App" alt="App mobile" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">APP</div><h3 class="r4v5-pro-card-title">App Mobile</h3><p class="r4v5-pro-card-text">Applicazioni iOS e Android con integrazioni cloud, IoT, BLE e geolocalizzazione.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+Social" alt="Social media" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">ADV</div><h3 class="r4v5-pro-card-title">Social & Contenuti</h3><p class="r4v5-pro-card-text">Strategia editoriale, creatività visiva e contenuti per costruire autorevolezza.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-why',
        label: 'Perché sceglierci',
        category: 'Sezioni Pro',
        order: 30,
        media: icons.why,
        content: '<section class="r4v5-pro-section" style="background:#f8fafc;"><div class="r4v5-pro-inner r4v5-pro-split"><div><span class="r4v5-pro-eyebrow">Perché sceglierci</span><h2 class="r4v5-pro-title">Non solo sviluppiamo. Pensiamo insieme a te.</h2><p class="r4v5-pro-text">Lavoriamo come un’estensione del tuo team: ascoltiamo, progettiamo, prototipiamo e misuriamo prima di dichiarare il successo.</p><ul class="r4v5-pro-check-list" role="list"><li><span class="r4v5-pro-check">✓</span><div><strong>Approccio su misura</strong><p>Nessun template preconfezionato: ogni progetto nasce dall’analisi delle tue esigenze.</p></div></li><li><span class="r4v5-pro-check">✓</span><div><strong>Consegne rispettate</strong><p>Rilasci progressivi, test continui e roadmap condivisa.</p></div></li><li><span class="r4v5-pro-check">✓</span><div><strong>Supporto continuativo</strong><p>Restiamo al tuo fianco per aggiornamenti, nuove funzionalità e ottimizzazioni.</p></div></li></ul><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Parla con un esperto</a></div><div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="' + imgVertical + '" alt="Consulenza digitale" loading="lazy"><div class="r4v5-pro-floating-badge"><div class="r4v5-pro-score">4.8 ★</div><p>valutazione media dei clienti</p></div></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-process',
        label: 'Processo 4 step',
        category: 'Sezioni Pro',
        order: 40,
        media: icons.process,
        content: '<section class="r4v5-pro-section"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">Il nostro metodo</span><h2 class="r4v5-pro-title">4 fasi. Zero improvvisazione.</h2><p class="r4v5-pro-text">Un processo chiaro, tracciabile e collaborativo che ti tiene aggiornato su ogni avanzamento.</p></header><div class="r4v5-pro-steps"><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">01</div><h3>Analisi & Strategia</h3><p>Definizione obiettivi, requisiti, architettura e roadmap del progetto.</p></div><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">02</div><h3>Design & Prototipo</h3><p>Wireframe e prototipi per validare UX e UI prima dello sviluppo.</p></div><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">03</div><h3>Sviluppo Iterativo</h3><p>Implementazione per sprint, test progressivi e rilasci verificabili.</p></div><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">04</div><h3>Lancio & Supporto</h3><p>Messa online, collaudo finale e supporto post-lancio.</p></div></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-faq-accordion',
        label: 'FAQ Accordion',
        category: 'Sezioni Pro',
        order: 50,
        media: icons.faq,
        content: '<section class="r4v5-pro-section" style="background:#f8fafc;"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">FAQ</span><h2 class="r4v5-pro-title">Domande frequenti</h2><p class="r4v5-pro-text">Tutto quello che vuoi sapere prima di iniziare a lavorare con noi.</p></header><div class="r4v5-pro-faq-list" data-r4v5-faq-accordion="1" data-r4v5-faq-single="true"><div class="r4v5-pro-faq-item is-open"><button type="button" class="r4v5-pro-faq-question" aria-expanded="true">Quanto costa sviluppare un sito web professionale?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Il costo dipende dalla complessità, dalle funzionalità richieste e dal livello di personalizzazione. La consulenza iniziale aiuta a definire un preventivo chiaro.</p></div></div><div class="r4v5-pro-faq-item"><button type="button" class="r4v5-pro-faq-question" aria-expanded="false">Lavorate solo in Sardegna?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Operiamo da Olbia e lavoriamo con clienti in Sardegna, in tutta Italia e anche da remoto.</p></div></div><div class="r4v5-pro-faq-item"><button type="button" class="r4v5-pro-faq-question" aria-expanded="false">Offrite assistenza dopo il lancio?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Sì, possiamo seguire aggiornamenti, manutenzione, evoluzioni funzionali e ottimizzazioni periodiche.</p></div></div></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-pro-final-cta',
        label: 'CTA finale Pro',
        category: 'Sezioni Pro',
        order: 60,
        media: icons.cta,
        content: '<section class="r4v5-pro-cta" data-r4-animation="zoom-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="800" data-r4-animation-once="true"><div class="r4v5-pro-inner"><span class="r4v5-pro-eyebrow" style="background:rgba(255,255,255,.16);color:#fff;">Inizia oggi</span><h2 class="r4v5-pro-title">Hai un progetto in mente? Parliamone senza impegno.</h2><p class="r4v5-pro-text">La prima consulenza è gratuita. In 30 minuti capiamo le tue esigenze e ti proponiamo un percorso concreto.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Richiedi consulenza gratuita</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-outline-white">Guarda i nostri lavori</a></div><p class="r4v5-pro-note">Risposta entro 24 ore lavorative · Nessun impegno · 100% gratuita</p></div></section>'
    });
})();
