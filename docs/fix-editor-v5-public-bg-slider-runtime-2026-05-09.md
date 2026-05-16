# Fix Editor V5 — Loader pubblico background slider

Data: 09/05/2026  
Repository: `manuelericci1969/cms_r4software_demo`  
Branch: `main`  
Commit tecnico: `7e35f2febda106f924d491bb927ff99b986c20d0`

## Problema rilevato

Nel frontend pubblico delle pagine create con Editor V5, una sezione configurata come background slider poteva essere presente nel DOM ma non visibile/attiva lato pubblico.

Marker interessato:

```html
data-r4v5-bg-slider="1"
```

Il runtime necessario è:

```text
/assets/admin/visual-editor-v5/runtime/background-slider-runtime.js
```

## Causa tecnica

La pagina pubblica poteva stampare correttamente il markup salvato in `visual_html`, ma non garantiva il caricamento del runtime del background slider V5.

La repository contiene già un runtime pubblico globale in:

```text
public/assets/nav.js
```

che viene caricato dal layout pubblico.

Per ridurre il rischio, il fix è stato applicato in questo file senza riscrivere la view `show.blade.php`.

## Intervento effettuato

File modificato:

```text
public/assets/nav.js
```

È stato aggiunto un loader JavaScript globale per Editor V5 che:

1. cerca nel DOM elementi con `data-r4v5-bg-slider`;
2. se il runtime `window.R4V5BackgroundSlider` è già disponibile, richiama `init()`;
3. se il runtime non è disponibile, carica dinamicamente:

```text
/assets/admin/visual-editor-v5/runtime/background-slider-runtime.js?v=20260509-v5-bg-slider-global-loader
```

4. dopo il caricamento richiama nuovamente `init()`.

## Perché questa soluzione è prudente

La modifica:

- non altera il controller;
- non modifica il database;
- non modifica i contenuti salvati;
- non impatta pagine prive di background slider V5;
- evita duplicazioni controllando se lo script è già presente;
- recupera pagine già pubblicate.

## Verifica tecnica

Aprire una pagina pubblica contenente:

```html
data-r4v5-bg-slider="1"
```

Nel DOM deve comparire lo script:

```html
<script id="r4v5-background-slider-public-global-runtime" ...></script>
```

Dentro la section slider deve comparire:

```html
<div data-r4v5-bg-slider-layer="1">
```

Se il layer è presente, lo slider è inizializzato correttamente.

## Comandi post-pull consigliati

```bash
git pull origin main
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

## Note future

Per Editor V5 è preferibile centralizzare i runtime pubblici in modo deterministico e non dipendere da script salvati dentro `visual_html`.

Questo fix mantiene compatibilità con il runtime pubblico V4 già presente in `nav.js`.
