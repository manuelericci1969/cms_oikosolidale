# Editor V5 R4Software — Recap tecnico lavoro 06/05/2026

Repository: `cms_r4software_demo`  
Branch di sviluppo: `feature/editor-v5-foundation`  
Target di rilascio: `main` per test su server/demo

---

## 1. Obiettivo del lavoro

La giornata di lavoro è stata dedicata al consolidamento dell'Editor V5 come nuova base stabile dell'editor visuale R4Software, superando i limiti emersi nella V4 e riportando progressivamente le funzionalità avanzate previste:

- Inspector moderno e contestuale;
- modifica testi senza conflitti con il canvas;
- gestione sfondi dei blocchi/componenti;
- slider immagini come sfondo;
- integrazione controlli GrapesJS dentro Inspector;
- animazioni blocchi e sfondi;
- runtime editor/pubblico per slider e animazioni;
- pulizia UX con sidebar destra disabilitata;
- Inspector organizzato in TAB;
- correzione controlli Slider Pro su tutte le slide;
- Inspector Immagine dedicato;
- eliminazione media dalla Media Library V5;
- Sezione Avanzata V5;
- primo runtime pubblico compatto per animazioni V5.

L'obiettivo tecnico è fare della V5 la versione definitiva dell'editor, mantenendo GrapesJS come motore canvas ma costruendo sopra un'interfaccia R4Software più guidata, stabile e professionale.

---

## 2. Problema iniziale affrontato

### 2.1 Editing testi dentro Slider Pro

Il primo problema emerso era l'impossibilità di modificare comodamente i testi dentro lo Slider Pro. Il doppio click entrava in conflitto con il runtime dello slider: invece di consentire l'editing testuale, lo slider riprendeva movimento o intercettava l'interazione.

### 2.2 Soluzione adottata

È stato introdotto un nuovo pannello Inspector dedicato ai testi:

`public/assets/admin/visual-editor-v5/panels/text-editor.js`

Il testo non dipende più solo dal doppio click sul canvas. Quando viene selezionato un elemento testuale, l'Inspector mostra un editor guidato con:

- contenuto testuale da textarea;
- font size;
- line-height;
- font weight;
- font style;
- letter spacing;
- allineamento;
- colore testo;
- sfondo testo;
- opzione “Nessuno sfondo sul testo”;
- margin;
- padding;
- border radius;
- link URL e target per elementi link/bottoni;
- preset rapidi per titolo hero e paragrafo.

Questa scelta ha risolto il problema operativo e ha definito il modello UX dell'Editor V5: ogni componente deve avere controlli contestuali nell'Inspector.

---

## 3. Inspector Testo V5

### File principali

- `public/assets/admin/visual-editor-v5/panels/text-editor.js`
- `resources/views/admin/pages/editV5.blade.php`

### Funzionalità disponibili

Quando si seleziona un elemento testuale (`h1`, `h2`, `h3`, `p`, `span`, `a`, `strong`, `em`, `li`, `blockquote`, ecc.), l'Inspector mostra il pannello “Editor testo”.

Il pannello consente di modificare:

- contenuto;
- dimensione font;
- altezza riga;
- peso;
- stile;
- letter spacing;
- allineamento;
- colore;
- sfondo;
- rimozione sfondo;
- margin;
- padding;
- radius;
- link e apertura in nuova finestra.

È stata aggiunta l'opzione:

```text
Nessuno sfondo sul testo
```

che rimuove `background` e `background-color` evitando sfondi indesiderati sui testi.

---

## 4. Sfondo / Media per blocchi e componenti

### File principali

- `public/assets/admin/visual-editor-v5/panels/background-media.js`
- `public/assets/admin/visual-editor-v5/media/media.js`
- `public/assets/admin/visual-editor-v5/runtime/background-slider-runtime.js`
- `public/assets/admin/visual-editor-v5/runtime/background-slider-editor-bridge.js`
- `app/Http/Controllers/Admin/PageVisualEditorV5Controller.php`

### Funzionalità implementate

Nel pannello `Inspector → Sfondo / Media` sono disponibili:

- nessuno sfondo;
- colore;
- gradiente;
- immagine singola da Media;
- slider immagini da Media;
- overlay colore;
- overlay opacità;
- background-size;
- background-position;
- background-repeat;
- attachment scroll/fixed;
- altezza minima blocco;
- colore testo del blocco.

### Slider immagini come sfondo

Attributi salvati sul componente:

```html
data-r4v5-bg-slider="1"
data-r4v5-bg-slider-images="[...]"
data-r4v5-bg-slider-autoplay="true"
data-r4v5-bg-slider-interval="4500"
data-r4v5-bg-slider-duration="700"
data-r4v5-bg-slider-fit="cover"
data-r4v5-bg-slider-position="center center"
```

Il runtime crea un layer interno:

```html
<div data-r4v5-bg-slider-layer="1"></div>
```

in modo che lo sfondo dinamico resti separato dai contenuti testuali e dagli altri elementi del blocco.

---

## 5. Inspector a TAB

### File principali

- `resources/views/admin/pages/editV5.blade.php`
- `public/assets/admin/visual-editor-v5/panels/panels.js`
- `public/assets/admin/visual-editor-v5/ui/inspector-tabs.js`
- `public/assets/admin/visual-editor-v5/panels/panels.css`
- `public/assets/admin/visual-editor-v5/panels/animations.js`

### Struttura attuale

```text
Inspector
├── Base
│   ├── Editor testo
│   ├── Sfondo / Media
│   ├── Immagine
│   ├── Sezione avanzata
│   └── pannelli principali contestuali
├── Animazioni
│   └── Animazioni blocco/sfondo
├── Stile
│   └── Style GrapesJS
└── Proprietà
    └── Traits GrapesJS
```

### UX

Le TAB interne dell'Inspector sono state uniformate alla barra principale `Widget / Inspector / Pagina / SEO`:

- stile piatto;
- testo compatto;
- separatori verticali;
- nessuna icona;
- underline blu `#0d6efd` sulla TAB attiva;
- supporto click e tastiera con frecce sinistra/destra.

La gestione del cambio TAB è stata spostata dentro `panels.js`, già caricato direttamente dalla Blade, così non dipende più dal caricamento dinamico del bridge `sidebar.js`.

---

## 6. Integrazione Style GrapesJS dentro Inspector

I container reali di GrapesJS:

```text
#r4v5Styles
#r4v5Traits
```

sono stati spostati dentro l'Inspector:

- `Stile` → Style GrapesJS;
- `Proprietà` → Traits GrapesJS.

Non sono stati duplicati i controlli: è stato mantenuto un solo punto di verità.

Il CSS dei controlli GrapesJS è stato uniformato allo stile R4Software in:

`public/assets/admin/visual-editor-v5/panels/panels.css`

---

## 7. Sidebar destra disabilitata

### File

`public/assets/admin/visual-editor-v5/ui/sidebar.js`

Dopo aver portato Style e Traits dentro l'Inspector, la sidebar destra non era più necessaria.

Comportamento attuale:

- la sidebar destra non si apre più alla selezione di un componente;
- il pulsante `Avanzato` non è più visibile;
- tutte le funzioni tecniche avanzate sono accessibili da Inspector.

---

## 8. Animazioni V5

### File principali

- `public/assets/admin/visual-editor-v5/panels/animations.js`
- `public/assets/admin/visual-editor-v5/runtime/animations-runtime.js`
- `public/assets/admin/visual-editor-v5/runtime/animations-editor-bridge.js`
- `public/assets/admin/visual-editor-v5/ui/sidebar.js`
- `public/assets/editor-v5/runtime/public-animations.js`
- `app/Http/Controllers/Admin/PageVisualEditorV5Controller.php`

### Animazioni blocco disponibili

- Nessuna;
- Fade in;
- Fade out soft;
- Fade up;
- Fade down;
- Fade left;
- Fade right;
- Zoom in;
- Zoom out;
- Flip up;
- Blur in;
- Slide up;
- Slide left;
- Slide right.

### Parametri blocco

- trigger: viewport o caricamento pagina;
- durata;
- delay;
- easing;
- una sola volta / ripetizione.

Attributi usati:

```html
data-r4-animation="fade-up"
data-r4-animation-trigger="viewport"
data-r4-animation-duration="800"
data-r4-animation-delay="0"
data-r4-animation-easing="ease"
data-r4-animation-once="true"
```

### Animazioni sfondo disponibili

- Nessuna;
- Fade;
- Zoom lento;
- Zoom in;
- Zoom out;
- Ken Burns;
- Pan left;
- Pan right;
- Pan up;
- Pan down;
- Pulse soft.

Attributi usati:

```html
data-r4-bg-animation="pulse-soft"
data-r4-bg-animation-duration="7000"
data-r4-bg-animation-delay="0"
data-r4-bg-animation-loop="true"
data-r4-bg-animation-easing="ease-in-out"
```

### Correzione conflitto blocco/sfondo

Durante i test è emerso un conflitto tra animazione del blocco e animazione dello sfondo: entrambe agivano sullo stesso elemento e la proprietà CSS `animation-name` poteva essere sovrascritta.

È stato corretto separando le responsabilità:

- il blocco principale gestisce l'animazione del componente;
- un layer interno gestisce l'animazione dello sfondo.

Per sfondi statici il runtime crea:

```html
<div data-r4-bg-animation-layer="1"></div>
```

Per slider di sfondo viene animato il layer esistente:

```html
[data-r4v5-bg-slider-layer]
```

### Bug aperto — animazioni lato pubblico non ancora visibili

Durante l'ultimo test è stato segnalato che le animazioni applicate a una `section` risultano visibili/funzionanti nell'editor, ma non sono percepibili nel frontend pubblico renderizzato da `show.blade.php`.

Verifiche già effettuate:

- nel frontend pubblico `document.querySelectorAll('[data-r4-animation]').length` restituisce `3`, quindi gli attributi `data-r4-animation` arrivano correttamente nell'HTML pubblico;
- il problema non è quindi il salvataggio degli attributi nel `visual_html`;
- il vecchio frontend pubblico supportava ancora principalmente `[data-anim]`, mentre Editor V5 salva `data-r4-animation`;
- è stato creato un nuovo runtime compatto dedicato a V5:

```text
public/assets/editor-v5/runtime/public-animations.js
```

- il controller V5 è stato aggiornato per iniettare il nuovo runtime quando trova `data-r4-animation`:

```html
<script id="r4v5-animations-public-runtime" src="/assets/editor-v5/runtime/public-animations.js?v=20260507-v5-public-animations" defer></script>
```

Stato attuale:

```text
BUG ANCORA DA RISOLVERE / VERIFICARE IN NUOVA CHAT
```

Prossima verifica necessaria:

1. fare pull dell'ultimo branch;
2. pulire cache Laravel;
3. risalvare la pagina dall'Editor V5;
4. controllare in console pubblica:

```js
document.getElementById('r4v5-animations-public-runtime')
```

5. controllare:

```js
[...document.querySelectorAll('[data-r4-animation]')].map(el => ({
  anim: el.getAttribute('data-r4-animation'),
  class: el.className,
  opacity: getComputedStyle(el).opacity,
  animationName: getComputedStyle(el).animationName,
  animationDuration: getComputedStyle(el).animationDuration
}))
```

Se il runtime non viene caricato, il prossimo intervento corretto è spostare il caricamento dei runtime V5 direttamente in `show.blade.php` o nel layout pubblico tramite `@push('scripts')`, evitando l'iniezione dentro `visual_html`.

---

## 9. Slider Pro

### File principali

- `public/assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js`
- `public/assets/admin/visual-editor-v5/runtime/slider-pro-editor-bridge.js`

### Problemi risolti

Durante il test è stato rilevato che la freccia destra dello Slider Pro era visibile solo sulla prima immagine/slide e spariva sulle slide successive.

### Soluzione adottata

Il runtime ora crea un layer controlli stabile, esterno al track delle slide:

```html
<div class="r4v5-slider-pro-controls">
  <button data-r4v5-slider-prev>‹</button>
  <button data-r4v5-slider-next>›</button>
  <div data-r4v5-slider-dots>...</div>
</div>
```

Questo layer è sempre figlio diretto dello slider e non viene trascinato dal movimento del track. Sono stati aggiunti anche stili fail-safe per garantire `z-index`, visibilità e `pointer-events`.

Stato utente: `Slider Pro` considerato funzionante e completo per la fase corrente.

---

## 10. Inspector Immagine V5

### File principali

- `public/assets/admin/visual-editor-v5/panels/image-inspector.js`
- `public/assets/admin/visual-editor-v5/ui/sidebar.js`

### Stato

Implementato e testato positivamente in locale.

Quando si seleziona una vera immagine `<img>` nel canvas, dentro `Inspector → Base` compare il pannello `Immagine`.

### Funzionalità disponibili

- URL immagine;
- sostituzione immagine da Media Library;
- alt text;
- title;
- width;
- height;
- object-fit;
- object-position;
- display;
- border radius;
- shadow;
- link immagine;
- target link;
- loading lazy/eager;
- preset cover 100%;
- reset stile.

---

## 11. Media Manager V5 — Eliminazione media

### File principali

- `resources/views/admin/pages/editV5.blade.php`
- `public/assets/admin/visual-editor-v5/media/media.js`
- backend già presente in `app/Http/Controllers/Admin/MediaController.php`

### Stato

Implementato e testato positivamente in locale.

### Backend verificato

Il backend dispone già della route:

```php
Route::delete('/admin/media/{medium}', [MediaController::class, 'destroy'])->name('destroy');
```

e il controller usa già:

```php
ImageUploadService::deleteWithVariants($medium);
```

### Funzionalità aggiunta nella Media Library V5

Nella modale Media è stato aggiunto il pulsante:

```text
Elimina selezionato
```

Comportamento:

- disponibile nella gestione media normale;
- nascosto nelle modalità speciali `sfondo elemento`, `slider sfondo`, `sfondo pagina`;
- attivo solo quando è selezionato un singolo media;
- chiede conferma prima della cancellazione;
- chiama `DELETE /admin/media/{id}`;
- aggiorna automaticamente la griglia dopo la cancellazione;
- avvisa che, se il file è usato in una pagina, il riferimento nella pagina resterà ma il file non sarà più disponibile.

---

## 12. Sezione Avanzata V5

### File principali

- `public/assets/admin/visual-editor-v5/widgets/layout.js`
- `public/assets/admin/visual-editor-v5/panels/advanced-section.js`
- `public/assets/admin/visual-editor-v5/ui/sidebar.js`

### Stato

Implementata e testata positivamente in locale.

Nel menu `Layout` è disponibile il nuovo widget:

```text
Sezione avanzata
```

La sezione nasce con:

- heading centrale;
- 3 colonne desktop;
- 2 colonne tablet;
- 1 colonna mobile;
- 3 card interne editabili;
- griglia responsive;
- attributi `data-r4v5-advanced-section` e relativi parametri.

### Inspector dedicato

Quando si seleziona la sezione esterna, in `Inspector → Base` compare il pannello `Sezione avanzata`.

Controlli disponibili:

- Desktop cols;
- Tablet cols;
- Mobile cols;
- Gap colonne;
- Gap righe;
- Max width interno;
- Padding sezione;
- Margin sezione;
- Altezza minima;
- Sfondo rapido;
- Colore sfondo;
- Colore testo;
- Aggiungi colonna;
- Rimuovi ultima colonna;
- Normalizza card.

Compatibilità:

- `Sfondo / Media`;
- slider immagini come sfondo;
- animazioni blocco/sfondo;
- Inspector testo;
- Inspector immagine;
- Style GrapesJS;
- Traits GrapesJS.

---

## 13. Runtime lato pubblico

### Stato attuale

Il controller V5 controlla il contenuto HTML e inietta automaticamente i runtime necessari quando rileva attributi V5:

- `data-r4v5-slider-pro` → Slider Pro runtime;
- `data-r4v5-bg-slider` → Background Slider runtime;
- `data-r4-animation` / `data-r4-bg-animation` → runtime pubblico animazioni V5.

### Runtime animazioni pubblico V5

È stato creato:

```text
public/assets/editor-v5/runtime/public-animations.js
```

ed è richiamato dal controller con:

```html
<script id="r4v5-animations-public-runtime" src="/assets/editor-v5/runtime/public-animations.js?v=20260507-v5-public-animations" defer></script>
```

Nota architetturale:

L'iniezione dei runtime dentro `visual_html` è funzionante ma non ideale. Il prossimo refactor consigliato è caricare i runtime V5 dal layout pubblico o da `show.blade.php`, in base alla presenza di attributi V5 nel contenuto, senza salvarli nel database.

---

## 14. Stato attuale dell'Editor V5

Attualmente l'Editor V5 consente di:

- aprire e modificare una pagina visuale;
- usare widget base e blocchi statici;
- usare la Sezione Avanzata V5;
- modificare testi tramite Inspector;
- modificare immagini tramite Inspector dedicato;
- cancellare media dalla Media Library V5;
- navigare l'Inspector con TAB interne;
- applicare stili testuali avanzati;
- gestire link;
- applicare sfondi colore, gradiente, immagine e slider;
- scegliere immagini dalla Media Library;
- gestire overlay e altezza minima;
- usare StyleManager e Traits GrapesJS dentro Inspector;
- applicare animazioni a blocchi/componenti in editor;
- applicare animazioni a sfondi statici o slider;
- utilizzare Slider Pro con frecce e dots stabili su tutte le slide;
- salvare bozza/pubblicare;
- visualizzare slider e sfondi dinamici lato pubblico.

Da verificare/risolvere:

- animazioni blocco lato pubblico su `show.blade.php` non ancora visibili nel test utente.

---

## 15. Checklist test consigliata

1. Aprire `/admin/pages/{id}/edit-v5`.
2. Verificare che Editor V5 carichi correttamente.
3. Verificare TAB principali `Widget / Inspector / Pagina / SEO`.
4. Entrare in Inspector e verificare TAB interne `Base / Animazioni / Stile / Proprietà`.
5. Inserire una `Sezione avanzata`.
6. Selezionare un testo e verificare `Editor testo`.
7. Selezionare una immagine e verificare `Inspector Immagine`.
8. Verificare `Sostituisci da Media`.
9. Aprire Media Manager e verificare `Elimina selezionato`.
10. Selezionare un blocco esterno e verificare `Sfondo / Media`.
11. Applicare immagine singola di sfondo.
12. Applicare slider immagini di sfondo.
13. Applicare animazione blocco `Fade in` o `Fade up`.
14. Applicare animazione sfondo `Pulse soft` o `Ken Burns`.
15. Salvare e verificare lato pubblico.
16. Verificare Slider Pro su tutte le slide, freccia destra inclusa.
17. Verificare che la sidebar destra non si apra più.
18. Verificare che Style GrapesJS e Proprietà siano dentro Inspector.
19. Verificare che non restino blocchi invisibili lato pubblico.
20. Verificare hard refresh/cache browser.
21. Verificare console browser per errori JS.
22. Verificare specificamente runtime animazioni pubblico:

```js
document.getElementById('r4v5-animations-public-runtime')
```

23. Verificare `animationName` degli elementi animati.

---

## 16. Prossimi step consigliati

Priorità immediata:

1. Risolvere definitivamente il bug animazioni lato pubblico su `show.blade.php`.
2. Spostare il caricamento runtime V5 dal controller/view salvata a `show.blade.php` o `layouts/app.blade.php` con condizione pulita.
3. Rimuovere progressivamente gli script runtime salvati dentro `visual_html` per evitare cache vecchie o duplicazioni.

Dopo il bug animazioni:

4. Migliorare la Sezione Avanzata V5 con preset:
   - servizi;
   - card vantaggi;
   - griglia immagini;
   - immagine + testo;
   - pricing;
   - testimonial.
5. Completare Inspector Testo con:
   - font family;
   - text-transform;
   - underline;
   - text decoration;
   - hover link;
   - reset stile testo;
   - editor testo lungo in modal.
6. Creare Inspector Bottone dedicato.
7. Creare Inspector Card/Blocco dedicato.
8. Migliorare Layout Pagina V5:
   - fullscreen;
   - landing;
   - blank;
   - nascondi header;
   - nascondi footer;
   - header trasparente;
   - header offset;
   - min-height 100vh;
   - gutter responsive.
9. Aggiungere Code Editor V5 per HTML/CSS/JS.
10. Creare vista Layers / Struttura pagina.

---

## 17. Commit recenti rilevanti

Ultimi commit significativi sul branch `feature/editor-v5-foundation`:

```text
a6221fe Add Editor V5 dedicated image inspector
05210ad Load Editor V5 image inspector panel
129b546 Expose Editor V5 media delete route to JavaScript
de86e71 Add delete action to Editor V5 media manager
bca31a2 Add Editor V5 advanced section widget
5614bfe Add Editor V5 advanced section inspector
cbc1971 Load Editor V5 advanced section inspector panel
2c4bafa Improve public visibility timing for Editor V5 animations
b55904f Bump Editor V5 public animations runtime cache key
f09b58c Add inline fallback for Editor V5 public animations
b2b36c5 Add compact public runtime for Editor V5 animations
3be2a7a Use compact public runtime for Editor V5 animations
```

Nota: alcune prove sul runtime animazioni pubblico sono ancora da validare; il bug rimane aperto fino a conferma lato frontend.

---

## 18. Note operative

Per aggiornare locale:

```bash
cd /Users/manuelericci/Sites/cms_r4software_demo

git fetch origin
git checkout feature/editor-v5-foundation
git pull origin feature/editor-v5-foundation

php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Per test dopo merge su `main`:

```bash
git fetch origin
git checkout main
git pull origin main

php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

---

## 19. Sintesi CTO

La V5 è ora una base solida e molto più ordinata della V4. L'Inspector è diventato il centro operativo dell'editor e permette di controllare testi, immagini, sfondi, media, animazioni, sezioni avanzate e stile avanzato in un unico punto.

Le funzionalità testate positivamente nell'ultima fase sono:

- Inspector Immagine;
- cancellazione media;
- Sezione Avanzata V5;
- Slider Pro stabile;
- Inspector a TAB;
- Style/Traits GrapesJS dentro Inspector.

Rimane da chiudere come priorità assoluta il bug delle animazioni lato pubblico su `show.blade.php`. La direzione tecnica consigliata è spostare il caricamento dei runtime V5 fuori dal contenuto salvato nel database e dentro la view pubblica/layout, con gestione condizionata e pulita degli asset runtime.
