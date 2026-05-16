# Branch sospesi e archiviati

Questo documento tiene traccia dei branch sperimentali o sospesi che non devono essere mergiati su `main` senza una nuova analisi tecnica.

## feature/licensing-modules

**Stato:** sospeso / archiviato.

**Branch archivio:**

```text
archive/licensing-modules-suspended-2026-04-29
```

**Motivo:**

Il branch `feature/licensing-modules` contiene una prima bozza del sistema di licensing/moduli, ma risulta divergente rispetto a `main` e non è più allineato allo stato attuale del CMS/CRM.

Il branch includeva una prima implementazione di:

- moduli prodotto;
- piani commerciali;
- installazioni licenza;
- override moduli per installazione;
- middleware `EnsureModuleEnabled`;
- gestione ruoli/moduli;
- seeders e migration dedicate;
- schermata admin per gestione moduli/licenze.

**Decisione tecnica:**

Non aggiornare, non fare rebase e non mergiare su `main`.

Quando verrà ripreso lo sviluppo del licensing, ripartire da `main` con un nuovo branch pulito, ad esempio:

```text
feature/modules-licensing-v2
```

La vecchia implementazione resta disponibile solo come riferimento storico nel branch archivio.

**Nota CTO:**

È preferibile riprogettare il licensing da zero, tenendo conto dello stato attuale del CRM, dei preventivi, dei contratti, dei pagamenti e della futura gestione installazioni cliente/dominio.
