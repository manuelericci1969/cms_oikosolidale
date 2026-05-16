import { mergeTraits, traitValue } from '../helpers.js';

export function registerTargetCardBlock(editor) {
    editor.DomComponents.addType('r4-target-card', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-target-card')) {
                return { type: 'r4-target-card' };
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
                    class: 'r4-target-card'
                },

                tc_icon: '🏨',
                tc_title: 'Hotel, B&B e strutture ricettive',
                tc_text: 'Descrizione del target e del caso d’uso.',
                tc_tags: 'hotel,ble,hospitality',

                traits: mergeTraits([
                    { type: 'text', name: 'tc_icon', label: 'Icona', changeProp: 1 },
                    { type: 'text', name: 'tc_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'tc_text', label: 'Testo', changeProp: 1 },
                    { type: 'textarea', name: 'tc_tags', label: 'Tag (separati da virgola)', changeProp: 1 }
                ])
            },

            init() {
                const render = () => {
                    const icon = traitValue(this, 'tc_icon', '🏨');
                    const title = traitValue(this, 'tc_title', 'Titolo target');
                    const text = traitValue(this, 'tc_text', 'Descrizione');
                    const tags = String(traitValue(this, 'tc_tags', ''))
                        .split(',')
                        .map(v => v.trim())
                        .filter(Boolean);

                    this.components(`
                        <div class="r4-target-card__icon">${icon}</div>
                        <div class="r4-target-card__title">${title}</div>
                        <div class="r4-target-card__text">${text}</div>
                        <div class="r4-target-card__tags">
                            ${tags.map(tag => `<span class="r4-target-card__tag">${tag}</span>`).join('')}
                        </div>
                    `);
                };

                render();
                this.on('change:tc_icon change:tc_title change:tc_text change:tc_tags', render);
            }
        }
    });

    editor.BlockManager.add('target-card', {
        label: 'Target Card',
        category: 'R4 Components',
        content: {
            type: 'r4-target-card'
        }
    });
}
