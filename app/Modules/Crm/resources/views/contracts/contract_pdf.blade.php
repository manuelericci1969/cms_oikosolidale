{{-- modules/Crm/resources/views/contracts/pdf.blade.php --}}
    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Contratto servizi - Preventivo {{ $quote->number }}</title>
    <style>
        @page {
            margin: 80px 40px 90px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .header {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 60px;
        }

        .header-left { float: left; }
        .header-right {
            float: right;
            text-align: right;
            font-size: 10px;
        }

        .footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #555;
            padding-top: 4px;
        }

        .footer .line {
            text-align: center;
        }

        h1, h2, h3, h4 {
            margin: 0 0 6px 0;
            padding: 0;
        }

        h1 { font-size: 18px; }
        h2 { font-size: 14px; margin-top: 12px; }
        h3 { font-size: 12px; margin-top: 8px; }

        p {
            margin: 0 0 5px 0;
            line-height: 1.4;
        }

        .small { font-size: 9px; }

        .section-title {
            margin-top: 12px;
            margin-bottom: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
            color: #555;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .meta-table td {
            vertical-align: top;
            font-size: 11px;
            padding: 3px 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border-bottom: 1px solid #eee;
            padding: 5px 4px;
        }

        .items-table th {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
        }

        .text-right { text-align: right; }
        .text-left  { text-align: left; }

        .totals-table {
            width: 45%;
            margin-left: 55%;
            margin-top: 8px;
            border-collapse: collapse;
            font-size: 10px;
        }

        .totals-table th,
        .totals-table td {
            padding: 3px 4px;
        }

        .totals-table tr:nth-child(odd) {
            background: #f8f8f8;
        }

        .totals-table tr:last-child th,
        .totals-table tr:last-child td {
            font-weight: bold;
        }

        .signature-block {
            margin-top: 20px;
            font-size: 10px;
        }

        .signature-block .col {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .signature-line {
            margin-top: 30px;
            border-top: 1px solid #000;
            width: 80%;
        }

        ol, ul {
            margin: 4px 0 4px 18px;
            padding: 0;
        }

        li { margin-bottom: 2px; }

        .page-break {
            page-break-before: always;
        }

        .note-legal {
            font-size: 8px;
            color: #777;
            margin-top: 5px;
        }
    </style>
</head>
<body>
@php
    $supplierName = $company['name'] ?? 'Il Fornitore';
@endphp

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        @if(!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" style="height: 48px;">
        @elseif(!empty($company['name']))
            <h2>{{ $company['name'] }}</h2>
        @endif
    </div>
    <div class="header-right">
        @if(!empty($company['name']))
            <strong>{{ $company['name'] }}</strong><br>
        @endif
        @if(!empty($company['address']))
            {{ $company['address'] }}<br>
        @endif
        @if(!empty($company['zip']) || !empty($company['city']))
            {{ $company['zip'] ?? '' }} {{ $company['city'] ?? '' }}
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
            Tel: {{ $company['phone'] }}
        @endif
    </div>
</div>

{{-- FOOTER --}}
<div class="footer">
    <div class="line">
        {{ $supplierName }}
        @if(!empty($company['vat']))
            · P.IVA {{ $company['vat'] }}
        @endif
        @if(!empty($company['address']) || !empty($company['city']) || !empty($company['zip']))
            · {{ $company['address'] ?? '' }},
            {{ $company['zip'] ?? '' }} {{ $company['city'] ?? '' }}
        @endif
        @if(!empty($company['email']))
            · {{ $company['email'] }}
        @endif
        @if(!empty($company['phone']))
            · {{ $company['phone'] }}
        @endif
    </div>
</div>

<main>
    {{-- TITOLO CONTRATTO --}}
    <h1>Contratto di fornitura servizi informatici</h1>
    <p class="small">
        Riferimento al preventivo n. <strong>{{ $quote->number }}</strong>
        del {{ $quote->date?->format('d/m/Y') ?? '-' }}.
    </p>
    <p class="small">
        Data contratto:
        <strong>{{ $quote->accepted_at?->format('d/m/Y') ?? $quote->date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong>
    </p>

    {{-- DATI DELLE PARTI --}}
    <div class="section-title">Dati delle parti</div>
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <strong>Fornitore</strong><br>
                {{ $supplierName }}<br>
                @if(!empty($company['address']))
                    {{ $company['address'] }}<br>
                @endif
                @if(!empty($company['zip']) || !empty($company['city']))
                    {{ $company['zip'] ?? '' }} {{ $company['city'] ?? '' }}
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
                @else
                    -
                @endif
            </td>
        </tr>
    </table>

    {{-- OGGETTO DEL CONTRATTO --}}
    <div class="section-title">Oggetto</div>
    <p>
        Con il presente contratto il Fornitore si impegna a fornire al Cliente i servizi
        e/o prodotti informatici indicati nel preventivo di cui sopra, alle condizioni
        economiche e tecniche ivi riportate e secondo i termini di seguito indicati.
    </p>

    {{-- ELENCO PRODOTTI / SERVIZI --}}
    <div class="section-title">Elenco dei servizi / prodotti forniti</div>
    <table class="items-table">
        <thead>
        <tr>
            <th class="text-left">Descrizione</th>
            <th class="text-right" style="width: 10%;">Qtà</th>
            <th class="text-right" style="width: 15%;">Prezzo unit.</th>
            <th class="text-right" style="width: 10%;">Sconto</th>
            <th class="text-right" style="width: 10%;">IVA</th>
            <th class="text-right" style="width: 15%;">Totale riga</th>
        </tr>
        </thead>
        <tbody>
        @foreach($quote->items as $item)
            @php
                $lineBase     = $item->quantity * $item->unit_price;
                $lineDiscount = $lineBase * ($item->discount_percent / 100);
                $lineNet      = $lineBase - $lineDiscount;
                $lineTax      = $lineNet * ($item->tax_rate / 100);
                $lineTotal    = $lineNet + $lineTax;
            @endphp
            <tr>
                <td>
                    <strong>{{ $item->description }}</strong><br>
                    <span class="small" style="color:#777;">
                        {{ number_format($item->quantity, 2, ',', '.') }} {{ $item->unit }}
                        x {{ number_format($item->unit_price, 2, ',', '.') }} €
                    </span>
                </td>
                <td class="text-right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                <td class="text-right">
                    @if($item->discount_percent > 0)
                        {{ number_format($item->discount_percent, 2, ',', '.') }} %
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->tax_rate, 2, ',', '.') }} %</td>
                <td class="text-right">{{ number_format($lineTotal, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <th class="text-left">Imponibile</th>
            <td class="text-right">{{ number_format($quote->subtotal, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <th class="text-left">Sconti</th>
            <td class="text-right">- {{ number_format($quote->discount_total, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <th class="text-left">Imposte</th>
            <td class="text-right">{{ number_format($quote->tax_total, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <th class="text-left">Totale contratto</th>
            <td class="text-right">{{ number_format($quote->total, 2, ',', '.') }} €</td>
        </tr>
    </table>

    {{-- MODALITÀ DI PAGAMENTO --}}
    <div class="section-title">Corrispettivo e modalità di pagamento</div>
    @if(!empty($quote->payment_terms))
        <p>{!! nl2br(e($quote->payment_terms)) !!}</p>
    @else
        <p>
            Il corrispettivo complessivo per i servizi oggetto del presente contratto è pari a
            <strong>{{ number_format($quote->total, 2, ',', '.') }} €</strong>, oltre IVA se dovuta.
            Le modalità e le scadenze di pagamento saranno definite tra le parti e riportate in fattura.
        </p>
    @endif

    @if(!empty($company['payment_coordinates']))
        <p class="small" style="margin-top:4px;">
            <strong>Coordinate per il bonifico:</strong><br>
            {!! nl2br(e($company['payment_coordinates'])) !!}
        </p>
    @elseif(!empty($company['iban']) || !empty($company['bank']))
        <p class="small" style="margin-top:4px;">
            <strong>Coordinate per il bonifico:</strong><br>
            @if(!empty($company['bank']))
                Banca: {{ $company['bank'] }}<br>
            @endif
            @if(!empty($company['iban']))
                IBAN: {{ $company['iban'] }}<br>
            @endif
            @if(!empty($company['bic']))
                BIC/SWIFT: {{ $company['bic'] }}
            @endif
        </p>
    @endif

    <p class="small" style="margin-top:4px;">
        Ai fini del presente contratto, la data di perfezionamento coincide con la data di
        ricezione del primo acconto / pagamento, salvo diverso accordo scritto tra le parti.
    </p>

    {{-- RICHIAMO AI TERMINI --}}
    <p class="small" style="margin-top:8px;">
        Al presente contratto si applicano i Termini di vendita e l'Informativa sul trattamento
        dei dati personali riportati nelle pagine seguenti, che il Cliente dichiara di aver letto,
        compreso e accettato.
    </p>

    {{-- FIRME (FRONTE) --}}
    <div class="signature-block">
        <div class="col">
            <p>Luogo e data: ____________________________</p>
            <p>Firma del Cliente</p>
            <div class="signature-line"></div>
        </div>
        <div class="col" style="text-align:right;">
            <p>Per il Fornitore</p>
            <div class="signature-line" style="float:right;"></div>
        </div>
    </div>

    <div class="note-legal">
        Testo contrattuale generato come fac-simile. Si raccomanda la verifica da parte
        del proprio consulente legale/fiscale prima dell’utilizzo.
    </div>

    {{-- ========================================================= --}}
    {{-- PAGINA 2: TERMINI DI VENDITA                             --}}
    {{-- ========================================================= --}}
    <div class="page-break"></div>

    <h2>Termini di vendita per fornitura di servizi informatici</h2>
    <p>
        Il Fornitore {{ $supplierName }} eroga, tra gli altri, i seguenti servizi:
    </p>
    <ul>
        <li>alloggiamento (hosting) e servizi accessori per la pubblicazione di siti e applicazioni web;</li>
        <li>registrazione e mantenimento di nomi a dominio;</li>
        <li>realizzazione di software, applicazioni web e mobile;</li>
        <li>realizzazione di grafiche e contenuti multimediali online/offline;</li>
        <li>consulenza in materia informatica e sicurezza informatica;</li>
        <li>progettazione e realizzazione di reti informatiche;</li>
        <li>configurazione e gestione di firewall e sistemi di sicurezza di rete.</li>
    </ul>

    <h3>1. Rapporto finanziario</h3>
    <ol>
        <li>Il Cliente si impegna a corrispondere il corrispettivo dovuto per i servizi acquistati, come indicato nel presente contratto e/o nel preventivo di riferimento.</li>
        <li>Salvo diversa pattuizione scritta, i servizi hanno durata annuale e non è prevista disdetta anticipata con rimborso dei corrispettivi già fatturati e/o pagati.</li>
        <li>In nessun caso il Fornitore sarà tenuto a rimborsare, in tutto o in parte, gli importi corrisposti dal Cliente per i servizi già erogati o comunque attivati.</li>
        <li>Regolare fattura verrà emessa sulla base dei dati fiscali forniti dal Cliente al momento dell’ordine o successivamente aggiornati.</li>
        <li>I termini di pagamento sono essenziali: in caso di ritardo, il Fornitore potrà sospendere i servizi (compresi hosting, manutenzione e aggiornamenti) fino all’integrale saldo, fatto salvo il diritto al recupero del credito e agli interessi di legge.</li>
        <li>La proprietà del codice sorgente degli applicativi sviluppati rimane al Fornitore, salvo diverso patto scritto. Al Cliente è riconosciuto un diritto d’uso non esclusivo per le finalità concordate.</li>
        <li>Script, moduli, componenti di CMS, sistemi di gestione immagini e testi online realizzati dal Fornitore restano di proprietà dello stesso, anche in caso di cessazione o disdetta del servizio, salvo diversa pattuizione scritta.</li>
    </ol>

    <h3>2. Prodotti e servizi offerti</h3>
    <ol>
        <li>Il Fornitore non effettua un controllo editoriale preventivo sui contenuti pubblicati dal Cliente, ma si riserva il diritto di richiedere la rimozione o modifiche di contenuti contrari a legge, al buon costume o alle presenti condizioni.</li>
        <li>Salvo diversa indicazione scritta, il Fornitore non presta garanzie ulteriori rispetto a quelle di legge e non risponde di dichiarazioni o promesse eventualmente formulate da terzi rivenditori o partner.</li>
        <li>Il Fornitore garantisce l’uso diligente delle infrastrutture e il miglior rendimento tecnicamente possibile, ma non risponde di malfunzionamenti, interruzioni o disservizi riconducibili a terze parti (provider, carrier, registri, ecc.).</li>
        <li>Il Fornitore si riserva il diritto di decidere se ospitare o meno, sui propri server, il materiale fornito dal Cliente.</li>
        <li>In caso di uso improprio o contrario alle presenti condizioni, il Fornitore potrà sospendere o revocare i servizi previa comunicazione via e-mail.</li>
        <li>In caso di revoca per inadempimento grave del Cliente, nessun importo potrà essere richiesto a titolo di rimborso.</li>
        <li>Il Cliente prende atto che alcune porzioni di codice (es. logiche proprietarie, script server-side) possono essere protette e non accessibili.</li>
    </ol>

    <h3>3. Maggiore età</h3>
    <p>
        Il Cliente dichiara di essere maggiorenne e di avere la capacità di sottoscrivere il presente contratto.
    </p>

    <h3>4. Netiquette e uso corretto dei servizi</h3>
    <ol>
        <li>Il Cliente si impegna a rispettare le regole di buona condotta in rete (netiquette) e la normativa vigente.</li>
        <li>È vietato l’invio di comunicazioni commerciali non richieste (spam), nonché la promozione o gestione di attività di gioco d’azzardo non autorizzate.</li>
        <li>È vietata la pubblicazione di contenuti illeciti, diffamatori, osceni, pornografici, razzisti, discriminatori, o comunque contrari alle leggi vigenti.</li>
        <li>È vietato l’utilizzo dei servizi per tentare accessi non autorizzati a sistemi informatici, per attività di cracking, phishing, distribuzione di malware o altre attività illecite.</li>
    </ol>

    <h3>5. Durata e recesso</h3>
    <ol>
        <li>Il presente contratto ha durata pari a quella del servizio indicata nel preventivo/ordine (di norma 12 mesi) e si rinnova tacitamente per uguale periodo salvo disdetta scritta almeno 30 giorni prima della scadenza.</li>
        <li>Il recesso anticipato del Cliente non dà diritto ad alcun rimborso delle somme già corrisposte per servizi attivati o in corso di erogazione, salvo diversi accordi scritti.</li>
    </ol>

    <h3>6. Limitazione di responsabilità</h3>
    <ol>
        <li>Il Cliente utilizza i servizi sotto la propria esclusiva responsabilità.</li>
        <li>Il Fornitore non risponde di danni diretti o indiretti, lucro cessante, perdita di dati o di opportunità commerciale derivanti dall’uso o dall’impossibilità di usare i servizi, salvo i limiti inderogabili di legge.</li>
        <li>In ogni caso l’eventuale responsabilità complessiva del Fornitore verso il Cliente non potrà eccedere l’importo complessivamente corrisposto dal Cliente per il servizio negli ultimi 12 mesi.</li>
    </ol>

    <h3>7. Scopi legali</h3>
    <p>
        Il Cliente è autorizzato a utilizzare i servizi esclusivamente per scopi leciti. È vietata la
        trasmissione o diffusione di materiale in violazione di diritti di terzi (copyright, marchi,
        segreti industriali, privacy, ecc.) o contrario alla normativa vigente.
    </p>

    <h3>8. Manleva</h3>
    <p>
        Il Cliente si impegna a manlevare e tenere indenne il Fornitore da qualsiasi pretesa,
        richiesta di risarcimento o sanzione derivante da un uso illecito o non conforme dei
        servizi, o dalla violazione di diritti di terzi tramite i contenuti pubblicati o le attività
        svolte tramite i sistemi del Fornitore.
    </p>

    <h3>9. Divieto di rivendita non autorizzata</h3>
    <p>
        Salvo accordo scritto, il Cliente non può rivendere a terzi gli spazi, gli account e-mail,
        le licenze software o altri servizi acquistati dal Fornitore.
    </p>

    <h3>10. Modifiche ai servizi</h3>
    <p>
        Il Fornitore potrà aggiornare o modificare le caratteristiche tecniche dei servizi offerti,
        purché tali modifiche non comportino un peggioramento essenziale delle prestazioni
        rispetto a quanto contrattualmente previsto, informando il Cliente in caso di variazioni
        rilevanti.
    </p>

    <h3>11. Foro competente</h3>
    <p>
        Per ogni controversia relativa all’interpretazione o esecuzione del presente contratto,
        sarà competente in via esclusiva il Foro di
        <strong>Olbia-Tempio</strong>, salvo diverse previsioni inderogabili di legge.
    </p>

    <p class="small" style="margin-top:10px;">
        Letti, compresi e specificamente approvati ai sensi degli artt. 1341 e 1342 c.c.
        i punti: 1 (Rapporto finanziario), 2 (Prodotti e servizi offerti), 4 (Netiquette e uso
        corretto), 5 (Durata e recesso), 6 (Limitazione di responsabilità), 7 (Scopi legali),
        8 (Manleva), 9 (Divieto di rivendita), 11 (Foro competente).
    </p>

    <div class="signature-block">
        <div class="col">
            <p>Luogo: ______________________</p>
            <p>Data:  ___ / ___ / ______</p>
            <p>Firma del Cliente per specifica approvazione</p>
            <div class="signature-line"></div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- PAGINA 3: PRIVACY / GDPR                                 --}}
    {{-- ========================================================= --}}
    <div class="page-break"></div>

    <h2>Informativa sul trattamento dei dati personali (Privacy / GDPR)</h2>

    <p class="small">
        Ai sensi del Regolamento (UE) 2016/679 (“GDPR”) e del D.Lgs. 196/2003 così come
        modificato dal D.Lgs. 101/2018.
    </p>

    <h3>Titolare del trattamento</h3>
    <p>
        Titolare del trattamento è {{ $supplierName }},
        con sede in {{ $company['address'] ?? '' }},
        {{ $company['zip'] ?? '' }} {{ $company['city'] ?? '' }} ({{ $company['province'] ?? '' }}),
        {{ $company['country'] ?? 'IT' }}.
    </p>

    <h3>Finalità del trattamento</h3>
    <p>I dati personali del Cliente sono trattati per le seguenti finalità:</p>
    <ul>
        <li>adempimento degli obblighi contrattuali e pre-contrattuali;</li>
        <li>adempimento di obblighi di legge, contabili e fiscali;</li>
        <li>gestione tecnica e amministrativa dei servizi forniti;</li>
        <li>eventuali comunicazioni informative sui servizi già forniti (soft spam), nei limiti di legge.</li>
    </ul>

    <h3>Base giuridica</h3>
    <p>
        La base giuridica del trattamento è l’esecuzione del contratto di cui il Cliente è parte,
        l’adempimento di obblighi di legge e, ove richiesto, il consenso dell’interessato.
    </p>

    <h3>Modalità del trattamento e conservazione</h3>
    <p>
        Il trattamento avviene con strumenti manuali e informatici, nel rispetto dei principi di
        liceità, correttezza, trasparenza, minimizzazione e sicurezza previsti dalla normativa
        vigente. I dati sono conservati per il tempo necessario all’esecuzione del contratto,
        all’assolvimento degli obblighi di legge e, comunque, non oltre i termini di prescrizione
        ordinaria dei diritti derivanti dal rapporto contrattuale.
    </p>

    <h3>Comunicazione e destinatari dei dati</h3>
    <p>
        I dati potranno essere comunicati a:
    </p>
    <ul>
        <li>consulenti fiscali, legali e amministrativi nei limiti necessari all’adempimento degli obblighi di legge;</li>
        <li>fornitori tecnici e soggetti terzi che erogano servizi funzionali (es. provider, data center), nominati ove necessario responsabili del trattamento;</li>
        <li>autorità competenti, su richiesta o per obbligo di legge.</li>
    </ul>

    <p>
        I dati non sono oggetto di diffusione indiscriminata.
    </p>

    <h3>Diritti dell’interessato</h3>
    <p>
        In qualità di interessato, il Cliente può esercitare in qualsiasi momento i diritti previsti
        dagli artt. 15-22 GDPR, tra cui:
    </p>
    <ul>
        <li>ottenere conferma dell’esistenza o meno di dati personali che lo riguardano;</li>
        <li>accedere ai dati, ottenerne la rettifica, l’aggiornamento o la cancellazione nei casi previsti;</li>
        <li>ottenere la limitazione del trattamento o opporsi, per motivi legittimi, al trattamento;</li>
        <li>ricevere i dati in formato strutturato, di uso comune e leggibile da dispositivo automatico (portabilità);</li>
        <li>proporre reclamo all’Autorità Garante per la Protezione dei Dati Personali.</li>
    </ul>

    <h3>Conferimento dei dati</h3>
    <p>
        Il conferimento dei dati contrattuali e fiscali è obbligatorio ai fini della conclusione ed
        esecuzione del contratto; l’eventuale rifiuto di fornire tali dati comporta l’impossibilità
        di dare corso al rapporto contrattuale.
    </p>

    <div class="signature-block">
        <div class="col">
            <p>Luogo: ______________________</p>
            <p>Data:  ___ / ___ / ______</p>
            <p>Firma del Cliente per presa visione dell'informativa privacy</p>
            <div class="signature-line"></div>
        </div>
    </div>

</main>
</body>
</html>
