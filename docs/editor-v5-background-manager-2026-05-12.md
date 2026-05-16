# Editor V5 Background Manager

Data: 12 maggio 2026  
Repository: `cms_r4software_demo`  
Branch: `feature/editor-v5-background-manager-cleanup-2026-05-12`

## Problema iniziale

La gestione degli sfondi dell'Editor V5 era frammentata tra più file:

- `public/assets/admin/visual-editor-v5/panels/background-media.js`
- `public/assets/admin/visual-editor-v5/panels/background-cleaner.js`
- `public/assets/admin/visual-editor-v5/media/media.js`
- `public/assets/admin/visual-editor-v5/runtime/background-slider-editor-bridge.js`
- `public/assets/admin/visual-editor-v5/runtime/background-slider-runtime.js`
- `resources/views/admin/pages/editV5.blade.php`

Il problema più delicato, già risolto in precedenza e da preservare, era la persistenza di vecchie immagini di sfondo dopo il passaggio a colore, gradiente o nessuno sfondo.

L'immagine poteva restare in diversi livelli:

1. style del componente GrapesJS;
2. attributi `data-r4v5-bg-*`;
3. CSS Composer di GrapesJS;
4. elemento reale nel canvas iframe;
5. hidden fields `visual_html`, `visual_css`, `visual_json`.

## Obiettivo del refactoring

Creare un punto unico per applicare, leggere e pulire gli sfondi elemento dell'Editor V5, lasciando distinta la gestione dello sfondo pagina in `meta[page_bg]`.

## Nuova architettura

È stato introdotto il file:

```text
public/assets/admin/visual-editor-v5/background/background-manager.js
```

Il manager espone il namespace globale:

```js
window.R4V5BackgroundManager
```

Il manager è responsabile di:

- leggere lo stato sfondo da un componente;
- applicare modalità `none`;
- applicare modalità `color`;
- applicare modalità `gradient`;
- applicare modalità `image`;
- applicare modalità `slider`;
- rimuovere proprietà incompatibili quando cambia modalità;
- pulire CSS Composer;
- pulire attributi `data-r4v5-bg-*` non coerenti;
- pulire lo stile reale nel canvas iframe;
- rimuovere layer runtime slider residui quando necessario;
- sincronizzare `visual_html`, `visual_css`, `visual_json`;
- mantenere compatibilità con pagine già salvate.

## Correzione dopo primo test locale

Durante il primo ciclo di test locale su `cms_r4software_demo` sono emersi questi problemi:

- `Colore → Immagine`: l'immagine veniva propagata a più section;
- `Immagine → Colore`: il colore veniva propagato a più section;
- `Immagine → Gradiente`: il gradiente veniva propagato a più section;
- `Slider → Immagine`: il cambio non veniva applicato;
- reload Editor V5: i valori di colore e gradiente non venivano ricaricati correttamente nei controlli.

La causa principale era l'applicazione del nuovo stile anche sulle regole del CSS Composer, potenzialmente condivise da più componenti.

La correzione applicata separa le responsabilità:

- CSS Composer: viene usato solo per rimuovere vecchie proprietà background residue;
- componente selezionato: riceve il nuovo sfondo tramite style del componente;
- canvas iframe: viene aggiornato solo l'elemento reale selezionato;
- colore e gradiente vengono salvati anche come attributi di stato dedicati.

Attributi aggiunti per la persistenza UI:

```text
data-r4v5-bg-color
data-r4v5-bg-gradient-from
data-r4v5-bg-gradient-to
data-r4v5-bg-gradient-angle
```

## Estensione compatibilità Code Editor / prompt SEO

Dopo la validazione degli sfondi è stato rilevato un problema di compatibilità tra codice generato dal prompt SEO, Code Editor e canvas GrapesJS:

- le animazioni non venivano reinizializzate nel canvas iframe dell'Editor V5;
- incollando HTML/CSS/JS completi dal prompt SEO, il codice poteva contenere `<body>`, `<style>` e `<script>` non normalizzati;
- alcune sezioni importate apparivano non coerenti o non completamente compatibili nel canvas;
- il runtime animazioni era presente lato pubblico, ma non veniva reiniettato lato editor;
- il JavaScript custom non veniva preservato in modo affidabile se salvato solo come tag `<script>` dentro GrapesJS.

Sono stati aggiunti/aggiornati:

```text
public/assets/admin/visual-editor-v5/runtime/editor-code-runtime-bridge.js
public/assets/admin/visual-editor-v5/core/editor.js
resources/views/admin/pages/editV5.blade.php
resources/views/page/show.blade.php
```

### `editor-code-runtime-bridge.js`

Nuovo bridge lato editor che:

- inietta `public-animations.js` dentro l'iframe GrapesJS;
- reinizializza `R4V5PublicAnimations.init()` nel canvas;
- aggiunge CSS di compatibilità editor per blocchi importati;
- esegue in modo controllato il JavaScript custom nel canvas editor;
- espone `window.R4V5EditorCodeRuntimeBridge.inject()` per reiniezione dopo `editor.setComponents()`;
- salva/ricarica il JavaScript custom in `visual_json.r4v5CustomJs` come fallback persistente, evitando di dipendere solo da `<script>` nel markup GrapesJS.

### `core/editor.js`

Il Code Editor ora normalizza codice incollato completo:

- estrae i blocchi `<style>` dall'HTML e li sposta nel campo CSS;
- estrae i blocchi `<script>` e li sposta nel campo JavaScript;
- rimuove wrapper `<!doctype>`, `<html>`, `<head>`, `<body>`;
- rimuove script runtime `r4v5-*` non persistibili;
- reinietta il runtime editor-side dopo `Applica al canvas`.

### Persistenza JavaScript custom

Il JavaScript personalizzato viene preservato tramite:

```text
visual_json.r4v5CustomJs
```

Questo evita il problema per cui GrapesJS può rimuovere o non ricaricare correttamente tag `<script>` inseriti nei componenti.

La vista pubblica `resources/views/page/show.blade.php` legge `visual_json.r4v5CustomJs` ed esegue il codice dentro un wrapper protetto `DOMContentLoaded`.

### Split sezioni importate

Dopo il test visivo nel canvas è emerso che una pagina incollata dal Code Editor poteva essere interpretata da GrapesJS come un unico wrapper pagina. In quel caso il tool `Trascina il widget qui` compariva solo nell'ultimo punto utile, invece di lavorare sezione per sezione.

La normalizzazione ora:

- rileva un wrapper unico `main`, `article` o `div` con più sezioni dirette;
- spacchetta il wrapper e importa le sezioni come componenti fratelli;
- marca le sezioni di primo livello con `data-r4v5-code-section`;
- aggiunge attributi GrapesJS di compatibilità:

```text
data-gjs-droppable="true"
data-gjs-selectable="true"
data-gjs-highlightable="true"
data-gjs-hoverable="true"
```

Sono stati aggiunti anche slot intermedi `data-r4v5-code-drop-slot` per consentire il trascinamento widget tra blocchi importati. La resa visuale del placeholder `+ Trascina il widget qui` può non essere identica al tool nativo, ma il drag & drop negli slot intermedi è stato validato come funzionante.

### `editV5.blade.php`

Aggiornato caricamento asset:

```text
public/assets/admin/visual-editor-v5/runtime/editor-code-runtime-bridge.js
```

Versioni asset aggiornate durante il ciclo per forzare cache busting.

## API del Background Manager

```js
R4V5BackgroundManager.apply(component, {
  mode: 'none'
});
```

```js
R4V5BackgroundManager.apply(component, {
  mode: 'color',
  color: '#ffffff',
  textColor: '#111827'
});
```

```js
R4V5BackgroundManager.apply(component, {
  mode: 'gradient',
  from: '#0d6efd',
  to: '#eaf3ff',
  angle: 135,
  textColor: '#111827'
});
```

```js
R4V5BackgroundManager.apply(component, {
  mode: 'image',
  image: '/storage/uploads/example.jpg',
  size: 'cover',
  position: 'center center',
  repeat: 'no-repeat',
  attachment: 'scroll',
  overlayColor: '#000000',
  overlayOpacity: 0.35,
  minHeight: '420px'
});
```

```js
R4V5BackgroundManager.apply(component, {
  mode: 'slider',
  images: [
    '/storage/uploads/image-1.jpg',
    '/storage/uploads/image-2.jpg'
  ],
  autoplay: true,
  interval: 4500,
  duration: 700,
  fit: 'cover',
  position: 'center center',
  overlayColor: '#000000',
  overlayOpacity: 0.35,
  minHeight: '420px'
});
```

```js
R4V5BackgroundManager.clear(component);
```

```js
R4V5BackgroundManager.read(component);
```

```js
R4V5BackgroundManager.sync();
```

## File toccati

### `background/background-manager.js`

Nuovo motore unico per gestione sfondi elemento.

### `panels/background-media.js`

Refactor conservativo: il file resta responsabile della UI Inspector, ma delega applicazione e pulizia al manager.

La UI è stata separata concettualmente in modalità:

- Nessuno;
- Colore;
- Gradiente;
- Immagine;
- Slider.

### `media/media.js`

Le azioni del Media Picker per:

- sfondo immagine;
- sfondo slider;

passano ora da `R4V5BackgroundManager.apply()` quando disponibile.

Sono stati mantenuti fallback legacy per evitare blocchi se il manager non fosse caricato.

Le funzioni di inserimento immagine normale, gallery, slider contenuto e loghi/lavori non sono state cambiate concettualmente.

### `panels/background-cleaner.js`

Il cleaner resta come safety layer.

Quando disponibile, delega al manager; in caso contrario mantiene la pulizia legacy su:

- style componente;
- attributi;
- CSS Composer;
- DOM iframe;
- hidden fields.

### `runtime/background-slider-editor-bridge.js`

Il bridge continua a iniettare il runtime slider nell'iframe.

È stata aggiunta una protezione per autocaricare il manager se non presente.

### `runtime/editor-code-runtime-bridge.js`

Nuovo bridge per compatibilità Code Editor, animazioni, JS custom persistente e drop slot nel canvas iframe.

### `runtime/background-slider-runtime.js`

Non modificato nella logica. Rimane compatibile con gli attributi pubblici già usati.

### `resources/views/admin/pages/editV5.blade.php`

Aggiornato il caricamento asset per includere esplicitamente il manager prima di Media Picker, pannello sfondi e cleaner, e per includere il nuovo bridge runtime del Code Editor.

### `resources/views/page/show.blade.php`

Aggiornata la vista pubblica per:

- nascondere gli slot vuoti `data-r4v5-code-drop-slot` sul frontend;
- leggere ed eseguire `visual_json.r4v5CustomJs` lato pubblico.

## Compatibilità attributi `data-r4v5-bg-*`

Restano compatibili e invariati:

```text
data-r4v5-bg-mode
data-r4v5-bg-image
data-r4v5-bg-slider
data-r4v5-bg-slider-images
data-r4v5-bg-slider-autoplay
data-r4v5-bg-slider-interval
data-r4v5-bg-slider-duration
data-r4v5-bg-slider-fit
data-r4v5-bg-slider-position
data-r4v5-bg-slider-min-height
data-r4v5-bg-overlay-color
data-r4v5-bg-overlay-opacity
```

Sono stati aggiunti, senza rompere compatibilità:

```text
data-r4v5-bg-color
data-r4v5-bg-gradient-from
data-r4v5-bg-gradient-to
data-r4v5-bg-gradient-angle
```

Il manager non cambia il runtime pubblico dello slider e non richiede migrazioni DB.

## Separazione sfondo elemento / sfondo pagina

Lo sfondo elemento resta nell'Inspector e viene gestito da `R4V5BackgroundManager`.

Lo sfondo pagina resta nella tab Pagina ed è salvato in:

```text
meta[page_bg]
```

Il refactor non mischia le due responsabilità.

## Test locali eseguiti su demo

Primo ciclo completo:

1. Nessuno → Colore = OK
2. Colore → Immagine = KO iniziale, poi OK dopo correzione CSS Composer scoped
3. Immagine → Colore = KO iniziale, poi OK dopo correzione CSS Composer scoped
4. Immagine → Nessuno = OK
5. Immagine → Gradiente = KO iniziale, poi OK dopo correzione CSS Composer scoped
6. Immagine → Slider = OK
7. Slider → Colore = OK
8. Slider → Nessuno = OK
9. Slider → Immagine = KO iniziale, poi OK dopo fallback immagine slider
10. Immagine scelta da Media Picker = OK
11. Slider scelto da Media Picker = OK
12. Salva bozza = OK
13. Ricarica Editor V5 con colore = KO iniziale, poi OK dopo persistenza `data-r4v5-bg-color`
14. Ricarica Editor V5 con gradiente = KO iniziale, poi OK dopo persistenza attributi gradiente
15. Anteprima pubblica = OK

## Test Code Editor / prompt SEO validati

Ciclo finale:

1. Apri Code Editor = OK
2. Incolla HTML + CSS = OK
3. Incolla JS nel tab JavaScript = OK
4. Applica al canvas = OK
5. Salva bozza = OK
6. Ricarica Editor V5 = OK
7. Riapri Code Editor = OK
8. JS ancora presente nel tab JavaScript = OK
9. Anteprima pubblica = OK

Test slot importati:

- sezioni importate selezionabili singolarmente = OK;
- trascinamento widget in uno slot intermedio = OK;
- salvataggio bozza dopo inserimento widget = OK;
- ricarica Editor V5 = OK;
- anteprima pubblica = OK;
- placeholder visuale `+ Trascina il widget qui` non sempre allineato al tool nativo, ma funzionalità di drop validata = OK funzionale.

## Test di regressione consigliato prima del merge

Prima del merge su `main` locale eseguire un ultimo giro rapido:

1. Immagine → Colore → Salva → Reload Editor;
2. Immagine → Gradiente → Salva → Reload Editor;
3. Slider → Immagine → Salva → Anteprima pubblica;
4. Media Picker → Immagine → Salva → Anteprima pubblica;
5. Media Picker → Slider → Salva → Anteprima pubblica;
6. Code Editor HTML + CSS + JS → Salva → Reload Editor → Anteprima pubblica;
7. Widget trascinato in slot intermedio → Salva → Reload Editor → Anteprima pubblica;
8. Verifica che le section non selezionate non cambino sfondo.

## Istruzioni per porting futuro

Solo dopo validazione locale su `cms_r4software_demo`:

1. portare gli stessi file su `cms_r4software` in branch dedicato;
2. eseguire test locali completi;
3. valutare merge su main locale;
4. solo dopo conferma procedere con produzione;
5. ripetere su `cms_memoriamica` in branch dedicato.

Non fare deploy produzione alla cieca.

## Stato attuale

Il branch è validato sui KO principali emersi dal primo test locale per la gestione sfondi.

È stata aggiunta e validata una patch successiva per compatibilità Code Editor, prompt SEO, animazioni, JS custom persistente nel canvas/editor/frontend e split delle sezioni importate.

Il cleaner non è stato eliminato. Il runtime pubblico slider non è stato rimosso. Gli attributi `data-r4v5-bg-*` sono preservati.
