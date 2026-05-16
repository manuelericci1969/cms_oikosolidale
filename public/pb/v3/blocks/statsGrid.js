import { mergeTraits, traitValue } from '../helpers.js';

export function registerStatsGridBlock(editor) {
    editor.DomComponents.addType('r4-stats-grid', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-stats-grid')) {
                return { type: 'r4-stats-grid' };
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
                    class: 'r4-stats-grid'
                },

                sg_cols: '3',
                sg_gap: '10px',

                sg_val_1: '25+',
                sg_lab_1: 'anni di esperienza',
                sg_val_2: '2',
                sg_lab_2: 'prodotti proprietari',
                sg_val_3: 'Web + App',
                sg_lab_3: 'ecosistema digitale',
                sg_val_4: '',
                sg_lab_4: '',

                traits: mergeTraits([
                    {
                        type: 'select',
                        name: 'sg_cols',
                        label: 'Colonne',
                        changeProp: 1,
                        options: [
                            { id: '2', name: '2' },
                            { id: '3', name: '3' },
                            { id: '4', name: '4' }
                        ]
                    },
                    { type: 'text', name: 'sg_gap', label: 'Gap', changeProp: 1 },

                    { type: 'text', name: 'sg_val_1', label: 'Valore 1', changeProp: 1 },
                    { type: 'text', name: 'sg_lab_1', label: 'Label 1', changeProp: 1 },

                    { type: 'text', name: 'sg_val_2', label: 'Valore 2', changeProp: 1 },
                    { type: 'text', name: 'sg_lab_2', label: 'Label 2', changeProp: 1 },

                    { type: 'text', name: 'sg_val_3', label: 'Valore 3', changeProp: 1 },
                    { type: 'text', name: 'sg_lab_3', label: 'Label 3', changeProp: 1 },

                    { type: 'text', name: 'sg_val_4', label: 'Valore 4', changeProp: 1 },
                    { type: 'text', name: 'sg_lab_4', label: 'Label 4', changeProp: 1 }
                ])
            },

            init() {
                const render = () => {
                    const cols = traitValue(this, 'sg_cols', '3');
                    const gap = traitValue(this, 'sg_gap', '10px');

                    const items = [
                        [traitValue(this, 'sg_val_1', ''), traitValue(this, 'sg_lab_1', '')],
                        [traitValue(this, 'sg_val_2', ''), traitValue(this, 'sg_lab_2', '')],
                        [traitValue(this, 'sg_val_3', ''), traitValue(this, 'sg_lab_3', '')],
                        [traitValue(this, 'sg_val_4', ''), traitValue(this, 'sg_lab_4', '')]
                    ].filter(([v, l]) => `${v}${l}`.trim() !== '');

                    this.addAttributes({
                        class: 'r4-stats-grid',
                        style: `--r4-stats-cols:${cols};--r4-stats-gap:${gap};`
                    });

                    this.components(`
                        ${items.map(([value, label]) => `
                            <div class="r4-stats-grid__item">
                                <div class="r4-stats-grid__value">${value}</div>
                                <div class="r4-stats-grid__label">${label}</div>
                            </div>
                        `).join('')}
                    `);
                };

                render();
                this.on(
                    'change:sg_cols change:sg_gap change:sg_val_1 change:sg_lab_1 change:sg_val_2 change:sg_lab_2 change:sg_val_3 change:sg_lab_3 change:sg_val_4 change:sg_lab_4',
                    render
                );
            }
        }
    });

    editor.BlockManager.add('stats-grid', {
        label: 'Stats Grid',
        category: 'R4 Components',
        content: {
            type: 'r4-stats-grid'
        }
    });
}
