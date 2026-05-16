@extends('layouts.app')

@section('title', 'Informativa Privacy')
@section('meta_description', 'Informativa sul trattamento dei dati personali ai sensi del Regolamento (UE) 2016/679 (GDPR).')

@section('content')
    @php
        use App\Models\Setting;

        $companyName     = Setting::get('company.name', 'R4Software');
        $companyAddress  = Setting::get('company.address');
        $companyCity     = Setting::get('company.city');
        $companyZip      = Setting::get('company.zip');
        $companyProvince = Setting::get('company.province');
        $companyVat      = Setting::get('company.vat');
        $companyEmail    = Setting::get('company.email');
        $companyPhone    = Setting::get('company.phone');
        $companyPec      = Setting::get('company.pec');
    @endphp

    <div class="container py-5">
        <h1 class="mb-4 h2">Informativa sul trattamento dei dati personali</h1>
        <p class="text-muted small mb-4">
            Ai sensi del Regolamento (UE) 2016/679 (“GDPR”) e della normativa nazionale vigente in materia di protezione dei dati personali.
        </p>

        <h2 class="h4 mt-4">1. Titolare del trattamento</h2>
        <p>
            Il sito web (di seguito, il <strong>“Sito”</strong>) è gestito da
            <strong>{{ $companyName }}</strong> (di seguito, il <strong>“Titolare”</strong>).
        </p>
        <p class="small text-muted">
            @if($companyAddress || $companyCity || $companyZip || $companyProvince)
                Sede:
                @if($companyAddress)
                    {{ $companyAddress }}
                @endif
                @if($companyZip || $companyCity)
                    – {{ $companyZip }} {{ $companyCity }}
                @endif
                @if($companyProvince)
                    ({{ $companyProvince }})
                @endif
                <br>
            @endif

            @if($companyVat)
                P.IVA {{ $companyVat }}<br>
            @endif

            @if($companyPhone)
                Tel: {{ $companyPhone }}
            @endif

            @if($companyEmail)
                @if($companyPhone) – @endif
                Email: <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>
            @endif
            <br>

            {{--@if($companyPec)
                PEC: <a href="mailto:{{ $companyPec }}">{{ $companyPec }}</a>
            @endif--}}
        </p>

        <h2 class="h4 mt-4">2. Tipologie di dati trattati</h2>
        <p>
            Tramite il Sito possono essere trattati, a titolo esemplificativo:
        </p>
        <ul>
            <li>
                <strong>Dati di navigazione</strong><br>
                Informazioni raccolte automaticamente durante la navigazione (es. indirizzo IP, data e ora di accesso,
                pagine visitate, user agent del browser, log di sistema). Tali dati sono necessari per la fruizione del Sito
                e vengono trattati anche per finalità di sicurezza informatica e manutenzione.
            </li>
            <li>
                <strong>Dati forniti volontariamente dall’utente</strong><br>
                Dati identificativi e di contatto (nome, cognome, indirizzo e-mail, eventuale numero di telefono,
                azienda di appartenenza, contenuto del messaggio) trasmessi tramite form di contatto,
                richieste di informazioni, richieste di preventivo, registrazione ad aree riservate o servizi.
            </li>
            <li>
                <strong>Dati relativi all’utilizzo del Sito</strong><br>
                Dati raccolti tramite cookie e strumenti analoghi (si veda la
                <a href="{{ route('policy.cookie') }}">Cookie Policy</a> per maggiori dettagli).
            </li>
        </ul>
        <p>
            Di norma il Titolare non tratta tramite il Sito categorie particolari di dati
            (es. dati sanitari, dati relativi a opinioni politiche, appartenenza sindacale, ecc.).
            Qualora tali dati fossero forniti dall’utente in maniera autonoma, il Titolare si riserva di cancellarli,
            se non strettamente necessari alle finalità dichiarate.
        </p>

        <h2 class="h4 mt-4">3. Finalità e base giuridica del trattamento</h2>

        <h3 class="h5 mt-3">3.1 Navigazione sul Sito e funzionamento tecnico</h3>
        <p>
            I dati di navigazione sono trattati al fine di:
        </p>
        <ul>
            <li>consentire il corretto funzionamento tecnico del Sito;</li>
            <li>garantire la sicurezza delle infrastrutture (prevenzione e rilevazione di abusi e/o anomalie);</li>
            <li>ottenere informazioni statistiche anonime sull’uso del Sito.</li>
        </ul>
        <p>
            <strong>Base giuridica:</strong> esecuzione di misure precontrattuali o contrattuali (art. 6.1.b GDPR)
            e legittimo interesse del Titolare alla sicurezza e al corretto funzionamento del Sito (art. 6.1.f GDPR).
        </p>

        <h3 class="h5 mt-3">3.2 Gestione delle richieste inviate tramite il Sito</h3>
        <p>
            I dati forniti volontariamente dagli utenti tramite form di contatto, richiesta di informazioni o preventivo
            sono trattati per:
        </p>
        <ul>
            <li>prendere in carico e rispondere alle richieste dell’utente;</li>
            <li>fornire assistenza e supporto tecnico/commerciale;</li>
            <li>eventualmente predisporre offerte e preventivi.</li>
        </ul>
        <p>
            <strong>Base giuridica:</strong> esecuzione di misure precontrattuali o contrattuali richieste dall’interessato
            (art. 6.1.b GDPR).
        </p>

        <h3 class="h5 mt-3">3.3 Adempimenti legali e difesa in giudizio</h3>
        <p>
            I dati possono essere trattati per adempiere ad obblighi previsti da leggi, regolamenti, normative nazionali
            e/o comunitarie, nonché per l’eventuale tutela dei diritti del Titolare in sede giudiziaria o stragiudiziale.
        </p>
        <p>
            <strong>Base giuridica:</strong> adempimento di obblighi legali (art. 6.1.c GDPR) e legittimo interesse
            del Titolare alla tutela dei propri diritti (art. 6.1.f GDPR).
        </p>

        <h3 class="h5 mt-3">3.4 Attività di marketing e comunicazioni commerciali (se presenti)</h3>
        <p>
            Solo previo consenso espresso dell’utente, i dati di contatto potranno essere utilizzati per l’invio di
            comunicazioni informative e promozionali relative ai prodotti/servizi del Titolare (es. newsletter,
            aggiornamenti su novità, eventi o offerte).
        </p>
        <p>
            <strong>Base giuridica:</strong> consenso dell’interessato (art. 6.1.a GDPR), liberamente prestato e
            revocabile in qualsiasi momento.
        </p>

        <h3 class="h5 mt-3">3.5 Analisi statistiche e miglioramento dei servizi</h3>
        <p>
            Dati aggregati e, ove possibile, anonimizzati possono essere utilizzati per finalità di analisi statistica
            sull’utilizzo del Sito, per migliorare i contenuti e i servizi offerti.
        </p>
        <p>
            <strong>Base giuridica:</strong> legittimo interesse del Titolare (art. 6.1.f GDPR) oppure, se richiesto dalla
            normativa e in presenza di strumenti di terza parte, consenso dell’interessato tramite banner cookie (art. 6.1.a GDPR).
        </p>

        <h2 class="h4 mt-4">4. Modalità del trattamento</h2>
        <p>
            Il trattamento dei dati personali avviene con strumenti cartacei, informatici e telematici,
            secondo logiche strettamente correlate alle finalità indicate e in modo da garantire la sicurezza
            e la riservatezza dei dati stessi, nel rispetto di quanto previsto dall’art. 32 GDPR.
        </p>
        <p>
            Sono adottate misure tecniche e organizzative adeguate a prevenire la perdita dei dati, usi illeciti o non corretti,
            accessi non autorizzati o trattamenti non conformi alle finalità della raccolta.
        </p>

        <h2 class="h4 mt-4">5. Periodo di conservazione dei dati</h2>
        <p>
            I dati vengono conservati per un periodo di tempo proporzionato alle finalità del trattamento e, comunque, non
            superiore a quanto necessario per adempiere agli obblighi legali e contrattuali o per la tutela dei legittimi interessi
            del Titolare. A titolo indicativo:
        </p>
        <ul>
            <li>
                <strong>Dati di navigazione:</strong> normalmente conservati per brevi periodi,
                salvo eventuali esigenze di accertamento di responsabilità in caso di reati informatici;
            </li>
            <li>
                <strong>Dati forniti tramite form di contatto/preventivo:</strong> conservati per il tempo strettamente
                necessario a gestire la richiesta e, se il rapporto prosegue, per tutta la durata del rapporto contrattuale
                e per un ulteriore periodo in conformità agli obblighi di legge (es. civilistici/fiscali);
            </li>
            <li>
                <strong>Dati per finalità di marketing:</strong> fino alla revoca del consenso o all’esercizio
                del diritto di opposizione, e comunque per periodi proporzionati alle finalità perseguite.
            </li>
        </ul>

        <h2 class="h4 mt-4">6. Destinatari dei dati</h2>
        <p>
            I dati personali possono essere comunicati a:
        </p>
        <ul>
            <li>collaboratori e dipendenti del Titolare, in qualità di soggetti autorizzati al trattamento;</li>
            <li>fornitori di servizi IT e cloud, consulenti tecnici, professionisti (es. consulenti legali, fiscali, ecc.),
                nei limiti necessari alle finalità indicate, nominati ove previsto come responsabili del trattamento ai sensi dell’art. 28 GDPR;</li>
            <li>società controllate/collegate o partner, se coinvolti nell’erogazione dei servizi richiesti dall’utente;</li>
            <li>autorità pubbliche e organismi di controllo, ove previsto da obblighi di legge o richieste legittime.</li>
        </ul>
        <p>
            I dati non sono oggetto di diffusione indiscriminata.
        </p>

        <h2 class="h4 mt-4">7. Trasferimento di dati verso Paesi extra UE</h2>
        <p>
            Alcuni fornitori di servizi (ad esempio, provider di servizi cloud o servizi di analisi)
            potrebbero avere sede o trattare dati al di fuori dello Spazio Economico Europeo (SEE).
        </p>
        <p>
            In tali casi, il trasferimento dei dati avverrà nel rispetto degli articoli 44 e ss. del GDPR,
            adottando le opportune garanzie (ad esempio, decisioni di adeguatezza della Commissione Europea
            o clausole contrattuali standard approvate dalla Commissione).
        </p>

        <h2 class="h4 mt-4">8. Diritti dell’interessato</h2>
        <p>
            L’utente, in qualità di interessato, può esercitare in qualsiasi momento i diritti previsti dagli articoli 15–22 del GDPR, tra cui:
        </p>
        <ul>
            <li><strong>Diritto di accesso</strong>: ottenere la conferma dell’esistenza o meno di dati personali che lo riguardano
                e la comunicazione di tali dati;</li>
            <li><strong>Diritto di rettifica</strong>: ottenere la rettifica di dati inesatti o l’integrazione di quelli incompleti;</li>
            <li><strong>Diritto alla cancellazione</strong> (“diritto all’oblio”): ottenere la cancellazione dei dati personali
                nei casi previsti dall’art. 17 GDPR;</li>
            <li><strong>Diritto di limitazione</strong>: ottenere la limitazione del trattamento nei casi previsti dall’art. 18 GDPR;</li>
            <li><strong>Diritto alla portabilità</strong>: ricevere in formato strutturato, di uso comune e leggibile da dispositivo
                automatico i dati personali forniti, e trasmetterli a un altro titolare (quando tecnicamente fattibile);</li>
            <li><strong>Diritto di opposizione</strong>: opporsi in qualsiasi momento al trattamento basato sul legittimo interesse
                del Titolare, salvo che vi siano motivi legittimi cogenti per proseguire il trattamento;</li>
            <li><strong>Diritto di revocare il consenso</strong>: qualora il trattamento sia basato sul consenso, revocarlo in
                qualsiasi momento, senza pregiudicare la liceità del trattamento effettuato prima della revoca.</li>
        </ul>
        <p>
            Per esercitare tali diritti, l’interessato può contattare il Titolare ai recapiti indicati nella presente informativa
            (si veda il paragrafo 1).
        </p>
        <p>
            Inoltre, l’interessato ha il diritto di proporre reclamo all’Autorità di controllo competente, in Italia il
            <strong>Garante per la protezione dei dati personali</strong> (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">www.garanteprivacy.it</a>).
        </p>

        <h2 class="h4 mt-4">9. Cookie e strumenti di tracciamento</h2>
        <p>
            Per informazioni dettagliate sull’uso di cookie e strumenti di tracciamento (inclusi, ad esempio, Google Analytics),
            si rinvia alla <a href="{{ route('policy.cookie') }}">Cookie Policy</a>, parte integrante della presente informativa.
        </p>

        <h2 class="h4 mt-4">10. Modifiche alla presente informativa</h2>
        <p>
            La presente informativa può essere soggetta a modifiche e aggiornamenti, anche in considerazione di eventuali
            cambiamenti normativi o dell’evoluzione dei servizi offerti dal Sito.
        </p>
        <p>
            Eventuali aggiornamenti saranno pubblicati su questa pagina. Si invita pertanto l’utente a consultare
            periodicamente l’Informativa Privacy per essere sempre informato sulle modalità di trattamento dei propri dati personali.
        </p>

        {{--<p class="text-muted small mt-4">
            Nota: il presente testo ha scopo informativo e generale. È consigliabile
            un confronto con il proprio consulente legale / privacy per adattarlo alle specifiche attività e
            ai trattamenti effettivamente svolti dal Titolare.
        </p>--}}
    </div>
@endsection
