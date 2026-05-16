# Editor R4Software — Intervento e rilascio produzione 06/05/2026

Repository: `cms_r4software_demo`  
Branch di sviluppo: `feature/editor-v5-foundation`  
Branch di rilascio: `main`  
Ambiente target: locale + server demo/produzione

---

## 1. Obiettivo dell'intervento

L'intervento ha avuto tre obiettivi principali:

1. chiudere il bug delle animazioni del nuovo Editor lato frontend pubblico;
2. rendere il nuovo Editor il percorso principale dalla lista pagine `/admin/pages`, senza esporre la dicitura tecnica `V5` all'utente finale;
3. preparare il branch `feature/editor-v5-foundation` al rilascio su `main`.

---

## 2. Bug risolto: animazioni visibili in editor ma non nel frontend pubblico

### Sintomo iniziale

Le animazioni applicate dal pannello `Inspector → Animazioni` risultavano visibili dentro l'Editor, ma non erano percepibili nel frontend pubblico renderizzato da `resources/views/page/show.blade.php`.

Nel frontend gli attributi risultavano presenti:

```js
document.querySelectorAll('[data-r4-animation]').length
```

Il runtime risultava caricato:

```js
document.getElementById('r4v5-animations-public-runtime')
```

ma l'animazione non partiva correttamente.

---

## 3. Analisi tecnica

Durante i test è emerso che:

- gli attributi `data-r4-animation` arrivavano correttamente nel markup pubblico;
- il file `public/assets/editor-v5/runtime/public-animations.js` veniva caricato;
- in alcuni casi gli elementi arrivavano già con classi runtime come `is-animated`, `is-r4-prepared`, `r4-animation-visible`;
- l'uso di sole `@keyframes` e `animation-name` non garantiva una ripartenza visibile dell'effetto;
- alcune durate potevano arrivare come valori CSS (`2.7s`, `2700ms`) e venivano interpretate in modo errato con `parseInt()`.

Il problema principale non era il salvataggio degli attributi, ma la combinazione di:

- stato runtime sporco salvato nel contenuto;
- runtime pubblico troppo fragile;
- parsing non robusto di durata e delay;
- gestione incompleta del repeat viewport.

---

## 4. Soluzione applicata

### 4.1 Runtime pubblico animazioni

File modificato:

```text
public/assets/editor-v5/runtime/public-animations.js
```

Il runtime è stato riscritto per usare una logica più robusta basata su transizioni inline:

1. prepara lo stato iniziale dell'elemento;
2. imposta `opacity`, `transform` e `filter` inline;
3. forza il reflow;
4. applica la `transition` con durata, delay ed easing configurati;
5. porta l'elemento allo stato finale.

Questa soluzione evita dipendenze fragili da `animation-name` e rende l'effetto visibile anche in presenza di CSS generato da GrapesJS o classi pregresse.

### 4.2 Parsing durata e delay

È stato aggiunto un parser dedicato per supportare correttamente:

```text
2700
2700ms
2.7s
2,7s
2.7
```

In questo modo una durata come `2.7s` viene normalizzata a `2700ms` e non più interpretata come pochi millisecondi.

### 4.3 Repeat viewport

La gestione `IntersectionObserver` è stata aggiornata:

- gli elementi con trigger `viewport` vengono sempre osservati;
- se un elemento è già visibile al caricamento, parte comunque dopo un piccolo delay;
- se `data-r4-animation-once="false"`, quando l'elemento esce dal viewport viene riportato allo stato iniziale;
- quando rientra nel viewport, l'animazione riparte.

### 4.4 Pulizia classi runtime al salvataggio

File modificato:

```text
app/Http/Controllers/Admin/PageVisualEditorV5Controller.php
```

È stata aggiunta la pulizia delle classi runtime dal `visual_html` prima del salvataggio:

```text
is-r4-prepared
is-animated
r4-animation-visible
is-r4-bg-animation-ready
```

Questo evita di salvare nel database uno stato visuale temporaneo dell'editor o del runtime pubblico.

### 4.5 Cache busting runtime

Il controller aggiorna la query string del runtime pubblico per forzare il browser a caricare la versione corretta:

```text
?v=20260506-v5-public-timing-repeat
```

---

## 5. Aggiornamento lista pagine `/admin/pages`

File modificato:

```text
resources/views/admin/pages/index.blade.php
```

Prima il pulsante principale della lista pagine puntava a:

```php
route('admin.pages.edit_v4', $page)
```

e mostrava:

```text
Editor V4
```

Ora punta al nuovo Editor:

```php
route('admin.pages.edit_v5', $page)
```

ma nell'interfaccia mostra semplicemente:

```text
Editor
```

La modifica è stata applicata sia alla vista desktop/tablet sia alla vista mobile.

---

## 6. File principali interessati

```text
public/assets/editor-v5/runtime/public-animations.js
app/Http/Controllers/Admin/PageVisualEditorV5Controller.php
resources/views/admin/pages/index.blade.php
docs/editor-v5-production-release-2026-05-06.md
```

---

## 7. Verifiche effettuate

Verifiche lato browser:

```js
document.getElementById('r4v5-animations-public-runtime')?.src
```

Risultato atteso:

```text
/assets/editor-v5/runtime/public-animations.js?v=20260506-v5-public-timing-repeat
```

Verifica elementi animati:

```js
[...document.querySelectorAll('[data-r4-animation]')].map(el => ({
  anim: el.getAttribute('data-r4-animation'),
  trigger: el.getAttribute('data-r4-animation-trigger'),
  once: el.getAttribute('data-r4-animation-once'),
  duration: el.getAttribute('data-r4-animation-duration'),
  delay: el.getAttribute('data-r4-animation-delay'),
  transition: el.style.transition,
  opacity: el.style.opacity,
  transform: el.style.transform,
  running: el.dataset.r4v5AnimRunning
}))
```

Esito utente: il comportamento risulta funzionante correttamente dopo gli ultimi fix.

---

## 8. Comandi aggiornamento locale

```bash
cd /Users/manuelericci/Sites/cms_r4software_demo

git fetch origin
git checkout main
git pull origin main

php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Se la pagina era già stata salvata con classi runtime sporche, aprire la pagina nell'Editor e salvarla di nuovo.

---

## 9. Comandi aggiornamento server produzione/demo

Sul server, dalla directory del progetto:

```bash
cd /percorso/del/progetto/cms_r4software_demo

git fetch origin
git checkout main
git pull origin main

php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Se il server usa permessi dedicati per Laravel, verificare dopo il deploy:

```bash
php artisan route:list | grep edit-v5
```

Controllare poi da browser:

```text
/admin/pages
```

Il pulsante principale deve mostrare `Editor` e aprire il nuovo editor.

---

## 10. Stato rilascio

Stato: pronto per merge su `main`.

Il nuovo Editor è il percorso principale dalla lista pagine. La dicitura `V5` resta solo a livello tecnico di route/file, non nell'interfaccia principale dell'utente.
