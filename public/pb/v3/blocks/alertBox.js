import { mergeTraits, traitValue, getHoverOptions } from '../helpers.js';

export function registerAlertBoxBlock(editor) {
    editor.DomComponents.addType('r4-alert-box', {
        isComponent: el => {
            if (el && el.classList && el.classList.contains('r4-alert-box')) {
                return { type: 'r4-alert-box' };
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
                    class: 'r4-alert-box r4-hover-border'
                },
                alert_icon: '⚠️',
                alert_title: 'Messaggio importante',
                alert_text: 'Presenza online e strumenti tecnici devono lavorare insieme per generare risultati concreti.',
                alert_accent: '#E8A020',
                alert_bg: '#FDF7E8',
                alert_text_color: '#6b4e00',
                alert_hover: 'border',
                traits: mergeTraits([
                    { type: 'text', name: 'alert_icon', label: 'Icona', changeProp: 1 },
                    { type: 'text', name: 'alert_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'alert_text', label: 'Testo', changeProp: 1 },
                    { type: 'color', name: 'alert_accent', label: 'Colore accento', changeProp: 1 },
                    { type: 'color', name: 'alert_bg', label: 'Sfondo', changeProp: 1 },
                    { type: 'color', name: 'alert_text_color', label: 'Colore testo', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'alert_hover',
                        label: 'Hover',
                        changeProp: 1,
                        options: getHoverOptions()
                    }
                ])
            },
            init() {
                const render = () => {
                    const icon = traitValue(this, 'alert_icon', '⚠️');
                    const title = traitValue(this, 'alert_title', 'Messaggio importante');
                    const text = traitValue(this, 'alert_text', 'Presenza online e strumenti tecnici devono lavorare insieme per generare risultati concreti.');
                    const accent = traitValue(this, 'alert_accent', '#E8A020');
                    const bg = traitValue(this, 'alert_bg', '#FDF7E8');
                    const textColor = traitValue(this, 'alert_text_color', '#6b4e00');
                    const hover = traitValue(this, 'alert_hover', 'border');

                    this.addAttributes({
                        class: `r4-alert-box ${hover ? 'r4-hover-' + hover : ''}`,
                        style: `--r4-accent:${accent};--r4-bg:${bg};--r4-text:${textColor};`
                    });

                    this.components(`
                        <div class="r4-alert-box__title">${icon} ${title}</div>
                        <div class="r4-alert-box__text">${text}</div>
                    `);
                };

                render();
                this.on('change:alert_icon change:alert_title change:alert_text change:alert_accent change:alert_bg change:alert_text_color change:alert_hover', render);
            }
        }
    });

    editor.BlockManager.add('alert-box', {
        label: 'Alert Box',
        category: 'R4 Components',
        content: {
            type: 'r4-alert-box',
            alert_icon: '⚠️',
            alert_title: 'Messaggio importante',
            alert_text: 'Un sito senza strategia converte poco. I social senza struttura disperdono valore.',
            alert_accent: '#E8A020',
            alert_bg: '#FDF7E8',
            alert_text_color: '#6b4e00',
            alert_hover: 'border'
        }
    });
}
