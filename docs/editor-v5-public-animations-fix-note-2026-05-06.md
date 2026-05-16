# Editor V5 — Nota tecnica animazioni frontend pubblico 06/05/2026

Repository: `cms_r4software_demo`  
Branch: `feature/editor-v5-foundation`  
Area: Editor V5 / runtime pubblico / `show.blade.php`

---

## 1. Contesto

Durante i test dell'Editor V5 è stato verificato che le animazioni impostate nell'editor risultano visibili correttamente dentro il canvas amministrativo, ma non partono nel frontend pubblico renderizzato dalla pagina pubblica.

Il caso osservato riguarda elementi, in particolare `section`, che arrivano nel frontend con gli attributi V5 corretti:

```html
data-r4-animation="fade-up"
data-r4-animation-trigger="viewport"
data-r4-animation-duration="800"
data-r4-animation-delay="0"
data-r4-animation-easing="ease"
data-r4-animation-once="true"
```

La presenza degli attributi lato pubblico conferma che il problema non riguarda il salvataggio del contenuto nel database, ma il caricamento o l'esecuzione del runtime pubblico.

---

## 2. Diagnosi tecnica

Il vecchio frontend pubblico gestiva principalmente attributi legacy come:

```html
data-anim="..."
```

Editor V5 invece salva e usa il nuovo standard:

```html
data-r4-animation="..."
data-r4-bg-animation="..."
```

Di conseguenza il frontend pubblico deve caricare un runtime dedicato a V5, indipendente dal runtime editor.

Runtime V5 dedicato:

```text
public/assets/editor-v5/runtime/public-animations.js
```

ID script atteso in pagina pubblica:

```html
<script id="r4v5-animations-public-runtime" src="/assets/editor-v5/runtime/public-animations.js?v=20260507-v5-public-animations" defer></script>
```

---

## 3. Strategia corretta

La soluzione più stabile non è salvare script runtime dentro `visual_html`, ma caricarli nella view pubblica o nel layout pubblico in modo condizionato.

Regola consigliata:

- se il contenuto pubblico contiene `data-r4-animation` o `data-r4-bg-animation`, caricare il runtime animazioni V5;
- se contiene `data-r4v5-slider-pro`, caricare il runtime Slider Pro;
- se contiene `data-r4v5-bg-slider`, caricare il runtime background slider;
- evitare script duplicati dentro il contenuto salvato nel database.

Punto consigliato per l'intervento:

```text
resources/views/public/pages/show.blade.php
```

oppure, se la struttura del progetto lo richiede:

```text
resources/views/layouts/app.blade.php
```

con `@push('scripts')` / `@stack('scripts')` se già disponibili.

---

## 4. Verifiche da fare dopo l'intervento

Dopo pull e pulizia cache:

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

Aprire la pagina pubblica e verificare in console:

```js
document.getElementById('r4v5-animations-public-runtime')
```

Risultato atteso: deve restituire il tag `<script>` del runtime V5.

Verificare poi gli elementi animati:

```js
[...document.querySelectorAll('[data-r4-animation]')].map(el => ({
  anim: el.getAttribute('data-r4-animation'),
  trigger: el.getAttribute('data-r4-animation-trigger'),
  class: el.className,
  opacity: getComputedStyle(el).opacity,
  transform: getComputedStyle(el).transform,
  animationName: getComputedStyle(el).animationName,
  animationDuration: getComputedStyle(el).animationDuration
}))
```

Risultato atteso:

- lo script runtime è presente;
- gli elementi con `data-r4-animation` vengono intercettati;
- al caricamento o all'ingresso in viewport ricevono la classe/stato runtime previsto;
- `opacity`, `transform` o `animationName` cambiano in base all'animazione scelta.

---

## 5. Checklist bug animazioni pubblico

1. Confermare che il file `public/assets/editor-v5/runtime/public-animations.js` esista nel branch.
2. Confermare che la pagina pubblica includa lo script con ID `r4v5-animations-public-runtime`.
3. Confermare che non ci siano errori 404 sul file runtime.
4. Confermare che il runtime venga caricato dopo il contenuto HTML pubblico.
5. Confermare che il runtime legga `[data-r4-animation]` e `[data-r4-bg-animation]`.
6. Confermare che il CSS necessario alle animazioni venga iniettato o sia incluso dal runtime.
7. Verificare che non ci siano regole CSS pubbliche che forzano `opacity`, `transform` o `animation` sovrascrivendo V5.
8. Verificare che il vecchio runtime `[data-anim]` non interferisca con i nuovi attributi V5.
9. Risalvare la pagina da Editor V5 dopo il pull per eliminare eventuale HTML vecchio.
10. Eseguire hard refresh del browser.

---

## 6. Stato operativo

Stato attuale del bug:

```text
APERTO / DA VERIFICARE SUL FRONTEND PUBBLICO
```

Conclusione tecnica:

Il salvataggio degli attributi V5 funziona. La priorità è rendere affidabile il caricamento del runtime pubblico in `show.blade.php` o nel layout pubblico, evitando di dipendere da script salvati dentro `visual_html`.

---

## 7. Prossimo intervento consigliato

Implementare caricamento runtime V5 lato view pubblica con condizione basata sul contenuto renderizzato.

Esempio concettuale Blade:

```blade
@if(str_contains($visualHtml ?? '', 'data-r4-animation') || str_contains($visualHtml ?? '', 'data-r4-bg-animation'))
    <script id="r4v5-animations-public-runtime" src="{{ asset('assets/editor-v5/runtime/public-animations.js') }}?v=20260507-v5-public-animations" defer></script>
@endif
```

Nota: adattare il nome variabile (`$visualHtml`, `$page->visual_html`, ecc.) alla struttura reale di `show.blade.php`.

---

## 8. Nota CTO

Questo aggiornamento documentale serve a lasciare traccia chiara del punto esatto da cui ripartire: il problema non è l'Inspector, non è il database e non è GrapesJS. Il punto critico è il runtime pubblico e il suo punto di caricamento nella pagina pubblica.
