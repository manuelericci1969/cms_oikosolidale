import { mergeTraits, traitValue } from '../helpers.js';

export function registerImageBlock(editor, openImagePicker) {
    editor.DomComponents.addType('r4-image-block', {
        isComponent: el => {
            if (el && el.tagName === 'IMG' && el.classList && el.classList.contains('r4-gjs-image')) {
                return { type: 'r4-image-block' };
            }
            return false;
        },
        model: {
            defaults: {
                tagName: 'img',
                draggable: true,
                droppable: false,
                editable: false,
                stylable: true,
                attributes: {
                    src: 'https://via.placeholder.com/800x500?text=Immagine',
                    alt: 'Immagine',
                    class: 'r4-gjs-image'
                },
                src: 'https://via.placeholder.com/800x500?text=Immagine',
                alt: 'Immagine',
                traits: mergeTraits([
                    { type: 'text', name: 'src', label: 'URL immagine', changeProp: 1 },
                    { type: 'text', name: 'alt', label: 'Alt', changeProp: 1 },
                    {
                        type: 'button',
                        text: 'Apri Media Picker',
                        full: true,
                        command: 'r4-open-image-picker'
                    }
                ])
            },
            init() {
                const syncAttrs = () => {
                    this.addAttributes({
                        src: traitValue(this, 'src', 'https://via.placeholder.com/800x500?text=Immagine'),
                        alt: traitValue(this, 'alt', 'Immagine')
                    });
                };

                syncAttrs();
                this.on('change:src change:alt', syncAttrs);
            }
        }
    });

    editor.Commands.add('r4-open-image-picker', {
        run(ed) {
            const selected = ed.getSelected();
            if (!selected) return;

            openImagePicker((url, item) => {
                if (!url) return;

                selected.set('src', url);
                selected.addAttributes({ src: url });

                if (item && item.alt) {
                    selected.set('alt', item.alt);
                    selected.addAttributes({ alt: item.alt });
                }
            }, { mode: 'image' });
        }
    });

    editor.BlockManager.add('image', {
        label: 'Image',
        category: 'Basic',
        content: { type: 'r4-image-block' }
    });
}
