(function () {
    'use strict';

    const registry = window.R4EditorV5Registry;
    if (!registry) return;

    const icons = {
        section: '<svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2"/><path d="M7 9h10M7 13h7"/></svg>',
        hero: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M6 9h8M6 13h6M16 15h2"/></svg>',
        columns: '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="7" height="14" rx="1"/><rect x="13" y="5" width="7" height="14" rx="1"/></svg>',
        cta: '<svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="3"/><path d="M8 12h7M13 9l3 3-3 3"/></svg>',
        advanced: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h4v4H7zM13 9h4v4h-4zM7 15h10"/></svg>',
        footer: '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M6 9h12M6 14h4M12 14h6M6 17h12"/></svg>'
    };

    registry.registerWidget({
        key: 'r4v5-simple-section',
        label: 'Sezione semplice',
        category: 'Layout',
        order: 10,
        media: icons.section,
        content: '<section style="padding:72px 24px;background:#ffffff;"><div style="max-width:1120px;margin:0 auto;"><h2 style="font-size:42px;line-height:1.1;font-weight:900;margin:0 0 18px;color:#111827;">Sezione V5</h2><p style="font-size:18px;line-height:1.75;color:#475569;margin:0;">Contenuto modificabile della sezione.</p></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-advanced-section',
        label: 'Sezione avanzata',
        category: 'Layout',
        order: 12,
        media: icons.advanced,
        content: '<section data-r4v5-advanced-section="1" data-r4v5-cols-desktop="3" data-r4v5-cols-tablet="2" data-r4v5-cols-mobile="1" data-r4v5-gap-x="24" data-r4v5-gap-y="24" style="padding:84px 24px;margin:0;background:#ffffff;color:#111827;min-height:0;"><div data-r4v5-advanced-inner="1" style="max-width:1120px;margin:0 auto;"><div style="margin:0 0 34px;text-align:center;"><span style="display:inline-flex;margin-bottom:12px;padding:8px 12px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Sezione avanzata</span><h2 style="font-size:clamp(34px,4vw,52px);line-height:1.08;font-weight:900;letter-spacing:-.03em;margin:0 0 14px;color:#111827;">Costruisci una sezione modulare</h2><p style="max-width:760px;margin:0 auto;font-size:18px;line-height:1.75;color:#64748b;">Gestisci colonne, gap, spaziature, sfondi e contenuti interni.</p></div><div data-r4v5-advanced-grid="1" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px 24px;"><article data-r4v5-advanced-col="1" style="padding:28px;border-radius:24px;background:#f8fafc;border:1px solid #e5e7eb;box-shadow:0 14px 34px rgba(15,23,42,.06);"><h3 style="font-size:24px;line-height:1.2;font-weight:900;margin:0 0 10px;color:#111827;">Colonna uno</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Testo modificabile. Puoi inserire immagini, bottoni o altri blocchi.</p></article><article data-r4v5-advanced-col="1" style="padding:28px;border-radius:24px;background:#f8fafc;border:1px solid #e5e7eb;box-shadow:0 14px 34px rgba(15,23,42,.06);"><h3 style="font-size:24px;line-height:1.2;font-weight:900;margin:0 0 10px;color:#111827;">Colonna due</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Ogni colonna è editabile e compatibile con Inspector, media e animazioni.</p></article><article data-r4v5-advanced-col="1" style="padding:28px;border-radius:24px;background:#f8fafc;border:1px solid #e5e7eb;box-shadow:0 14px 34px rgba(15,23,42,.06);"><h3 style="font-size:24px;line-height:1.2;font-weight:900;margin:0 0 10px;color:#111827;">Colonna tre</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Usa questa base per servizi, vantaggi, card o contenuti informativi.</p></article></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-footer-builder',
        label: 'Footer builder',
        category: 'Layout',
        order: 14,
        media: icons.footer,
        content: '<footer data-r4v5-footer-builder="1" data-r4v5-footer-cols-desktop="4" data-r4v5-footer-cols-tablet="2" data-r4v5-footer-cols-mobile="1" data-r4v5-footer-gap-x="32" data-r4v5-footer-gap-y="28" style="padding:72px 24px 28px;margin:0;background:#0f172a;color:#e5e7eb;"><div data-r4v5-footer-inner="1" style="max-width:1180px;margin:0 auto;"><div data-r4v5-footer-grid="1" style="display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:28px 32px;align-items:start;"><div data-r4v5-footer-col="brand" style="min-width:0;"><h3 data-r4v5-footer-brand="1" style="font-size:26px;line-height:1.2;font-weight:900;margin:0 0 12px;color:#ffffff;">R4Software</h3><p data-r4v5-footer-description="1" style="font-size:15px;line-height:1.75;color:#cbd5e1;margin:0 0 18px;">Sviluppiamo software, CRM, siti web professionali e soluzioni digitali su misura per aziende e professionisti.</p><a data-r4v5-footer-cta="1" href="/contatti" style="display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:999px;background:#0d6efd;color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;">Richiedi consulenza</a></div><nav data-r4v5-footer-col="links" aria-label="Footer servizi" style="min-width:0;"><h4 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;font-weight:900;margin:0 0 14px;color:#ffffff;">Servizi</h4><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Sviluppo software</a><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">CRM su misura</a><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Siti web professionali</a></nav><nav data-r4v5-footer-col="company" aria-label="Footer azienda" style="min-width:0;"><h4 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;font-weight:900;margin:0 0 14px;color:#ffffff;">Azienda</h4><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Chi siamo</a><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Portfolio</a><a href="#" style="display:block;color:#cbd5e1;text-decoration:none;margin:0 0 10px;font-size:15px;">Contatti</a></nav><div data-r4v5-footer-col="contacts" style="min-width:0;"><h4 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;font-weight:900;margin:0 0 14px;color:#ffffff;">Contatti</h4><p style="font-size:15px;line-height:1.7;color:#cbd5e1;margin:0;">Olbia, Sardegna<br>Email: info@r4software.it<br>Web: www.r4software.it</p></div></div><div data-r4v5-footer-bottom="1" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;border-top:1px solid rgba(255,255,255,.12);margin-top:42px;padding-top:22px;color:#94a3b8;font-size:13px;"><span>© 2026 R4Software s.r.l. Tutti i diritti riservati.</span><span><a href="/privacy-policy" style="color:#94a3b8;text-decoration:none;">Privacy</a> · <a href="/cookie-policy" style="color:#94a3b8;text-decoration:none;">Cookie</a></span></div></div></footer>'
    });

    registry.registerWidget({
        key: 'r4v5-hero-simple',
        label: 'Hero semplice',
        category: 'Layout',
        order: 20,
        media: icons.hero,
        content: '<section style="padding:96px 24px;background:linear-gradient(135deg,#eaf3ff,#ffffff);"><div style="max-width:1120px;margin:0 auto;"><span style="display:inline-flex;margin-bottom:16px;padding:8px 12px;border-radius:999px;background:#ffffff;color:#0d6efd;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;">Editor V5</span><h1 style="font-size:clamp(42px,6vw,76px);line-height:1.02;font-weight:900;letter-spacing:-.05em;margin:0 0 20px;color:#111827;">Crea pagine professionali</h1><p style="max-width:720px;font-size:20px;line-height:1.75;color:#475569;margin:0 0 28px;">Un editor più stabile, modulare e compatibile con i contenuti esistenti.</p><a href="#" style="display:inline-flex;padding:14px 22px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Richiedi informazioni</a></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-two-columns',
        label: 'Due colonne',
        category: 'Layout',
        order: 30,
        media: icons.columns,
        content: '<section style="padding:72px 24px;background:#ffffff;"><div style="max-width:1120px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:center;"><div><h2 style="font-size:40px;line-height:1.1;font-weight:900;margin:0 0 16px;color:#111827;">Titolo colonna</h2><p style="font-size:18px;line-height:1.75;color:#475569;margin:0;">Testo descrittivo della prima colonna.</p></div><div style="padding:30px;border-radius:24px;background:#f8fafc;border:1px solid #e5e7eb;"><h3 style="font-size:24px;font-weight:900;margin:0 0 12px;color:#111827;">Seconda colonna</h3><p style="font-size:16px;line-height:1.7;color:#64748b;margin:0;">Contenuto modificabile.</p></div></div></section>'
    });

    registry.registerWidget({
        key: 'r4v5-final-cta',
        label: 'CTA finale',
        category: 'Layout',
        order: 40,
        media: icons.cta,
        content: '<section style="padding:82px 24px;background:#111827;color:#ffffff;text-align:center;"><div style="max-width:900px;margin:0 auto;"><h2 style="font-size:clamp(36px,5vw,58px);line-height:1.08;font-weight:900;margin:0 0 18px;color:#ffffff;">Pronto a iniziare?</h2><p style="font-size:19px;line-height:1.75;color:#cbd5e1;margin:0 0 28px;">Trasforma questa sezione in una chiamata all’azione efficace.</p><a href="#" style="display:inline-flex;padding:14px 22px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Contattaci</a></div></section>'
    });
})();
