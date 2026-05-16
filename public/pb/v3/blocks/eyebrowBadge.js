import { mergeTraits, traitValue } from '../helpers.js';

export function registerEyebrowBadgeBlock(editor) {
    editor.DomComponents.addType('r4-eyebrow-badge', {
        isComponent: (el) => {
            if (el && el.classList && el.classList.contains('r4-eyebrow-badge')) {
                return { type: 'r4-eyebrow-badge' };
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
                    class: 'r4-eyebrow-badge is-animated'
                },

                eyebrow_text: 'R4Software · Siti Web · Social · Software House · Olbia',
                eyebrow_text_color: '#087A54',
                eyebrow_bg_color: '#D9F2E8',
                eyebrow_border_color: 'rgba(12,168,116,.22)',
                eyebrow_dot_color: '#0CA874',
                eyebrow_font_size: '11px',
                eyebrow_font_weight: '700',
                eyebrow_letter_spacing: '.13em',
                eyebrow_text_transform: 'uppercase',
                eyebrow_gap: '8px',
                eyebrow_radius: '999px',
                eyebrow_padding_y: '6px',
                eyebrow_padding_x: '13px',
                eyebrow_dot_size: '8px',
                eyebrow_dot_animation: '1',
                eyebrow_dot_duration: '1.8s',
                eyebrow_align: 'left',

                traits: mergeTraits([
                    { type: 'text', name: 'eyebrow_text', label: 'Testo', changeProp: 1 },
                    { type: 'color', name: 'eyebrow_text_color', label: 'Colore testo', changeProp: 1 },
                    { type: 'color', name: 'eyebrow_bg_color', label: 'Sfondo', changeProp: 1 },
                    { type: 'color', name: 'eyebrow_border_color', label: 'Colore bordo', changeProp: 1 },
                    { type: 'color', name: 'eyebrow_dot_color', label: 'Colore pallino', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_font_size', label: 'Font size', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_font_weight', label: 'Font weight', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_letter_spacing', label: 'Letter spacing', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'eyebrow_text_transform',
                        label: 'Transform',
                        changeProp: 1,
                        options: [
                            { id: 'uppercase', name: 'UPPERCASE' },
                            { id: 'none', name: 'Normale' }
                        ]
                    },
                    { type: 'text', name: 'eyebrow_gap', label: 'Gap', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_radius', label: 'Radius', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_padding_y', label: 'Padding Y', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_padding_x', label: 'Padding X', changeProp: 1 },
                    { type: 'text', name: 'eyebrow_dot_size', label: 'Dimensione pallino', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'eyebrow_dot_animation',
                        label: 'Animazione pallino',
                        changeProp: 1,
                        options: [
                            { id: '1', name: 'Attiva' },
                            { id: '0', name: 'Disattiva' }
                        ]
                    },
                    { type: 'text', name: 'eyebrow_dot_duration', label: 'Durata animazione', changeProp: 1 },
                    {
                        type: 'select',
                        name: 'eyebrow_align',
                        label: 'Allineamento',
                        changeProp: 1,
                        options: [
                            { id: 'left', name: 'Sinistra' },
                            { id: 'center', name: 'Centro' },
                            { id: 'right', name: 'Destra' }
                        ]
                    }
                ])
            },

            init() {
                const render = () => {
                    const text = traitValue(this, 'eyebrow_text', 'Eyebrow');
                    const textColor = traitValue(this, 'eyebrow_text_color', '#087A54');
                    const bgColor = traitValue(this, 'eyebrow_bg_color', '#D9F2E8');
                    const borderColor = traitValue(this, 'eyebrow_border_color', 'rgba(12,168,116,.22)');
                    const dotColor = traitValue(this, 'eyebrow_dot_color', '#0CA874');
                    const fontSize = traitValue(this, 'eyebrow_font_size', '11px');
                    const fontWeight = traitValue(this, 'eyebrow_font_weight', '700');
                    const letterSpacing = traitValue(this, 'eyebrow_letter_spacing', '.13em');
                    const textTransform = traitValue(this, 'eyebrow_text_transform', 'uppercase');
                    const gap = traitValue(this, 'eyebrow_gap', '8px');
                    const radius = traitValue(this, 'eyebrow_radius', '999px');
                    const py = traitValue(this, 'eyebrow_padding_y', '6px');
                    const px = traitValue(this, 'eyebrow_padding_x', '13px');
                    const dotSize = traitValue(this, 'eyebrow_dot_size', '8px');
                    const dotAnimation = traitValue(this, 'eyebrow_dot_animation', '1') === '1';
                    const dotDuration = traitValue(this, 'eyebrow_dot_duration', '1.8s');
                    const align = traitValue(this, 'eyebrow_align', 'left');

                    this.addAttributes({
                        class: `r4-eyebrow-badge${dotAnimation ? ' is-animated' : ''}`,
                        style: [
                            `--r4-eyebrow-text:${textColor}`,
                            `--r4-eyebrow-bg:${bgColor}`,
                            `--r4-eyebrow-border:${borderColor}`,
                            `--r4-eyebrow-dot:${dotColor}`,
                            `--r4-eyebrow-font-size:${fontSize}`,
                            `--r4-eyebrow-font-weight:${fontWeight}`,
                            `--r4-eyebrow-letter:${letterSpacing}`,
                            `--r4-eyebrow-transform:${textTransform}`,
                            `--r4-eyebrow-gap:${gap}`,
                            `--r4-eyebrow-radius:${radius}`,
                            `--r4-eyebrow-py:${py}`,
                            `--r4-eyebrow-px:${px}`,
                            `--r4-eyebrow-dot-size:${dotSize}`,
                            `--r4-eyebrow-dot-duration:${dotDuration}`,
                            `text-align:${align}`
                        ].join(';')
                    });

                    this.components(`
                        <span class="r4-eyebrow-badge__dot"></span>
                        <span class="r4-eyebrow-badge__text">${text}</span>
                    `);
                };

                render();

                this.on(
                    'change:eyebrow_text change:eyebrow_text_color change:eyebrow_bg_color change:eyebrow_border_color change:eyebrow_dot_color change:eyebrow_font_size change:eyebrow_font_weight change:eyebrow_letter_spacing change:eyebrow_text_transform change:eyebrow_gap change:eyebrow_radius change:eyebrow_padding_y change:eyebrow_padding_x change:eyebrow_dot_size change:eyebrow_dot_animation change:eyebrow_dot_duration change:eyebrow_align',
                    render
                );
            }
        }
    });

    editor.BlockManager.add('eyebrow-badge', {
        label: 'Eyebrow Badge',
        category: 'R4 Components',
        content: {
            type: 'r4-eyebrow-badge'
        }
    });
}
