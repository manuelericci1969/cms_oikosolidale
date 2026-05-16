# CRM - Pagamenti Stripe per rinnovo servizi

## Obiettivo

Questa funzionalità permette di generare link di pagamento Stripe per i servizi cliente in scadenza o scaduti.

Il link può essere:

- copiato manualmente;
- inviato via email;
- inviato via WhatsApp;
- aperto direttamente dal pannello admin.

La prima versione gestisce il pagamento di un singolo servizio per volta.

## Branch sviluppo

```text
feature/stripe-service-payments
```

## Dipendenza Composer

La funzionalità usa il pacchetto ufficiale Stripe PHP SDK:

```bash
composer require stripe/stripe-php
```

Se il pacchetto è già presente nel `composer.json`, in locale eseguire:

```bash
composer update
composer dump-autoload -o
```

## Variabili ambiente

Aggiungere nel file `.env`:

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=
STRIPE_CURRENCY=eur
STRIPE_PAYMENT_LINK_TTL_HOURS=23
```

Per la produzione usare le chiavi live:

```env
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CURRENCY=eur
STRIPE_PAYMENT_LINK_TTL_HOURS=23
```

Nota: Stripe Checkout richiede una scadenza sessione entro 24 ore. Il codice limita automaticamente il valore massimo a 23 ore.

Dopo aver modificato `.env`:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Database

La funzionalità crea la tabella:

```text
crm_service_payment_links
```

Migration:

```text
database/migrations/2026_04_29_100000_create_crm_service_payment_links_table.php
```

Eseguire:

```bash
php artisan migrate
```

## Percorso admin

Per un servizio esistente:

```text
/admin/crm/services/{service}/payment-links
```

Dalla pagina è possibile:

- creare un link Stripe Checkout;
- copiare il link;
- aprire il link;
- verificare manualmente lo stato da Stripe;
- inviare il link via email;
- inviare il link via WhatsApp.

## Dati usati per il pagamento

Il sistema usa:

- cliente collegato al servizio;
- email cliente, se presente;
- telefono/WhatsApp cliente, se presente;
- nome servizio o prodotto;
- data scadenza servizio;
- prezzo rinnovo lordo (`renew_price_gross`).

È possibile sovrascrivere importo e descrizione al momento della creazione del link.

## Stati link pagamento

Valori previsti:

```text
pending
paid
expired
cancelled
failed
```

## Aggiornamento stato pagamento

Il pagamento può diventare `paid` in tre modi:

1. il cliente completa Stripe Checkout e torna alla pagina `success`;
2. l'admin clicca il pulsante `Verifica stato Stripe` dalla tabella link;
3. Stripe invia il webhook `checkout.session.completed`.

Il metodo 1 e 2 consentono di testare in locale anche senza webhook.

Il webhook resta necessario in produzione per coprire il caso in cui il cliente paghi ma chiuda la pagina prima del redirect di ritorno.

## Rinnovo automatico servizio

Quando il link viene confermato come `paid`, il sistema rinnova automaticamente il servizio collegato.

Regole:

- se il servizio ha scadenza futura, rinnova partendo dalla scadenza attuale;
- se il servizio è già scaduto, rinnova partendo dalla data del pagamento;
- lo stato servizio viene riportato ad `active`;
- nel link pagamento vengono salvate vecchia e nuova scadenza nei metadata;
- il rinnovo è protetto da transazione e lock per evitare doppi rinnovi se arrivano sia redirect che webhook.

Periodicità:

```text
week  => +1 settimana
month => +1 mese
year  => +1 anno
vuoto => +1 anno
```

Attualmente la periodicità usa il campo esistente `renewal_vat_mode`, già usato dal form servizi come `Periodicità rinnovo`.

## Webhook Stripe

La V1 include il controller webhook:

```text
App\Modules\Crm\Http\Controllers\StripeWebhookController
```

Eventi gestiti:

```text
checkout.session.completed
checkout.session.expired
```

Rotta configurata:

```text
POST /crm/stripe/callback
```

In fase di test locale si può usare Stripe CLI:

```bash
stripe listen --forward-to http://127.0.0.1:8000/crm/stripe/callback
```

Il comando restituisce il valore `whsec_...` da inserire in `STRIPE_WEBHOOK_SECRET`.

## Test locale consigliato

1. Entrare nel branch:

```bash
git checkout feature/stripe-service-payments
git pull origin feature/stripe-service-payments
```

2. Installare/aggiornare le dipendenze:

```bash
composer update
composer dump-autoload -o
```

3. Configurare `.env` con chiavi test Stripe.

4. Eseguire:

```bash
php artisan optimize:clear
php artisan migrate
php artisan route:list | grep payment
```

5. Aprire un servizio esistente:

```text
/admin/crm/services/{service}/payment-links
```

6. Creare link pagamento.

7. Inviare link via WhatsApp oppure aprire direttamente Stripe Checkout.

8. Pagare con carta test Stripe:

```text
4242 4242 4242 4242
```

9. Attendere il ritorno alla pagina di successo.

10. Rientrare nella pagina admin e verificare:

- stato `Pagato`;
- nuova scadenza servizio;
- colonna `Rinnovo` con vecchia e nuova data.

11. Se lo stato non si aggiorna, cliccare `Verifica stato Stripe`.

## Note operative

Prima di portare in produzione verificare:

- importi corretti;
- descrizione servizio corretta;
- email cliente corretta;
- telefono cliente corretto;
- link Stripe funzionante;
- stato `paid` aggiornato dopo pagamento;
- nuova scadenza servizio corretta;
- webhook funzionante;
- nessun errore nei log Laravel.

## Produzione

Quando il test locale è positivo:

1. merge su `main`;
2. deploy produzione;
3. `composer install --no-dev --optimize-autoloader`;
4. `php artisan migrate --force`;
5. configurazione chiavi Stripe live;
6. configurazione webhook live dalla dashboard Stripe;
7. test con pagamento reale minimo.

## Evoluzioni future

Possibili estensioni:

- campo dedicato `renewal_period` al posto del campo legacy `renewal_vat_mode`;
- pagamento multiplo di più servizi in un solo checkout;
- generazione ricevuta/fattura;
- riconciliazione con contabilità;
- report incassi Stripe;
- reminder automatici con link pagamento già incluso.
