# Editor V4 centralizzato - Note operative e rollback

## Stato attuale

EditorV4 è stato estratto in un pacchetto Laravel privato:

- Repository package: `cms-editor-v4-package`
- Package Composer: `r4software/cms-editor-v4-package`
- Repository GitHub privata: `git@github.com:manuelericci1969/cms-editor-v4-package.git`

Il CMS `cms_r4software` usa il package tramite Composer VCS privato.

## Repository coinvolte

### CMS

Percorso locale:

```bash
/Users/manuelericci/Sites/cms_r4software
```

Branch di test CMS:

```bash
feature/install-cms-editor-v4-package
```

### Package Editor V4

Percorso locale:

```bash
/Users/manuelericci/Sites/cms-editor-v4-package
```

Repository remota:

```bash
git@github.com:manuelericci1969/cms-editor-v4-package.git
```

## Versione package corrente in test

```text
0.1.3
```

La versione `0.1.3` introduce la view isolata dell'Editor V4: l'editor non estende più `admin.layout`, quindi non eredita più sidebar/topbar, asset plugin, script CRM o altri JS globali del CMS.

## Aggiornamento CMS locale dopo una modifica package

Dal CMS:

```bash
cd /Users/manuelericci/Sites/cms_r4software

composer update r4software/cms-editor-v4-package --ignore-platform-req=ext-imagick

php artisan vendor:publish --tag=cms-editor-v4-assets --force

php artisan optimize:clear
```

Verifica versione/commit installato:

```bash
composer show r4software/cms-editor-v4-package | grep -E "versions|source|dist"
```

## Test locale Editor V4

Aprire:

```text
/admin/pages/44/edit-v4
```

Eseguire refresh forzato browser:

```text
CMD + SHIFT + R
```

Test minimo:

1. doppio click su un testo;
2. scrivere `ciao come stai`;
3. verificare che non venga salvato/renderizzato come `iats emoc oaic`;
4. provare selezione testo, backspace, scrittura a metà frase;
5. salvare e ricaricare.

## Rollback rapido package nel CMS

Se la versione package crea problemi, dal CMS si può tornare al commit precedente del branch o ripristinare `composer.lock` dal commit precedente:

```bash
git status --short

git checkout -- composer.json composer.lock public/vendor/cms-editor-v4

composer install --ignore-platform-req=ext-imagick
php artisan optimize:clear
```

Oppure tornare al branch main del CMS:

```bash
git checkout main
composer install --ignore-platform-req=ext-imagick
php artisan optimize:clear
```

## Regola produzione

Non fare merge su `main` e non deployare in produzione finché:

- Editor V4 non supera il test testo/RTE;
- Composer nel CMS non punta a una versione package stabile/taggata;
- è stato verificato il rollback locale.
