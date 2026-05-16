import { mergeTraits, traitValue } from '../helpers.js';

export function registerFaqBlock(editor) {
    editor.DomComponents.addType('r4-faq-block', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-faq-block')) {
                return { type: 'r4-faq-block' };
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
                    class: 'r4-faq-block'
                },

                faq_q_1: 'Domanda frequente 1?',
                faq_a_1: 'Risposta alla domanda frequente 1.',
                faq_q_2: 'Domanda frequente 2?',
                faq_a_2: 'Risposta alla domanda frequente 2.',
                faq_q_3: 'Domanda frequente 3?',
                faq_a_3: 'Risposta alla domanda frequente 3.',

                traits: mergeTraits([
                    { type: 'text', name: 'faq_q_1', label: 'Domanda 1', changeProp: 1 },
                    { type: 'textarea', name: 'faq_a_1', label: 'Risposta 1', changeProp: 1 },
                    { type: 'text', name: 'faq_q_2', label: 'Domanda 2', changeProp: 1 },
                    { type: 'textarea', name: 'faq_a_2', label: 'Risposta 2', changeProp: 1 },
                    { type: 'text', name: 'faq_q_3', label: 'Domanda 3', changeProp: 1 },
                    { type: 'textarea', name: 'faq_a_3', label: 'Risposta 3', changeProp: 1 }
                ])
            },

            init() {
                const render = () => {
                    const rows = [1, 2, 3].map(i => ({
                        q: traitValue(this, `faq_q_${i}`, ''),
                        a: traitValue(this, `faq_a_${i}`, '')
                    })).filter(item => `${item.q}${item.a}`.trim() !== '');

                    this.components(`
                        ${rows.map(item => `
                            <div class="r4-faq-block__item">
                                <div class="r4-faq-block__q">${item.q}</div>
                                <div class="r4-faq-block__a">${item.a}</div>
                            </div>
                        `).join('')}
                    `);
                };

                render();
                this.on(
                    'change:faq_q_1 change:faq_a_1 change:faq_q_2 change:faq_a_2 change:faq_q_3 change:faq_a_3',
                    render
                );
            }
        }
    });

    editor.BlockManager.add('faq-block', {
        label: 'FAQ Block',
        category: 'R4 Components',
        content: {
            type: 'r4-faq-block'
        }
    });
}
