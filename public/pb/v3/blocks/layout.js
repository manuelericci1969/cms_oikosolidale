import { mergeTraits } from '../helpers.js';

export function registerLayoutBlocks(editor) {
    editor.DomComponents.addType('r4-section', {
        model: {
            defaults: {
                tagName: 'section',
                stylable: true,
                droppable: true,
                traits: mergeTraits([]),
                components: `
                    <div style="max-width:1180px;margin:0 auto;padding:60px 20px;">
                        <h2>Nuova sezione</h2>
                        <p>Contenuto della sezione</p>
                    </div>
                `
            }
        }
    });

    editor.BlockManager.add('section', {
        label: 'Section',
        category: 'Layout',
        content: { type: 'r4-section' }
    });

    editor.BlockManager.add('two-columns', {
        label: '2 Colonne',
        category: 'Layout',
        content: `
            <section style="padding:60px 20px;">
                <div style="max-width:1180px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    <div><h3>Colonna 1</h3><p>Contenuto</p></div>
                    <div><h3>Colonna 2</h3><p>Contenuto</p></div>
                </div>
            </section>
        `
    });

    editor.BlockManager.add('hero', {
        label: 'Hero',
        category: 'Landing',
        content: `
            <section style="padding:90px 20px;background:linear-gradient(135deg,#001c8f 0%,#0027c4 100%);color:#fff;">
                <div style="max-width:1180px;margin:0 auto;">
                    <div style="display:grid;grid-template-columns:1fr;gap:24px;">
                        <div>
                            <div style="display:inline-block;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.12);margin-bottom:18px;">
                                Badge Hero
                            </div>
                            <h1 style="font-size:46px;line-height:1.15;margin:0 0 18px;">Titolo Hero</h1>
                            <p style="font-size:18px;margin:0 0 28px;">Descrizione hero</p>
                            <a href="#" class="r4-gjs-button" style="display:inline-block;padding:14px 26px;border-radius:10px;background:#ff7a00;color:#fff;text-decoration:none;font-weight:700;">
                                CTA primaria
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        `
    });
}
