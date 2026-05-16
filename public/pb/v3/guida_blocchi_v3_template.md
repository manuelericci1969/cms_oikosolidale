# Guida operativa blocchi V3 + template richiesta nuovo blocco

## Scopo

Questo documento serve a:

- definire dove creare e salvare i nuovi blocchi del Visual Builder V3
- spiegare come strutturarli correttamente
- indicare come renderli disponibili nell'editor
- fornire un template standard da compilare quando si vuole richiedere un nuovo blocco

---

# 1. Struttura progetto consigliata

I blocchi del builder V3 devono essere organizzati in questa struttura:

```bash
public/pb/v3/
  editor.js
  ui.js
  helpers.js
  blocks/
    basic.js
    layout.js
    featureCard.js
    productCard.js
    alertBox.js
    imageBlock.js
```

## Ruolo dei file

### `editor.js`
Gestisce:

- inizializzazione di GrapesJS
- caricamento `visual_html`, `visual_css`, `visual_json`
- registrazione dei blocchi
- device manager
- eventi editor
- integrazione media picker

### `ui.js`
Gestisce:

- bottoni salva / pubblica
- apertura pannello impostazioni
- code editor
- cambio device
- toggle UI dei campi pagina

### `helpers.js`
Contiene utility condivise, per esempio:

- `safeParseJson()`
- `mergeTraits()`
- `traitValue()`
- `normalizeTarget()`
- `formatHtml()`
- `formatCss()`
- `applyAnimationRuntime()`

### `blocks/*.js`
Ogni file contiene uno o più blocchi coerenti tra loro.

Esempi:

- `featureCard.js` → blocchi card servizio
- `productCard.js` → blocchi card prodotto
- `imageBlock.js` → blocco immagine con media picker

---

# 2. Dove salvare un nuovo blocco

Ogni nuovo blocco va creato dentro:

```bash
public/pb/v3/blocks/
```

## Convenzione consigliata

Usare **un file per blocco** oppure **un file per famiglia di blocchi**.

### Esempi

- blocco testimonial → `testimonial.js`
- blocco faq → `faq.js`
- blocco pricing → `pricingTable.js`
- blocco team → `teamCard.js`
- blocco call to action → `ctaBox.js`

---

# 3. Come è fatto un blocco

Ogni blocco deve avere 2 parti principali.

## A. Registrazione del componente

Si usa:

```javascript
editor.DomComponents.addType(...)
```

Serve a definire:

- il tipo GrapesJS del componente
- come viene riconosciuto nell'editor
- quali proprietà possiede
- quali trait mostrare
- come si aggiorna quando cambiano i valori

## B. Registrazione del blocco trascinabile

Si usa:

```javascript
editor.BlockManager.add(...)
```

Serve a far comparire il blocco nel pannello “Blocchi” del builder.

---

# 4. Convenzioni naming consigliate

## Nome file
Usare camelCase:

- `testimonial.js`
- `faqAccordion.js`
- `pricingTable.js`

## Nome export
Usare un nome chiaro:

- `registerTestimonialBlock`
- `registerFaqAccordionBlock`
- `registerPricingTableBlock`

## Nome type GrapesJS
Usare kebab-case:

- `testimonial-block`
- `faq-accordion`
- `pricing-table`

## Classe CSS principale
Usare prefisso coerente:

- `r4-testimonial`
- `r4-faq`
- `r4-pricing-table`

---

# 5. Schema standard di un blocco

Template minimo consigliato:

```javascript
import { mergeTraits, traitValue, normalizeTarget } from '../helpers.js';

export function registerNomeBlocco(editor) {
    editor.DomComponents.addType('nome-blocco', {
        isComponent: el => {
            if (el && el.classList && el.classList.contains('r4-nome-blocco')) {
                return { type: 'nome-blocco' };
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
                copyable: true,
                removable: true,
                attributes: {
                    class: 'r4-nome-blocco'
                },

                block_title: 'Titolo blocco',
                block_text: 'Testo blocco',

                traits: mergeTraits([
                    { type: 'text', name: 'block_title', label: 'Titolo', changeProp: 1 },
                    { type: 'textarea', name: 'block_text', label: 'Testo', changeProp: 1 }
                ])
            },

            init() {
                const render = () => {
                    const title = traitValue(this, 'block_title', 'Titolo blocco');
                    const text = traitValue(this, 'block_text', 'Testo blocco');

                    this.components(`
                        <div class="r4-nome-blocco__inner">
                            <h3>${title}</h3>
                            <p>${text}</p>
                        </div>
                    `);
                };

                render();
                this.on('change:block_title change:block_text', render);
            }
        }
    });

    editor.BlockManager.add('nome-blocco', {
        label: 'Nome Blocco',
        category: 'Custom',
        content: {
            type: 'nome-blocco',
            block_title: 'Titolo blocco',
            block_text: 'Testo blocco'
        }
    });
}
```

---

# 6. Come rendere disponibile un nuovo blocco nell'editor

Dopo aver creato il file in `public/pb/v3/blocks/`, bisogna fare 2 passaggi.

## Passo 1: import in `editor.js`

Esempio:

```javascript
import { registerTestimonialBlock } from './blocks/testimonial.js';
```

## Passo 2: registrazione del blocco

Esempio:

```javascript
registerBasicBlocks(editor);
registerLayoutBlocks(editor);
registerImageBlock(editor, openImagePicker);
registerFeatureCardBlock(editor);
registerProductCardBlock(editor);
registerAlertBoxBlock(editor);
registerTestimonialBlock(editor);
```

Da quel momento il blocco è disponibile nel pannello del builder.

---

# 7. Categorie blocchi consigliate

Nel `BlockManager.add()` c'è il campo:

```javascript
category: '...'
```

Categorie consigliate:

- `Basic`
- `Layout`
- `Landing`
- `R4 Components`
- `Marketing`
- `Content`
- `Advanced`

### Regola pratica

- blocchi generici → `Basic` / `Layout`
- blocchi aziendali personalizzati → `R4 Components`
- blocchi per landing / conversione → `Landing` / `Marketing`

---

# 8. Buone pratiche

## Fare

- tenere ogni blocco isolato nel suo file
- usare helper condivisi da `helpers.js`
- aggiornare il contenuto tramite una funzione `render()`
- usare nomi chiari per trait e classi CSS
- testare salvataggio, reload editor e frontend

## Evitare

- mettere logica lunga nella Blade
- duplicare helper in più file
- usare nomi generici come `card`, `box`, `component`
- creare file enormi con blocchi non correlati
- mescolare logica UI editor e logica blocco

---

# 9. Checklist integrazione nuovo blocco

## Creazione file
- creare il file in `public/pb/v3/blocks/`
- esportare una funzione `register...`

## Contenuto blocco
- definire `DomComponents.addType(...)`
- definire `BlockManager.add(...)`
- usare helper comuni

## Integrazione editor
- importare il file in `editor.js`
- richiamare `register...(...)`

## Test
- il blocco compare nel pannello
- si trascina nel canvas
- i trait funzionano
- salva correttamente
- ricaricando l'editor resta uguale
- si visualizza correttamente nel frontend

---

# 10. Template da compilare per richiedere un nuovo blocco

Copia e compila questo template quando vuoi chiedere la realizzazione di un nuovo blocco.

```md
# Richiesta nuovo blocco V3

## 1. Nome blocco
- Nome interno:
- Nome visibile nell'editor:
- Categoria editor:

## 2. Obiettivo del blocco
- A cosa serve:
- In quali pagine verrà usato:
- Se è un blocco generico o specifico R4:

## 3. Contenuti previsti
- Titolo: sì/no
- Sottotitolo: sì/no
- Testo descrittivo: sì/no
- Immagine: sì/no
- Icona: sì/no
- Bottone CTA: sì/no
- Bottone secondario: sì/no
- Badge / etichetta: sì/no
- Prezzo: sì/no
- Elenco puntato: sì/no
- Sfondo personalizzabile: sì/no
- Colore accento: sì/no
- Hover effect: sì/no
- Animazioni: sì/no

## 4. Campi modificabili nei trait
- Elenca tutti i campi che devono essere modificabili:
  -
  -
  -

## 5. Comportamento grafico
- Layout desiderato:
- Desktop:
- Tablet:
- Mobile:
- Altezza minima: sì/no
- Larghezza piena: sì/no
- Boxed: sì/no

## 6. Stile visivo
- Stile riferimento:
- Colori principali:
- Bordi:
- Ombre:
- Radius:
- Effetto hover desiderato:

## 7. Media
- Deve usare media picker: sì/no
- Tipo media: immagine / background / icona / altro
- Deve avere alt text: sì/no

## 8. CTA
- CTA primaria: sì/no
- Testo CTA primaria:
- Link CTA primaria:
- CTA secondaria: sì/no
- Testo CTA secondaria:
- Link CTA secondaria:
- Target _self / _blank:

## 9. Dati statici o dinamici
- Il blocco è statico o dinamico:
- Se dinamico, quali dati deve ricevere:

## 10. Note aggiuntive
- Vincoli:
- Esempi grafici:
- Comportamenti particolari:
```

---

# 11. Template rapido da dare a ChatGPT per creare un nuovo blocco

Usa questo prompt standard.

```md
Voglio creare un nuovo blocco per il Visual Builder V3.

## Nome blocco
- Nome file: [es. testimonial.js]
- Funzione export: [es. registerTestimonialBlock]
- Type GrapesJS: [es. testimonial-block]
- Classe CSS principale: [es. r4-testimonial]
- Categoria editor: [es. R4 Components]

## Obiettivo
[Descrivi a cosa serve il blocco]

## Campi modificabili
- [campo 1]
- [campo 2]
- [campo 3]

## Contenuto iniziale di default
- titolo:
- testo:
- bottone:
- immagine:
- colori:

## Comportamento grafico
- layout desktop:
- layout mobile:
- hover:
- animazioni:

## Media picker
- sì/no
- dove serve:

## Output richiesto
Restituiscimi:
1. il file completo `public/pb/v3/blocks/[nome-file]`
2. l'import da aggiungere in `editor.js`
3. la riga di registrazione da aggiungere in `editor.js`
4. eventuali modifiche necessarie a `helpers.js` solo se davvero servono
```

---

# 12. Output standard atteso quando si crea un nuovo blocco

Quando si richiede un nuovo blocco, l'output dovrebbe includere sempre:

1. file completo del blocco
2. eventuali import necessari
3. riga di registrazione in `editor.js`
4. note tecniche se richiede media picker o helper extra

---

# 13. Conclusione

La regola operativa corretta è:

- creare ogni blocco in `public/pb/v3/blocks/`
- esportare una funzione `register...`
- importarla in `editor.js`
- registrarla dopo gli altri blocchi
- testare editor, salvataggio e frontend

Questo approccio mantiene il builder V3 pulito, leggibile e scalabile.
