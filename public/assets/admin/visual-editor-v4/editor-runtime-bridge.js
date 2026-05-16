(function () {
    'use strict';

    function normalizeLegacyAnimations(body) {
        if (!body) return;

        body.querySelectorAll('[data-anim]').forEach(function (element) {
            if (!element.hasAttribute('data-r4-animation')) {
                element.setAttribute('data-r4-animation', element.getAttribute('data-anim') || '');
            }
            if (!element.hasAttribute('data-r4-animation-duration') && element.hasAttribute('data-anim-duration')) {
                element.setAttribute('data-r4-animation-duration', element.getAttribute('data-anim-duration') || '700');
            }
            if (!element.hasAttribute('data-r4-animation-delay') && element.hasAttribute('data-anim-delay')) {
                element.setAttribute('data-r4-animation-delay', element.getAttribute('data-anim-delay') || '0');
            }
            if (!element.hasAttribute('data-r4-animation-distance') && element.hasAttribute('data-anim-distance')) {
                element.setAttribute('data-r4-animation-distance', element.getAttribute('data-anim-distance') || '40');
            }
        });
    }

    function revealAnimatedElements(body) {
        if (!body) return;

        body.querySelectorAll('[data-r4-animation], [data-anim]').forEach(function (element) {
            element.classList.add('r4-animation-visible', 'is-animated');
        });
    }

    function advancedSectionHtml() {
        return [
            '<section class="r4v4-advanced-section r4v4-section-grid" data-r4v4-advanced-section="1" data-r4-component="section-grid" style="position:relative;padding:96px 24px;margin:0;background:linear-gradient(135deg,#f8fafc,#eaf3ff);overflow:hidden;">',
                '<div class="r4v4-advanced-section__overlay" style="position:absolute;inset:0;background:radial-gradient(circle at top right,rgba(13,110,253,.16),transparent 42%);pointer-events:none;"></div>',
                '<div class="r4v4-advanced-section__container" style="position:relative;z-index:1;max-width:1180px;margin:0 auto;">',
                    '<div class="r4v4-advanced-section__header" style="max-width:760px;margin:0 0 38px;">',
                        '<span style="display:inline-flex;margin-bottom:14px;padding:7px 12px;border-radius:999px;background:#ffffff;color:#0d6efd;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;box-shadow:0 8px 22px rgba(15,23,42,.08);">Sezione avanzata</span>',
                        '<h2 style="font-size:clamp(34px,4vw,56px);line-height:1.05;font-weight:900;letter-spacing:-.045em;margin:0 0 18px;color:#111827;">Costruisci una sezione modulare</h2>',
                        '<p style="font-size:18px;line-height:1.75;color:#475569;margin:0;">Gestisci sfondo, gradienti, immagini, colonne, spaziature, pulsanti, testi e contenuti interni direttamente dall’editor visuale.</p>',
                    '</div>',
                    '<div class="r4v4-advanced-section__grid r4v4-section-grid-inner" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;align-items:stretch;">',
                        '<div class="r4v4-advanced-section__card r4v4-section-column" style="padding:30px;border-radius:24px;background:#ffffff;border:1px solid #e5e7eb;box-shadow:0 16px 40px rgba(15,23,42,.08);">',
                            '<img src="https://placehold.co/640x420?text=Immagine" alt="" style="width:100%;height:190px;object-fit:cover;border-radius:18px;margin-bottom:22px;">',
                            '<h3 style="font-size:24px;font-weight:900;line-height:1.15;margin:0 0 12px;color:#111827;">Blocco immagine</h3>',
                            '<p style="font-size:16px;line-height:1.7;color:#64748b;margin:0 0 18px;">Sostituisci l’immagine, cambia alt, link, spaziature, bordi e ombre.</p>',
                            '<a href="#" style="display:inline-flex;padding:12px 18px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Approfondisci</a>',
                        '</div>',
                        '<div class="r4v4-advanced-section__card r4v4-section-column" style="padding:30px;border-radius:24px;background:#111827;color:#ffffff;border:1px solid #1f2937;box-shadow:0 16px 40px rgba(15,23,42,.12);">',
                            '<div style="width:58px;height:58px;border-radius:20px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:22px;">✓</div>',
                            '<h3 style="font-size:24px;font-weight:900;line-height:1.15;margin:0 0 12px;color:#ffffff;">Blocco testo</h3>',
                            '<p style="font-size:16px;line-height:1.7;color:#cbd5e1;margin:0 0 18px;">Modifica font, colore, dimensione, allineamento e contenuti dal pannello avanzato.</p>',
                            '<a href="#" style="display:inline-flex;padding:12px 18px;border-radius:999px;background:#ffffff;color:#111827;text-decoration:none;font-weight:900;">Scopri</a>',
                        '</div>',
                        '<div class="r4v4-advanced-section__card r4v4-section-column" style="padding:30px;border-radius:24px;background:#ffffff;border:1px solid #e5e7eb;box-shadow:0 16px 40px rgba(15,23,42,.08);">',
                            '<div style="width:58px;height:58px;border-radius:20px;background:#eaf3ff;color:#0d6efd;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:900;margin-bottom:22px;">3</div>',
                            '<h3 style="font-size:24px;font-weight:900;line-height:1.15;margin:0 0 12px;color:#111827;">Colonne flessibili</h3>',
                            '<p style="font-size:16px;line-height:1.7;color:#64748b;margin:0 0 18px;">Duplica o elimina colonne, cambia gap, margini, padding e struttura della sezione.</p>',
                            '<a href="#" style="display:inline-flex;padding:12px 18px;border-radius:999px;background:#0d6efd;color:#fff;text-decoration:none;font-weight:900;">Configura</a>',
                        '</div>',
                    '</div>',
                '</div>',
            '</section>'
        ].join('');
    }

    function registerAdvancedSectionWidget(editor) {
        if (!editor || !editor.BlockManager || editor.__r4v4AdvancedSectionRegistered) return false;
        editor.__r4v4AdvancedSectionRegistered = true;

        editor.BlockManager.add('r4v4-advanced-section-widget', {
            label: 'Sezione avanzata',
            category: 'Layout',
            media: '<span class="r4v4-block-icon">▦</span>',
            content: advancedSectionHtml()
        });

        return true;
    }

    function injectRuntime(editor) {
        if (!editor || !editor.Canvas) return false;

        registerAdvancedSectionWidget(editor);

        const doc = editor.Canvas.getDocument && editor.Canvas.getDocument();
        const body = editor.Canvas.getBody && editor.Canvas.getBody();

        if (!doc || !doc.head || !body) return false;

        body.classList.add('page-visual-content');
        normalizeLegacyAnimations(body);

        if (!doc.getElementById('r4v4-runtime-css')) {
            const link = doc.createElement('link');
            link.id = 'r4v4-runtime-css';
            link.rel = 'stylesheet';
            link.href = '/assets/page-builder/v4/runtime.css?v=' + Date.now();
            doc.head.appendChild(link);
        }

        if (!doc.getElementById('r4v4-runtime-js')) {
            const script = doc.createElement('script');
            script.id = 'r4v4-runtime-js';
            script.src = '/assets/page-builder/v4/runtime.js?v=' + Date.now();
            script.defer = true;
            script.onload = function () {
                if (doc.defaultView && doc.defaultView.R4V4Runtime && typeof doc.defaultView.R4V4Runtime.boot === 'function') {
                    doc.defaultView.R4V4Runtime.boot();
                }
            };
            doc.body.appendChild(script);
        } else if (doc.defaultView && doc.defaultView.R4V4Runtime && typeof doc.defaultView.R4V4Runtime.boot === 'function') {
            doc.defaultView.R4V4Runtime.boot();
        }

        if (!doc.getElementById('r4v4-editor-preview-fixes')) {
            const style = doc.createElement('style');
            style.id = 'r4v4-editor-preview-fixes';
            style.textContent = [
                'html,body{min-height:100%;overflow:auto!important;}',
                '.page-visual-content{min-height:100%;}',
                '.r4v4-advanced-slider,.r4v4-fullscreen-slider{outline:1px dashed rgba(13,110,253,.25);}',
                '.r4v4-fullscreen-slider{min-height:760px!important;}',
                '.r4v4-fullscreen-slider__viewport{min-height:760px!important;}',
                '.r4v4-fullscreen-slider__content{min-height:760px!important;}',
                '.r4v4-slider-arrow,.r4v4-slider-dot{pointer-events:none;}',
                '[data-r4-animation]::after,[data-anim]::after{content:"Animazione";display:inline-block;margin-left:8px;padding:3px 7px;border-radius:999px;background:#eaf3ff;color:#0d6efd;font-size:11px;font-weight:800;vertical-align:middle;}'
            ].join('\n');
            doc.head.appendChild(style);
        }

        revealAnimatedElements(body);

        return true;
    }

    function getEditor() {
        return window.r4VisualEditorV4Instance || window.R4VisualEditorV4Editor || window.editorV4 || window.gjsEditor || null;
    }

    function boot() {
        const editor = getEditor();
        if (!editor) return false;

        registerAdvancedSectionWidget(editor);
        injectRuntime(editor);
        editor.on('load', function () { injectRuntime(editor); });
        editor.on('component:add', function () { window.setTimeout(function () { injectRuntime(editor); }, 50); });
        editor.on('component:update', function () { window.setTimeout(function () { injectRuntime(editor); }, 50); });
        editor.on('component:selected', function () { window.setTimeout(function () { injectRuntime(editor); }, 50); });

        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        let attempts = 0;
        const timer = window.setInterval(function () {
            attempts++;
            if (boot() || attempts > 40) {
                window.clearInterval(timer);
            }
        }, 150);
    });
})();
