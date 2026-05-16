# CMS / CRM R4Software

Repository principale del CMS/CRM sviluppato da R4Software.

Il progetto è basato su Laravel e include funzionalità CMS, CRM, gestione contenuti, preventivi, contratti, clienti, pagamenti, moduli e strumenti operativi per installazioni personalizzate.

## Stato progetto

Il repository contiene una piattaforma in evoluzione continua. Ogni funzionalità stabile deve essere accompagnata da una nota tecnica o da una guida d'uso nella cartella `docs/`.

## Documentazione progetto

La documentazione operativa viene mantenuta nella cartella `docs/`.

### CRM

- [CRM - Piano pagamenti nei preventivi](docs/crm-preventivi-piano-pagamenti.md)

Funzionalità già documentata:

- scelta della forma di pagamento nel preventivo;
- testo libero / standard;
- acconto alla firma;
- rate multiple con scadenza e importo;
- visualizzazione nella scheda preventivo;
- stampa nel PDF preventivo;
- retrocompatibilità con i preventivi esistenti.

### Documentazione da completare

Le prossime sezioni da documentare sono:

- CRM - Contratti, firma e download contratto firmato;
- CRM - Pagamenti clienti acquisiti e rinnovi annuali;
- Installazione locale del progetto;
- Deploy in produzione;
- Gestione moduli e licensing;
- Page Builder / editor visuale;
- Configurazione email, SMTP e allegati PDF.

## Branch principali

- `main`: ramo stabile di produzione.
- `feature/*`: rami di sviluppo per nuove funzionalità.
- `page_builder/*`: rami dedicati al page builder/editor visuale.

Prima di portare una funzionalità su `main`, testarla in locale e verificare:

```bash
git status
php artisan optimize:clear
php artisan migrate
npm run build
```

## Installazione locale - promemoria rapido

Esempio flusso base per ambiente locale:

```bash
git clone git@github.com:manuelericci1969/cms_r4software.git
cd cms_r4software
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Configurare `.env` con database, URL locale, mailer e servizi necessari.

## Deploy produzione - promemoria rapido

Dopo il merge su `main`, in produzione:

```bash
git checkout main
git pull origin main

composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
npm install
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verifiche rapide:

```bash
php artisan migrate:status
php artisan route:list
```

## Storage pubblico

Comandi utili per diagnosticare e correggere il link pubblico storage (`public/storage -> storage/app/public`).

```bash
# Diagnosi: mostra path reali e stato di public/storage
php artisan storage:diag

# Fix rapido: ricrea public/storage -> storage/app/public
# Rinomina automaticamente eventuali file/cartelle che bloccano il link
php artisan storage:relink

# Se l'hosting blocca i symlink: copia i file invece del link
# Workaround. Preferire Follow Symlinks nelle impostazioni hosting.
php artisan storage:relink --copy

# Pulizia cache consigliata dopo interventi su storage/config
php artisan optimize:clear
```

## Convenzioni operative

### Nuove funzionalità

Per ogni nuova funzionalità:

1. creare un branch dedicato;
2. sviluppare e testare in locale;
3. aggiungere o aggiornare la documentazione in `docs/`;
4. aprire una PR verso `main`;
5. fare merge solo dopo test positivo.

### Database

Le modifiche allo schema devono sempre passare da migration Laravel.

Quando possibile, usare migration retrocompatibili e sicure per ambienti già aggiornati.

### PDF e documenti

Le viste PDF devono essere testate con DomPDF prima del merge in produzione.

Verificare sempre:

- layout A4;
- logo aziendale;
- dati cliente;
- totali;
- condizioni di pagamento;
- compatibilità con record già esistenti.

## Stack tecnico

- Laravel
- PHP
- MySQL/MariaDB
- Blade
- Vite
- Bootstrap
- DomPDF
- Moduli CRM/CMS custom

## Note

Questo README sostituisce il README standard Laravel e diventa il punto di ingresso tecnico-operativo del progetto.
