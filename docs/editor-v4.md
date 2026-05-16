# Editor V4 - Documentazione tecnica e funzionale

## Stato

Versione confermata e portata in produzione su `main`.

Commit di riferimento finale attuale:

```text
1a53b563be973537e58a887cf7c9d9ca1e459c8a
```

Questa documentazione descrive il lavoro svolto sull'Editor V4 del CMS/CRM R4Software, con particolare riferimento a:

- organizzazione della UI;
- menu laterale sinistro;
- impostazioni pagina;
- pannello stile/proprietà;
- canvas;
- messaggi di salvataggio;
- isolamento del CSS pubblico;
- pannello Layers flottante.

---

## Obiettivo dell'intervento

L'intervento ha avuto l'obiettivo di rendere l'Editor V4 più usabile, compatto, coerente graficamente e sicuro nel rendering pubblico.

Prima dell'intervento l'Editor V4 presentava una struttura più dispersiva:

```text
Menu sinistro    Canvas centrale    Menu destro
Blocchi/Layers   Area pagina        Stile/Proprietà
```

La nuova impostazione concentra gli strumenti principali nel menu sinistro, libera spazio per il canvas e rende contestuali i pannelli di modifica.

---

## Struttura finale dell'interfaccia

La struttura finale è:

```text
Topbar compatta scura
Menu sinistro compatto/scuro    Canvas esteso
Layers flottante opzionale      Pannello stile contestuale nel menu sinistro
```

Il menu destro viene mantenuto nel markup per compatibilità con GrapesJS, ma viene collassato graficamente a larghezza zero. I contenitori GrapesJS di stile e proprietà vengono spostati dinamicamente nel menu sinistro.

Il pannello `Layers`, invece, non occupa più spazio fisso nella sidebar sinistra: viene spostato in un pannello flottante sopra il canvas, apribile e chiudibile dalla topbar.

---

## File principali coinvolti

### Blade

```text
resources/views/admin/pages/editV4.blade.php
```

Contiene:

- struttura principale dell'Editor V4;
- topbar;
- canvas;
- sidebar sinistra;
- sidebar destra mantenuta per compatibilità;
- contenitore `#r4v4-layers` iniziale;
- inclusione degli asset CSS/JS dedicati.

### Controller / Provider / Support

```text
app/Http/Controllers/Admin/PageVisualEditorV4Controller.php
app/Providers/AppServiceProvider.php
app/Support/VisualEditorCssScope.php
```

### JavaScript

```text
public/assets/admin/visual-editor-v4/app.js
public/assets/admin/visual-editor-v4/sidebar-tabs.js
public/assets/admin/visual-editor-v4/page-settings.js
public/assets/admin/visual-editor-v4/flash-message.js
public/assets/admin/visual-editor-v4/layers-floating.js
```

### CSS

```text
public/assets/admin/visual-editor-v4/editor.css
public/assets/admin/visual-editor-v4/sidebar-compact.css
public/assets/admin/visual-editor-v4/topbar-compact.css
public/assets/admin/visual-editor-v4/layers-floating.css
```

---

## Menu sinistro a tab

È stato introdotto un sistema a tab nel menu sinistro.

Tab disponibili:

```text
Pagina
Widget
Elementi
Stile
```

### Pagina

Contiene le impostazioni della pagina:

- titolo pagina;
- slug;
- estratto;
- data pubblicazione;
- stato;
- homepage;
- meta title;
- meta description;
- meta keywords;
- visibilità frontend;
- layout pagina.

I pulsanti di azione sono posizionati alla fine naturale dello scroll del tab:

```text
Applica
Salva
Media
```

Il comportamento sticky è stato rimosso per lasciare i pulsanti alla fine degli elementi del menu Pagina.

### Widget

Contiene i componenti già pronti e sezioni preconfigurate.

Esempi:

- hero;
- card marketing;
- statistiche;
- testimonianze;
- pricing;
- CTA;
- componenti CrewLive;
- componenti interattivi.

### Elementi

Contiene elementi base e layout.

Esempi:

- sezioni;
- container;
- colonne;
- testo;
- titolo;
- pulsante;
- immagine;
- video;
- separatore;
- spaziatore.

### Stile

Il tab `Stile` non è sempre visibile.

Comportamento previsto:

```text
Nessun elemento selezionato:
Pagina | Widget | Elementi

Elemento selezionato nel canvas:
Pagina | Widget | Elementi | Stile
```

Quando viene selezionato un elemento nel canvas, il tab Stile compare automaticamente e viene aperto.

All'interno del tab Stile vengono mostrati:

```text
Stile elemento
Proprietà elemento
```

Questi pannelli corrispondono rispettivamente al `styleManager` e al `traitManager` di GrapesJS.

---

## Restyling pannello Stile / Proprietà

Il pannello Stile / Proprietà è stato uniformato al tema scuro dell'Editor V4.

Il problema iniziale era la presenza di box bianchi e componenti GrapesJS non coerenti con il menu sinistro.

La correzione è stata applicata in:

```text
public/assets/admin/visual-editor-v4/topbar-compact.css
```

Obiettivi raggiunti:

- eliminazione del bianco pieno;
- fondo scuro coerente con la sidebar;
- input, select e textarea scuri;
- label leggibili;
- tag classi evidenti;
- bottoni coerenti;
- colori ad alto contrasto;
- nessuna modifica alla logica di salvataggio.

Palette principale usata:

```text
background base: #15181d
card/pannelli:   #202733
header sezioni:  #1a2130
testi primari:   #ffffff
testi normali:   #dbe4f0
testi secondari: #94a3b8
input bg:        #171d28
accent:          #0d6efd
```

---

## Gestione impostazioni pagina

Le impostazioni pagina sono state spostate nel menu sinistro, nel tab `Pagina`.

Il vecchio drawer laterale delle impostazioni pagina è stato disabilitato per evitare duplicazioni e conflitti.

Nel file:

```text
public/assets/admin/visual-editor-v4/sidebar-tabs.js
```

è impostato:

```js
window.R4V4_DISABLE_PAGE_SETTINGS_DRAWER = true;
```

Il file:

```text
public/assets/admin/visual-editor-v4/page-settings.js
```

mantiene una compatibilità minima: se richiamato, reindirizza l'apertura delle impostazioni al tab `Pagina`.

---

## Salvataggio impostazioni pagina

Il salvataggio è stato corretto utilizzando campi reali del form, con attributi `name` coerenti con il controller Laravel.

Esempi:

```html
<input type="text" name="meta[title]">
<textarea name="meta[description]"></textarea>
<input type="text" name="meta[keywords]">
<select name="meta[layout][width]"></select>
<input type="number" name="meta[layout][gutter]">
<input type="number" name="meta[layout][top]">
```

Le checkbox usano il pattern Laravel corretto:

```html
<input type="hidden" name="meta[show_title]" value="0">
<input type="checkbox" name="meta[show_title]" value="1">
```

Questo consente di salvare correttamente sia il valore attivo che quello disattivo.

Campi gestiti:

```text
meta[title]
meta[description]
meta[keywords]
meta[show_title]
meta[show_excerpt]
meta[show_pubdate]
meta[show_author]
meta[show_breadcrumbs]
meta[layout][width]
meta[layout][gutter]
meta[layout][top]
```

Il controller interessato è:

```text
app/Http/Controllers/Admin/PageVisualEditorV4Controller.php
```

Il controller riceve `meta` come array, normalizza il layout e salva i valori nel campo `meta` della pagina.

---

## Sincronizzazione contenuto GrapesJS

Prima del salvataggio vengono sincronizzati i campi hidden dell'editor:

```text
visual_html
visual_css
visual_json
```

La sincronizzazione avviene leggendo i dati dall'istanza GrapesJS:

```js
editor.getHtml()
editor.getCss()
editor.getProjectData()
```

Questo garantisce che il salvataggio includa sia le impostazioni pagina sia il contenuto visuale aggiornato.

---

## Isolamento CSS dell'Editor V4 nel frontend pubblico

È stato risolto un problema critico: lo stile applicato a un elemento del canvas poteva propagarsi a parti esterne della pagina pubblica, come `body`, header, footer, nav o layout globale.

Il problema nasceva dal fatto che GrapesJS poteva generare CSS con selettori generici, ad esempio:

```css
body { background: #000; }
section { background: #000; }
.container { ... }
```

Nel frontend pubblico questi selettori potevano colpire anche elementi fuori dal contenuto visuale.

### Soluzione implementata

È stato introdotto il support class:

```text
app/Support/VisualEditorCssScope.php
```

Questo helper esegue lo scope del CSS generato dall'editor dentro il wrapper pubblico:

```text
.page-visual-content
```

Esempi:

```css
section { background: #000; }
```

viene confinato come:

```css
.page-visual-content section { background: #000; }
```

Mentre selettori globali come:

```css
body { background: #000; }
html { ... }
:root { ... }
```

vengono neutralizzati o convertiti sul wrapper del contenuto visuale, senza toccare il layout esterno.

### Aggancio al rendering

Lo scope viene applicato tramite view composer in:

```text
app/Providers/AppServiceProvider.php
```

View interessata:

```text
page.show
```

In questo modo sia l'anteprima admin V4 sia la pagina pubblica usano lo stesso rendering protetto.

### Regola funzionale

```text
Le impostazioni pagina modificano il layout/metadati della pagina.
Gli stili degli elementi modificano solo il contenuto dell'Editor V4.
Nessuno stile generato dall'Editor V4 deve propagarsi a header, footer, body o layout globale.
```

---

## Sidebar destra collassata

La sidebar destra viene mantenuta nel Blade per non rompere l'inizializzazione di GrapesJS, che si aspetta ancora i contenitori:

```text
#r4v4-styles
#r4v4-traits
```

Successivamente questi contenitori vengono spostati nel tab `Stile` del menu sinistro.

Il CSS collassa la sidebar destra:

```css
.r4v4-sidebar-right {
    width: 0 !important;
    min-width: 0 !important;
    padding: 0 !important;
    border-left: 0 !important;
    overflow: hidden !important;
    opacity: 0;
    pointer-events: none;
}
```

Questa scelta rende la modifica reversibile e riduce il rischio di regressioni.

---

## Canvas esteso

L'area di lavoro è stata allargata sfruttando lo spazio lasciato libero dalla sidebar destra.

Il layout della workspace usa:

```css
.r4v4-workspace {
    grid-template-columns: 260px minmax(0, 1fr) 0 !important;
}
```

Quindi:

```text
260px     menu sinistro
1fr       canvas
0px       sidebar destra collassata
```

Il canvas viene centrato e portato alla massima larghezza disponibile.

---

## Preview Desktop, Tablet e Mobile

È stato corretto il problema per cui le simulazioni Tablet e Mobile non producevano differenze visive.

Il problema era causato da regole CSS che forzavano il frame GrapesJS al 100% anche quando veniva selezionato un dispositivo diverso.

È stato aggiunto un override CSS che distingue i pulsanti dispositivo attivi:

```text
Desktop = frame 100%
Tablet  = frame centrato a 768px
Mobile  = frame centrato a 390px
```

Comportamento finale:

```text
Desktop: canvas pieno
Tablet: anteprima centrata e ristretta
Mobile: anteprima centrata e ristretta
```

---

## Topbar compatta scura

È stato introdotto il file:

```text
public/assets/admin/visual-editor-v4/topbar-compact.css
```

La topbar è stata uniformata allo stile del menu sinistro:

- sfondo scuro;
- pulsanti squadrati;
- font 10px;
- separatori verticali;
- altezza compatta;
- niente radius;
- scroll orizzontale se i pulsanti non entrano.

Pulsanti principali mantenuti:

```text
Dashboard
Esci / Pagine
V3 legacy
Anteprima admin
Apri pubblica
Media
Layers
Focus canvas
Annulla
Ripeti
Preview canvas
Svuota
Desktop
Tablet
Mobile
Salva bozza
Pubblica
```

Sono stati rimossi dalla toolbar:

```text
Impostazioni pagina
Allarga canvas / Mostra stile
```

Le impostazioni pagina sono ora raggiungibili dal tab `Pagina` del menu sinistro.

---

## Layers flottante

Il pannello `Layers` è stato trasformato da pannello fisso nella sidebar sinistra a pannello flottante sopra il canvas.

Branch di sviluppo:

```text
feature/pb-v4-floating-layers
```

Commit finale portato su main:

```text
1a53b563be973537e58a887cf7c9d9ca1e459c8a
```

File aggiunti:

```text
public/assets/admin/visual-editor-v4/layers-floating.css
public/assets/admin/visual-editor-v4/layers-floating.js
```

File aggiornato:

```text
resources/views/admin/pages/editV4.blade.php
```

### Comportamento

```text
- pulsante “Layers” nella topbar;
- apertura/chiusura del pannello dal pulsante “Layers”;
- chiusura tramite X nel pannello;
- pannello scuro con testi chiari;
- il vecchio box Layers nel menu sinistro viene nascosto;
- #r4v4-layers viene spostato via JavaScript dentro il pannello flottante;
- stato aperto/chiuso salvato in localStorage.
```

### Motivazione tecnica

`#r4v4-layers` resta nel Blade per compatibilità con l'inizializzazione GrapesJS, ma viene spostato via JavaScript nel pannello flottante.

Questo approccio evita di rompere il layer manager e rende la modifica facilmente reversibile.

---

## Messaggi flash di salvataggio

È stato introdotto il file:

```text
public/assets/admin/visual-editor-v4/flash-message.js
```

Il messaggio:

```text
Pagina Editor V4 aggiornata.
```

ora può essere chiuso manualmente tramite pulsante `X` e, se è un messaggio di successo, viene rimosso automaticamente dopo 3 secondi.

Comportamento:

```text
Messaggi di successo: auto-dismiss dopo 3 secondi
Messaggi di errore: restano visibili finché l'utente non li chiude
```

Lo stile dei messaggi è definito in:

```text
public/assets/admin/visual-editor-v4/topbar-compact.css
```

---

## Asset caricati dall'Editor V4

Nel Blade `editV4.blade.php` sono caricati gli asset principali:

```blade
<link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v4/editor.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v4/sidebar-compact.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v4/topbar-compact.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v4/layers-floating.css') }}">
```

```blade
<script src="{{ asset('assets/admin/visual-editor-v4/app.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/sidebar-tabs.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/layers-floating.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/media-bridge.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/media-tools.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/slider-pro.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/editor-runtime-bridge.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/focus-mode.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/page-settings.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/flash-message.js') }}"></script>
<script src="{{ asset('assets/admin/visual-editor-v4/animation-tools.js') }}"></script>
```

Il file `panel-toggle.js` non viene più caricato, perché la sidebar destra è stata collassata e la gestione stile/proprietà è stata spostata nel tab sinistro `Stile`.

---

## Branch e commit principali

### Prima fase: sidebar e canvas

Branch iniziale:

```text
feature/pb-v4-sidebar-tabs
```

Commit finale portato su main:

```text
77a43aef3fe9ffb425bb720aeda099d8454a6ef9
```

Modifiche principali:

- sidebar sinistra a tab;
- impostazioni pagina nel menu sinistro;
- scroll del menu sinistro corretto;
- tab Stile condizionale;
- sidebar destra collassata;
- canvas esteso;
- preview Tablet/Mobile ripristinata.

### Seconda fase: topbar e messaggi flash

Branch:

```text
feature/pb-v4-dark-topbar
```

Commit finale portato su main:

```text
ffc5f136f59b091473568fe06ad04fe34877dc1d
```

Modifiche principali:

- topbar scura e compatta;
- nuovo `topbar-compact.css`;
- nuovo `flash-message.js`;
- messaggi di successo chiudibili e auto-dismiss dopo 3 secondi.

### Terza fase: documentazione iniziale

Commit:

```text
b5dcb2c95828f72c04545d4b348b3f1f284c629c
```

Modifiche principali:

- creazione di `docs/editor-v4.md`.

### Quarta fase: isolamento CSS pubblico

Branch:

```text
hotfix/pb-v4-css-scope
```

Commit finale portato su main:

```text
4efda98dfff4f9480fb47e132992ea54e0e90a95
```

Modifiche principali:

- aggiunto `app/Support/VisualEditorCssScope.php`;
- aggiunto view composer in `AppServiceProvider`;
- confinato il CSS visuale dentro `.page-visual-content`;
- impedita la propagazione di stili generici su layout pubblico.

### Quinta fase: skin scura del pannello Stile / Proprietà

Commit:

```text
096168a87c98efc8fe154aca649a41a741e89677
```

Modifiche principali:

- pannello Stile / Proprietà scurito;
- migliorato contrasto label/input/select;
- rimossi box bianchi non coerenti.

### Sesta fase: Layers flottante

Branch:

```text
feature/pb-v4-floating-layers
```

Commit finale portato su main:

```text
1a53b563be973537e58a887cf7c9d9ca1e459c8a
```

Modifiche principali:

- aggiunto `layers-floating.css`;
- aggiunto `layers-floating.js`;
- aggiunto pulsante `Layers` in topbar;
- spostato `#r4v4-layers` in pannello flottante;
- vecchio box Layers nascosto nella sidebar;
- stato aperto/chiuso salvato in localStorage.

---

## Comandi di aggiornamento locale

```bash
cd /Users/manuelericci/Sites/cms_r4software
git checkout main
git pull origin main
php artisan optimize:clear
```

Per verificare il commit corrente:

```bash
git log --oneline -5
```

---

## Comandi di aggiornamento produzione/Plesk

```bash
git checkout main
git pull origin main

php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

Le modifiche frontend dell'Editor V4 sono su asset statici in `public/assets`, quindi per questi interventi non è necessario eseguire `npm run build`.

---

## Note di rollback

La modifica è stata organizzata in file dedicati e facilmente reversibili.

Per tornare alla versione precedente al Layers flottante:

```bash
git revert 1a53b563be973537e58a887cf7c9d9ca1e459c8a
```

Per tornare alla versione precedente allo scoping CSS pubblico:

```bash
git revert 4efda98dfff4f9480fb47e132992ea54e0e90a95
```

Per tornare alla versione precedente alla topbar compatta e ai flash message:

```bash
git revert ffc5f136f59b091473568fe06ad04fe34877dc1d
```

Per tornare alla versione precedente alla riorganizzazione sidebar/canvas:

```bash
git revert 77a43aef3fe9ffb425bb720aeda099d8454a6ef9
```

In alternativa, si può creare un branch di rollback dal commit stabile desiderato.

---

## Prossimi miglioramenti consigliati

Possibili evoluzioni future:

1. Rendere il pannello Layers trascinabile.
2. Aggiungere posizione persistente del pannello Layers in localStorage.
3. Pulizia definitiva del Blade rimuovendo la sidebar destra solo dopo ulteriore stabilizzazione.
4. Miglioramento del pannello Stile con gruppi ancora più compatti.
5. Aggiunta di preset grafici per elementi selezionati.
6. Salvataggio automatico temporizzato opzionale.
7. Indicatori visivi di salvataggio in corso / salvato.
8. Versioning interno delle pagine create con Editor V4.
9. Manuale utente separato per operatori non tecnici.

---

## Sintesi finale

L'Editor V4 ora presenta una UX più compatta, professionale e sicura:

```text
- menu sinistro unico per pagina, widget, elementi e stile;
- canvas più ampio;
- Layers flottante sopra il canvas;
- controlli stile/proprietà contestuali;
- impostazioni pagina salvate correttamente;
- topbar coerente con il tema scuro;
- pannello Stile / Proprietà scuro e leggibile;
- messaggi flash non invasivi;
- preview responsive funzionante;
- CSS visuale confinato nel contenuto editoriale pubblico.
```

Questa versione è attualmente considerata stabile e confermata in produzione.
