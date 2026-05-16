import { mergeTraits, traitValue } from '../helpers.js';
import {
    safeImages,
    stringifyImages,
    appendImagesToComponent,
    replaceImagesInComponent,
    clearImagesInComponent
} from '../mediaManager.js';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function registerImageSliderBlock(editor, openImagePicker) {
    editor.DomComponents.addType('r4-image-slider', {
        isComponent: el => {
            if (el && el.classList && el.classList.contains('r4-image-slider')) {
                return { type: 'r4-image-slider' };
            }
            return false;
        },

        model: {
            defaults: {
                tagName: 'section',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                removable: true,
                stylable: true,
                attributes: {
                    class: 'r4-image-slider'
                },

                slider_title: 'Slider immagini',
                slider_subtitle: 'Una selezione di immagini in evidenza.',
                slider_show_title: 'true',
                slider_show_subtitle: 'true',
                slider_show_caption: 'true',
                slider_lightbox: 'true',
                slider_autoplay: 'true',
                slider_autoplay_delay: '3500',
                slider_show_arrows: 'true',
                slider_show_dots: 'true',
                slider_height: '460',
                slider_radius: '18',

                slider_images: stringifyImages([]),

                traits: mergeTraits([
                    { type: 'text', name: 'slider_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'slider_subtitle', label: 'Sottotitolo', changeProp: 1 },

                    {
                        type: 'select',
                        name: 'slider_show_title',
                        label: 'Mostra titolo',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'slider_show_subtitle',
                        label: 'Mostra sottotitolo',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'slider_show_caption',
                        label: 'Mostra caption',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'slider_lightbox',
                        label: 'Lightbox',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'slider_autoplay',
                        label: 'Autoplay',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'number',
                        name: 'slider_autoplay_delay',
                        label: 'Delay autoplay (ms)',
                        changeProp: 1,
                        min: 1000,
                        max: 15000
                    },
                    {
                        type: 'select',
                        name: 'slider_show_arrows',
                        label: 'Mostra frecce',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'slider_show_dots',
                        label: 'Mostra dots',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'number',
                        name: 'slider_height',
                        label: 'Altezza slider',
                        changeProp: 1,
                        min: 180,
                        max: 900
                    },
                    {
                        type: 'number',
                        name: 'slider_radius',
                        label: 'Border radius',
                        changeProp: 1,
                        min: 0,
                        max: 60
                    },
                    {
                        type: 'button',
                        text: 'Aggiungi immagini',
                        full: true,
                        command: 'r4-slider-add-images'
                    },
                    {
                        type: 'button',
                        text: 'Sostituisci immagini',
                        full: true,
                        command: 'r4-slider-replace-images'
                    },
                    {
                        type: 'button',
                        text: 'Svuota slider',
                        full: true,
                        command: 'r4-slider-clear-images'
                    }
                ])
            },

            init() {
                const render = () => {
                    const title = traitValue(this, 'slider_title', 'Slider immagini');
                    const subtitle = traitValue(this, 'slider_subtitle', '');
                    const showTitle = traitValue(this, 'slider_show_title', 'true') === 'true';
                    const showSubtitle = traitValue(this, 'slider_show_subtitle', 'true') === 'true';
                    const showCaption = traitValue(this, 'slider_show_caption', 'true') === 'true';
                    const lightbox = traitValue(this, 'slider_lightbox', 'true') === 'true';
                    const autoplay = traitValue(this, 'slider_autoplay', 'true') === 'true';
                    const showArrows = traitValue(this, 'slider_show_arrows', 'true') === 'true';
                    const showDots = traitValue(this, 'slider_show_dots', 'true') === 'true';
                    const autoplayDelay = Math.max(1000, Math.min(15000, parseInt(traitValue(this, 'slider_autoplay_delay', '3500'), 10) || 3500));
                    const height = Math.max(180, Math.min(900, parseInt(traitValue(this, 'slider_height', '460'), 10) || 460));
                    const radius = Math.max(0, Math.min(60, parseInt(traitValue(this, 'slider_radius', '18'), 10) || 18));
                    const images = safeImages(traitValue(this, 'slider_images', '[]'));

                    this.addAttributes({
                        class: 'r4-image-slider',
                        style: [
                            `--r4-slider-height:${height}px`,
                            `--r4-slider-radius:${radius}px`
                        ].join(';'),
                        'data-slider-autoplay': autoplay ? '1' : '0',
                        'data-slider-delay': String(autoplayDelay),
                        'data-slider-arrows': showArrows ? '1' : '0',
                        'data-slider-dots': showDots ? '1' : '0',
                        'data-slider-lightbox': lightbox ? '1' : '0'
                    });

                    const headerHtml = `
                        ${(showTitle && title) ? `<h2 class="r4-image-slider__title">${escapeHtml(title)}</h2>` : ''}
                        ${(showSubtitle && subtitle) ? `<div class="r4-image-slider__subtitle">${escapeHtml(subtitle)}</div>` : ''}
                    `;

                    const slidesHtml = images.length
                        ? images.map((item, index) => {
                            const previewSrc = item.q75 || item.q59 || item.thumb || item.full || item.src;
                            const fullSrc = item.full || item.src || previewSrc;

                            const media = lightbox
                                ? `
                                    <a
                                        href="${escapeHtml(fullSrc)}"
                                        class="r4-image-slider__media"
                                        data-slider-lightbox-trigger="1"
                                        data-slider-src="${escapeHtml(fullSrc)}"
                                        data-slider-alt="${escapeHtml(item.alt || '')}"
                                        data-slider-caption="${escapeHtml(item.caption || '')}"
                                    >
                                        <img
                                            src="${escapeHtml(previewSrc)}"
                                            alt="${escapeHtml(item.alt || `Slide ${index + 1}`)}"
                                            class="r4-image-slider__img"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                `
                                : `
                                    <div class="r4-image-slider__media">
                                        <img
                                            src="${escapeHtml(previewSrc)}"
                                            alt="${escapeHtml(item.alt || `Slide ${index + 1}`)}"
                                            class="r4-image-slider__img"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </div>
                                `;

                            return `
                                <div class="r4-image-slider__slide ${index === 0 ? 'is-active' : ''}" data-slide-index="${index}">
                                    ${media}
                                    ${(showCaption && item.caption) ? `<div class="r4-image-slider__caption">${escapeHtml(item.caption)}</div>` : ''}
                                </div>
                            `;
                        }).join('')
                        : `
                            <div class="r4-image-slider__empty">
                                Nessuna immagine selezionata
                            </div>
                        `;

                    const dotsHtml = images.length > 1 && showDots
                        ? `
                            <div class="r4-image-slider__dots">
                                ${images.map((_, index) => `
                                    <button
                                        type="button"
                                        class="r4-image-slider__dot ${index === 0 ? 'is-active' : ''}"
                                        data-slider-dot="${index}"
                                        aria-label="Vai alla slide ${index + 1}"
                                    ></button>
                                `).join('')}
                            </div>
                        `
                        : '';

                    const arrowsHtml = images.length > 1 && showArrows
                        ? `
                            <button type="button" class="r4-image-slider__arrow r4-image-slider__arrow--prev" data-slider-prev="1" aria-label="Slide precedente">&#10094;</button>
                            <button type="button" class="r4-image-slider__arrow r4-image-slider__arrow--next" data-slider-next="1" aria-label="Slide successiva">&#10095;</button>
                        `
                        : '';

                    this.components(`
                        <div class="r4-image-slider__inner">
                            <div class="r4-image-slider__header">
                                ${headerHtml}
                            </div>

                            <div class="r4-image-slider__viewport">
                                <div class="r4-image-slider__track">
                                    ${slidesHtml}
                                </div>
                                ${arrowsHtml}
                            </div>

                            ${dotsHtml}
                        </div>
                    `);
                };

                render();

                this.on(
                    'change:slider_title change:slider_subtitle change:slider_show_title change:slider_show_subtitle change:slider_show_caption change:slider_lightbox change:slider_autoplay change:slider_autoplay_delay change:slider_show_arrows change:slider_show_dots change:slider_height change:slider_radius change:slider_images',
                    render
                );
            }
        }
    });

    editor.Commands.add('r4-slider-add-images', {
        async run(ed) {
            const selected = ed.getSelected();
            if (!selected || selected.get('type') !== 'r4-image-slider') return;

            const picked = await openImagePicker({ mode: 'image', multiple: true });
            if (!picked || !picked.length) return;

            appendImagesToComponent(selected, 'slider_images', picked);
        }
    });

    editor.Commands.add('r4-slider-replace-images', {
        async run(ed) {
            const selected = ed.getSelected();
            if (!selected || selected.get('type') !== 'r4-image-slider') return;

            const picked = await openImagePicker({ mode: 'image', multiple: true });
            if (!picked || !picked.length) return;

            replaceImagesInComponent(selected, 'slider_images', picked);
        }
    });

    editor.Commands.add('r4-slider-clear-images', {
        run(ed) {
            const selected = ed.getSelected();
            if (!selected || selected.get('type') !== 'r4-image-slider') return;

            const ok = window.confirm('Vuoi svuotare lo slider?');
            if (!ok) return;

            clearImagesInComponent(selected, 'slider_images');
        }
    });

    editor.BlockManager.add('image-slider', {
        label: 'Slider Immagini',
        category: 'R4 Components',
        content: {
            type: 'r4-image-slider',
            slider_title: 'Slider immagini',
            slider_subtitle: 'Una selezione di immagini in evidenza.',
            slider_show_title: 'true',
            slider_show_subtitle: 'true',
            slider_show_caption: 'true',
            slider_lightbox: 'true',
            slider_autoplay: 'true',
            slider_autoplay_delay: '3500',
            slider_show_arrows: 'true',
            slider_show_dots: 'true',
            slider_height: '460',
            slider_radius: '18'
        }
    });
}
