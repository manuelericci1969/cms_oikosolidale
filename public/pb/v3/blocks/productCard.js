import { mergeTraits, normalizeTarget, traitValue, getHoverOptions } from '../helpers.js';

export function registerProductCardBlock(editor) {
    editor.DomComponents.addType('r4-product-card', {
        isComponent: el => {
            if (el && el.classList && el.classList.contains('r4-product-card')) {
                return { type: 'r4-product-card' };
            }
            return false;
        },
        model: {
            defaults: {
                tagName: 'div',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                removable: true,
                stylable: true,
                attributes: {
                    class: 'r4-product-card r4-hover-lift'
                },
                product_icon: '🔑',
                product_tag: 'Prodotto',
                product_title: 'HMobile BLE Key',
                product_price: 'da 349€ + IVA',
                product_subtitle: 'Chiave elettronica BLE',
                product_text: 'Controllo accessi da smartphone con log, permessi temporanei e integrazione software.',
                product_cta_text: 'Scopri di più',
                product_cta_href: '#',
                product_cta_target: '_self',
                product_secondary_text: 'Richiedi demo',
                product_secondary_href: '#',
                product_secondary_target: '_self',
                product_accent: '#0E8FBF',
                product_bg: '#ffffff',
                product_text_color: '#1f2937',
                product_hover: 'lift',
                traits: mergeTraits([
                    { type: 'text', name: 'product_icon', label: 'Icona', changeProp: 1 },
                    { type: 'text', name: 'product_tag', label: 'Tag', changeProp: 1 },
                    { type: 'text', name: 'product_title', label: 'Titolo', changeProp: 1 },
                    { type: 'text', name: 'product_price', label: 'Prezzo', changeProp: 1 },
                    { type: 'text', name: 'product_subtitle', label: 'Sottotitolo', changeProp: 1 },
                    { type: 'textarea', name: 'product_text', label: 'Descrizione', changeProp: 1 },
                    { type: 'text', name: 'product_cta_text', label: 'CTA primaria testo', changeProp: 1 },
                    { type: 'text', name: 'product_cta_href', label: 'CTA primaria link', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'product_cta_target',
                        label: 'CTA primaria target',
                        changeProp: 1,
                        options: [
                            { id: '_self', name: 'Stessa finestra' },
                            { id: '_blank', name: 'Nuova finestra' }
                        ]
                    },
                    { type: 'text', name: 'product_secondary_text', label: 'CTA secondaria testo', changeProp: 1 },
                    { type: 'text', name: 'product_secondary_href', label: 'CTA secondaria link', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'product_secondary_target',
                        label: 'CTA secondaria target',
                        changeProp: 1,
                        options: [
                            { id: '_self', name: 'Stessa finestra' },
                            { id: '_blank', name: 'Nuova finestra' }
                        ]
                    },
                    { type: 'color', name: 'product_accent', label: 'Colore accento', changeProp: 1 },
                    { type: 'color', name: 'product_bg', label: 'Sfondo', changeProp: 1 },
                    { type: 'color', name: 'product_text_color', label: 'Colore testo', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'product_hover',
                        label: 'Hover',
                        changeProp: 1,
                        options: getHoverOptions()
                    }
                ])
            },
            init() {
                const render = () => {
                    const icon = traitValue(this, 'product_icon', '🔑');
                    const tag = traitValue(this, 'product_tag', 'Prodotto');
                    const title = traitValue(this, 'product_title', 'HMobile BLE Key');
                    const price = traitValue(this, 'product_price', 'da 349€ + IVA');
                    const subtitle = traitValue(this, 'product_subtitle', 'Chiave elettronica BLE');
                    const text = traitValue(this, 'product_text', 'Controllo accessi da smartphone con log, permessi temporanei e integrazione software.');
                    const ctaText = traitValue(this, 'product_cta_text', 'Scopri di più');
                    const ctaHref = traitValue(this, 'product_cta_href', '#');
                    const ctaTarget = normalizeTarget(traitValue(this, 'product_cta_target', '_self'));
                    const secondaryText = traitValue(this, 'product_secondary_text', 'Richiedi demo');
                    const secondaryHref = traitValue(this, 'product_secondary_href', '#');
                    const secondaryTarget = normalizeTarget(traitValue(this, 'product_secondary_target', '_self'));
                    const accent = traitValue(this, 'product_accent', '#0E8FBF');
                    const bg = traitValue(this, 'product_bg', '#ffffff');
                    const textColor = traitValue(this, 'product_text_color', '#1f2937');
                    const hover = traitValue(this, 'product_hover', 'lift');

                    this.addAttributes({
                        class: `r4-product-card ${hover ? 'r4-hover-' + hover : ''}`,
                        style: `--r4-accent:${accent};--r4-bg:${bg};--r4-text:${textColor};`
                    });

                    this.components(`
                        <div class="r4-product-card__band"></div>
                        <div class="r4-product-card__body">
                            <div class="r4-product-card__head">
                                <span class="r4-product-card__tag">${tag}</span>
                                <span class="r4-product-card__price">${price}</span>
                            </div>
                            <div class="r4-product-card__icon">${icon}</div>
                            <h3 class="r4-product-card__title">${title}</h3>
                            <div class="r4-product-card__subtitle">${subtitle}</div>
                            <div class="r4-product-card__text">${text}</div>
                            <div class="r4-product-card__actions">
                                <a class="r4-product-card__btn r4-product-card__btn--primary" href="${ctaHref}" target="${ctaTarget}">${ctaText}</a>
                                <a class="r4-product-card__btn r4-product-card__btn--secondary" href="${secondaryHref}" target="${secondaryTarget}">${secondaryText}</a>
                            </div>
                        </div>
                    `);
                };

                render();
                this.on('change:product_icon change:product_tag change:product_title change:product_price change:product_subtitle change:product_text change:product_cta_text change:product_cta_href change:product_cta_target change:product_secondary_text change:product_secondary_href change:product_secondary_target change:product_accent change:product_bg change:product_text_color change:product_hover', render);
            }
        }
    });

    editor.BlockManager.add('product-card', {
        label: 'Product Card',
        category: 'R4 Components',
        content: {
            type: 'r4-product-card',
            product_icon: '💧',
            product_tag: 'Prodotto IoT',
            product_title: 'HMFluxus',
            product_price: 'da 690€ + IVA',
            product_subtitle: 'Controllo consumi e perdite acqua',
            product_text: 'Monitoraggio consumi idrici, rilevazione perdite e chiusura automatica dell’impianto.',
            product_cta_text: 'Scopri HMFluxus',
            product_cta_href: '/hmfluxus',
            product_cta_target: '_self',
            product_secondary_text: 'Richiedi informazioni',
            product_secondary_href: '/crm/contatti',
            product_secondary_target: '_self',
            product_accent: '#0CA874',
            product_bg: '#ffffff',
            product_text_color: '#1f2937',
            product_hover: 'lift'
        }
    });
}
