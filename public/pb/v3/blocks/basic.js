import { mergeTraits, normalizeTarget, traitValue } from '../helpers.js';

export function registerBasicBlocks(editor) {
    editor.DomComponents.addType('r4-button', {
        isComponent: el => {
            if (el && el.tagName === 'A' && el.classList && el.classList.contains('r4-gjs-button')) {
                return { type: 'r4-button' };
            }
            return false;
        },
        model: {
            defaults: {
                tagName: 'a',
                draggable: true,
                droppable: false,
                editable: false,
                stylable: true,
                copyable: true,
                removable: true,
                attributes: {
                    href: '#',
                    target: '_self',
                    class: 'r4-gjs-button'
                },
                button_text: 'Bottone',
                traits: mergeTraits([
                    { type: 'text', name: 'button_text', label: 'Testo bottone', changeProp: 1 },
                    { type: 'text', name: 'href', label: 'Href', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'target',
                        label: 'Target',
                        changeProp: 1,
                        options: [
                            { id: '_self', name: 'Stessa finestra' },
                            { id: '_blank', name: 'Nuova finestra' }
                        ]
                    }
                ]),
                style: {
                    display: 'inline-block',
                    padding: '14px 24px',
                    'border-radius': '10px',
                    background: '#2563eb',
                    color: '#ffffff',
                    'text-decoration': 'none',
                    'font-weight': '700'
                }
            },

            init() {
                const syncButtonText = () => {
                    const txt = String(this.get('button_text') || '').trim() || 'Bottone';
                    this.components(txt);
                };

                const syncAttrs = () => {
                    this.addAttributes({
                        href: traitValue(this, 'href', '#'),
                        target: normalizeTarget(traitValue(this, 'target', '_self'))
                    });
                };

                if (!this.get('button_text')) {
                    const existingText = this.components().length ? this.components().toHTML() : '';
                    this.set('button_text', existingText || 'Bottone', { silent: true });
                }

                syncButtonText();
                syncAttrs();

                this.on('change:button_text', syncButtonText);
                this.on('change:href change:target', syncAttrs);
            }
        }
    });

    editor.DomComponents.addType('r4-text', {
        model: {
            defaults: {
                tagName: 'div',
                components: '<p>Scrivi il tuo testo qui</p>',
                editable: true,
                droppable: true,
                stylable: true,
                traits: mergeTraits([])
            }
        }
    });

    editor.DomComponents.addType('r4-heading', {
        model: {
            defaults: {
                tagName: 'h2',
                components: 'Titolo',
                editable: true,
                droppable: false,
                stylable: true,
                traits: mergeTraits([])
            }
        }
    });

    editor.BlockManager.add('text', {
        label: 'Text',
        category: 'Basic',
        content: { type: 'r4-text' }
    });

    editor.BlockManager.add('heading', {
        label: 'Heading',
        category: 'Basic',
        content: { type: 'r4-heading' }
    });

    editor.BlockManager.add('button', {
        label: 'Button',
        category: 'Basic',
        content: {
            type: 'r4-button',
            button_text: 'Bottone',
            href: '#',
            target: '_self',
            attributes: {
                href: '#',
                target: '_self',
                class: 'r4-gjs-button'
            }
        }
    });
}
