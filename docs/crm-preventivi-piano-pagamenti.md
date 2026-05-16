# CRM - Piano pagamenti nei preventivi

## Funzionalità

Il modulo preventivi del CRM supporta due modalità di gestione delle condizioni di pagamento:

1. **Testo libero / standard**
   - Permette di indicare manualmente le condizioni di pagamento nel campo testuale dedicato.
   - È retrocompatibile con i preventivi esistenti.

2. **Acconto alla firma + rate**
   - Permette di creare un piano di pagamento strutturato.
   - Il piano può includere un acconto alla firma e una o più rate successive.
   - Ogni voce può avere descrizione, scadenza e importo.

## Dove si configura

Percorso amministrativo:

```text
Admin > CRM > Preventivi > Nuovo preventivo / Modifica preventivo
```

Nel form del preventivo è presente la sezione **Forma di pagamento**.

Da questa sezione è possibile scegliere:

- `Testo libero / standard`
- `Acconto alla firma + rate`

## Acconto alla firma

Quando viene scelta la modalità **Acconto alla firma + rate**, è possibile abilitare l'opzione **Prevedi acconto alla firma**.

Campi disponibili:

- **Descrizione acconto**: valore predefinito `Acconto alla firma`.
- **Scadenza acconto**: opzionale; se lasciata vuota verrà indicato `Alla firma`.
- **Importo acconto**: importo dell'acconto richiesto al cliente.

## Rate

Il sistema permette di aggiungere una o più rate.

Per ogni rata sono disponibili:

- **Descrizione**: esempio `Rata 1`, `Rata 2`, `Saldo finale`.
- **Scadenza**: data di pagamento prevista.
- **Importo**: importo della rata.

È possibile aggiungere o rimuovere righe rata direttamente dal form.

## Visualizzazione nel preventivo

Quando il preventivo usa la modalità strutturata, nella scheda del preventivo viene mostrata la tabella **Piano di pagamento**.

La tabella riporta:

- voce di pagamento;
- scadenza;
- importo;
- totale del piano pagamenti.

Il campo **Condizioni / note di pagamento** resta disponibile e può essere usato per aggiungere note testuali, ad esempio coordinate operative, modalità bonifico, causale o accordi specifici.

## PDF preventivo

Il piano pagamenti viene riportato anche nel PDF del preventivo.

Nel PDF vengono mostrati:

- acconto alla firma, se previsto;
- rate inserite;
- scadenze;
- importi;
- totale piano pagamenti;
- eventuali condizioni testuali aggiuntive.

## Retrocompatibilità

I preventivi creati prima dell'introduzione del piano pagamenti strutturato continuano a funzionare normalmente.

Se un preventivo non ha un piano pagamenti strutturato, il sistema usa il campo testuale `payment_terms` come in precedenza.

## Note tecniche

Campi database aggiunti alla tabella `crm_quotes`:

```text
payment_type
payment_schedule
```

Valori previsti per `payment_type`:

```text
free_text
structured
```

`payment_schedule` salva il piano pagamenti in formato JSON.

Esempio struttura dati:

```json
{
  "deposit": {
    "enabled": true,
    "label": "Acconto alla firma",
    "due_date": null,
    "amount": 500
  },
  "installments": [
    {
      "label": "Rata 1",
      "due_date": "2026-05-30",
      "amount": 750
    },
    {
      "label": "Rata 2",
      "due_date": "2026-06-30",
      "amount": 750
    }
  ]
}
```

## Test consigliati dopo aggiornamento

Dopo il deploy verificare:

1. Creazione preventivo con modalità testo libero.
2. Creazione preventivo con acconto alla firma.
3. Creazione preventivo con acconto e più rate.
4. Visualizzazione della scheda preventivo.
5. Download PDF del preventivo.
6. Invio preventivo via email con PDF allegato.
7. Apertura di un preventivo creato prima dell'aggiornamento.

## Data introduzione

Funzionalità introdotta con il branch:

```text
feature/quote-payment-schedule
```

Poi mergiata su `main` dopo test locale e verifica in produzione.
