(function () {
    'use strict';

    const registry = window.R4EditorV5Registry;
    if (!registry) return;

    const icon = '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M7 10h10M7 14h7"/></svg>';
    const imgHero = 'https://placehold.co/900x700?text=Hero+Image';
    const imgCard = 'https://placehold.co/700x420?text=Servizio';
    const imgVertical = 'https://placehold.co/760x860?text=Consulenza';

    function register(item) {
        registry.registerWidget(Object.assign({ media: icon }, item));
    }

    // ==============================
    // Widget Marketing esistenti
    // ==============================
    register({
        key: 'r4v5-section-header',
        label: 'Section header',
        category: 'Marketing',
        order: 10,
        content: '<div style="max-width:860px;margin:0 auto 42px;text-align:center;"><span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Sezione</span><h2 style="font-size:clamp(34px,5vw,56px);line-height:1.08;font-weight:900;letter-spacing:-.04em;margin:0 0 16px;color:#111827;">Titolo della sezione</h2><p style="font-size:18px;line-height:1.75;color:#64748b;margin:0;">Descrizione sintetica e orientata alla conversione.</p></div>'
    });

    register({
        key: 'r4v5-feature-card',
        label: 'Feature card',
        category: 'Marketing',
        order: 20,
        content: '<div style="padding:30px;border-radius:26px;background:#ffffff;border:1px solid #e5e7eb;box-shadow:0 18px 42px rgba(15,23,42,.08);"><div style="width:48px;height:48px;border-radius:16px;background:#eaf3ff;color:#0d6efd;display:flex;align-items:center;justify-content:center;font-weight:900;margin-bottom:18px;">✓</div><h3 style="font-size:24px;font-weight:900;margin:0 0 12px;color:#111827;">Funzionalità chiave</h3><p style="font-size:16px;line-height:1.75;color:#64748b;margin:0;">Descrivi il vantaggio principale per il cliente.</p></div>'
    });

    register({
        key: 'r4v5-product-card',
        label: 'Product card',
        category: 'Marketing',
        order: 30,
        content: '<article style="overflow:hidden;border-radius:28px;background:#ffffff;border:1px solid #e5e7eb;box-shadow:0 20px 50px rgba(15,23,42,.1);"><img src="https://placehold.co/900x520?text=Prodotto" alt="Prodotto" style="width:100%;height:auto;display:block;"><div style="padding:28px;"><span style="display:inline-flex;margin-bottom:12px;padding:7px 10px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Soluzione</span><h3 style="font-size:26px;font-weight:900;margin:0 0 12px;color:#111827;">Nome prodotto</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0 0 18px;">Descrizione breve del prodotto o servizio.</p><a href="#" style="display:inline-flex;padding:12px 18px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Scopri di più</a></div></article>'
    });

    register({
        key: 'r4v5-alert-box',
        label: 'Alert box',
        category: 'Marketing',
        order: 40,
        content: '<div style="padding:20px 22px;border-radius:20px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;"><strong style="display:block;margin-bottom:6px;font-size:16px;">Messaggio importante</strong><p style="margin:0;font-size:15px;line-height:1.65;">Usa questo box per evidenziare una nota, una promo o un avviso.</p></div>'
    });

    register({
        key: 'r4v5-stats-grid',
        label: 'Stats grid',
        category: 'Marketing',
        order: 50,
        content: '<section style="padding:64px 24px;background:#f8fafc;"><div style="max-width:1120px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:18px;"><div style="padding:26px;border-radius:24px;background:#ffffff;border:1px solid #e5e7eb;text-align:center;"><div style="font-size:44px;font-weight:900;color:#0d6efd;line-height:1;">+120</div><p style="margin:10px 0 0;color:#64748b;font-weight:700;">Progetti</p></div><div style="padding:26px;border-radius:24px;background:#ffffff;border:1px solid #e5e7eb;text-align:center;"><div style="font-size:44px;font-weight:900;color:#0d6efd;line-height:1;">98%</div><p style="margin:10px 0 0;color:#64748b;font-weight:700;">Clienti soddisfatti</p></div><div style="padding:26px;border-radius:24px;background:#ffffff;border:1px solid #e5e7eb;text-align:center;"><div style="font-size:44px;font-weight:900;color:#0d6efd;line-height:1;">24/7</div><p style="margin:10px 0 0;color:#64748b;font-weight:700;">Supporto</p></div></div></section>'
    });

    register({
        key: 'r4v5-faq-static',
        label: 'FAQ statica',
        category: 'Marketing',
        order: 60,
        content: '<section style="padding:72px 24px;background:#ffffff;"><div style="max-width:900px;margin:0 auto;"><h2 style="font-size:42px;line-height:1.1;font-weight:900;margin:0 0 28px;color:#111827;text-align:center;">Domande frequenti</h2><div style="display:grid;gap:14px;"><div style="padding:22px;border-radius:20px;background:#f8fafc;border:1px solid #e5e7eb;"><h3 style="font-size:19px;font-weight:900;margin:0 0 8px;color:#111827;">Prima domanda?</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Risposta chiara e sintetica alla domanda.</p></div><div style="padding:22px;border-radius:20px;background:#f8fafc;border:1px solid #e5e7eb;"><h3 style="font-size:19px;font-weight:900;margin:0 0 8px;color:#111827;">Seconda domanda?</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Risposta chiara e sintetica alla domanda.</p></div></div></div></section>'
    });

    register({
        key: 'r4v5-gallery-static',
        label: 'Gallery statica',
        category: 'Media',
        order: 10,
        content: '<section style="padding:72px 24px;background:#ffffff;"><div style="max-width:1120px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:18px;"><img src="https://placehold.co/600x420?text=Foto+1" alt="Foto 1" style="width:100%;border-radius:22px;display:block;"><img src="https://placehold.co/600x420?text=Foto+2" alt="Foto 2" style="width:100%;border-radius:22px;display:block;"><img src="https://placehold.co/600x420?text=Foto+3" alt="Foto 3" style="width:100%;border-radius:22px;display:block;"></div></section>'
    });

    register({
        key: 'r4v5-advanced-hero-static',
        label: 'Hero avanzato',
        category: 'Marketing',
        order: 70,
        content: '<section style="padding:104px 24px;background:#111827;color:#fff;overflow:hidden;"><div style="max-width:1180px;margin:0 auto;display:grid;grid-template-columns:1.05fr .95fr;gap:42px;align-items:center;"><div><span style="display:inline-flex;margin-bottom:16px;padding:8px 12px;border-radius:999px;background:rgba(13,110,253,.18);color:#93c5fd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">R4Software</span><h1 style="font-size:clamp(44px,6vw,78px);line-height:1.02;font-weight:900;letter-spacing:-.055em;margin:0 0 22px;color:#fff;">Soluzioni digitali professionali</h1><p style="font-size:20px;line-height:1.75;color:#cbd5e1;margin:0 0 30px;">CRM, siti web e software su misura per aziende che vogliono crescere.</p><a href="#" style="display:inline-flex;padding:15px 24px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Richiedi consulenza</a></div><div style="padding:20px;border-radius:32px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);"><img src="https://placehold.co/760x560?text=Dashboard" alt="Dashboard" style="width:100%;display:block;border-radius:24px;"></div></div></section>'
    });

    // ==============================
    // Nuovi widget Contenuti Pro
    // ==============================
    register({ key: 'r4v5-pro-bullet-list', label: 'Elenco puntato', category: 'Contenuti', order: 10, content: '<ul class="r4v5-pro-list-basic"><li>Primo punto elenco modificabile</li><li>Secondo punto elenco con testo descrittivo</li><li>Terzo punto elenco utile per contenuti editoriali</li></ul>' });
    register({ key: 'r4v5-pro-number-list', label: 'Elenco numerato', category: 'Contenuti', order: 20, content: '<ol class="r4v5-pro-list-basic"><li>Prima fase del processo</li><li>Seconda fase operativa</li><li>Terza fase di verifica e consegna</li></ol>' });
    register({ key: 'r4v5-pro-check-list', label: 'Lista check avanzata', category: 'Contenuti', order: 30, content: '<ul class="r4v5-pro-check-list" role="list"><li><span class="r4v5-pro-check" aria-hidden="true">✓</span><div><strong>Vantaggio principale</strong><p>Descrivi in modo chiaro il beneficio per il cliente o per il progetto.</p></div></li><li><span class="r4v5-pro-check" aria-hidden="true">✓</span><div><strong>Secondo vantaggio</strong><p>Aggiungi una spiegazione sintetica, concreta e orientata al valore.</p></div></li><li><span class="r4v5-pro-check" aria-hidden="true">✓</span><div><strong>Supporto continuo</strong><p>Indica un elemento di fiducia, assistenza o garanzia post-lancio.</p></div></li></ul>' });
    register({ key: 'r4v5-pro-article-block', label: 'Articolo / testo lungo', category: 'Contenuti', order: 40, content: '<section class="r4v5-pro-section"><article class="r4v5-pro-article"><span class="r4v5-pro-pill">Approfondimento</span><h2>Titolo articolo o contenuto editoriale</h2><p>Questo blocco è pensato per testi lunghi, pagine informative, landing SEO e contenuti editoriali.</p><p>Usalo per spiegare un servizio, descrivere un processo, raccontare un progetto o costruire una sezione SEO più completa.</p></article></section>' });
    register({ key: 'r4v5-pro-blockquote', label: 'Citazione / quote', category: 'Contenuti', order: 50, content: '<blockquote class="r4v5-pro-quote">“Una citazione forte aiuta a fissare un concetto chiave e rende la pagina più autorevole e memorabile.”</blockquote>' });
    register({ key: 'r4v5-pro-badge', label: 'Badge / label', category: 'Contenuti', order: 60, content: '<span class="r4v5-pro-pill">Nuovo servizio</span>' });

    // ==============================
    // Nuove sezioni Pro
    // ==============================
    register({
        key: 'r4v5-pro-hero',
        label: 'Hero Pro',
        category: 'Sezioni Pro',
        order: 10,
        content: '<section class="r4v5-pro-hero" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true"><div class="r4v5-pro-inner r4v5-pro-hero-grid"><div><span class="r4v5-pro-eyebrow">🚀 Software, Web & App</span><h1 class="r4v5-pro-title r4v5-pro-hero-title">Trasformiamo la tua idea <span class="r4v5-pro-accent">in una soluzione digitale</span></h1><p class="r4v5-pro-text">Sviluppiamo siti web professionali, software su misura, app mobile e strategie digitali per aziende, hotel, professionisti e attività locali.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Richiedi consulenza</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-ghost">Scopri i servizi</a></div><div class="r4v5-pro-trust"><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Nessun costo nascosto</span><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Prima consulenza gratuita</span><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Supporto post-lancio</span></div></div><div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="' + imgHero + '" alt="Team al lavoro su una soluzione digitale" width="900" height="700" loading="eager"></div></div></section>'
    });

    register({
        key: 'r4v5-pro-services',
        label: 'Servizi Pro',
        category: 'Sezioni Pro',
        order: 20,
        content: '<section class="r4v5-pro-section"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">I nostri servizi</span><h2 class="r4v5-pro-title">Tutto il digitale che ti serve sotto un unico tetto</h2><p class="r4v5-pro-text">Dalle landing page ai gestionali complessi: sviluppiamo soluzioni digitali su misura per far crescere la tua attività.</p></header><div class="r4v5-pro-grid r4v5-pro-grid-4"><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+Web" alt="Siti web professionali" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">WEB</div><h3 class="r4v5-pro-card-title">Siti Web Professionali</h3><p class="r4v5-pro-card-text">Siti aziendali, landing page e portali SEO, mobile-first e orientati alla conversione.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+CRM" alt="Software gestionali" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">CRM</div><h3 class="r4v5-pro-card-title">Software & Gestionali</h3><p class="r4v5-pro-card-text">CRM, dashboard, backoffice e piattaforme costruite intorno ai tuoi processi reali.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+App" alt="App mobile" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">APP</div><h3 class="r4v5-pro-card-title">App Mobile</h3><p class="r4v5-pro-card-text">Applicazioni iOS e Android con integrazioni cloud, IoT, BLE e geolocalizzazione.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article><article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="' + imgCard + '+Social" alt="Social media" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">ADV</div><h3 class="r4v5-pro-card-title">Social & Contenuti</h3><p class="r4v5-pro-card-text">Strategia editoriale, creatività visiva e contenuti per costruire autorevolezza.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article></div></div></section>'
    });

    register({
        key: 'r4v5-pro-why',
        label: 'Perché sceglierci',
        category: 'Sezioni Pro',
        order: 30,
        content: '<section class="r4v5-pro-section" style="background:#f8fafc;"><div class="r4v5-pro-inner r4v5-pro-split"><div><span class="r4v5-pro-eyebrow">Perché sceglierci</span><h2 class="r4v5-pro-title">Non solo sviluppiamo. Pensiamo insieme a te.</h2><p class="r4v5-pro-text">Lavoriamo come un’estensione del tuo team: ascoltiamo, progettiamo, prototipiamo e misuriamo prima di dichiarare il successo.</p><ul class="r4v5-pro-check-list" role="list"><li><span class="r4v5-pro-check">✓</span><div><strong>Approccio su misura</strong><p>Nessun template preconfezionato: ogni progetto nasce dall’analisi delle tue esigenze.</p></div></li><li><span class="r4v5-pro-check">✓</span><div><strong>Consegne rispettate</strong><p>Rilasci progressivi, test continui e roadmap condivisa.</p></div></li><li><span class="r4v5-pro-check">✓</span><div><strong>Supporto continuativo</strong><p>Restiamo al tuo fianco per aggiornamenti, nuove funzionalità e ottimizzazioni.</p></div></li></ul><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Parla con un esperto</a></div><div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="' + imgVertical + '" alt="Consulenza digitale" loading="lazy"><div class="r4v5-pro-floating-badge"><div class="r4v5-pro-score">4.8 ★</div><p>valutazione media dei clienti</p></div></div></div></section>'
    });

    register({ key: 'r4v5-pro-process', label: 'Processo 4 step', category: 'Sezioni Pro', order: 40, content: '<section class="r4v5-pro-section"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">Il nostro metodo</span><h2 class="r4v5-pro-title">4 fasi. Zero improvvisazione.</h2><p class="r4v5-pro-text">Un processo chiaro, tracciabile e collaborativo che ti tiene aggiornato su ogni avanzamento.</p></header><div class="r4v5-pro-steps"><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">01</div><h3>Analisi & Strategia</h3><p>Definizione obiettivi, requisiti, architettura e roadmap del progetto.</p></div><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">02</div><h3>Design & Prototipo</h3><p>Wireframe e prototipi per validare UX e UI prima dello sviluppo.</p></div><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">03</div><h3>Sviluppo Iterativo</h3><p>Implementazione per sprint, test progressivi e rilasci verificabili.</p></div><div class="r4v5-pro-step"><div class="r4v5-pro-step-num">04</div><h3>Lancio & Supporto</h3><p>Messa online, collaudo finale e supporto post-lancio.</p></div></div></div></section>' });
    register({ key: 'r4v5-pro-faq-accordion', label: 'FAQ Accordion', category: 'Sezioni Pro', order: 50, content: '<section class="r4v5-pro-section" style="background:#f8fafc;"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">FAQ</span><h2 class="r4v5-pro-title">Domande frequenti</h2><p class="r4v5-pro-text">Tutto quello che vuoi sapere prima di iniziare a lavorare con noi.</p></header><div class="r4v5-pro-faq-list" data-r4v5-faq-accordion="1" data-r4v5-faq-single="true"><div class="r4v5-pro-faq-item is-open"><button type="button" class="r4v5-pro-faq-question" aria-expanded="true">Quanto costa sviluppare un sito web professionale?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Il costo dipende dalla complessità, dalle funzionalità richieste e dal livello di personalizzazione.</p></div></div><div class="r4v5-pro-faq-item"><button type="button" class="r4v5-pro-faq-question" aria-expanded="false">Lavorate solo in Sardegna?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Operiamo da Olbia e lavoriamo con clienti in Sardegna, in tutta Italia e anche da remoto.</p></div></div><div class="r4v5-pro-faq-item"><button type="button" class="r4v5-pro-faq-question" aria-expanded="false">Offrite assistenza dopo il lancio?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Sì, possiamo seguire aggiornamenti, manutenzione, evoluzioni funzionali e ottimizzazioni periodiche.</p></div></div></div></div></section>' });
    register({ key: 'r4v5-pro-final-cta', label: 'CTA finale Pro', category: 'Sezioni Pro', order: 60, content: '<section class="r4v5-pro-cta" data-r4-animation="zoom-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="800" data-r4-animation-once="true"><div class="r4v5-pro-inner"><span class="r4v5-pro-eyebrow" style="background:rgba(255,255,255,.16);color:#fff;">Inizia oggi</span><h2 class="r4v5-pro-title">Hai un progetto in mente? Parliamone senza impegno.</h2><p class="r4v5-pro-text">La prima consulenza è gratuita. In 30 minuti capiamo le tue esigenze e ti proponiamo un percorso concreto.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Richiedi consulenza gratuita</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-outline-white">Guarda i nostri lavori</a></div><p class="r4v5-pro-note">Risposta entro 24 ore lavorative · Nessun impegno · 100% gratuita</p></div></section>' });

    // Iniezione sicura CSS/JS nel canvas GrapesJS, senza document.write.
    function injectProRuntime() {
        const editor = window.R4EditorV5;
        const doc = editor && editor.Canvas && editor.Canvas.getDocument ? editor.Canvas.getDocument() : null;
        if (!doc || !doc.head) return;

        if (!doc.getElementById('r4v5-widgets-pro-editor-style')) {
            const link = doc.createElement('link');
            link.id = 'r4v5-widgets-pro-editor-style';
            link.rel = 'stylesheet';
            link.href = '/assets/editor-v5/runtime/widgets-pro.css?v=20260507-v5-widgets-pro-fix';
            doc.head.appendChild(link);
        }

        if (!doc.getElementById('r4v5-widgets-pro-editor-runtime')) {
            const script = doc.createElement('script');
            script.id = 'r4v5-widgets-pro-editor-runtime';
            script.src = '/assets/editor-v5/runtime/widgets-pro.js?v=20260507-v5-widgets-pro-fix';
            script.defer = true;
            doc.head.appendChild(script);
        } else if (doc.defaultView && doc.defaultView.R4EditorV5WidgetsPro) {
            doc.defaultView.R4EditorV5WidgetsPro.init(doc);
        }
    }

    const timer = setInterval(function () {
        if (window.R4EditorV5) {
            injectProRuntime();
            window.R4EditorV5.on('load canvas:frame:load component:add component:update', injectProRuntime);
            clearInterval(timer);
        }
    }, 120);
})();
