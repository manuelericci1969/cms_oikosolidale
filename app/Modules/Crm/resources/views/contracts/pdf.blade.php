{{-- modules/Crm/resources/views/contracts/pdf.blade.php --}}
    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Contratto - Preventivo {{ $quote->number }}</title>
    <style>
        @page {
            margin: 30mm 20mm 25mm 20mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h1, h2, h3 {
            margin: 0 0 8px 0;
            padding: 0;
        }

        h1 {
            font-size: 18px;
        }

        h2 {
            font-size: 14px;
            margin-top: 18px;
        }

        h3 {
            font-size: 12px;
            margin-top: 10px;
        }

        p {
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        ul {
            margin: 0 0 6px 18px;
            padding: 0;
        }

        .small {
            font-size: 9px;
        }

        .mb-1  { margin-bottom: 4px; }
        .mb-2  { margin-bottom: 8px; }
        .mb-3  { margin-bottom: 12px; }
        .mb-4  { margin-bottom: 16px; }
        .mt-2  { margin-top: 8px; }
        .mt-3  { margin-top: 12px; }
        .mt-4  { margin-top: 16px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 2px 4px;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .sig-line {
            margin-top: 25px;
            border-top: 1px solid #000;
            width: 80%;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

@php
    use App\Models\Setting;

    // Testi da Settings
    $termsFromSettings   = Setting::get('crm.contract_terms', '');
    $privacyFromSettings = Setting::get('crm.contract_privacy', '');

    // Dati bancari letti direttamente da company.*
    $bankName = Setting::get('company.bank');
    $iban     = Setting::get('company.iban');
    $bic      = Setting::get('company.bic');
    $pec      = Setting::get('company.pec');
    $sdi      = Setting::get('company.sdi');

    // Verifico se c'è almeno un dato bancario non vuoto
    $hasBankData = trim(
        (string) (($bankName ?? '') . ($iban ?? '') . ($bic ?? '') . ($pec ?? '') . ($sdi ?? ''))
    ) !== '';
@endphp

{{-- ====================== PAGINA 1: INTESTAZIONE CONTRATTO ====================== --}}

<h1 class="mb-3">Contratto per fornitura servizi – Preventivo {{ $quote->number }}</h1>

<p class="mb-2">
    Tra:
</p>

<table class="meta-table mb-2">
    <tr>
        <td style="width: 50%;">
            <strong>Fornitore</strong><br>
            <strong>{{ $company['name'] ?? config('app.name') }}</strong><br>
            @if(!empty($company['address']))
                {{ $company['address'] }}<br>
            @endif
            @if(!empty($company['zip']) || !empty($company['city']))
                {{ $company['zip'] }} {{ $company['city'] }}
                @if(!empty($company['province'])) ({{ $company['province'] }}) @endif
                <br>
            @endif
            @if(!empty($company['vat']))
                P.IVA: {{ $company['vat'] }}<br>
            @endif
            @if(!empty($company['email']))
                Email: {{ $company['email'] }}<br>
            @endif
            @if(!empty($company['phone']))
                Tel: {{ $company['phone'] }}<br>
            @endif
        </td>
        <td style="width: 50%;">
            <strong>Cliente</strong><br>
            @if($quote->customer)
                <strong>{{ $quote->customer->name }}</strong><br>
                @if($quote->customer->billing_address)
                    {{ $quote->customer->billing_address }}<br>
                @endif
                @if($quote->customer->billing_zip || $quote->customer->billing_city)
                    {{ $quote->customer->billing_zip }} {{ $quote->customer->billing_city }}<br>
                @endif
                @if($quote->customer->billing_province)
                    ({{ $quote->customer->billing_province }})<br>
                @endif
                @if($quote->customer->vat_number)
                    P.IVA: {{ $quote->customer->vat_number }}<br>
                @endif
                @if($quote->customer->tax_code)
                    C.F.: {{ $quote->customer->tax_code }}<br>
                @endif
                @if($quote->customer->email)
                    Email: {{ $quote->customer->email }}<br>
                @endif
            @endif
        </td>
    </tr>
</table>

<p class="mb-2">
    di seguito congiuntamente denominate anche le “Parti”.
</p>

<p class="mb-2">
    Il presente contratto disciplina la fornitura dei servizi oggetto del
    <strong>preventivo n. {{ $quote->number }}</strong> del
    <strong>{{ $quote->date?->format('d/m/Y') }}</strong>,
    che si intende qui integralmente richiamato ed allegato.
</p>

<h2 class="mt-3">Corrispettivo e condizioni principali</h2>

<p class="mb-2">
    L’importo complessivo dell’offerta è pari a
    <strong>{{ number_format($quote->total ?? 0, 2, ',', '.') }} €</strong>
    (come dettagliato nel preventivo richiamato).
</p>

@if(!empty($quote->payment_terms))
    <p class="mb-1"><strong>Condizioni di pagamento concordate:</strong></p>
    <p class="mb-2">
        {!! nl2br(e($quote->payment_terms)) !!}
    </p>
@endif

{{-- =================== COORDINATE BANCARIE =================== --}}
@if($hasBankData)
    <h3 class="mt-2">Coordinate bancarie per il pagamento</h3>
    <p class="mb-3">
        @if(!empty($bankName))
            Banca: {{ $bankName }}<br>
        @endif
        @if(!empty($iban))
            IBAN: {{ $iban }}<br>
        @endif
        @if(!empty($bic))
            BIC / SWIFT: {{ $bic }}<br>
        @endif
        @if(!empty($pec))
            PEC: {{ $pec }}<br>
        @endif
        @if(!empty($sdi))
            Codice SDI: {{ $sdi }}<br>
        @endif
    </p>
@endif
{{-- =========================================================== --}}

@if(!empty($quote->valid_until))
    <p class="mb-2">
        La validità economica dell’offerta è fissata fino al
        <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>,
        salvo eventuali proroghe o rinnovi concordati per iscritto tra le Parti.
    </p>
@endif

<p class="mb-3">
    L’accettazione del preventivo da parte del Cliente, anche tramite conferma
    telematica con codice OTP e successiva firma del presente documento, vale
    quale piena accettazione delle condizioni economiche e delle
    <strong>condizioni generali di contratto</strong> riportate nelle sezioni
    che seguono.
</p>

<p class="small mb-3">
    Il presente contratto ha natura di accordo quadro riferito ai servizi
    specificati nel preventivo richiamato e costituisce, insieme al preventivo
    stesso, l’intera regolamentazione dei rapporti tra le Parti in relazione
    alle prestazioni ivi descritte.
</p>

<div class="page-break"></div>

{{-- ====================== TERMINI DI VENDITA ====================== --}}

<h2>Termini di vendita per fornitura servizi informatici</h2>

@if(trim((string)$termsFromSettings) !== '')
    {!! nl2br(e($termsFromSettings)) !!}
@else
    {{-- ========== TESTO DI DEFAULT, MODIFICABILE ========== --}}

    <h3>1. Rapporto finanziario</h3>
    <p>
        Il Cliente si impegna a corrispondere al Fornitore i corrispettivi
        indicati nel preventivo e/o nel modulo d’ordine sottoscritto.
        Salvo diverso accordo scritto, i pagamenti sono effettuati secondo le
        modalità e le scadenze ivi riportate.
    </p>
    <p>
        In caso di mancato o ritardato pagamento, anche parziale, il Fornitore
        ha facoltà di sospendere l’erogazione dei servizi, fermo restando il
        diritto al pagamento integrale di quanto dovuto e al risarcimento
        dell’eventuale maggior danno.
    </p>
    <p>
        Le somme già corrisposte non sono rimborsabili, salvo i casi espressamente
        previsti dalla legge o quelli concordati tra le Parti per iscritto.
    </p>

    <h3>2. Prodotti e servizi offerti</h3>
    <p>
        Il Fornitore eroga servizi informatici e/o di consulenza, sviluppo software,
        assistenza tecnica e servizi accessori, come descritti nel preventivo.
        Salvo diversa pattuizione scritta, eventuali attività ulteriori rispetto
        a quelle espressamente indicate saranno oggetto di nuovo preventivo e di
        separato consenso del Cliente.
    </p>
    <p>
        Il Fornitore si impegna a prestare i servizi con la diligenza professionale
        richiesta dalla natura dell’incarico, senza però garantire risultati
        diversi da quelli ragionevolmente conseguibili e dipendenti anche dalla
        collaborazione del Cliente.
    </p>

    <h3>3. Maggiore età e legittimazione</h3>
    <p>
        Il Cliente dichiara di avere la piena capacità di agire e di essere
        legittimato a sottoscrivere il contratto. Qualora agisca in nome e per
        conto di una persona giuridica, il soggetto firmatario garantisce di
        disporre dei necessari poteri di rappresentanza.
    </p>

    <h3>4. Netiquette e corretto utilizzo dei servizi</h3>
    <p>
        Il Cliente si impegna a utilizzare i servizi nel rispetto delle regole
        di buona fede, della netiquette e della normativa vigente. È vietato
        utilizzare i servizi per:
    </p>
    <ul>
        <li>invio di comunicazioni indesiderate o massive (spam);</li>
        <li>pubblicare o trasmettere contenuti illeciti, diffamatori, osceni,
            violenti o comunque contrari all’ordine pubblico e al buon costume;</li>
        <li>porre in essere attività che possano danneggiare la sicurezza o
            l’integrità di reti, sistemi o dati di terzi.</li>
    </ul>
    <p>
        In caso di violazione, il Fornitore potrà sospendere i servizi, anche
        senza preavviso, fermo restando il diritto alla risoluzione del contratto
        e al risarcimento dell’eventuale danno.
    </p>

    <h3>5. Durata del rapporto e fine del rapporto</h3>
    <p>
        La durata del rapporto è quella indicata nel preventivo o, in mancanza,
        coincide con il tempo necessario all’esecuzione delle attività oggetto
        del contratto.
    </p>
    <p>
        Alla scadenza o al completamento dei servizi, il rapporto si intende
        concluso. Eventuali proroghe, rinnovi o contratti di manutenzione
        continuativa dovranno essere espressamente concordati per iscritto.
    </p>

    <h3>6. Responsabilità limitata del Fornitore</h3>
    <p>
        Il Fornitore non è responsabile per interruzioni o malfunzionamenti dei
        servizi dovuti a cause di forza maggiore, a fatti di terzi, a guasti delle
        reti di comunicazione elettronica, a errata utilizzazione delle soluzioni
        da parte del Cliente o a mancata cooperazione del Cliente stesso.
    </p>
    <p>
        In ogni caso, la responsabilità del Fornitore, per qualunque titolo,
        non potrà eccedere l’importo complessivamente corrisposto dal Cliente
        per il servizio oggetto della contestazione, con esclusione dei danni
        indiretti, consequenziali o da lucro cessante.
    </p>

    <h3>7. Scopi legali e contenuti</h3>
    <p>
        Il Cliente è l’unico responsabile dei contenuti, dei dati e delle
        informazioni trattati o pubblicati tramite i servizi forniti. Egli
        garantisce che tali contenuti non violano diritti di terzi
        (diritti d’autore, marchi, brevetti, segreti commerciali, dati personali)
        e non costituiscono violazione di norme imperative.
    </p>
    <p>
        In caso di contestazioni da parte di terzi, il Cliente terrà manlevato
        e indenne il Fornitore da ogni pretesa, costo o spesa che dovesse
        derivarne.
    </p>

    <h3>8. Identificazione e veridicità dei dati</h3>
    <p>
        Il Cliente garantisce la veridicità e l’esattezza dei dati comunicati
        al Fornitore in sede di richiesta d’offerta, ordine e durante l’esecuzione
        del contratto. Eventuali variazioni (sede, riferimenti, recapiti, ecc.)
        dovranno essere prontamente comunicate.
    </p>

    <h3>9. Divieto di rivendita dei servizi</h3>
    <p>
        Salvo espressa autorizzazione scritta del Fornitore, è vietato rivendere,
        concedere in sublicenza o mettere altrimenti a disposizione di terzi
        i servizi oggetto del presente contratto.
    </p>

    <h3>10. Modifiche dei servizi</h3>
    <p>
        Il Fornitore potrà apportare ai servizi modifiche tecniche, organizzative
        o migliorative che non pregiudichino in modo sostanziale le funzionalità
        concordate, informandone il Cliente con congruo preavviso quando le
        modifiche siano rilevanti.
    </p>
    <p>
        In caso di modifiche sostanziali che incidano in modo significativo sulle
        prestazioni pattuite, il Cliente avrà facoltà di recedere dal contratto,
        senza penali, dandone comunicazione scritta entro il termine indicato
        nell’avviso di modifica.
    </p>

    <h3>11. Diritto di recesso del Cliente (se applicabile)</h3>
    <p>
        Ove il Cliente rivesta la qualifica di consumatore ai sensi del Codice
        del Consumo, potrà esercitare il diritto di recesso entro i termini e con
        le modalità previste dalla normativa vigente, fatto salvo quanto stabilito
        per le prestazioni già completamente eseguite su sua esplicita richiesta.
    </p>

    <h3>12. Foro competente</h3>
    <p>
        Per ogni controversia relativa all’interpretazione, esecuzione o
        validità del presente contratto sarà competente in via esclusiva
        il Foro del luogo in cui il Fornitore ha la propria sede legale,
        salvo che il Cliente rivesta la qualifica di consumatore e sia
        diversamente previsto dalla legge.
    </p>
@endif

<div class="page-break"></div>

{{-- ====================== PRIVACY / GDPR ====================== --}}

<h2>Informativa sul trattamento dei dati personali (Privacy – GDPR)</h2>

@if(trim((string)$privacyFromSettings) !== '')
    {!! nl2br(e($privacyFromSettings)) !!}
@else
    {{-- ========== TESTO DI DEFAULT, MODIFICABILE ========== --}}
    <p>
        Ai sensi del Regolamento (UE) 2016/679 (“GDPR”) e della normativa
        nazionale vigente in materia di protezione dei dati personali,
        il Fornitore, in qualità di Titolare del trattamento, informa il Cliente
        che i dati personali raccolti in fase di richiesta di offerta, stipula ed
        esecuzione del presente contratto saranno trattati per le seguenti finalità:
    </p>
    <ul>
        <li>gestione delle richieste di preventivo e delle trattative precontrattuali;</li>
        <li>adempimento degli obblighi contrattuali e delle attività operative connesse;</li>
        <li>adempimento di obblighi di legge, contabili, fiscali e di natura amministrativa;</li>
        <li>eventuale tutela dei diritti del Fornitore in sede giudiziaria o stragiudiziale.</li>
    </ul>
    <p>
        La base giuridica del trattamento è la necessità di eseguire il contratto
        e di adempiere gli obblighi di legge cui è soggetto il Fornitore.
    </p>
    <p>
        I dati potranno essere comunicati a soggetti terzi che agiscono in qualità
        di responsabili del trattamento (ad es. consulenti fiscali e legali,
        fornitori di servizi IT, istituti di credito) oppure di autonomi titolari
        (ad es. amministrazioni finanziarie e altri enti pubblici nei limiti di
        legge). I dati non saranno diffusi.
    </p>
    <p>
        I dati personali saranno conservati per il tempo necessario all’esecuzione
        del contratto e, successivamente, per il periodo richiesto dalla normativa
        civilistica e fiscale (di regola almeno 10 anni dalla cessazione del
        rapporto).
    </p>
    <p>
        Il Cliente potrà esercitare in qualsiasi momento i diritti previsti dagli
        artt. 15–22 del GDPR (accesso, rettifica, cancellazione, limitazione,
        portabilità, opposizione) inoltrando apposita richiesta al Fornitore
        ai recapiti indicati in questo documento. Resta inoltre salvo il diritto
        di proporre reclamo all’Autorità Garante per la protezione dei dati
        personali.
    </p>
    <p class="small">
        Il presente testo ha natura esemplificativa e dovrà essere eventualmente
        adattato alla specifica organizzazione del Titolare e alle indicazioni
        del proprio consulente legale o privacy.
    </p>
@endif

{{-- ====================== FIRME ====================== --}}

<h2 class="mt-4">Accettazione del contratto</h2>

<p class="mb-2">
    Il Cliente dichiara di aver letto e compreso il contenuto del presente
    contratto, di aver visionato il preventivo richiamato e di accettare
    espressamente le clausole tutte, con particolare riguardo alle condizioni
    economiche, alle limitazioni di responsabilità, alle modalità di recesso e
    al foro competente.
</p>

<p class="mb-3">
    Luogo e data: ________________________________
</p>

<table class="signature-table">
    <tr>
        <td>
            <p><strong>Per il Fornitore</strong></p>
            <p>{{ $company['name'] ?? '' }}</p>
            <div class="sig-line"></div>
            <p class="small">Firma</p>
        </td>
        <td>
            <p><strong>Per il Cliente</strong></p>
            <p>{{ $quote->customer->name ?? '' }}</p>
            <div class="sig-line"></div>
            <p class="small">Firma</p>
        </td>
    </tr>
</table>

</body>
</html>
