import { mergeTraits, normalizeTarget, traitValue, getHoverOptions } from '../helpers.js';

export function registerFeatureCardBlock(editor) {
    editor.DomComponents.addType('r4-feature-card', {
        isComponent: el => {
            if (el && el.classList && el.classList.contains('r4-feature-card')) {
                return { type: 'r4-feature-card' };
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
                    class: 'r4-feature-card r4-hover-lift'
                },
                feature_icon: '💧',
                feature_title: 'Titolo componente',
                feature_text: 'Descrizione del componente con un testo chiaro e professionale.',
                feature_label: 'Servizio',
                feature_cta_text: 'Scopri di più',
                feature_cta_href: '#',
                feature_cta_target: '_self',
                feature_accent: '#0CA874',
                feature_bg: '#ffffff',
                feature_text_color: '#1f2937',
                feature_hover: 'lift',
                traits: mergeTraits([
                    { type: 'text', name: 'feature_icon', label: 'Icona', changeProp: 1 },
                    { type: 'text', name: 'feature_label', label: 'Label', changeProp: 1 },
                    { type: 'text', name: 'feature_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'feature_text', label: 'Testo', changeProp: 1 },
                    { type: 'text', name: 'feature_cta_text', label: 'CTA testo', changeProp: 1 },
                    { type: 'text', name: 'feature_cta_href', label: 'CTA link', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'feature_cta_target',
                        label: 'CTA target',
                        changeProp: 1,
                        options: [
                            { id: '_self', name: 'Stessa finestra' },
                            { id: '_blank', name: 'Nuova finestra' }
                        ]
                    },
                    { type: 'color', name: 'feature_accent', label: 'Colore accento', changeProp: 1 },
                    { type: 'color', name: 'feature_bg', label: 'Sfondo', changeProp: 1 },
                    { type: 'color', name: 'feature_text_color', label: 'Colore testo', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'feature_hover',
                        label: 'Hover',
                        changeProp: 1,
                        options: getHoverOptions()
                    }
                ])
            },
            init() {
                const render = () => {
                    const icon = traitValue(this, 'feature_icon', '💧');
                    const label = traitValue(this, 'feature_label', 'Servizio');
                    const title = traitValue(this, 'feature_title', 'Titolo componente');
                    const text = traitValue(this, 'feature_text', 'Descrizione del componente con un testo chiaro e professionale.');
                    const ctaText = traitValue(this, 'feature_cta_text', 'Scopri di più');
                    const ctaHref = traitValue(this, 'feature_cta_href', '#');
                    const ctaTarget = normalizeTarget(traitValue(this, 'feature_cta_target', '_self'));
                    const accent = traitValue(this, 'feature_accent', '#0CA874');
                    const bg = traitValue(this, 'feature_bg', '#ffffff');
                    const textColor = traitValue(this, 'feature_text_color', '#1f2937');
                    const hover = traitValue(this, 'feature_hover', 'lift');

                    this.addAttributes({
                        class: `r4-feature-card ${hover ? 'r4-hover-' + hover : ''}`,
                        style: `--r4-accent:${accent};--r4-bg:${bg};--r4-text:${textColor};`
                    });

                    this.components(`
                        <div class="r4-feature-card__icon">${icon}</div>
                        <div class="r4-feature-card__label">${label}</div>
                        <h3 class="r4-feature-card__title">${title}</h3>
                        <div class="r4-feature-card__text">${text}</div>
                        <a class="r4-feature-card__cta" href="${ctaHref}" target="${ctaTarget}">${ctaText}</a>
                    `);
                };

                render();
                this.on('change:feature_icon change:feature_label change:feature_title change:feature_text change:feature_cta_text change:feature_cta_href change:feature_cta_target change:feature_accent change:feature_bg change:feature_text_color change:feature_hover', render);
            }
        }
    });

    editor.BlockManager.add('feature-card', {
        label: 'Feature Card',
        category: 'R4 Components',
        content: {
            type: 'r4-feature-card',
            feature_icon: '🌐',
            feature_label: 'Servizio',
            feature_title: 'Realizzazione siti web professionali',
            feature_text: 'Sviluppiamo siti web professionali, chiari e progettati per convertire.',
            feature_cta_text: 'Scopri i siti web',
            feature_cta_href: '/siti-web-olbia',
            feature_cta_target: '_self',
            feature_accent: '#0CA874',
            feature_bg: '#ffffff',
            feature_text_color: '#1f2937',
            feature_hover: 'lift'
        }
    });
}
