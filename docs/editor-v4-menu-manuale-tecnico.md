# Manuale tecnico - Editor V4 Menu Modulare

**Progetto:** CMS/CRM R4Software  
**Branch di lavoro:** `feature/editor-v4-modular-menu`  
**Area:** Editor V4 / menu laterale sinistro  
**Obiettivo:** separare HTML, CSS e JavaScript di ogni voce del menu per facilitare manutenzione, debug e sviluppo futuro.

---

## 1. Branch e flusso di lavoro

Il refactor e le correzioni sono stati applicati sul branch:

```bash
git checkout feature/editor-v4-modular-menu
```

Il branch e stato creato da `main`, non da `page_builder/pb_v4`, perche `page_builder/pb_v4` risultava molto indietro rispetto al codice corrente.

Flusso consigliato:

```bash
cd /Users/manuelericci/Sites/cms_r4software

git fetch origin
git checkout feature/editor-v4-modular-menu
git pull origin feature/editor-v4-modular-menu

php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

Con MAMP/PHP 8.3.9:

```bash
/Applications/MAMP/bin/php/php8.3.9/bin/php artisan optimize:clear
```

---

## 2. File principale dell'Editor V4

Il file principale della pagina editor e:

```text
resources/views/admin/pages/editV4.blade.php
```

Qui vengono caricati:

- i campi nascosti `visual_html`, `visual_css`, `visual_json`;
- la struttura GrapesJS;
- i template Blade del menu modulare;
- i CSS del menu;
- i JS modulari del menu.

Quando devi aggiungere o rimuovere una voce di menu, questo e il file da controllare per verificare che il partial Blade e lo script JS siano inclusi.

---

## 3. Struttura HTML modulare

Ogni voce del menu ha un proprio partial Blade dentro:

```text
resources/views/admin/pages/editor-v4/menu/
```

Struttura attuale:

```text
resources/views/admin/pages/editor-v4/menu/
├── page-settings/html.blade.php
├── layout/html.blade.php
├── widgets/html.blade.php
├── elements/html.blade.php
├── spacing/html.blade.php
├── typography/html.blade.php
├── background/html.blade.php
├── border/html.blade.php
├── effects/html.blade.php
└── advanced/html.blade.php
```

Ogni file contiene un tag `<template>` con un ID univoco, per esempio:

```blade
<template id="r4v4-menu-template-layout">
    ...
</template>
```

Il JavaScript legge questi template e li monta nel menu sinistro.

---

## 4. Struttura JavaScript modulare

I file JavaScript del menu si trovano in:

```text
public/assets/admin/visual-editor-v4/menu/
```

Struttura attuale:

```text
public/assets/admin/visual-editor-v4/menu/
├── registry.js
├── helpers.js
├── boot.js
├── page-settings.js
├── layout.js
├── widgets.js
├── elements.js
├── spacing.js
├── typography.js
├── background.js
├── border.js
├── effects.js
└── advanced.js
```

### `registry.js`

Registra i moduli del menu. Intervieni qui solo se devi cambiare la logica generale di registrazione.

### `helpers.js`

Contiene funzioni comuni:

- lettura configurazione `R4VisualEditorV4`;
- selezione elementi DOM;
- lettura e scrittura valori input;
- sincronizzazione `visual_html`, `visual_css`, `visual_json` prima del submit;
- flash message interni al pannello.

Intervieni qui quando una funzione serve a piu moduli.

### `boot.js`

Monta il menu sinistro, crea i tab, distribuisce le categorie GrapesJS e mostra i pannelli selection-only quando viene selezionato un elemento nel canvas.

Intervieni qui quando vuoi modificare:

- ordine globale di montaggio;
- comportamento dei tab;
- distribuzione Widget / Elementi;
- visibilita dei pannelli quando un elemento e selezionato.

---

## 5. Struttura CSS modulare

I CSS del menu sono in:

```text
public/assets/admin/visual-editor-v4/menu/
```

File attuali:

```text
public/assets/admin/visual-editor-v4/menu/
├── core.css
├── layout.css
└── spacing.css
```

Per ora il CSS principale e `core.css`, che corregge larghezza menu, tab e sovrapposizione dei campi.

---

## 6. Come allargare o stringere il menu sinistro

Apri:

```text
public/assets/admin/visual-editor-v4/menu/core.css
```

Trova:

```css
:root {
    --r4v4-left-menu-width: 340px;
}
```

Per allargare:

```css
:root {
    --r4v4-left-menu-width: 380px;
}
```

Per stringere:

```css
:root {
    --r4v4-left-menu-width: 320px;
}
```

Questa variabile viene usata da:

```css
.r4v4-workspace {
    grid-template-columns: var(--r4v4-left-menu-width) minmax(0, 1fr) 0 !important;
}

.r4v4-sidebar-left {
    width: var(--r4v4-left-menu-width) !important;
    min-width: var(--r4v4-left-menu-width) !important;
}
```

Dopo la modifica:

```bash
php artisan view:clear
```

Poi ricarica il browser con hard refresh:

```text
CMD + SHIFT + R
```

---

## 7. Come correggere sovrapposizioni di label, input, textarea, select

La correzione principale e in:

```text
public/assets/admin/visual-editor-v4/menu/core.css
```

Regole chiave:

```css
.r4v4-sidebar-left .r4v4-page-card label,
.r4v4-sidebar-left .r4v4-left-switch {
    position: relative;
    display: block !important;
    width: 100%;
    box-sizing: border-box;
    clear: both;
}
```

E:

```css
.r4v4-sidebar-left .r4v4-page-card input[type="text"],
.r4v4-sidebar-left .r4v4-page-card input[type="number"],
.r4v4-sidebar-left .r4v4-page-card input[type="datetime-local"],
.r4v4-sidebar-left .r4v4-page-card textarea,
.r4v4-sidebar-left .r4v4-page-card select {
    display: block !important;
    width: 100% !important;
    max-width: 100%;
    min-height: 34px;
    box-sizing: border-box;
    clear: both;
    margin: 5px 0 0 !important;
    line-height: 1.35 !important;
}
```

Se vedi ancora sovrapposizione, controlla prima:

1. cache browser;
2. `php artisan view:clear`;
3. ordine dei CSS in `editV4.blade.php`;
4. che `core.css` venga caricato dopo `sidebar-compact.css`.

---

# 8. Manuale per voce di menu

## 8.1 Pagina

### HTML

```text
resources/views/admin/pages/editor-v4/menu/page-settings/html.blade.php
```

Contiene titolo pagina, slug, estratto, data pubblicazione, stato, homepage, SEO, visibilita frontend e pulsanti Applica, Salva, Media.

### JS

```text
public/assets/admin/visual-editor-v4/menu/page-settings.js
```

Responsabilita:

- legge i dati iniziali da `window.R4VisualEditorV4.pageSettings`;
- popola i campi del pannello;
- aggiorna i campi hidden del form;
- sincronizza i dati prima del salvataggio;
- espone `window.R4V4LeftPageSettings`.

### Dove intervenire

- Per aggiungere un nuovo campo pagina: modifica `page-settings/html.blade.php`.
- Per salvare il campo nel form: modifica `page-settings.js`.
- Per accettare il campo lato backend: verifica `PageVisualEditorV4Controller.php`.

---

## 8.2 Layout

### HTML

```text
resources/views/admin/pages/editor-v4/menu/layout/html.blade.php
```

Contiene larghezza pagina, gutter e spazio superiore.

### CSS

```text
public/assets/admin/visual-editor-v4/menu/layout.css
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/layout.js
```

### Dove intervenire

- Per aggiungere un campo layout: `layout/html.blade.php`.
- Per idratare o applicare il valore: `layout.js`.
- Per stile visuale specifico: `layout.css`.

---

## 8.3 Widget

### HTML

```text
resources/views/admin/pages/editor-v4/menu/widgets/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/widgets.js
```

### Dove intervenire

- Per cambiare il testo iniziale: `widgets/html.blade.php`.
- Per cambiare quali categorie entrano nei widget: `boot.js`, array `WIDGET_CATEGORIES`.

---

## 8.4 Elementi

### HTML

```text
resources/views/admin/pages/editor-v4/menu/elements/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/elements.js
```

### Dove intervenire

- Per cambiare testo o markup: `elements/html.blade.php`.
- Per cambiare quali categorie entrano negli elementi: `boot.js`, array `ELEMENT_CATEGORIES`.

---

## 8.5 Spaziatura

### HTML

```text
resources/views/admin/pages/editor-v4/menu/spacing/html.blade.php
```

### CSS

```text
public/assets/admin/visual-editor-v4/menu/spacing.css
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/spacing.js
```

### Dove intervenire

- Per aggiungere input margin/padding: `spacing/html.blade.php`.
- Per applicare stile al componente GrapesJS selezionato: `spacing.js`.
- Per impaginazione controlli: `spacing.css`.

---

## 8.6 Tipografia

### HTML

```text
resources/views/admin/pages/editor-v4/menu/typography/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/typography.js
```

### Dove intervenire

- Controlli HTML: `typography/html.blade.php`.
- Logica applicazione stile: `typography.js`.
- CSS dedicato eventuale: creare `typography.css` e includerlo in `editV4.blade.php`.

---

## 8.7 Sfondo

### HTML

```text
resources/views/admin/pages/editor-v4/menu/background/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/background.js
```

### Dove intervenire

- Markup controlli: `background/html.blade.php`.
- Logica su elemento selezionato: `background.js`.
- Eventuali stili dedicati: creare `background.css`.

---

## 8.8 Bordi

### HTML

```text
resources/views/admin/pages/editor-v4/menu/border/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/border.js
```

### Dove intervenire

- Markup controlli: `border/html.blade.php`.
- Applicazione proprieta CSS: `border.js`.
- Stile pannello: creare `border.css`.

---

## 8.9 Effetti

### HTML

```text
resources/views/admin/pages/editor-v4/menu/effects/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/effects.js
```

### Dove intervenire

- Markup controlli: `effects/html.blade.php`.
- Logica animazioni: `effects.js`.
- CSS dedicato: creare `effects.css`.

---

## 8.10 Avanzate

### HTML

```text
resources/views/admin/pages/editor-v4/menu/advanced/html.blade.php
```

### JS

```text
public/assets/admin/visual-editor-v4/menu/advanced.js
```

### Dove intervenire

- Markup controlli: `advanced/html.blade.php`.
- Scrittura attributi/classi sul componente selezionato: `advanced.js`.
- CSS dedicato: creare `advanced.css`.

---

## 9. Come aggiungere una nuova voce di menu

Esempio: aggiungere `Responsive`.

### 1. Crea il partial Blade

```text
resources/views/admin/pages/editor-v4/menu/responsive/html.blade.php
```

```blade
<template id="r4v4-menu-template-responsive">
    <div class="r4v4-page-card r4v4-menu-responsive">
        <div class="r4v4-page-card-title">Responsive</div>
        <div class="r4v4-left-panel-hint">Gestione visibilita e stile per desktop, tablet e mobile.</div>
    </div>
</template>
```

### 2. Crea il JS

```text
public/assets/admin/visual-editor-v4/menu/responsive.js
```

```js
(function () {
    'use strict';

    const h = window.R4V4MenuHelpers;

    window.R4V4SidebarMenu.register({
        key: 'responsive',
        label: 'Responsive',
        order: 110,
        templateId: 'r4v4-menu-template-responsive',
        selectionOnly: true,
        mount(panel) {
            panel.innerHTML = h.templateHtml(this.templateId);
        }
    });
})();
```

### 3. Includi tutto in `editV4.blade.php`

Aggiungi:

```blade
@include('admin.pages.editor-v4.menu.responsive.html')
```

E:

```blade
<script src="{{ asset('assets/admin/visual-editor-v4/menu/responsive.js') }}"></script>
```

Se hai CSS dedicato:

```blade
<link rel="stylesheet" href="{{ asset('assets/admin/visual-editor-v4/menu/responsive.css') }}">
```

---

## 10. Debug rapido

### Menu non si vede

Controlla in console browser:

```js
window.R4V4SidebarMenu
window.R4V4MenuHelpers
window.r4VisualEditorV4Instance
```

### I tab non funzionano

Controlla che siano caricati in questo ordine:

```text
registry.js
helpers.js
page-settings.js
layout.js
widgets.js
elements.js
spacing.js
typography.js
background.js
border.js
effects.js
advanced.js
boot.js
```

`boot.js` deve essere caricato dopo tutti i moduli.

### I componenti finiscono nel tab sbagliato

Apri:

```text
public/assets/admin/visual-editor-v4/menu/boot.js
```

Modifica:

```js
const ELEMENT_CATEGORIES = ['layout', 'base', 'media'];
const WIDGET_CATEGORIES = ['marketing', 'interattivi', 'crewlive', 'pro', 'widget'];
```

### Le modifiche non si vedono

Esegui:

```bash
php artisan view:clear
php artisan optimize:clear
```

Poi hard refresh browser.

---

## 11. Checklist prima del merge su main

- [ ] Editor V4 si apre senza errori console.
- [ ] Menu sinistro visibile e largo abbastanza.
- [ ] Nessuna sovrapposizione tra label, input, textarea, select.
- [ ] Tab Pagina funzionante.
- [ ] Tab Layout funzionante.
- [ ] Widget ed Elementi mostrano i blocchi GrapesJS.
- [ ] Selezionando un elemento appaiono i tab selection-only.
- [ ] Salvataggio bozza funzionante.
- [ ] Pubblicazione funzionante.
- [ ] Riapertura pagina con contenuto caricato.
- [ ] Frontend pubblico invariato.

---

## 12. Merge finale

Solo dopo test locale positivo:

```bash
git checkout main
git pull origin main
git merge feature/editor-v4-modular-menu
git push origin main
```

In produzione:

```bash
git pull origin main
php artisan optimize:clear
php artisan view:clear
```
