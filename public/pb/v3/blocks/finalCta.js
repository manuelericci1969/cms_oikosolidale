import { mergeTraits, normalizeTarget, traitValue } from '../helpers.js';

export function registerFinalCtaBlock(editor) {
    editor.DomComponents.addType('r4-final-cta', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-final-cta')) {
                return { type: 'r4-final-cta' };
            }
            return false;
        },

        model: {
            defaults: {
                tagName: 'div',
                draggable: true,
                droppable: false,
                editable: false,
                stylable: true,
                attributes: {
                    class: 'r4-final-cta'
                },

                fcta_title: 'Hai bisogno di siti web, social o software su misura?',
                fcta_text: 'Raccontaci il tuo progetto e ti aiutiamo a capire la soluzione più adatta.',
                fcta_btn_1_text: 'Contattaci',
                fcta_btn_1_href: '/crm/contatti',
                fcta_btn_1_target: '_self',
                fcta_btn_2_text: 'Scopri i servizi',
                fcta_btn_2_href: '/siti-web-olbia',
                fcta_btn_2_target: '_self',

                traits: mergeTraits([
                    { type: 'text', name: 'fcta_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'fcta_text', label: 'Testo', changeProp: 1 },
                    { type: 'text', name: 'fcta_btn_1_text', label: 'Bottone 1 testo', changeProp: 1 },
                    { type: 'text', name: 'fcta_btn_1_href', label: 'Bottone 1 link', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'fcta_btn_1_target',
                        label: 'Target bottone 1',
                        changeProp: 1,
                        options: [
                            { id: '_self', name: 'Stessa finestra' },
                            { id: '_blank', name: 'Nuova finestra' }
                        ]
                    },
                    { type: 'text', name: 'fcta_btn_2_text', label: 'Bottone 2 testo', changeProp: 1 },
                    { type: 'text', name: 'fcta_btn_2_href', label: 'Bottone 2 link', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'fcta_btn_2_target',
                        label: 'Target bottone 2',
                        changeProp: 1,
                        options: [
                            { id: '_self', name: 'Stessa finestra' },
                            { id: '_blank', name: 'Nuova finestra' }
                        ]
                    }
                ])
            },

            init() {
                const render = () => {
                    const title = traitValue(this, 'fcta_title', 'Titolo CTA');
                    const text = traitValue(this, 'fcta_text', 'Testo CTA');
                    const b1t = traitValue(this, 'fcta_btn_1_text', 'Contattaci');
                    const b1h = traitValue(this, 'fcta_btn_1_href', '#');
                    const b1x = normalizeTarget(traitValue(this, 'fcta_btn_1_target', '_self'));
                    const b2t = traitValue(this, 'fcta_btn_2_text', 'Scopri');
                    const b2h = traitValue(this, 'fcta_btn_2_href', '#');
                    const b2x = normalizeTarget(traitValue(this, 'fcta_btn_2_target', '_self'));

                    this.components(`
                        <div class="r4-final-cta__copy">
                            <h2 class="r4-final-cta__title">${title}</h2>
                            <div class="r4-final-cta__text">${text}</div>
                            <div class="r4-final-cta__actions">
                                <a class="r4-final-cta__btn r4-final-cta__btn--primary" href="${b1h}" target="${b1x}">${b1t}</a>
                                <a class="r4-final-cta__btn r4-final-cta__btn--ghost" href="${b2h}" target="${b2x}">${b2t}</a>
                            </div>
                        </div>
                    `);
                };

                render();
                this.on(
                    'change:fcta_title change:fcta_text change:fcta_btn_1_text change:fcta_btn_1_href change:fcta_btn_1_target change:fcta_btn_2_text change:fcta_btn_2_href change:fcta_btn_2_target',
                    render
                );
            }
        }
    });

    editor.BlockManager.add('final-cta', {
        label: 'Final CTA',
        category: 'R4 Components',
        content: {
            type: 'r4-final-cta'
        }
    });
}
