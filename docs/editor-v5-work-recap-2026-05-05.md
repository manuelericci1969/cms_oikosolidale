# Editor V5 — Recap lavoro del 05/05/2026

## Repository e branch

Repository:

```text
manuelericci1969/cms_r4software_demo
```

Branch di lavoro:

```text
feature/editor-v5-foundation
```

Obiettivo generale: costruire un nuovo **Editor V5** stabile, evitando i problemi riscontrati nella V4, mantenendo però widget, componenti, tool, media library e logica layout compatibili con il CMS R4Software.

---

## Contesto iniziale

La V4 presentava un problema grave di scrittura nel canvas: durante l’editing il cursore veniva spostato a sinistra e il testo risultava scritto al contrario. Dopo diversi test su V4, menu, guard, runtime e tool, si è deciso di non proseguire sulla V4 ma di creare una nuova base **Editor V5** più pulita e modulare.

Punti chiave emersi:

- V3 scriveva correttamente.
- V4 aveva conflitti probabilmente legati a script/menu/runtime introdotti nel tempo.
- La soluzione scelta è stata creare un Editor V5 mantenendo GrapesJS, ma con struttura JS più controllata.
- Ogni step è stato sviluppato su branch GitHub e poi testato localmente.

---

## Stato attuale validato

Alla fine della sessione risultano funzionanti:

- Editor V5 accessibile.
- Scrittura nel canvas corretta.
- Widget base e marketing inseribili.
- Media library funzionante.
- Inserimento immagini da Media.
- Gallery da Media.
- Slider base da Media.
- Preview pubblica integrata nel canvas.
- Impostazioni pagina/SEO separate.
- Layout pagina salvato.
- Sfondo pagina globale salvato e funzionante lato pubblico.
- Scelta immagine sfondo pagina da Media.
- Slider Pro visibile nel canvas.
- Slider Pro editabile nei testi.
- Slider Pro con immagini da Media.
- Slider Pro dinamico nel canvas dopo runtime iframe.
- Runtime Slider Pro iniettato nel pubblico tramite salvataggio HTML V5.
- Controlli avanzati Slider Pro aggiunti nell’Inspector.

---

## File principali coinvolti

### Blade Editor V5

```text
resources/views/admin/pages/editV5.blade.php
```

Contiene:

- form Editor V5
- configurazione JS globale `window.R4EditorV5Config`
- tab sinistra: Widget / Inspector / Pagina / SEO
- modal Media V5
- inclusione asset CSS/JS principali

### Controller V5

```text
app/Http/Controllers/Admin/PageVisualEditorV5Controller.php
```

Gestisce:

- edit V5
- preview V5
- update/salvataggio V5
- normalizzazione meta pagina
- layout pagina
- sfondo pagina globale
- SEO
- home page
- injection runtime Slider Pro dentro `visual_html` salvato

### Core Editor V5

```text
public/assets/admin/visual-editor-v5/core/editor.js
public/assets/admin/visual-editor-v5/core/registry.js
```

Gestiscono inizializzazione GrapesJS, sincronizzazione campi hidden e registrazione widget.

### UI V5

```text
public/assets/admin/visual-editor-v5/ui/left-sidebar.js
public/assets/admin/visual-editor-v5/ui/sidebar.js
public/assets/admin/visual-editor-v5/ui/public-preview.js
public/assets/admin/visual-editor-v5/ui/page-preview.js
```

Nota: `page-preview.js` era stato usato per tentare la preview live dentro il canvas, ma la soluzione validata è stata `public-preview.js`, cioè preview pubblica integrata via iframe.

### Media V5

```text
public/assets/admin/visual-editor-v5/media/media.js
public/assets/admin/visual-editor-v5/media/media.css
public/assets/admin/visual-editor-v5/media/slider-pro-media.js
```

Gestiscono:

- libreria Media
- selezione immagini
- upload
- inserimento immagine
- gallery
- slider
- logo grid
- scelta sfondo pagina da Media
- generazione Slider Pro da immagini selezionate

### Widget V5

```text
public/assets/admin/visual-editor-v5/widgets/base.js
public/assets/admin/visual-editor-v5/widgets/layout.js
public/assets/admin/visual-editor-v5/widgets/static.js
public/assets/admin/visual-editor-v5/widgets/slider-pro.js
```

Contengono widget base, layout, marketing/media e Slider Pro.

### Runtime Slider Pro

```text
public/assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js
public/assets/admin/visual-editor-v5/runtime/slider-pro-editor-bridge.js
```

Gestiscono:

- autoplay
- frecce
- dots
- effetto slide/fade
- durata animazione
- tempo di sosta
- blocco editing nel canvas
- runtime dentro iframe GrapesJS

### Controlli Inspector Slider Pro

```text
public/assets/admin/visual-editor-v5/panels/slider-pro-controls.js
```

Aggiunge controlli dedicati quando viene selezionato uno Slider Pro.

---

## Impostazioni Pagina / SEO

È stata separata la logica tra **Pagina** e **SEO**.

### Tab Pagina

Campi principali:

```text
meta[page_title]
meta[page_excerpt]
is_homepage
meta[layout][width]
meta[layout][gutter]
meta[layout][top]
meta[page_bg]
meta[show_title]
meta[show_excerpt]
meta[show_pubdate]
meta[show_author]
meta[show_breadcrumbs]
```

### Tab SEO

Campi principali:

```text
meta[seo_title]
meta[seo_description]
meta[seo_keywords]
```

Per compatibilità con la V4 e il frontend esistente, il controller continua a valorizzare anche:

```text
meta[title]
meta[description]
meta[keywords]
```

---

## Layout pagina V5

Gestito in:

```text
meta[layout]
```

Struttura:

```php
[
    'width' => 'standard|boxed|full',
    'gutter' => 0-120,
    'top' => 0-240,
]
```

Il lato pubblico risulta funzionante. La preview live nel canvas non è risultata affidabile a causa dell’isolamento iframe GrapesJS; per questo è stata introdotta la preview pubblica integrata.

---

## Sfondo pagina globale

Gestito in:

```text
meta[page_bg]
```

Tipi supportati:

```text
none
color
gradient
image
```

Funzionanti lato pubblico:

- colore sfondo
- gradiente
- immagine sfondo
- overlay immagine
- scelta immagine da Media

La scelta immagine da Media compila automaticamente:

```text
meta[page_bg][image][src]
```

---

## Preview pubblica integrata nel canvas

La preview live diretta nel canvas non era stabile. È stata quindi introdotta una preview pubblica integrata tramite iframe.

File:

```text
public/assets/admin/visual-editor-v5/ui/public-preview.js
```

Funzionamento:

- compare il pulsante `Preview pagina` nell’area canvas
- apre un iframe sopra il canvas
- carica la rotta reale di anteprima pubblica V5
- mostra la versione salvata
- per vedere modifiche a layout/sfondo occorre salvare bozza e poi aggiornare la preview

Nota importante:

```text
La preview integrata mostra la versione salvata, non le modifiche non ancora salvate.
```

---

## Widget e UI Sidebar

Sono stati organizzati i widget su due colonne.

È stata corretta la visualizzazione delle icone SVG:

- icone lineari
- stroke corretti
- niente fill pieno
- nessuna libreria esterna

File principale CSS:

```text
public/assets/admin/visual-editor-v5/editor.css
```

---

## Slider Pro V5

### Stato attuale

Lo Slider Pro è stato introdotto come widget Media.

Funzioni implementate:

- inseribile dal pannello Widget
- inseribile da Media selezionando più immagini
- testi modificabili nel canvas
- frecce sinistra/destra
- dots/pallini
- autoplay
- pausa hover
- runtime separato
- runtime dentro iframe GrapesJS
- runtime pubblico iniettato in `visual_html` al salvataggio
- controlli Inspector

### File coinvolti

```text
public/assets/admin/visual-editor-v5/widgets/slider-pro.js
public/assets/admin/visual-editor-v5/media/slider-pro-media.js
public/assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js
public/assets/admin/visual-editor-v5/runtime/slider-pro-editor-bridge.js
public/assets/admin/visual-editor-v5/panels/slider-pro-controls.js
```

### Controlli avanzati implementati

Quando si seleziona lo Slider Pro nel canvas, nell’Inspector compaiono:

```text
Animazione: slide / fade
Autoplay: attivo / disattivo
Tempo di sosta ms
Tempo animazione ms
Altezza slider px
Blocco editor: bloccato / dinamico
```

### Modalità editing

Nel canvas è possibile bloccare lo slider per modificare testi e immagini senza che cambi slide.

Modalità:

```text
1. doppio click sullo slider
2. oppure Inspector → Blocco editor = Bloccato per editing
```

Quando bloccato:

- autoplay si ferma
- lo slider non cambia slide mentre si modificano testi/immagini
- compare un badge informativo

### Runtime pubblico

Il controller V5, al salvataggio, controlla se in `visual_html` esiste:

```html
data-r4v5-slider-pro
```

Se presente, aggiunge automaticamente:

```html
<script id="r4v5-slider-pro-public-runtime" src="/assets/admin/visual-editor-v5/runtime/slider-pro-runtime.js?v=20260505-v5-slider-pro-public" defer></script>
```

Questo serve a far funzionare lo slider in:

- preview integrata
- anteprima pubblica
- pagina pubblica reale

Nota: dopo modifiche al runtime, è necessario risalvare la pagina V5.

---

## Ultima attività completata

Ultimi commit rilevanti:

```text
29481b378c1b8c0cd1a35140666622d942d3a0cb
Add Slider Pro animation settings and editor lock mode

8736e7d147aff92dfcb90a96f3b59f59582d3ccb
Add Slider Pro inspector controls

fc1c0492b9f046fa09d459c1f8ef22a778a30092
Load Slider Pro inspector controls
```

---

## Come riprendere il lavoro domani

### Comandi locali

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

Poi hard refresh browser.

### Rotta di test

```text
/admin/pages/4/edit-v5
```

### Test prioritari da fare

1. Inserire Slider Pro da Widget.
2. Inserire Slider Pro da Media con 2/3 immagini.
3. Selezionare Slider Pro.
4. Aprire Inspector.
5. Testare:
   - animazione slide
   - animazione fade
   - autoplay attivo/disattivo
   - tempo di sosta
   - tempo animazione
   - altezza slider
   - blocco editor
6. Salvare bozza.
7. Aprire Preview pagina integrata.
8. Verificare frecce, dots e autoplay.
9. Aprire anteprima pubblica esterna.
10. Verificare lo stesso comportamento.

---

## Prossimi step consigliati

### 1. Verifica finale Slider Pro

Controllare che:

- fade funzioni lato pubblico
- slide funzioni lato pubblico
- autoplay rispetti `data-interval`
- durata animazione rispetti `data-duration`
- frecce e dots funzionino sempre
- blocco editor non venga mantenuto erroneamente lato pubblico, se non desiderato

### 2. Migliorare gestione singole slide

Da implementare:

- selezione singola slide
- duplicazione slide
- eliminazione slide
- cambio immagine singola slide da Media
- ordinamento slide
- titolo/sottotitolo/pulsante per ogni slide
- overlay per singola slide
- posizione testo per singola slide

### 3. Migliorare Inspector generale

Obiettivo:

- sidebar sinistra più professionale
- input più compatti
- sezioni collapsable
- icone più curate
- controlli contestuali solo quando serve

### 4. Pulizia tecnica

Da valutare:

- rimuovere o disattivare `page-preview.js` se non più usato
- consolidare caricamento script dinamici
- eventualmente spostare runtime pubblici in una zona asset pubblica dedicata, non sotto `assets/admin`
- valutare se modificare direttamente `show.blade.php` per caricare runtime noti invece di iniettarli in `visual_html`

---

## Nota importante per nuova chat

Quando si riprende il lavoro, partire da questo contesto:

```text
Stiamo lavorando sul branch feature/editor-v5-foundation della repository cms_r4software_demo.
Editor V5 è stabile nella scrittura.
La preview pubblica integrata funziona.
Slider Pro è stato appena evoluto con controlli avanzati e blocco editor.
La prima cosa da fare è testare Slider Pro lato canvas e lato pubblico dopo pull e risalvataggio pagina.
```
