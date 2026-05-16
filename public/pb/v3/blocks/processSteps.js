import { mergeTraits, traitValue } from '../helpers.js';

export function registerProcessStepsBlock(editor) {
    editor.DomComponents.addType('r4-process-steps', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-process-steps')) {
                return { type: 'r4-process-steps' };
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
                    class: 'r4-process-steps'
                },

                ps_cols: '5',

                ps_icon_1: '🔍',
                ps_title_1: 'Analisi',
                ps_text_1: 'Obiettivi e criticità',

                ps_icon_2: '🧭',
                ps_title_2: 'Strategia',
                ps_text_2: 'Struttura e priorità',

                ps_icon_3: '💻',
                ps_title_3: 'Sviluppo',
                ps_text_3: 'Implementazione',

                ps_icon_4: '🚀',
                ps_title_4: 'Rilascio',
                ps_text_4: 'Messa online',

                ps_icon_5: '📈',
                ps_title_5: 'Evoluzione',
                ps_text_5: 'Supporto e crescita',

                traits: mergeTraits([
                    {
                        type: 'select',
                        name: 'ps_cols',
                        label: 'Colonne',
                        changeProp: 1,
                        options: [
                            { id: '3', name: '3' },
                            { id: '4', name: '4' },
                            { id: '5', name: '5' }
                        ]
                    },

                    { type: 'text', name: 'ps_icon_1', label: 'Icona 1', changeProp: 1 },
                    { type: 'text', name: 'ps_title_1', label: 'Titolo 1', changeProp: 1 },
                    { type: 'text', name: 'ps_text_1', label: 'Testo 1', changeProp: 1 },

                    { type: 'text', name: 'ps_icon_2', label: 'Icona 2', changeProp: 1 },
                    { type: 'text', name: 'ps_title_2', label: 'Titolo 2', changeProp: 1 },
                    { type: 'text', name: 'ps_text_2', label: 'Testo 2', changeProp: 1 },

                    { type: 'text', name: 'ps_icon_3', label: 'Icona 3', changeProp: 1 },
                    { type: 'text', name: 'ps_title_3', label: 'Titolo 3', changeProp: 1 },
                    { type: 'text', name: 'ps_text_3', label: 'Testo 3', changeProp: 1 },

                    { type: 'text', name: 'ps_icon_4', label: 'Icona 4', changeProp: 1 },
                    { type: 'text', name: 'ps_title_4', label: 'Titolo 4', changeProp: 1 },
                    { type: 'text', name: 'ps_text_4', label: 'Testo 4', changeProp: 1 },

                    { type: 'text', name: 'ps_icon_5', label: 'Icona 5', changeProp: 1 },
                    { type: 'text', name: 'ps_title_5', label: 'Titolo 5', changeProp: 1 },
                    { type: 'text', name: 'ps_text_5', label: 'Testo 5', changeProp: 1 }
                ])
            },

            init() {
                const render = () => {
                    const cols = traitValue(this, 'ps_cols', '5');

                    const steps = [1, 2, 3, 4, 5].map((i) => ({
                        num: i,
                        icon: traitValue(this, `ps_icon_${i}`, ''),
                        title: traitValue(this, `ps_title_${i}`, ''),
                        text: traitValue(this, `ps_text_${i}`, '')
                    })).filter(item => `${item.icon}${item.title}${item.text}`.trim() !== '');

                    this.addAttributes({
                        class: 'r4-process-steps',
                        style: `--r4-process-cols:${cols};`
                    });

                    this.components(`
                        ${steps.map((step) => `
                            <div class="r4-process-steps__item">
                                <div class="r4-process-steps__num">${step.num}</div>
                                <div class="r4-process-steps__icon">${step.icon}</div>
                                <div class="r4-process-steps__title">${step.title}</div>
                                <div class="r4-process-steps__text">${step.text}</div>
                            </div>
                        `).join('')}
                    `);
                };

                render();
                this.on(
                    'change:ps_cols change:ps_icon_1 change:ps_title_1 change:ps_text_1 change:ps_icon_2 change:ps_title_2 change:ps_text_2 change:ps_icon_3 change:ps_title_3 change:ps_text_3 change:ps_icon_4 change:ps_title_4 change:ps_text_4 change:ps_icon_5 change:ps_title_5 change:ps_text_5',
                    render
                );
            }
        }
    });

    editor.BlockManager.add('process-steps', {
        label: 'Process Steps',
        category: 'R4 Components',
        content: {
            type: 'r4-process-steps'
        }
    });
}
