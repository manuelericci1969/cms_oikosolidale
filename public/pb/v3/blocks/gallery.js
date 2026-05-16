import { mergeTraits, traitValue } from '../helpers.js';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function safeImages(value) {
    if (Array.isArray(value)) {
        return value
            .filter(item => item && typeof item === 'object')
            .map(item => ({
                src: String(item.src || '').trim(),
                alt: String(item.alt || '').trim(),
                caption: String(item.caption || '').trim()
            }))
            .filter(item => item.src !== '');
    }

    if (typeof value === 'string' && value.trim() !== '') {
        try {
            const parsed = JSON.parse(value);
            if (Array.isArray(parsed)) {
                return safeImages(parsed);
            }
        } catch (e) {
            return [];
        }
    }

    return [];
}

function stringifyImages(images) {
    try {
        return JSON.stringify(safeImages(images));
    } catch (e) {
        return '[]';
    }
}

export function registerGalleryBlock(editor, openImagePicker) {
    editor.DomComponents.addType('r4-gallery', {
        isComponent: el => {
            if (el && el.classList && el.classList.contains('r4-gallery')) {
                return { type: 'r4-gallery' };
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
                    class: 'r4-gallery'
                },

                gallery_title: 'Galleria fotografica',
                gallery_subtitle: 'Una selezione di immagini del progetto, prodotto o struttura.',
                gallery_show_title: 'true',
                gallery_show_subtitle: 'true',
                gallery_show_caption: 'true',
                gallery_lightbox: 'true',

                gallery_cols_desktop: '3',
                gallery_cols_tablet: '2',
                gallery_cols_mobile: '1',
                gallery_gap: '16',
                gallery_radius: '14',

                gallery_images: stringifyImages([
                    {
                        src: 'https://via.placeholder.com/1200x800?text=Foto+1',
                        alt: 'Foto 1',
                        caption: 'Caption foto 1'
                    },
                    {
                        src: 'https://via.placeholder.com/1200x800?text=Foto+2',
                        alt: 'Foto 2',
                        caption: 'Caption foto 2'
                    },
                    {
                        src: 'https://via.placeholder.com/1200x800?text=Foto+3',
                        alt: 'Foto 3',
                        caption: 'Caption foto 3'
                    }
                ]),

                traits: mergeTraits([
                    { type: 'text', name: 'gallery_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'gallery_subtitle', label: 'Sottotitolo', changeProp: 1 },

                    {
                        type: 'select',
                        name: 'gallery_show_title',
                        label: 'Mostra titolo',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'gallery_show_subtitle',
                        label: 'Mostra sottotitolo',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'gallery_show_caption',
                        label: 'Mostra caption',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },
                    {
                        type: 'select',
                        name: 'gallery_lightbox',
                        label: 'Lightbox',
                        changeProp: 1,
                        options: [
                            { id: 'true', name: 'Sì' },
                            { id: 'false', name: 'No' }
                        ]
                    },

                    { type: 'number', name: 'gallery_cols_desktop', label: 'Colonne desktop', changeProp: 1, min: 1, max: 6 },
                    { type: 'number', name: 'gallery_cols_tablet', label: 'Colonne tablet', changeProp: 1, min: 1, max: 4 },
                    { type: 'number', name: 'gallery_cols_mobile', label: 'Colonne mobile', changeProp: 1, min: 1, max: 2 },
                    { type: 'number', name: 'gallery_gap', label: 'Gap immagini', changeProp: 1, min: 0, max: 80 },
                    { type: 'number', name: 'gallery_radius', label: 'Border radius', changeProp: 1, min: 0, max: 60 },

                    {
                        type: 'button',
                        text: 'Aggiungi immagini',
                        full: true,
                        command: 'r4-gallery-add-images'
                    },
                    {
                        type: 'button',
                        text: 'Svuota galleria',
                        full: true,
                        command: 'r4-gallery-clear-images'
                    }
                ])
            },

            init() {
                const render = () => {
                    const title = traitValue(this, 'gallery_title', 'Galleria fotografica');
                    const subtitle = traitValue(this, 'gallery_subtitle', '');
                    const showTitle = traitValue(this, 'gallery_show_title', 'true') === 'true';
                    const showSubtitle = traitValue(this, 'gallery_show_subtitle', 'true') === 'true';
                    const showCaption = traitValue(this, 'gallery_show_caption', 'true') === 'true';
                    const lightbox = traitValue(this, 'gallery_lightbox', 'true') === 'true';

                    const colsDesktop = Math.max(1, Math.min(6, parseInt(traitValue(this, 'gallery_cols_desktop', '3'), 10) || 3));
                    const colsTablet = Math.max(1, Math.min(4, parseInt(traitValue(this, 'gallery_cols_tablet', '2'), 10) || 2));
                    const colsMobile = Math.max(1, Math.min(2, parseInt(traitValue(this, 'gallery_cols_mobile', '1'), 10) || 1));
                    const gap = Math.max(0, Math.min(80, parseInt(traitValue(this, 'gallery_gap', '16'), 10) || 16));
                    const radius = Math.max(0, Math.min(60, parseInt(traitValue(this, 'gallery_radius', '14'), 10) || 14));
                    const images = safeImages(traitValue(this, 'gallery_images', '[]'));

                    this.addAttributes({
                        class: 'r4-gallery',
                        style: [
                            `--r4-gallery-cols-desktop:${colsDesktop}`,
                            `--r4-gallery-cols-tablet:${colsTablet}`,
                            `--r4-gallery-cols-mobile:${colsMobile}`,
                            `--r4-gallery-gap:${gap}px`,
                            `--r4-gallery-radius:${radius}px`
                        ].join(';')
                    });

                    const headerHtml = `
                        ${(showTitle && title) ? `<h2 class="r4-gallery__title">${escapeHtml(title)}</h2>` : ''}
                        ${(showSubtitle && subtitle) ? `<div class="r4-gallery__subtitle">${escapeHtml(subtitle)}</div>` : ''}
                    `;

                    const itemsHtml = images.length
                        ? images.map((item, index) => {
                            const imgTag = `
                                <img
                                    src="${escapeHtml(item.src)}"
                                    alt="${escapeHtml(item.alt || `Immagine ${index + 1}`)}"
                                    class="r4-gallery__img"
                                    loading="lazy"
                                    decoding="async"
                                >
                            `;

                            const mediaHtml = lightbox
                                ? `
                                    <a
                                        href="${escapeHtml(item.src)}"
                                        class="r4-gallery__media"
                                        data-gallery-lightbox="1"
                                        data-gallery-src="${escapeHtml(item.src)}"
                                        data-gallery-alt="${escapeHtml(item.alt || '')}"
                                        data-gallery-caption="${escapeHtml(item.caption || '')}"
                                    >
                                        ${imgTag}
                                    </a>
                                `
                                : `
                                    <div class="r4-gallery__media">
                                        ${imgTag}
                                    </div>
                                `;

                            return `
                                <div class="r4-gallery__item">
                                    ${mediaHtml}
                                    ${(showCaption && item.caption) ? `<div class="r4-gallery__caption">${escapeHtml(item.caption)}</div>` : ''}
                                </div>
                            `;
                        }).join('')
                        : `
                            <div class="r4-gallery__empty">
                                Nessuna immagine selezionata
                            </div>
                        `;

                    this.components(`
                        <div class="r4-gallery__inner">
                            <div class="r4-gallery__header">
                                ${headerHtml}
                            </div>
                            <div class="r4-gallery__grid">
                                ${itemsHtml}
                            </div>
                        </div>
                    `);
                };

                render();

                this.on(
                    'change:gallery_title change:gallery_subtitle change:gallery_show_title change:gallery_show_subtitle change:gallery_show_caption change:gallery_lightbox change:gallery_cols_desktop change:gallery_cols_tablet change:gallery_cols_mobile change:gallery_gap change:gallery_radius change:gallery_images',
                    render
                );
            }
        }
    });

    editor.Commands.add('r4-gallery-add-images', {
        run(ed) {
            const selected = ed.getSelected();
            if (!selected || selected.get('type') !== 'r4-gallery') return;

            openImagePicker((url, item) => {
                if (!url) return;

                const currentImages = safeImages(selected.get('gallery_images'));
                currentImages.push({
                    src: url,
                    alt: String(item?.alt || ''),
                    caption: String(item?.title || item?.alt || '')
                });

                selected.set('gallery_images', stringifyImages(currentImages));
            }, { mode: 'image' });
        }
    });

    editor.Commands.add('r4-gallery-clear-images', {
        run(ed) {
            const selected = ed.getSelected();
            if (!selected || selected.get('type') !== 'r4-gallery') return;

            const ok = window.confirm('Vuoi svuotare la galleria?');
            if (!ok) return;

            selected.set('gallery_images', '[]');
        }
    });

    editor.BlockManager.add('gallery', {
        label: 'Gallery Foto',
        category: 'R4 Components',
        content: {
            type: 'r4-gallery',
            gallery_title: 'Galleria fotografica',
            gallery_subtitle: 'Una selezione di immagini del progetto, prodotto o struttura.',
            gallery_show_title: 'true',
            gallery_show_subtitle: 'true',
            gallery_show_caption: 'true',
            gallery_lightbox: 'true',
            gallery_cols_desktop: '3',
            gallery_cols_tablet: '2',
            gallery_cols_mobile: '1',
            gallery_gap: '16',
            gallery_radius: '14'
        }
    });
}
