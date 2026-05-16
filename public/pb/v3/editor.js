import { openImagePicker } from '../mediaPicker.js';
import { safeParseJson, applyAnimationRuntime } from './helpers.js';
import { initEditorUI } from './ui.js';
import { registerBasicBlocks } from './blocks/basic.js';
import { registerLayoutBlocks } from './blocks/layout.js';
import { registerImageBlock } from './blocks/imageBlock.js';
import { registerFeatureCardBlock } from './blocks/featureCard.js';
import { registerProductCardBlock } from './blocks/productCard.js';
import { registerAlertBoxBlock } from './blocks/alertBox.js';
import { registerGalleryBlock } from './blocks/gallery.js';
import { registerImageSliderBlock } from './blocks/imageSlider.js';
import { registerEyebrowBadgeBlock } from './blocks/eyebrowBadge.js';
import { registerSectionHeaderBlock } from './blocks/sectionHeader.js';
import { registerStatsGridBlock } from './blocks/statsGrid.js';
import { registerFaqBlock } from './blocks/faqBlock.js';
import { registerFinalCtaBlock } from './blocks/finalCta.js';

(function () {
    const form = document.getElementById('pageFormV3');
    const statusField = document.getElementById('statusFieldV3');
    const htmlField = document.getElementById('visual_html');
    const cssField = document.getElementById('visual_css');
    const jsonField = document.getElementById('visual_json');

    if (!form || !htmlField || !cssField || !jsonField) {
        console.warn('V3 editor: campi form mancanti.');
        return;
    }

    const initialProject = safeParseJson((jsonField.value || '').trim(), null);

    const editor = grapesjs.init({
        container: '#gjs',
        height: '900px',
        fromElement: false,
        storageManager: false,
        components: htmlField.value || '',
        style: cssField.value || '',
        blockManager: { appendTo: '#gjs-blocks' },
        layerManager: { appendTo: '#gjs-layers' },
        styleManager: { appendTo: '#gjs-styles' },
        traitManager: { appendTo: '#gjs-traits' },
        selectorManager: { componentFirst: true },
        panels: { defaults: [] }
    });

    window.r4V3Editor = {
        instance: editor,

        refresh() {
            try {
                const pos = {
                    x: window.scrollX || window.pageXOffset || 0,
                    y: window.scrollY || window.pageYOffset || 0
                };

                editor.refresh();

                window.scrollTo(pos.x, pos.y);

                requestAnimationFrame(function () {
                    window.scrollTo(pos.x, pos.y);
                });

                setTimeout(function () {
                    window.scrollTo(pos.x, pos.y);
                }, 80);

                setTimeout(function () {
                    window.scrollTo(pos.x, pos.y);
                }, 180);
            } catch (e) {
                console.warn('V3 editor refresh error', e);
            }
        }
    };

    function getFrameDocument() {
        try {
            return editor.Canvas.getDocument();
        } catch (e) {
            return null;
        }
    }

    function getFrameBody() {
        try {
            return editor.Canvas.getBody();
        } catch (e) {
            return null;
        }
    }

    function injectCanvasStyles(doc) {
        if (!doc) return;

        const existingRuntimeStyle = doc.getElementById('r4-v3-runtime-style');
        if (existingRuntimeStyle) existingRuntimeStyle.remove();

        const existingRuntimeLink = doc.getElementById('r4-v3-runtime-css-link');
        if (existingRuntimeLink) existingRuntimeLink.remove();

        const existingPageCss = doc.getElementById('r4-v3-page-visual-css');
        if (existingPageCss) existingPageCss.remove();

        const existingVars = doc.getElementById('r4-v3-editor-vars');
        if (existingVars) existingVars.remove();

        const link = doc.createElement('link');
        link.id = 'r4-v3-runtime-css-link';
        link.rel = 'stylesheet';
        link.href = `/pb/v3/runtime.css?v=${Date.now()}`;
        doc.head.appendChild(link);

        const parentStyles = window.getComputedStyle(document.documentElement);

        const fontBody = parentStyles.getPropertyValue('--font-body') || 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        const fontHeading = parentStyles.getPropertyValue('--font-heading') || fontBody;
        const fontTitle = parentStyles.getPropertyValue('--font-title') || fontHeading;

        const weightBody = parentStyles.getPropertyValue('--weight-body') || '400';
        const weightHeading = parentStyles.getPropertyValue('--weight-heading') || '700';
        const weightTitle = parentStyles.getPropertyValue('--weight-title') || '700';

        const editorVars = doc.createElement('style');
        editorVars.id = 'r4-v3-editor-vars';
        editorVars.innerHTML = `
        :root {
            --font-body: ${fontBody};
            --font-heading: ${fontHeading};
            --font-title: ${fontTitle};
            --weight-body: ${weightBody};
            --weight-heading: ${weightHeading};
            --weight-title: ${weightTitle};
        }
    `;
        doc.head.appendChild(editorVars);

        const pageCssStyle = doc.createElement('style');
        pageCssStyle.id = 'r4-v3-page-visual-css';

        /*
         * Importante:
         * cssField.value contiene il CSS salvato della pagina.
         * editor.getCss() contiene il CSS corrente nel composer GrapesJS.
         * Lo iniettiamo anche nell'iframe per allineare builder e frontend.
         */
        pageCssStyle.innerHTML = `
        ${cssField.value || ''}
        ${editor.getCss ? editor.getCss() : ''}
    `;

        doc.head.appendChild(pageCssStyle);

        const style = doc.createElement('style');
        style.id = 'r4-v3-runtime-style';
        style.innerHTML = `
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            min-height: 100% !important;
            width: 100% !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            scroll-behavior: auto !important;
            overflow-anchor: none !important;
        }

        body {
            box-sizing: border-box !important;
            font-family: var(--font-body, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
            font-weight: var(--weight-body, 400);
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--font-heading, var(--font-body, system-ui)) !important;
            font-weight: var(--weight-heading, 700);
        }

        h1 {
            font-family: var(--font-title, var(--font-heading, var(--font-body, system-ui))) !important;
            font-weight: var(--weight-title, 700);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box !important;
        }

        * {
            scroll-margin-top: 0 !important;
            scroll-margin-bottom: 0 !important;
        }

        [contenteditable="true"]:focus,
        input:focus,
        textarea:focus,
        select:focus,
        button:focus,
        a:focus {
            outline: none !important;
            scroll-margin-top: 0 !important;
            scroll-margin-bottom: 0 !important;
        }

        [data-gjs-type],
        .gjs-selected,
        .gjs-hovered,
        .gjs-comp-selected {
            scroll-margin-top: 0 !important;
            scroll-margin-bottom: 0 !important;
        }

        [data-anim] {
            opacity: 1;
            animation-fill-mode: both;
            animation-duration: 600ms;
            animation-delay: 0ms;
        }

        [data-anim="fade-in"] { animation-name: r4FadeIn; }
        [data-anim="fade-up"] { animation-name: r4FadeUp; }
        [data-anim="fade-left"] { animation-name: r4FadeLeft; }
        [data-anim="fade-right"] { animation-name: r4FadeRight; }
        [data-anim="zoom-in"] { animation-name: r4ZoomIn; }

        [data-anim="flip-up"] {
            animation-name: r4FlipUp;
            transform-origin: center bottom;
        }

        @keyframes r4FadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes r4FadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes r4FadeLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes r4FadeRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes r4ZoomIn {
            from { opacity: 0; transform: scale(.92); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes r4FlipUp {
            from { opacity: 0; transform: perspective(800px) rotateX(20deg) translateY(20px); }
            to { opacity: 1; transform: perspective(800px) rotateX(0) translateY(0); }
        }
    `;

        doc.head.appendChild(style);
    }

    function initSharedLightbox() {
        try {
            const canvasDoc = getFrameDocument();
            const canvasBody = getFrameBody();

            if (!canvasDoc || !canvasBody) return;

            let lightbox = canvasDoc.getElementById('r4-gallery-lightbox');

            if (!lightbox) {
                lightbox = canvasDoc.createElement('div');
                lightbox.id = 'r4-gallery-lightbox';
                lightbox.className = 'r4-gallery-lightbox';
                lightbox.innerHTML = `
                    <div class="r4-gallery-lightbox__dialog">
                        <button type="button" class="r4-gallery-lightbox__close" aria-label="Chiudi">&times;</button>
                        <img src="" alt="" class="r4-gallery-lightbox__img">
                        <div class="r4-gallery-lightbox__caption"></div>
                    </div>
                `;
                canvasBody.appendChild(lightbox);
            }

            if (lightbox.__r4Bound) return;
            lightbox.__r4Bound = true;

            const img = lightbox.querySelector('.r4-gallery-lightbox__img');
            const caption = lightbox.querySelector('.r4-gallery-lightbox__caption');
            const closeBtn = lightbox.querySelector('.r4-gallery-lightbox__close');

            const closeLightbox = () => {
                lightbox.classList.remove('is-open');

                if (img) {
                    img.src = '';
                    img.alt = '';
                }

                if (caption) {
                    caption.textContent = '';
                }
            };

            canvasDoc.addEventListener('click', (event) => {
                const galleryTrigger = event.target.closest('[data-gallery-lightbox="1"]');
                const sliderTrigger = event.target.closest('[data-slider-lightbox-trigger="1"]');
                const trigger = galleryTrigger || sliderTrigger;

                if (trigger) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (editor.getEditing && editor.getEditing()) {
                        return;
                    }

                    const src = trigger.getAttribute('data-gallery-src') || trigger.getAttribute('data-slider-src') || '';
                    const alt = trigger.getAttribute('data-gallery-alt') || trigger.getAttribute('data-slider-alt') || '';
                    const cap = trigger.getAttribute('data-gallery-caption') || trigger.getAttribute('data-slider-caption') || '';

                    if (img) {
                        img.src = src;
                        img.alt = alt;
                    }

                    if (caption) {
                        caption.textContent = cap;
                    }

                    lightbox.classList.add('is-open');
                    return;
                }

                if (event.target === lightbox || event.target.closest('.r4-gallery-lightbox__close')) {
                    event.preventDefault();
                    closeLightbox();
                }
            });

            canvasDoc.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeLightbox();
                }
            });

            closeBtn?.addEventListener('click', closeLightbox);
        } catch (e) {
            console.warn('shared lightbox init error', e);
        }
    }

    function initSliderRuntime() {
        try {
            const canvasDoc = getFrameDocument();
            if (!canvasDoc) return;

            const sliderRoots = canvasDoc.querySelectorAll('.r4-image-slider');

            sliderRoots.forEach((root) => {
                const slides = Array.from(root.querySelectorAll('.r4-image-slider__slide'));
                const dots = Array.from(root.querySelectorAll('.r4-image-slider__dot'));
                const prevBtn = root.querySelector('[data-slider-prev="1"]');
                const nextBtn = root.querySelector('[data-slider-next="1"]');

                if (root.__r4SliderTimer) {
                    clearInterval(root.__r4SliderTimer);
                    root.__r4SliderTimer = null;
                }

                if (!slides.length || slides.length <= 1) {
                    return;
                }

                const autoplay = root.getAttribute('data-slider-autoplay') === '1';
                const delay = Math.max(1000, parseInt(root.getAttribute('data-slider-delay') || '3500', 10) || 3500);

                let index = slides.findIndex((slide) => slide.classList.contains('is-active'));
                if (index < 0) index = 0;

                const renderSlider = () => {
                    slides.forEach((slide, i) => {
                        slide.classList.toggle('is-active', i === index);
                    });

                    dots.forEach((dot, i) => {
                        dot.classList.toggle('is-active', i === index);
                    });
                };

                const goTo = (newIndex) => {
                    if (newIndex < 0) newIndex = slides.length - 1;
                    if (newIndex >= slides.length) newIndex = 0;

                    index = newIndex;
                    renderSlider();
                };

                const next = () => goTo(index + 1);
                const prev = () => goTo(index - 1);

                const stopAutoplay = () => {
                    if (root.__r4SliderTimer) {
                        clearInterval(root.__r4SliderTimer);
                        root.__r4SliderTimer = null;
                    }
                };

                const startAutoplay = () => {
                    stopAutoplay();

                    if (!autoplay) return;

                    root.__r4SliderTimer = setInterval(() => {
                        next();
                    }, delay);
                };

                if (!root.__r4SliderBound) {
                    prevBtn?.addEventListener('click', (e) => {
                        e.preventDefault();
                        prev();
                        startAutoplay();
                    });

                    nextBtn?.addEventListener('click', (e) => {
                        e.preventDefault();
                        next();
                        startAutoplay();
                    });

                    dots.forEach((dot, i) => {
                        dot.addEventListener('click', (e) => {
                            e.preventDefault();
                            goTo(i);
                            startAutoplay();
                        });
                    });

                    root.addEventListener('mouseenter', stopAutoplay);
                    root.addEventListener('mouseleave', startAutoplay);

                    root.__r4SliderBound = true;
                }

                renderSlider();
                startAutoplay();
            });
        } catch (e) {
            console.warn('slider runtime init error', e);
        }
    }

    function initCanvasEnvironment() {
        const frame = editor.Canvas.getFrameEl();
        const doc = getFrameDocument();
        const body = getFrameBody();

        if (doc) {
            injectCanvasStyles(doc);
        }

        if (body) {
            body.style.background = '#ffffff';
            body.style.margin = '0';
            body.style.padding = '0';
            body.style.width = '100%';
            body.style.maxWidth = '100%';
            body.style.scrollBehavior = 'auto';
            body.style.overflowX = 'hidden';
            body.style.overflowY = 'auto';
            body.style.overflowAnchor = 'none';
        }

        if (doc?.documentElement) {
            doc.documentElement.style.scrollBehavior = 'auto';
            doc.documentElement.style.overflowAnchor = 'none';
        }

        if (frame) {
            frame.style.background = '#ffffff';
            frame.style.width = '100%';
        }

        applyAnimationRuntime(editor);
        initSharedLightbox();
        initSliderRuntime();
    }

    editor.DeviceManager.add({
        id: 'Desktop',
        name: 'Desktop',
        width: ''
    });

    editor.DeviceManager.add({
        id: 'Tablet',
        name: 'Tablet',
        width: '768px',
        widthMedia: '992px'
    });

    editor.DeviceManager.add({
        id: 'Mobile portrait',
        name: 'Mobile',
        width: '375px',
        widthMedia: '480px'
    });

    registerBasicBlocks(editor);
    registerLayoutBlocks(editor);
    registerImageBlock(editor, openImagePicker);
    registerFeatureCardBlock(editor);
    registerProductCardBlock(editor);
    registerAlertBoxBlock(editor);
    registerGalleryBlock(editor, openImagePicker);
    registerImageSliderBlock(editor, openImagePicker);
    registerEyebrowBadgeBlock(editor);
    registerSectionHeaderBlock(editor);
    registerStatsGridBlock(editor);
    registerFaqBlock(editor);
    registerFinalCtaBlock(editor);

    if (initialProject && typeof initialProject === 'object') {
        try {
            editor.loadProjectData(initialProject);
        } catch (e) {
            console.warn('Impossibile caricare visual_json, uso html/css.', e);
        }
    }

    editor.on('load', () => {
        initCanvasEnvironment();
    });

    editor.on('canvas:frame:load', () => {
        initCanvasEnvironment();
    });

    editor.on('component:update:attributes', () => {
        applyAnimationRuntime(editor);
        initSliderRuntime();
    });

    editor.on('component:add', () => {
        applyAnimationRuntime(editor);
        initSliderRuntime();
    });

    editor.on('component:remove', () => {
        applyAnimationRuntime(editor);
        initSliderRuntime();
    });

    editor.on('component:selected', () => {
        const pos = {
            x: window.scrollX || window.pageXOffset || 0,
            y: window.scrollY || window.pageYOffset || 0
        };

        if (window.r4V3Panels && typeof window.r4V3Panels.openRight === 'function') {
            if (typeof window.r4V3Panels.preservePageScroll === 'function') {
                window.r4V3Panels.preservePageScroll(function () {
                    window.r4V3Panels.openRight();
                });
            } else {
                window.r4V3Panels.openRight();
            }
        }

        window.scrollTo(pos.x, pos.y);

        requestAnimationFrame(function () {
            window.scrollTo(pos.x, pos.y);
        });

        setTimeout(function () {
            window.scrollTo(pos.x, pos.y);
        }, 80);

        setTimeout(function () {
            window.scrollTo(pos.x, pos.y);
        }, 180);
    });

    editor.on('component:toggled', () => {
        const selected = editor.getSelected();

        if (!selected) return;

        const pos = {
            x: window.scrollX || window.pageXOffset || 0,
            y: window.scrollY || window.pageYOffset || 0
        };

        if (window.r4V3Panels && typeof window.r4V3Panels.openRight === 'function') {
            if (typeof window.r4V3Panels.preservePageScroll === 'function') {
                window.r4V3Panels.preservePageScroll(function () {
                    window.r4V3Panels.openRight();
                });
            } else {
                window.r4V3Panels.openRight();
            }
        }

        window.scrollTo(pos.x, pos.y);

        requestAnimationFrame(function () {
            window.scrollTo(pos.x, pos.y);
        });

        setTimeout(function () {
            window.scrollTo(pos.x, pos.y);
        }, 80);

        setTimeout(function () {
            window.scrollTo(pos.x, pos.y);
        }, 180);
    });

    editor.on('canvas:drop', () => {
        applyAnimationRuntime(editor);
        initSliderRuntime();
    });

    editor.on('component:drag:end', () => {
        applyAnimationRuntime(editor);
        initSliderRuntime();
    });

    editor.on('rte:enable', (view) => {
        try {
            const el = view?.el;

            if (el && typeof el.focus === 'function') {
                try {
                    el.focus({ preventScroll: true });
                } catch (err) {
                    el.focus();
                }
            }
        } catch (e) {
            console.warn('rte:enable focus error', e);
        }
    });

    initEditorUI(editor, {
        form,
        statusField,
        htmlField,
        cssField,
        jsonField
    });

    const pickPageBgImageBtn = document.getElementById('pickPageBgImageBtn');
    const pageBgImageSrc = document.getElementById('pageBgImageSrc');

    pickPageBgImageBtn?.addEventListener('click', async () => {
        const picked = await openImagePicker({ mode: 'image' });
        if (!picked) return;

        const bestUrl =
            picked.full ||
            picked.url ||
            picked.src ||
            picked.thumb ||
            '';

        if (pageBgImageSrc) {
            pageBgImageSrc.value = bestUrl;
        }
    });
})();
