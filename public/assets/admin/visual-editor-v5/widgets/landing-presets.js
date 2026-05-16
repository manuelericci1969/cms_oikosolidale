(function () {
    'use strict';

    const registry = window.R4EditorV5Registry;
    if (!registry) return;

    const icon = '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>';

    function register(item) {
        registry.registerWidget(Object.assign({ media: icon }, item));
    }

    const hero = `<section class="r4v5-pro-hero" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true">
        <div class="r4v5-pro-inner r4v5-pro-hero-grid">
            <div>
                <span class="r4v5-pro-eyebrow">🚀 Software, Web & App</span>
                <h1 class="r4v5-pro-title r4v5-pro-hero-title">Trasformiamo la tua idea <span class="r4v5-pro-accent">in una soluzione digitale</span></h1>
                <p class="r4v5-pro-text">Sviluppiamo siti web professionali, software su misura, app mobile e strategie digitali per aziende, hotel, professionisti e attività locali.</p>
                <div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Richiedi consulenza</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-ghost">Scopri i servizi</a></div>
                <div class="r4v5-pro-trust"><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Nessun costo nascosto</span><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Prima consulenza gratuita</span><span class="r4v5-pro-trust-item"><span class="r4v5-pro-check-dot">✓</span>Supporto post-lancio</span></div>
            </div>
            <div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="https://placehold.co/900x700?text=Hero+Image" alt="Team al lavoro" width="900" height="700" loading="eager"></div>
        </div>
    </section>`;

    const problemSolution = `<section style="padding:86px 24px;background:#f8fafc;">
        <div style="max-width:1180px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <article style="padding:34px;border-radius:32px;background:#fff;border:1px solid #fee2e2;box-shadow:0 18px 45px rgba(15,23,42,.06);">
                <span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:#fef2f2;color:#dc2626;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">Il problema</span>
                <h2 style="font-size:clamp(30px,4vw,44px);line-height:1.12;font-weight:950;margin:0 0 16px;color:#111827;">Processi lenti, sito poco efficace, poca conversione</h2>
                <p style="font-size:17px;line-height:1.75;color:#64748b;margin:0;">Molte aziende lavorano ancora con strumenti scollegati, poca automazione e una comunicazione digitale che non genera richieste qualificate.</p>
            </article>
            <article style="padding:34px;border-radius:32px;background:#0d6efd;color:#fff;box-shadow:0 24px 60px rgba(13,110,253,.22);">
                <span style="display:inline-flex;margin-bottom:14px;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.18);color:#fff;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">La soluzione</span>
                <h2 style="font-size:clamp(30px,4vw,44px);line-height:1.12;font-weight:950;margin:0 0 16px;color:#fff;">Una piattaforma digitale costruita sui tuoi obiettivi</h2>
                <p style="font-size:17px;line-height:1.75;color:rgba(255,255,255,.86);margin:0;">Progettiamo siti, software, CRM e app con metodo, performance, SEO e supporto continuo per trasformare il digitale in risultati misurabili.</p>
            </article>
        </div>
    </section>`;

    const services = `<section class="r4v5-pro-section">
        <div class="r4v5-pro-inner">
            <header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">I nostri servizi</span><h2 class="r4v5-pro-title">Tutto il digitale che ti serve sotto un unico tetto</h2><p class="r4v5-pro-text">Dalle landing page ai gestionali complessi: sviluppiamo soluzioni digitali su misura per far crescere la tua attività.</p></header>
            <div class="r4v5-pro-grid r4v5-pro-grid-4">
                <article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="https://placehold.co/700x420?text=Web" alt="Siti web" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">WEB</div><h3 class="r4v5-pro-card-title">Siti Web Professionali</h3><p class="r4v5-pro-card-text">Siti aziendali, landing page e portali SEO, mobile-first e orientati alla conversione.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article>
                <article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="https://placehold.co/700x420?text=CRM" alt="CRM" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">CRM</div><h3 class="r4v5-pro-card-title">Software & Gestionali</h3><p class="r4v5-pro-card-text">CRM, dashboard, backoffice e piattaforme costruite intorno ai tuoi processi reali.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article>
                <article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="https://placehold.co/700x420?text=App" alt="App" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">APP</div><h3 class="r4v5-pro-card-title">App Mobile</h3><p class="r4v5-pro-card-text">Applicazioni iOS e Android con integrazioni cloud, IoT, BLE e geolocalizzazione.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article>
                <article class="r4v5-pro-card"><img class="r4v5-pro-card-img" src="https://placehold.co/700x420?text=Social" alt="Social" loading="lazy"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">ADV</div><h3 class="r4v5-pro-card-title">Social & Contenuti</h3><p class="r4v5-pro-card-text">Strategia editoriale, creatività visiva e contenuti per costruire autorevolezza.</p><a class="r4v5-pro-card-link" href="#">Scopri di più →</a></div></article>
            </div>
        </div>
    </section>`;

    const stats = `<section style="padding:76px 24px;background:#0f172a;color:#fff;">
        <div style="max-width:1180px;margin:0 auto;"><div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;">
            <div style="padding:26px;border-radius:26px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);text-align:center;"><strong data-r4v5-count="120" data-r4v5-count-prefix="+" style="display:block;font-size:44px;line-height:1;font-weight:950;color:#fff;">+120</strong><span style="display:block;margin-top:10px;color:#cbd5e1;font-weight:800;">Progetti</span></div>
            <div style="padding:26px;border-radius:26px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);text-align:center;"><strong data-r4v5-count="98" data-r4v5-count-suffix="%" style="display:block;font-size:44px;line-height:1;font-weight:950;color:#fff;">98%</strong><span style="display:block;margin-top:10px;color:#cbd5e1;font-weight:800;">Soddisfazione</span></div>
            <div style="padding:26px;border-radius:26px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);text-align:center;"><strong data-r4v5-count="25" style="display:block;font-size:44px;line-height:1;font-weight:950;color:#fff;">25</strong><span style="display:block;margin-top:10px;color:#cbd5e1;font-weight:800;">Anni esperienza</span></div>
            <div style="padding:26px;border-radius:26px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);text-align:center;"><strong data-r4v5-count="24" data-r4v5-count-suffix="h" style="display:block;font-size:44px;line-height:1;font-weight:950;color:#fff;">24h</strong><span style="display:block;margin-top:10px;color:#cbd5e1;font-weight:800;">Risposta media</span></div>
        </div></div>
    </section>`;

    const portfolio = `<section style="padding:86px 24px;background:#fff;">
        <div style="max-width:1180px;margin:0 auto;">
            <header style="display:flex;justify-content:space-between;gap:24px;align-items:end;margin-bottom:34px;"><div><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Portfolio</span><h2 style="font-size:clamp(34px,5vw,54px);line-height:1.08;font-weight:950;letter-spacing:-.04em;margin:0;color:#111827;">Lavori e casi studio</h2></div><a href="#" style="display:inline-flex;padding:12px 18px;border-radius:999px;background:#111827;color:#fff;text-decoration:none;font-weight:900;">Vedi tutti</a></header>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;">
                <article style="overflow:hidden;border-radius:28px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 18px 45px rgba(15,23,42,.08);"><img src="https://placehold.co/700x460?text=Progetto" alt="Progetto" style="width:100%;display:block;aspect-ratio:16/10;object-fit:cover;"><div style="padding:24px;"><span style="color:#0d6efd;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">CMS / Web</span><h3 style="font-size:22px;margin:8px 0;color:#111827;font-weight:950;">Nome progetto</h3><p style="margin:0;color:#64748b;line-height:1.65;">Breve descrizione del risultato ottenuto.</p></div></article>
                <article style="overflow:hidden;border-radius:28px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 18px 45px rgba(15,23,42,.08);"><img src="https://placehold.co/700x460?text=Progetto" alt="Progetto" style="width:100%;display:block;aspect-ratio:16/10;object-fit:cover;"><div style="padding:24px;"><span style="color:#0d6efd;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">CRM</span><h3 style="font-size:22px;margin:8px 0;color:#111827;font-weight:950;">Nome progetto</h3><p style="margin:0;color:#64748b;line-height:1.65;">Automazione processi e dashboard operative.</p></div></article>
                <article style="overflow:hidden;border-radius:28px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 18px 45px rgba(15,23,42,.08);"><img src="https://placehold.co/700x460?text=Progetto" alt="Progetto" style="width:100%;display:block;aspect-ratio:16/10;object-fit:cover;"><div style="padding:24px;"><span style="color:#0d6efd;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">App</span><h3 style="font-size:22px;margin:8px 0;color:#111827;font-weight:950;">Nome progetto</h3><p style="margin:0;color:#64748b;line-height:1.65;">Esperienza mobile e integrazione cloud.</p></div></article>
            </div>
        </div>
    </section>`;

    const faqCta = `<section class="r4v5-pro-section" style="background:#f8fafc;">
        <div class="r4v5-pro-inner">
            <header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">FAQ</span><h2 class="r4v5-pro-title">Domande frequenti</h2></header>
            <div class="r4v5-pro-faq-list" data-r4v5-faq-accordion="1" data-r4v5-faq-single="true">
                <div class="r4v5-pro-faq-item is-open"><button type="button" class="r4v5-pro-faq-question" aria-expanded="true">Quanto costa sviluppare un sito professionale?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Dipende da obiettivi, contenuti e funzionalità. La prima analisi serve a costruire un preventivo chiaro.</p></div></div>
                <div class="r4v5-pro-faq-item"><button type="button" class="r4v5-pro-faq-question" aria-expanded="false">Lavorate anche da remoto?<span class="r4v5-pro-faq-icon" aria-hidden="true"></span></button><div class="r4v5-pro-faq-answer"><p>Sì, lavoriamo con clienti in Sardegna, in Italia e da remoto con processi condivisi.</p></div></div>
            </div>
        </div>
    </section>
    <section class="r4v5-pro-cta">
        <div class="r4v5-pro-inner"><span class="r4v5-pro-eyebrow" style="background:rgba(255,255,255,.16);color:#fff;">Inizia oggi</span><h2 class="r4v5-pro-title">Hai un progetto in mente? Parliamone senza impegno.</h2><p class="r4v5-pro-text">La prima consulenza è gratuita. In 30 minuti capiamo le tue esigenze e ti proponiamo un percorso concreto.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Richiedi consulenza gratuita</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-outline-white">Guarda i nostri lavori</a></div></div>
    </section>`;

    const corporateLanding = `<section class="r4v5-pro-hero" style="background:linear-gradient(135deg,#0f172a 0%,#111827 48%,#0d6efd 100%);" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true">
        <div class="r4v5-pro-inner r4v5-pro-hero-grid">
            <div><span class="r4v5-pro-eyebrow" style="background:rgba(255,255,255,.14);color:#fff;">Sito aziendale</span><h1 class="r4v5-pro-title r4v5-pro-hero-title" style="color:#fff;">La presenza digitale professionale per aziende e professionisti</h1><p class="r4v5-pro-text" style="color:rgba(255,255,255,.84);">Una struttura completa per presentare azienda, servizi, metodo, numeri, recensioni e richiesta contatto.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Richiedi preventivo</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-outline-white">Vedi servizi</a></div></div>
            <div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="https://placehold.co/900x700?text=Azienda" alt="Sito aziendale" loading="eager"></div>
        </div>
    </section>
    <section class="r4v5-pro-section"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">Chi siamo</span><h2 class="r4v5-pro-title">Un partner tecnico e strategico, non un semplice fornitore</h2><p class="r4v5-pro-text">Racconta identità, esperienza, valori e punti di forza dell’azienda con un taglio chiaro e commerciale.</p></header><div class="r4v5-pro-grid r4v5-pro-grid-3"><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">01</div><h3 class="r4v5-pro-card-title">Esperienza</h3><p class="r4v5-pro-card-text">Spazio dedicato alla storia aziendale, ai risultati ottenuti e al posizionamento.</p></div></article><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">02</div><h3 class="r4v5-pro-card-title">Metodo</h3><p class="r4v5-pro-card-text">Descrivi come lavori, come accompagni il cliente e come gestisci il progetto.</p></div></article><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><div class="r4v5-pro-icon">03</div><h3 class="r4v5-pro-card-title">Supporto</h3><p class="r4v5-pro-card-text">Evidenzia assistenza, manutenzione, continuità e aggiornamenti.</p></div></article></div></div></section>
    <section style="padding:86px 24px;background:#f8fafc;"><div style="max-width:1180px;margin:0 auto;"><header style="max-width:760px;margin-bottom:34px;"><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">Servizi</span><h2 style="font-size:clamp(34px,5vw,54px);line-height:1.08;font-weight:950;margin:0;color:#111827;">Aree operative</h2></header><div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;"><div style="padding:28px;border-radius:28px;background:#fff;border:1px solid #e5e7eb;"><h3 style="margin:0 0 10px;color:#111827;">Servizio principale</h3><p style="margin:0;color:#64748b;line-height:1.7;">Descrizione breve orientata al valore per il cliente.</p></div><div style="padding:28px;border-radius:28px;background:#fff;border:1px solid #e5e7eb;"><h3 style="margin:0 0 10px;color:#111827;">Servizio secondario</h3><p style="margin:0;color:#64748b;line-height:1.7;">Descrizione breve orientata al valore per il cliente.</p></div><div style="padding:28px;border-radius:28px;background:#fff;border:1px solid #e5e7eb;"><h3 style="margin:0 0 10px;color:#111827;">Consulenza</h3><p style="margin:0;color:#64748b;line-height:1.7;">Analisi, pianificazione e supporto operativo.</p></div><div style="padding:28px;border-radius:28px;background:#fff;border:1px solid #e5e7eb;"><h3 style="margin:0 0 10px;color:#111827;">Assistenza</h3><p style="margin:0;color:#64748b;line-height:1.7;">Supporto post vendita e continuità nel tempo.</p></div></div></div></section>
    <section class="r4v5-pro-cta"><div class="r4v5-pro-inner"><h2 class="r4v5-pro-title">Vuoi presentare meglio la tua azienda?</h2><p class="r4v5-pro-text">Usa questa pagina come base per un sito corporate completo.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Contattaci</a></div></div></section>`;

    const leadGenerationLanding = `<section class="r4v5-pro-hero" style="background:#ffffff;" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true">
        <div class="r4v5-pro-inner r4v5-pro-hero-grid">
            <div><span class="r4v5-pro-eyebrow">Lead generation</span><h1 class="r4v5-pro-title r4v5-pro-hero-title">Trasforma visite in richieste di contatto qualificate</h1><p class="r4v5-pro-text">Landing pensata per campagne Google, Meta, LinkedIn o invii diretti. Struttura veloce: promessa, vantaggi, prova sociale, FAQ e modulo richiesta.</p><div class="r4v5-pro-btns"><a href="#contatto" class="r4v5-pro-btn r4v5-pro-btn-primary">Voglio essere ricontattato</a><a href="#vantaggi" class="r4v5-pro-btn r4v5-pro-btn-ghost">Perché funziona</a></div></div>
            <div style="padding:28px;border-radius:32px;background:#f8fafc;border:1px solid #e5e7eb;box-shadow:0 24px 60px rgba(15,23,42,.08);" id="contatto"><h3 style="margin:0 0 10px;font-size:26px;color:#111827;">Richiedi una consulenza</h3><p style="margin:0 0 18px;color:#64748b;line-height:1.7;">Compila i dati e ricevi una prima valutazione.</p><input placeholder="Nome e cognome" style="width:100%;padding:14px 16px;margin-bottom:10px;border-radius:14px;border:1px solid #d1d5db;"><input placeholder="Email o telefono" style="width:100%;padding:14px 16px;margin-bottom:10px;border-radius:14px;border:1px solid #d1d5db;"><textarea placeholder="Di cosa hai bisogno?" style="width:100%;min-height:110px;padding:14px 16px;margin-bottom:14px;border-radius:14px;border:1px solid #d1d5db;"></textarea><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary" style="width:100%;justify-content:center;">Invia richiesta</a></div>
        </div>
    </section>
    <section id="vantaggi" style="padding:76px 24px;background:#0f172a;color:#fff;"><div style="max-width:1180px;margin:0 auto;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;"><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);"><strong style="display:block;font-size:34px;">01</strong><h3>Promessa chiara</h3><p style="color:#cbd5e1;">Il visitatore capisce subito cosa ottiene.</p></div><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);"><strong style="display:block;font-size:34px;">02</strong><h3>CTA visibile</h3><p style="color:#cbd5e1;">La richiesta contatto è sempre evidente.</p></div><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);"><strong style="display:block;font-size:34px;">03</strong><h3>Fiducia</h3><p style="color:#cbd5e1;">Numeri, metodo e FAQ riducono i dubbi.</p></div><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);"><strong style="display:block;font-size:34px;">04</strong><h3>Conversione</h3><p style="color:#cbd5e1;">La pagina guida l’utente verso un’unica azione.</p></div></div></section>
    <section class="r4v5-pro-section"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">Offerta</span><h2 class="r4v5-pro-title">Perché richiedere una consulenza ora</h2></header><div class="r4v5-pro-grid r4v5-pro-grid-3"><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><h3 class="r4v5-pro-card-title">Analisi iniziale</h3><p class="r4v5-pro-card-text">Valutiamo la situazione attuale e individuiamo le priorità.</p></div></article><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><h3 class="r4v5-pro-card-title">Piano operativo</h3><p class="r4v5-pro-card-text">Definiamo una roadmap concreta, sostenibile e misurabile.</p></div></article><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><h3 class="r4v5-pro-card-title">Preventivo chiaro</h3><p class="r4v5-pro-card-text">Costi, tempi e attività vengono spiegati senza zone grigie.</p></div></article></div></div></section>
    <section class="r4v5-pro-section" style="background:#f8fafc;"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">FAQ</span><h2 class="r4v5-pro-title">Prima di compilare</h2></header><div class="r4v5-pro-faq-list" data-r4v5-faq-accordion="1" data-r4v5-faq-single="true"><div class="r4v5-pro-faq-item is-open"><button type="button" class="r4v5-pro-faq-question" aria-expanded="true">La consulenza è gratuita?<span class="r4v5-pro-faq-icon"></span></button><div class="r4v5-pro-faq-answer"><p>Sì, la prima valutazione serve a capire se possiamo aiutarti e con quale percorso.</p></div></div><div class="r4v5-pro-faq-item"><button type="button" class="r4v5-pro-faq-question" aria-expanded="false">Quanto tempo serve per ricevere risposta?<span class="r4v5-pro-faq-icon"></span></button><div class="r4v5-pro-faq-answer"><p>Normalmente rispondiamo entro una giornata lavorativa.</p></div></div></div></div></section>`;

    const portfolioAgencyLanding = `<section class="r4v5-pro-hero" style="background:#f8fafc;" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true">
        <div class="r4v5-pro-inner"><div style="max-width:860px;"><span class="r4v5-pro-eyebrow">Portfolio / Agency</span><h1 class="r4v5-pro-title r4v5-pro-hero-title">Mostra lavori, competenze e risultati con una pagina ad alto impatto</h1><p class="r4v5-pro-text">Preset ideale per agenzie, studi professionali, freelance, fotografi, architetti, creativi e software house.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Vedi portfolio</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-ghost">Richiedi progetto</a></div></div></div>
    </section>
    <section style="padding:86px 24px;background:#fff;"><div style="max-width:1240px;margin:0 auto;"><div style="display:grid;grid-template-columns:1.2fr .8fr;gap:22px;margin-bottom:22px;"><article style="overflow:hidden;border-radius:32px;background:#111827;color:#fff;"><img src="https://placehold.co/900x520?text=Project+01" alt="Project 01" style="width:100%;display:block;aspect-ratio:16/9;object-fit:cover;"><div style="padding:28px;"><span style="color:#93c5fd;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">Featured</span><h2 style="font-size:34px;margin:8px 0;">Progetto principale</h2><p style="color:#cbd5e1;line-height:1.7;">Racconta il caso studio più importante con obiettivo, soluzione e risultato.</p></div></article><div style="display:grid;gap:22px;"><article style="padding:28px;border-radius:32px;background:#f8fafc;border:1px solid #e5e7eb;"><h3 style="font-size:26px;margin:0 0 10px;">Approccio</h3><p style="margin:0;color:#64748b;line-height:1.7;">Analisi, progettazione, sviluppo, misurazione.</p></article><article style="padding:28px;border-radius:32px;background:#eaf3ff;border:1px solid #bfdbfe;"><h3 style="font-size:26px;margin:0 0 10px;color:#0d6efd;">Risultato</h3><p style="margin:0;color:#475569;line-height:1.7;">Miglioramento della percezione, più contatti, più autorevolezza.</p></article></div></div><div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;"><article style="overflow:hidden;border-radius:28px;border:1px solid #e5e7eb;"><img src="https://placehold.co/700x460?text=Work+01" alt="Work 01" style="width:100%;display:block;"><div style="padding:22px;"><h3>Case study 01</h3><p style="color:#64748b;">Descrizione breve del lavoro.</p></div></article><article style="overflow:hidden;border-radius:28px;border:1px solid #e5e7eb;"><img src="https://placehold.co/700x460?text=Work+02" alt="Work 02" style="width:100%;display:block;"><div style="padding:22px;"><h3>Case study 02</h3><p style="color:#64748b;">Descrizione breve del lavoro.</p></div></article><article style="overflow:hidden;border-radius:28px;border:1px solid #e5e7eb;"><img src="https://placehold.co/700x460?text=Work+03" alt="Work 03" style="width:100%;display:block;"><div style="padding:22px;"><h3>Case study 03</h3><p style="color:#64748b;">Descrizione breve del lavoro.</p></div></article></div></div></section>
    <section class="r4v5-pro-section" style="background:#0f172a;color:#fff;"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow" style="background:rgba(255,255,255,.14);color:#fff;">Competenze</span><h2 class="r4v5-pro-title" style="color:#fff;">Cosa sappiamo fare</h2></header><div class="r4v5-pro-grid r4v5-pro-grid-4"><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);">Branding</div><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);">Web Design</div><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);">Sviluppo</div><div style="padding:24px;border-radius:24px;background:rgba(255,255,255,.08);">Marketing</div></div></div></section>
    <section class="r4v5-pro-cta"><div class="r4v5-pro-inner"><h2 class="r4v5-pro-title">Hai un progetto da realizzare?</h2><p class="r4v5-pro-text">Questa struttura è pronta per portfolio, casi studio e richiesta consulenza.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Parliamone</a></div></div></section>`;

    const localBusinessLanding = `<section class="r4v5-pro-hero" data-r4-animation="fade-in" data-r4-animation-trigger="viewport" data-r4-animation-duration="900" data-r4-animation-once="true">
        <div class="r4v5-pro-inner r4v5-pro-hero-grid"><div><span class="r4v5-pro-eyebrow">Attività locale</span><h1 class="r4v5-pro-title r4v5-pro-hero-title">La pagina perfetta per hotel, ristoranti, professionisti e servizi locali</h1><p class="r4v5-pro-text">Preset pensato per presentare servizi, territorio, punti di forza, recensioni, mappa e contatto rapido.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-primary">Prenota / Contatta</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-ghost">Scopri di più</a></div></div><div class="r4v5-pro-visual"><img class="r4v5-pro-img" src="https://placehold.co/900x700?text=Local+Business" alt="Attività locale" loading="eager"></div></div>
    </section>
    <section class="r4v5-pro-section" style="background:#f8fafc;"><div class="r4v5-pro-inner"><header class="r4v5-pro-header"><span class="r4v5-pro-eyebrow">Perché sceglierci</span><h2 class="r4v5-pro-title">Vicini, affidabili, facili da contattare</h2></header><div class="r4v5-pro-grid r4v5-pro-grid-3"><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><h3 class="r4v5-pro-card-title">Servizio rapido</h3><p class="r4v5-pro-card-text">Descrivi il vantaggio principale per chi cerca una soluzione immediata.</p></div></article><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><h3 class="r4v5-pro-card-title">Esperienza locale</h3><p class="r4v5-pro-card-text">Racconta conoscenza del territorio, affidabilità e presenza.</p></div></article><article class="r4v5-pro-card"><div class="r4v5-pro-card-body"><h3 class="r4v5-pro-card-title">Assistenza diretta</h3><p class="r4v5-pro-card-text">Evidenzia contatto umano, telefono, WhatsApp e supporto.</p></div></article></div></div></section>
    <section style="padding:86px 24px;background:#fff;"><div style="max-width:1180px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center;"><div><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;">Servizi</span><h2 style="font-size:clamp(34px,5vw,54px);line-height:1.08;font-weight:950;margin:0 0 16px;color:#111827;">Cosa offriamo</h2><p style="font-size:17px;line-height:1.8;color:#64748b;">Inserisci i servizi principali con descrizioni brevi, chiare e orientate alla conversione.</p><ul style="display:grid;gap:12px;margin:24px 0 0;padding:0;list-style:none;"><li>✓ Servizio principale</li><li>✓ Consulenza personalizzata</li><li>✓ Preventivo chiaro</li><li>✓ Supporto diretto</li></ul></div><div style="height:360px;border-radius:32px;background:#e5e7eb;display:grid;place-items:center;color:#64748b;font-weight:900;">Mappa / Immagine sede</div></div></section>
    <section class="r4v5-pro-cta"><div class="r4v5-pro-inner"><h2 class="r4v5-pro-title">Vuoi ricevere informazioni?</h2><p class="r4v5-pro-text">Struttura ideale per una pagina locale orientata a telefonate, WhatsApp e richieste dirette.</p><div class="r4v5-pro-btns"><a href="#" class="r4v5-pro-btn r4v5-pro-btn-white">Chiama ora</a><a href="#" class="r4v5-pro-btn r4v5-pro-btn-outline-white">Scrivi su WhatsApp</a></div></div></section>`;

    register({
        key: 'r4v5-preset-r4software-landing',
        label: 'Landing completa R4Software',
        category: 'Preset Landing',
        order: 10,
        content: hero + problemSolution + services + stats + portfolio + faqCta
    });

    register({
        key: 'r4v5-preset-corporate-company',
        label: 'Sito aziendale corporate',
        category: 'Preset Landing',
        order: 20,
        content: corporateLanding
    });

    register({
        key: 'r4v5-preset-lead-generation',
        label: 'Landing lead generation',
        category: 'Preset Landing',
        order: 30,
        content: leadGenerationLanding
    });

    register({
        key: 'r4v5-preset-portfolio-agency',
        label: 'Portfolio / Agency',
        category: 'Preset Landing',
        order: 40,
        content: portfolioAgencyLanding
    });

    register({
        key: 'r4v5-preset-local-business',
        label: 'Attività locale / Servizi',
        category: 'Preset Landing',
        order: 50,
        content: localBusinessLanding
    });
})();
