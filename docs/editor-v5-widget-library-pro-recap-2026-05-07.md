# Editor V5 Widget Library Pro — Spike tecnica 07/05/2026

Repository: `cms_r4software_demo`  
Branch: `feature/editor-v5-widget-library-pro`  
Obiettivo: sperimentare una libreria professionale di widget/sezioni per Editor V5 senza toccare `main`.

---

## Obiettivo

L'esperimento nasce dal confronto tra i widget attualmente disponibili in Editor V5 e un HTML landing professionale con sezioni evolute:

- hero con badge, CTA, trust items e immagine;
- servizi con card immagini/icona/link;
- sezione perché sceglierci con lista check;
- processo a step;
- FAQ accordion;
- CTA finale;
- contenuti editoriali come elenchi, articolo, quote e badge.

La V5 aveva già una foundation solida, ma mancava una libreria editoriale più completa.

---

## File aggiunti

### Runtime/CSS condivisi

- `public/assets/editor-v5/runtime/widgets-pro.css`
- `public/assets/editor-v5/runtime/widgets-pro.js`

### Widget editor

- `public/assets/admin/visual-editor-v5/widgets/content.js`
- `public/assets/admin/visual-editor-v5/widgets/sections-pro.js`

### Bridge editor GrapesJS

- `public/assets/admin/visual-editor-v5/runtime/widgets-pro-editor-bridge.js`

Il bridge carica automaticamente `widgets-pro.css` e `widgets-pro.js` dentro l'iframe di GrapesJS, perché i CSS caricati nella Blade admin non vengono applicati automaticamente al canvas.

---

## File modificati

### `public/assets/admin/visual-editor-v5/widgets/static.js`

È stato aggiunto un caricamento controllato tramite `document.write` durante il parsing della pagina admin, così i nuovi file widget vengono registrati prima del boot di `core/editor.js`.

File caricati:

- `widgets-pro.css`
- `widgets/content.js`
- `widgets/sections-pro.js`
- `runtime/widgets-pro-editor-bridge.js`

### `app/Http/Controllers/Admin/PageVisualEditorV5Controller.php`

Il metodo `withV5PublicRuntimes()` ora riconosce i widget Pro e inietta automaticamente nel frontend pubblico:

```html
<link id="r4v5-widgets-pro-public-style" rel="stylesheet" href="/assets/editor-v5/runtime/widgets-pro.css?...">
<script id="r4v5-widgets-pro-public-runtime" src="/assets/editor-v5/runtime/widgets-pro.js?..." defer></script>
```

Il riconoscimento avviene quando il contenuto contiene:

- `r4v5-pro-`
- `data-r4v5-faq-accordion`
- `data-r4v5-count`

Sono stati aggiunti anche metodi di pulizia per evitare duplicazioni:

- `hasWidgetsProMarkup()`
- `removeRuntimeLink()`

---

## Nuovi widget aggiunti

### Categoria `Contenuti`

- `Elenco puntato`
- `Elenco numerato`
- `Lista check avanzata`
- `Articolo / testo lungo`
- `Citazione / quote`
- `Badge / label`

### Categoria `Sezioni Pro`

- `Hero Pro`
- `Servizi Pro`
- `Perché sceglierci`
- `Processo 4 step`
- `FAQ Accordion`
- `CTA finale Pro`

---

## Runtime interattivo

`widgets-pro.js` gestisce:

- FAQ accordion con apertura/chiusura;
- modalità single-open di default;
- contatori semplici tramite `data-r4v5-count` per evoluzioni future.

---

## Note tecniche

Questa è una spike sperimentale. La scelta di caricare i nuovi file da `static.js` evita modifiche invasive alla Blade `editV5.blade.php`, ma in una futura stabilizzazione si può rendere più pulito il caricamento inserendo direttamente i nuovi asset nella Blade o in un asset loader dedicato.

Per la produzione definitiva sarebbe consigliato:

1. spostare il caricamento dei runtime V5 dal contenuto salvato nel DB verso `show.blade.php` o layout pubblico;
2. trasformare i widget Pro in pacchetto modulare versionato;
3. aggiungere inspector dedicati per card, button, FAQ e sezioni Pro;
4. aggiungere testimonial slider, pricing, logo grid e team card in una seconda milestone.

---

## Test locale consigliato

```bash
cd /Users/manuelericci/Sites/cms_r4software_demo

git fetch origin
git checkout feature/editor-v5-widget-library-pro
git pull origin feature/editor-v5-widget-library-pro

php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Poi testare:

1. apertura `/admin/pages/{id}/edit-v5`;
2. presenza categorie `Contenuti` e `Sezioni Pro` nel pannello widget;
3. inserimento `Hero Pro`;
4. inserimento `Servizi Pro`;
5. inserimento `FAQ Accordion`;
6. salvataggio bozza;
7. pubblicazione;
8. verifica frontend pubblico;
9. apertura/chiusura FAQ nel frontend;
10. responsive mobile/tablet/desktop.

---

## Esito spike

Implementazione pronta per import locale e test manuale.

Se la direzione convince, il branch può diventare base per una milestone stabile `Editor V5 Widget Library Pro`. Se non convince, il branch può essere eliminato senza impatto su `main`.
