# Editor V5 Widget Library Pro — Recap sessione serale 07/05/2026

Repository: `manuelericci1969/cms_r4software_demo`  
Branch di lavoro: `feature/editor-v5-widget-library-pro`  
Contesto: spike tecnica isolata per potenziare l'Editor V5 con una libreria di widget/componenti professionali.

---

## Obiettivo della sessione

L'obiettivo era verificare e ampliare l'Editor V5 perché alcuni widget/componenti risultavano assenti o insufficienti rispetto a un HTML landing professionale fornito come riferimento.

In particolare mancavano o erano deboli:

- elenchi puntati/numerati avanzati;
- blocchi articolo/testo lungo;
- sezioni landing complete;
- FAQ accordion;
- testimonial;
- pricing;
- portfolio/lavori;
- contatto/lead;
- preset landing completa;
- CSS/runtime pubblico per i nuovi widget.

La scelta tecnica è stata lavorare su un branch sperimentale, senza toccare `main`, così da poter testare e, se necessario, eliminare il branch senza impatti sulla versione stabile.

---

## Branch usato

```bash
git checkout feature/editor-v5-widget-library-pro
```

Per aggiornare in locale:

```bash
cd /Users/mac/Sites/cms_r4software_demo

git fetch origin
git checkout feature/editor-v5-widget-library-pro
git pull origin feature/editor-v5-widget-library-pro
```

---

## Problema ambiente locale risolto

Sul MacBook con MAMP PRO era presente PHP CLI 8.3.12 da Homebrew, mentre il progetto richiede PHP `>= 8.4.0`.

Errore visto:

```text
Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.4.0". You are running 8.3.12.
```

Dopo aggiornamento MAMP PRO, è disponibile PHP 8.4.2:

```bash
/Applications/MAMP/bin/php/php8.4.2/bin/php -v
```

Comandi cache consigliati:

```bash
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan view:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan optimize:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan config:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan route:clear
```

---

## File aggiunti

### Runtime widget Pro

```text
public/assets/editor-v5/runtime/widgets-pro.css
public/assets/editor-v5/runtime/widgets-pro.js
```

Funzione:

- CSS condiviso editor/frontend per i widget Pro;
- FAQ accordion;
- contatori semplici `data-r4v5-count`;
- comportamento responsive dei widget Pro.

### Bridge editor GrapesJS

```text
public/assets/admin/visual-editor-v5/runtime/widgets-pro-editor-bridge.js
```

Funzione:

- inietta CSS/JS Pro dentro l'iframe GrapesJS;
- evita che i componenti siano visibili male nell'editor;
- inizializza il runtime Pro anche nel canvas.

### Widget contenuti

```text
public/assets/admin/visual-editor-v5/widgets/content.js
```

Aggiunge widget editoriali:

- elenco puntato;
- elenco numerato;
- lista check avanzata;
- articolo / testo lungo;
- citazione / quote;
- badge / label.

### Widget sezioni Pro

```text
public/assets/admin/visual-editor-v5/widgets/sections-pro.js
```

Aggiunge:

- Hero Pro;
- Servizi Pro;
- Perché sceglierci;
- Processo 4 step;
- FAQ Accordion;
- CTA finale Pro.

### Widget sezioni extra

```text
public/assets/admin/visual-editor-v5/widgets/sections-extra.js
```

Aggiunge sezioni aggiuntive:

- Stats Pro counter;
- Testimonial Pro grid;
- Pricing Pro;
- Logo / Clienti grid;
- Team / Profilo Pro;
- Contatto / Lead Pro;
- Portfolio / Lavori Pro;
- Problema / Soluzione Pro;
- Timeline verticale Pro;
- Comparazione Pro.

### Preset landing completa

```text
public/assets/admin/visual-editor-v5/widgets/landing-presets.js
```

Aggiunge il widget:

```text
Preset Landing → Landing completa R4Software
```

Il preset contiene una landing preassemblata con:

- Hero;
- Problema / Soluzione;
- Servizi;
- Stats;
- Portfolio;
- FAQ;
- CTA finale.

Nota importante: il file è stato creato, ma va verificato che venga caricato correttamente dalla Blade.

---

## File modificati

### `public/assets/admin/visual-editor-v5/widgets/static.js`

È stato inizialmente usato un caricamento sperimentale tramite `document.write`, ma ha causato la scomparsa dei widget.

Correzione applicata:

- rimosso `document.write`;
- registrazione diretta dei widget;
- mantenuto caricamento stabile delle categorie già funzionanti;
- iniezione runtime Pro nel canvas GrapesJS in modo più sicuro.

### `public/assets/admin/visual-editor-v5/widgets/base.js`

Aggiunta categoria:

```text
Liste Pro
```

Preset aggiunti:

- Lista Pro - pallini blu;
- Lista Pro - check verde;
- Lista Pro - numeri card;
- Lista Pro - quadrati dark;
- Lista Pro - box soft;
- Lista Pro pulita - blu circle;
- Lista Pro pulita - viola boxed;
- Lista Pro pulita - dot arancio;
- Lista Pro pulita - rombo dark.

Nota: l'utente ha verificato che funzionano, ma ha indicato che non producono ancora l'effetto desiderato. Sono quindi da considerare una bozza da rivedere più avanti.

### `public/assets/editor-v5/runtime/widgets-pro.css`

Aggiunti:

- sistema CSS widget Pro;
- sistema CSS `r4v5-list-pro-*`;
- responsive safety layer per widget con griglie inline;
- media query per tablet/mobile;
- fallback mobile per griglie 2/3/4/5 colonne.

### `app/Http/Controllers/Admin/PageVisualEditorV5Controller.php`

Aggiornato il metodo `withV5PublicRuntimes()` per riconoscere widget Pro e iniettare automaticamente nel frontend pubblico:

```html
<link id="r4v5-widgets-pro-public-style" rel="stylesheet" href="/assets/editor-v5/runtime/widgets-pro.css?...">
<script id="r4v5-widgets-pro-public-runtime" src="/assets/editor-v5/runtime/widgets-pro.js?..." defer></script>
```

Trigger riconosciuti:

```text
r4v5-pro-
data-r4v5-faq-accordion
data-r4v5-count
```

Sono stati aggiunti metodi di pulizia per evitare duplicazioni runtime.

### `resources/views/admin/pages/editV5.blade.php`

La Blade carica già molti asset dell'Editor V5 e il file `sections-extra.js` è stato agganciato.

Punto da verificare domani:

- assicurarsi che carichi anche `landing-presets.js` prima di `core/editor.js`.

La riga necessaria è:

```blade
<script src="{{ asset('assets/admin/visual-editor-v5/widgets/landing-presets.js') }}?v={{ $editorV5AssetVersion }}"></script>
```

Deve stare tra:

```blade
<script src="{{ asset('assets/admin/visual-editor-v5/widgets/sections-extra.js') }}?v={{ $editorV5AssetVersion }}"></script>
```

e:

```blade
<script src="{{ asset('assets/admin/visual-editor-v5/core/editor.js') }}?v={{ $editorV5AssetVersion }}"></script>
```

---

## Stato test fatto dall'utente

### Funziona

L'utente ha confermato che:

- dopo la correzione di `static.js`, i widget sono tornati visibili;
- i nuovi widget compaiono;
- le sezioni Pro/Extra sembrano funzionare;
- il responsive sembra migliorato;
- la libreria generale è utilizzabile.

### Da verificare

- `Preset Landing → Landing completa R4Software` non era ancora visibile perché `landing-presets.js` non risultava caricato dalla Blade;
- le Liste Pro funzionano ma non danno ancora l'effetto desiderato;
- va fatto test frontend completo dopo salvataggio/publish;
- va verificata console browser per eventuali errori JS.

---

## Script creato localmente

È stato preparato uno script locale:

```text
update-editor-v5-pro.sh
```

Obiettivo:

- pull branch;
- verificare PHP 8.4.2 MAMP;
- inserire `landing-presets.js` nella Blade;
- pulire cache Laravel.

Lo script ha fallito sul pattern automatico perché la Blade non corrispondeva al formato previsto.

Patch alternativa proposta con Python:

```bash
python3 <<'PY'
from pathlib import Path

blade = Path("resources/views/admin/pages/editV5.blade.php")
content = blade.read_text()

if "landing-presets.js" in content:
    print("landing-presets.js è già presente. Nessuna modifica necessaria.")
    raise SystemExit(0)

needle = """<script src="{{ asset('assets/admin/visual-editor-v5/widgets/sections-extra.js') }}?v={{ $editorV5AssetVersion }}"></script>"""
insert = needle + """<script src="{{ asset('assets/admin/visual-editor-v5/widgets/landing-presets.js') }}?v={{ $editorV5AssetVersion }}"></script>"""

if needle not in content:
    print("ERRORE: non trovo sections-extra.js nel formato atteso.")
    for line in content.splitlines():
        if "sections-extra.js" in line:
            print(line)
    raise SystemExit(1)

content = content.replace(needle, insert, 1)
blade.write_text(content)
print("OK: landing-presets.js inserito dopo sections-extra.js")
PY
```

---

## Attenzione a `composer.lock`

Durante i test locali è risultato modificato:

```text
composer.lock
```

Nota importante:

- non committare `composer.lock` insieme ai widget, salvo verifica consapevole;
- probabilmente è cambiato per effetto di Composer/PHP locale;
- prima di commit fare sempre:

```bash
git status
```

Per committare solo la Blade, eventualmente:

```bash
git add resources/views/admin/pages/editV5.blade.php
git commit -m "Load Editor V5 landing presets"
git push origin feature/editor-v5-widget-library-pro
```

Non usare `git add .` in questa fase.

---

## Test consigliato domani

Dopo aggiornamento/pull:

```bash
cd /Users/mac/Sites/cms_r4software_demo

git fetch origin
git checkout feature/editor-v5-widget-library-pro
git pull origin feature/editor-v5-widget-library-pro

/Applications/MAMP/bin/php/php8.4.2/bin/php artisan view:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan optimize:clear
```

Aprire Editor V5 e fare hard refresh:

```text
CMD + SHIFT + R
```

Verificare categorie:

- Base;
- Layout;
- Marketing;
- Media;
- Contenuti;
- Sezioni Pro;
- Sezioni Pro Extra;
- Liste Pro;
- Preset Landing.

Testare in ordine:

1. `Hero Pro`;
2. `Servizi Pro`;
3. `FAQ Accordion`;
4. `Stats Pro counter`;
5. `Portfolio / Lavori Pro`;
6. `Contatto / Lead Pro`;
7. `Landing completa R4Software`.

Poi:

- salvare bozza;
- ricaricare editor;
- pubblicare;
- verificare frontend pubblico;
- verificare FAQ;
- verificare responsive mobile/tablet;
- verificare console browser.

---

## Prossimi step CTO consigliati

### 1. Stabilizzare caricamento asset

Obiettivo: evitare caricamenti sparsi nei widget e spostare la gestione asset in un loader chiaro.

Da fare:

- verificare `editV5.blade.php`;
- caricare `landing-presets.js` prima di `core/editor.js`;
- valutare un array asset dedicato nella Blade.

### 2. Pulire widget duplicati/deboli

Alcune Liste Pro non convincono ancora.

Da fare:

- tenerle nascoste o rimuoverle dalla categoria;
- rifare una `Lista Pro` unica con controlli migliori;
- evitare troppi preset simili.

### 3. Migliorare Inspector

Obiettivo: modificare senza codice:

- colori;
- testi CTA;
- immagini;
- numero card;
- stile lista;
- background;
- spaziature.

### 4. Ridurre CSS inline

Molti widget sono ancora molto inline.

Da fare:

- spostare progressivamente in `widgets-pro.css`;
- lasciare HTML più pulito;
- usare classi `r4v5-pro-*`.

### 5. Documentazione utente

Creare manuale:

```text
docs/editor-v5-widget-library-pro-user-guide.md
```

Contenuti:

- come usare ogni categoria;
- quali widget usare per landing;
- workflow consigliato;
- come sostituire immagini;
- come testare frontend.

---

## Stato finale sessione

La spike è promettente.

Editor V5 ora dispone di una libreria widget molto più ricca e vicina a un page builder professionale.

Prima di portare su `main`, serve ancora:

1. test completo locale;
2. verifica caricamento preset landing;
3. pulizia delle Liste Pro;
4. test frontend pubblico;
5. eventuale PR finale o merge controllato.
