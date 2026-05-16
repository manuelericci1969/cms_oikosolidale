# Editor R4Software — Release finale Editor visuale

Data: 07/05/2026  
Repository: `cms_r4software_demo`  
Branch sviluppo: `feature/editor-v5-foundation`  
Branch produzione: `main`

---

## 1. Obiettivo

Questa release consolida il nuovo Editor visuale come ambiente principale per la gestione delle pagine CMS.

L'obiettivo è stato portare l'Editor a una base stabile, utilizzabile e pronta per produzione, con particolare attenzione a:

- interfaccia più chiara e professionale;
- canvas più ampio e realmente utilizzabile;
- animazioni funzionanti nel frontend pubblico;
- gestione struttura pagina più leggibile;
- Code Editor integrato HTML/CSS/JavaScript;
- miglioramento della gestione media;
- compatibilità con il flusso di salvataggio esistente.

---

## 2. Percorso editor dalla lista pagine

File coinvolto:

```text
resources/views/admin/pages/index.blade.php
```

La lista pagine `/admin/pages` ora usa il nuovo Editor come percorso principale.

Il pulsante principale mostra:

```text
Editor
```

senza esporre la dicitura tecnica `V5` all'utente.

La route usata internamente resta:

```php
route('admin.pages.edit_v5', $page)
```

Il vecchio editor resta disponibile come fallback tecnico nel menu, ma non è più il percorso principale.

---

## 3. Toolbar e identità visiva

File coinvolti:

```text
resources/views/admin/pages/editV5.blade.php
public/assets/admin/visual-editor-v5/editor.css
```

La toolbar superiore è stata rifinita con:

- brand R4 visibile;
- nome editor;
- nome pagina corrente;
- pulsanti in stile tab coerenti con l'ambiente dell'editor;
- azioni principali sempre disponibili in alto.

Azioni disponibili:

```text
Dashboard
Esci / Pagine
V4 fallback
Anteprima
Media
Codice
Struttura
Annulla
Ripeti
Desktop
Tablet
Mobile
Salva bozza
Pubblica
```

---

## 4. Canvas full width e simulazione dispositivi

File coinvolti:

```text
public/assets/admin/visual-editor-v5/editor.css
public/assets/admin/visual-editor-v5/core/editor.js
public/assets/admin/visual-editor-v5/ui/left-sidebar.js
```

Il canvas GrapesJS è stato normalizzato per usare tutto lo spazio disponibile in modalità Desktop.

Sono stati corretti/forzati:

- contenitore canvas;
- wrapper GrapesJS;
- iframe interno;
- frame wrapper;
- dimensioni `width` e `height` al 100%;
- rimozione di cornici e margini inutili.

Il risultato è un'area di lavoro più ampia, pulita e coerente.

### Fix simulazione Tablet/Mobile

Dopo il rilascio finale è stato individuato un piccolo bug: i pulsanti `Tablet` e `Mobile` non restringevano il canvas, perché la normalizzazione full width forzava sempre iframe e frame wrapper a `width: 100%`.

La correzione è stata applicata direttamente su `main` nel file:

```text
public/assets/admin/visual-editor-v5/ui/left-sidebar.js
```

Il runtime ora applica correttamente:

```text
Desktop → 100%
Tablet  → 768px
Mobile  → 375px
```

Inoltre:

- il frame viene centrato nell'area canvas;
- il pulsante dispositivo attivo viene evidenziato;
- il fix viene riapplicato dopo `device:select`, `canvas:frame:load` e `load` per evitare che GrapesJS o altri runtime riportino il canvas in full width.

---

## 5. Animazioni frontend pubblico

File coinvolti:

```text
public/assets/editor-v5/runtime/public-animations.js
app/Http/Controllers/Admin/PageVisualEditorV5Controller.php
```

### Problema risolto

Le animazioni erano visibili in editor ma non partivano correttamente nel frontend pubblico.

Gli attributi arrivavano correttamente:

```html
[data-r4-animation]
```

ma il runtime pubblico non rendeva l'effetto visibile in modo affidabile.

### Soluzione

Il runtime è stato reso più robusto usando transizioni inline su:

```text
opacity
transform
filter
```

invece di dipendere solo da `animation-name` e `@keyframes`.

Sono stati corretti anche:

- parsing di durata/delay come `2.7s`, `2700ms`, `2700`;
- ripetizione viewport con `data-r4-animation-once="false"`;
- pulizia delle classi runtime salvate per errore nel markup;
- cache key del runtime pubblico.

Classi runtime rimosse al salvataggio:

```text
is-r4-prepared
is-animated
r4-animation-visible
is-r4-bg-animation-ready
```

---

## 6. Code Editor HTML/CSS/JavaScript

File coinvolti:

```text
public/assets/admin/visual-editor-v5/core/editor.js
public/assets/admin/visual-editor-v5/core/code-editor-readable.js
resources/views/admin/pages/editV5.blade.php
```

È stato introdotto un Code Editor integrato accessibile dal pulsante:

```text
Codice
```

Il Code Editor permette di modificare:

- HTML;
- CSS;
- JavaScript personalizzato.

Funzioni incluse:

- tab separate HTML/CSS/JS;
- applicazione diretta al canvas;
- sync con i campi hidden `visual_html`, `visual_css`, `visual_json`;
- formattazione automatica leggibile;
- pulsanti `Formatta tab attiva`, `Formatta tutto`, `Testo a capo`;
- statistiche righe/caratteri.

Il JavaScript custom viene gestito come:

```html
<script data-r4v5-custom-js="1">
// codice personalizzato
</script>
```

---

## 7. Modal Struttura pagina

File coinvolto:

```text
public/assets/admin/visual-editor-v5/ui/layers.js
```

La Struttura pagina non è più dentro la sidebar laterale.

È stata spostata nel menu alto con il pulsante:

```text
Struttura
```

Al click viene aperto un modal flottante.

Funzioni incluse:

- visualizzazione gerarchica degli elementi della pagina;
- selezione di sezioni, colonne, testi, immagini, pulsanti e blocchi;
- evidenza blu dell'elemento selezionato;
- riepilogo dell'elemento selezionato in alto;
- ricerca elementi;
- refresh manuale;
- aggiornamento automatico quando la pagina cambia;
- chiusura con `Esc`;
- modal trascinabile sul monitor;
- modal ridimensionabile;
- larghezza ridotta per non occupare troppo spazio nel canvas.

---

## 8. Media manager

Sono state consolidate le funzioni media già integrate nell'Editor:

- apertura libreria media;
- upload immagini;
- selezione immagini;
- inserimento immagine singola;
- inserimento gallery;
- inserimento slider;
- inserimento griglia loghi/lavori;
- eliminazione media selezionati.

---

## 9. Preview e UI editor

È stata gestita la rimozione/nascondimento del vecchio bottone laterale:

```text
Preview pagina
```

nel contesto editor, quando presente nel contenuto o nel canvas.

La preview corretta resta disponibile dalla toolbar superiore tramite:

```text
Anteprima
```

---

## 10. File principali della release

```text
resources/views/admin/pages/index.blade.php
resources/views/admin/pages/editV5.blade.php
app/Http/Controllers/Admin/PageVisualEditorV5Controller.php
public/assets/admin/visual-editor-v5/editor.css
public/assets/admin/visual-editor-v5/core/editor.js
public/assets/admin/visual-editor-v5/core/code-editor-readable.js
public/assets/admin/visual-editor-v5/ui/layers.js
public/assets/admin/visual-editor-v5/ui/left-sidebar.js
public/assets/editor-v5/runtime/public-animations.js
```

---

## 11. Verifiche consigliate post deploy

### Editor

- Aprire `/admin/pages`;
- cliccare su `Editor`;
- verificare toolbar R4;
- verificare canvas full width;
- verificare pulsanti `Codice` e `Struttura`.

### Simulazione dispositivi

- cliccare `Desktop` e verificare canvas pieno;
- cliccare `Tablet` e verificare canvas centrato a circa `768px`;
- cliccare `Mobile` e verificare canvas centrato a circa `375px`;
- verificare che il pulsante attivo sia evidenziato nella toolbar.

### Code Editor

- aprire `Codice`;
- verificare tab HTML/CSS/JavaScript;
- usare `Formatta tutto`;
- modificare HTML o CSS;
- applicare al canvas;
- salvare bozza.

### Struttura

- aprire `Struttura`;
- trascinare il modal;
- selezionare un elemento;
- verificare evidenza blu;
- verificare selezione nel canvas;
- chiudere con `Esc`.

### Animazioni

- applicare un'animazione a un blocco;
- salvare/pubblicare;
- verificare frontend pubblico;
- verificare repeat viewport se configurato.

### Media

- aprire Media;
- inserire immagine;
- eliminare media selezionato dove necessario;
- salvare e verificare frontend.

---

## 12. Comandi deploy locale

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

---

## 13. Comandi deploy server

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

Se il server usa PHP Plesk esplicito:

```bash
/opt/plesk/php/8.3/bin/php artisan optimize:clear
/opt/plesk/php/8.3/bin/php artisan view:clear
/opt/plesk/php/8.3/bin/php artisan config:clear
/opt/plesk/php/8.3/bin/php artisan route:clear
```

---

## 14. Stato release

Stato: pronta per produzione.

Questa release rappresenta la base stabile dell'Editor visuale R4Software.

Gli sviluppi successivi potranno concentrarsi su:

- template/sezioni salvabili;
- responsive avanzato;
- Inspector più ordinato;
- eventuale integrazione Monaco Editor o CodeMirror;
- pulizia definitiva delle diciture tecniche residue;
- documentazione utente semplificata.
