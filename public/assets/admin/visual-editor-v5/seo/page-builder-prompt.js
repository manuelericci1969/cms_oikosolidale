(function () {
    'use strict';

    function value(name, fallback) {
        var el = document.querySelector('[name="' + name + '"]');
        return el ? (el.value || '') : (fallback || '');
    }

    function pageTitle() {
        return value('meta[page_title]', value('title', document.querySelector('.r4v5-subtitle') ? document.querySelector('.r4v5-subtitle').textContent.trim() : ''));
    }

    function pageSlug() {
        return value('slug', '');
    }

    function currentSeoTitle() {
        return value('meta[seo][title]', value('meta[seo_title]', value('meta[title]', pageTitle())));
    }

    function currentSeoDescription() {
        return value('meta[seo][description]', value('meta[seo_description]', value('meta[description]', '')));
    }

    function currentFocusKeyword() {
        return value('meta[seo][focus_keyword]', value('meta[seo_keywords]', value('meta[keywords]', '')));
    }

    function getEditorHtml() {
        try {
            if (window.R4EditorV5 && typeof window.R4EditorV5.getHtml === 'function') {
                return window.R4EditorV5.getHtml() || '';
            }
        } catch (error) {
            return '';
        }
        return value('visual_html', '');
    }

    function detectCurrentSections(html) {
        var found = [];
        var checks = [
            ['Hero', /hero|r4v5-pro-hero|r4seo.*hero/i],
            ['Servizi', /servizi|services|r4v5-pro-card/i],
            ['CTA', /cta|call.to.action|r4v5-pro-cta/i],
            ['FAQ', /faq|accordion|data-r4v5-faq/i],
            ['Pricing', /pricing|price|prezzo|piano/i],
            ['Portfolio/Testimonianze', /portfolio|case.study|testimonial|recension/i],
            ['Prodotti', /prodotto|product|hmfluxus|hmobile|crm/i]
        ];

        checks.forEach(function (item) {
            if (item[1].test(html)) found.push(item[0]);
        });

        return found.length ? found.join(', ') : 'non rilevate automaticamente';
    }

    function buildPrompt() {
        var title = currentSeoTitle() || pageTitle() || '[titolo pagina da definire]';
        var slug = pageSlug() || '[slug-da-definire]';
        var description = currentSeoDescription() || '[meta description da definire]';
        var keyword = currentFocusKeyword() || '[focus keyword da definire]';
        var html = getEditorHtml();
        var currentSections = detectCurrentSections(html);

        return [
            'Agisci come CTO senior Laravel, web designer senior e SEO specialist senior per R4Software.',
            '',
            'Devi costruire una pagina completa da incollare nel componente CODICE dell\'Editor V5 del CMS R4Software.',
            '',
            'CONTESTO PAGINA',
            '- Titolo pagina: ' + title,
            '- Slug: ' + slug,
            '- Focus keyword principale: ' + keyword,
            '- Meta description attuale: ' + description,
            '- Sezioni attualmente rilevate nel canvas: ' + currentSections,
            '',
            'OBIETTIVO',
            'Realizza una pagina moderna, elegante, autorevole e orientata alla conversione. Deve essere SEO/SEF friendly, mobile-first, veloce, leggibile e compatibile con il frontend pubblico del CMS R4Software.',
            '',
            'VINCOLI TECNICI IMPORTANTI',
            '- Restituisci solo HTML, CSS e JavaScript separati.',
            '- Non usare framework esterni.',
            '- Non usare Bootstrap, Tailwind, jQuery o librerie CDN.',
            '- Usa classi con prefisso dedicato e isolato, ad esempio r4page2026-* oppure r4custom-*.',
            '- Non usare <html>, <head>, <body> o <main> come wrapper principale.',
            '- Usa come wrapper principale un <section> o un <div> con id univoco.',
            '- Evita CSS globali su body, html, h1, p, a non scoped.',
            '- Tutto il CSS deve essere scoped sotto la classe principale della pagina.',
            '- Il codice deve funzionare dentro .page-visual-content.',
            '- Evita script invasivi su window/document se non necessari.',
            '- Il JavaScript deve essere vanilla e protetto da IIFE.',
            '- Le animazioni devono usare data-r4-animation quando possibile.',
            '- Le FAQ devono essere compatibili con data-r4v5-faq-accordion e data-r4v5-faq-single.',
            '- I pulsanti devono avere link reali e modificabili.',
            '- Evita placeholder generici tipo lorem ipsum.',
            '',
            'CLASSI E ATTRIBUTI COMPATIBILI EDITOR V5 / R4SOFTWARE',
            '- data-r4-animation="fade-in"',
            '- data-r4-animation="slide-up"',
            '- data-r4-animation-trigger="viewport"',
            '- data-r4-animation-duration="850"',
            '- data-r4-animation-once="true"',
            '- data-r4v5-bg-mode="slider" per hero con slider immagini, se utile',
            '- data-r4v5-bg-slider="1"',
            '- data-r4v5-bg-slider-images="[...]"',
            '- data-r4v5-bg-slider-autoplay="true"',
            '- data-r4v5-bg-slider-interval="4500"',
            '- data-r4v5-bg-slider-duration="3000"',
            '- data-r4v5-bg-overlay-color="#000000"',
            '- data-r4v5-bg-overlay-opacity="0.45"',
            '- data-r4v5-bg-slider-min-height="650px"',
            '- data-r4v5-faq-accordion',
            '- data-r4v5-faq-single="true"',
            '',
            'WIDGET / SEZIONI DA USARE O EMULARE CON HTML COMPATIBILE',
            '- Hero professionale con H1, testo, CTA primaria e secondaria.',
            '- Strip di posizionamento con 3/4 punti forti.',
            '- Grid servizi/cards.',
            '- Sezione problema/soluzione.',
            '- Sezione processo/metodo in step.',
            '- Sezione prodotti o casi d\'uso.',
            '- Sezione vantaggi competitivi.',
            '- FAQ accordion in JavaScript vanilla.',
            '- CTA finale forte.',
            '',
            'SEO ON PAGE',
            '- Usa un solo H1.',
            '- Usa H2 descrittivi per le sezioni principali.',
            '- Integra naturalmente la focus keyword: ' + keyword,
            '- Integra keyword correlate e geografiche quando coerenti: Olbia, Sardegna, Costa Smeralda, sviluppo software, CRM, CMS, siti web professionali, SEO, social media marketing, app mobile, IoT.',
            '- Scrivi testi reali e commercialmente efficaci.',
            '- Inserisci CTA chiare: Richiedi una consulenza, Contatta R4Software, Scopri il servizio.',
            '',
            'BRAND E STILE VISIVO',
            '- Stile tecnologico, mediterraneo, elegante, affidabile.',
            '- Palette consigliata: blu #0d6efd, blu notte #0f172a, bianco #ffffff, grigio #475569, azzurro chiaro #eaf3ff.',
            '- Layout moderno con max-width 1180px, card arrotondate, ombre leggere, spaziatura ampia.',
            '- Mobile responsive con media query max-width 980px e 720px.',
            '',
            'OUTPUT RICHIESTO',
            '1. Blocco HTML completo pronto da incollare nel componente CODICE.',
            '2. Blocco CSS completo e scoped.',
            '3. Blocco JavaScript vanilla solo se necessario.',
            '4. Dopo il codice, proponi anche:',
            '   - SEO title max 60 caratteri;',
            '   - meta description max 160 caratteri;',
            '   - focus keyword;',
            '   - 8 keyword correlate;',
            '   - suggerimento og:image 1200x630.',
            '',
            'Non inserire spiegazioni generiche. Produci direttamente il codice e i testi finali.'
        ].join('\n');
    }

    function ensureSection(panel) {
        if (!panel || panel.querySelector('#r4v5PageBuilderPrompt')) return;

        var wrapper = panel.querySelector('.r4v5-seo-panel');
        if (!wrapper) return;

        var section = document.createElement('div');
        section.className = 'r4v5-seo-section';
        section.innerHTML = [
            '<div class="r4v5-seo-section-title">Prompt costruzione pagina completa</div>',
            '<div class="r4v5-seo-prompt" id="r4v5PageBuilderPrompt"></div>',
            '<button type="button" class="r4v5-seo-copy" id="r4v5PageBuilderPromptCopy" style="margin-top:10px">Copia prompt pagina completa</button>'
        ].join('');

        wrapper.appendChild(section);

        var copy = section.querySelector('#r4v5PageBuilderPromptCopy');
        copy.addEventListener('click', function () {
            var prompt = section.querySelector('#r4v5PageBuilderPrompt');
            if (!prompt) return;
            if (navigator.clipboard) navigator.clipboard.writeText(prompt.textContent || '');
            copy.textContent = 'Prompt copiato';
            setTimeout(function () { copy.textContent = 'Copia prompt pagina completa'; }, 1600);
        });

        updatePrompt();
    }

    function updatePrompt() {
        var box = document.getElementById('r4v5PageBuilderPrompt');
        if (!box) return;
        box.textContent = buildPrompt();
    }

    function boot() {
        var tries = 0;
        var timer = window.setInterval(function () {
            tries++;
            var panel = document.querySelector('[data-r4v5-left-panel="seo"]');
            ensureSection(panel);
            updatePrompt();
            if ((panel && panel.querySelector('#r4v5PageBuilderPrompt')) || tries > 40) {
                window.clearInterval(timer);
            }
        }, 150);

        document.addEventListener('input', function (event) {
            if (event.target && event.target.closest('[data-r4v5-left-panel="seo"]')) updatePrompt();
        });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.closest('[data-r4v5-left-panel="seo"]')) updatePrompt();
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
