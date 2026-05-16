import { mergeTraits, traitValue } from '../helpers.js';

export function registerSectionHeaderBlock(editor) {
    editor.DomComponents.addType('r4-section-header', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-section-header-block')) {
                return { type: 'r4-section-header' };
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
                    class: 'r4-section-header-block'
                },

                sh_show_eyebrow: '1',
                sh_eyebrow: 'Servizi principali',
                sh_title: 'Titolo sezione',
                sh_subtitle: 'Sottotitolo della sezione con testo descrittivo chiaro e leggibile.',
                sh_align: 'center',
                sh_max_width: '860px',
                sh_title_color: '#0A2E1F',
                sh_subtitle_color: '#3D5C4A',

                traits: mergeTraits([
                    {
                        type: 'select',
                        name: 'sh_show_eyebrow',
                        label: 'Mostra eyebrow',
                        changeProp: 1,
                        options: [
                            { id: '1', name: 'Sì' },
                            { id: '0', name: 'No' }
                        ]
                    },
                    { type: 'text', name: 'sh_eyebrow', label: 'Eyebrow', changeProp: 1 },
                    { type: 'text', name: 'sh_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'sh_subtitle', label: 'Sottotitolo', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'sh_align',
                        label: 'Allineamento',
                        changeProp: 1,
                        options: [
                            { id: 'left', name: 'Sinistra' },
                            { id: 'center', name: 'Centro' }
                        ]
                    },
                    { type: 'text', name: 'sh_max_width', label: 'Max width subtitle', changeProp: 1 },
                    { type: 'color', name: 'sh_title_color', label: 'Colore titolo', changeProp: 1 },
                    { type: 'color', name: 'sh_subtitle_color', label: 'Colore sottotitolo', changeProp: 1 }
                ])
            },

            init() {
                const render = () => {
                    const showEyebrow = traitValue(this, 'sh_show_eyebrow', '1') === '1';
                    const eyebrow = traitValue(this, 'sh_eyebrow', 'Servizi principali');
                    const title = traitValue(this, 'sh_title', 'Titolo sezione');
                    const subtitle = traitValue(this, 'sh_subtitle', '');
                    const align = traitValue(this, 'sh_align', 'center');
                    const maxWidth = traitValue(this, 'sh_max_width', '860px');
                    const titleColor = traitValue(this, 'sh_title_color', '#0A2E1F');
                    const subtitleColor = traitValue(this, 'sh_subtitle_color', '#3D5C4A');

                    this.addAttributes({
                        class: 'r4-section-header-block',
                        style: `text-align:${align};`
                    });

                    this.components(`
                        <div class="r4-section-header-block__inner">
                            ${showEyebrow ? `<div class="r4-eyebrow-badge is-animated"><span class="r4-eyebrow-badge__dot"></span><span class="r4-eyebrow-badge__text">${eyebrow}</span></div>` : ''}
                            <h2 class="r4-section-header-block__title" style="color:${titleColor};">${title}</h2>
                            <div class="r4-section-header-block__subtitle" style="color:${subtitleColor};max-width:${maxWidth};${align === 'center' ? 'margin-left:auto;margin-right:auto;' : ''}">
                                ${subtitle}
                            </div>
                        </div>
                    `);
                };

                render();
                this.on(
                    'change:sh_show_eyebrow change:sh_eyebrow change:sh_title change:sh_subtitle change:sh_align change:sh_max_width change:sh_title_color change:sh_subtitle_color',
                    render
                );
            }
        }
    });

    editor.BlockManager.add('section-header', {
        label: 'Section Header',
        category: 'R4 Components',
        content: {
            type: 'r4-section-header'
        }
    });
}
