@extends('layouts.app')

@section('title', 'Cookie Policy')
@section('meta_description', 'Informazioni sull’uso dei cookie e tecnologie similari su questo sito.')

@section('content')
    <div class="container py-5">
        <h1 class="mb-4 h2">Cookie Policy</h1>

        <p class="text-muted small mb-4">
            Ultimo aggiornamento: {{ now()->format('d/m/Y') }}
        </p>

        <h2 class="h4 mt-4">1. Titolare del trattamento</h2>
        <p>
            Il sito web (di seguito, il <strong>“Sito”</strong>) è gestito da
            <strong>R4Software</strong> (di seguito, il <strong>“Titolare”</strong>).
        </p>
        <p class="small text-muted">
            <!-- Suggerimento: personalizza con i dati completi -->
            Con sede in [inserire indirizzo completo], P.IVA [inserire P.IVA], e-mail:
            <a href="mailto:info@r4software.it">info@r4software.it</a>.
        </p>

        <h2 class="h4 mt-4">2. Cosa sono i cookie</h2>
        <p>
            I cookie sono piccoli file di testo che i siti visitati dall’utente inviano al suo dispositivo
            (computer, tablet, smartphone), dove vengono memorizzati per essere poi ritrasmessi agli stessi siti
            alla successiva visita. I cookie consentono al Sito di funzionare in modo efficiente, di ricordare
            le preferenze dell’utente e di ottenere informazioni a fini statistici o pubblicitari.
        </p>
        <p>
            Oltre ai cookie, il Sito può utilizzare tecnologie assimilate (ad esempio pixel, tag, local storage),
            di seguito congiuntamente definite <strong>“cookie”</strong>.
        </p>

        <h2 class="h4 mt-4">3. Tipologie di cookie utilizzati</h2>

        <h3 class="h5 mt-3">3.1 Cookie tecnici (necessari)</h3>
        <p>
            Sono i cookie indispensabili per il corretto funzionamento del Sito e per consentire la navigazione
            e l’utilizzo delle sue funzionalità (ad esempio, mantenimento della sessione, autenticazione
            all’area riservata, memorizzazione della scelta espressa nel banner dei cookie).
        </p>
        <p>
            Questi cookie non richiedono il consenso dell’utente e sono impostati automaticamente
            all’accesso al Sito.
        </p>

        <h3 class="h5 mt-3">3.2 Cookie di preferenza/funzionali</h3>
        <p>
            Permettono di ricordare alcune scelte dell’utente (ad esempio, la lingua, l’area geografica o
            determinate impostazioni) al fine di migliorare l’esperienza di navigazione. Se non comportano
            attività di profilazione, rientrano nell’ambito dei cookie tecnici; in caso contrario, ne è richiesto
            il consenso.
        </p>

        <h3 class="h5 mt-3">3.3 Cookie statistici/analitici</h3>
        <p>
            I cookie statistici o analitici vengono utilizzati per raccogliere informazioni in forma aggregata
            sull’uso del Sito (ad esempio, numero di visitatori, pagine più visitate, tempo di permanenza,
            provenienza del traffico).
        </p>
        <p>
            Quando, attraverso opportune misure di anonimizzazione (ad esempio mascheramento dell’indirizzo IP)
            e configurazioni che impediscano l’incrocio dei dati con altre informazioni, non consentono di
            identificare direttamente l’utente, tali cookie possono essere trattati come cookie tecnici.
            In caso contrario, il loro utilizzo è subordinato al consenso dell’utente.
        </p>

        <h3 class="h5 mt-3">3.4 Cookie di profilazione e marketing</h3>
        <p>
            Sono cookie utilizzati per tracciare la navigazione dell’utente e creare profili relativi ai suoi
            interessi al fine di mostrare annunci pubblicitari personalizzati (anche su siti terzi).
        </p>
        <p>
            Attualmente il Sito <strong>non utilizza</strong> cookie di profilazione direttamente gestiti dal
            Titolare. Eventuali cookie di profilazione di terze parti saranno attivati solo previo
            <strong>consenso esplicito</strong> dell’utente tramite il banner o il pannello di gestione
            dei cookie.
        </p>

        <h2 class="h4 mt-4">4. Cookie di prima parte e di terza parte</h2>
        <p>
            I cookie possono essere:
        </p>
        <ul>
            <li><strong>di prima parte</strong>, installati direttamente dal Titolare tramite il Sito;</li>
            <li><strong>di terza parte</strong>, installati da un sito web diverso da quello visitato
                dall’utente (ad esempio, fornitori di servizi di analisi, servizi pubblicitari, social network).
            </li>
        </ul>
        <p>
            L’utilizzo di cookie di terza parte è disciplinato dalle relative informative, alle quali si rinvia.
        </p>

        <h2 class="h4 mt-4">5. Cookie utilizzati dal Sito</h2>

        <h3 class="h5 mt-3">5.1 Cookie tecnici necessari</h3>
        <p>
            Il Sito utilizza cookie tecnici necessari per:
        </p>
        <ul>
            <li>gestire la sessione di navigazione;</li>
            <li>permettere l’autenticazione all’area riservata (se presente);</li>
            <li>memorizzare la scelta effettuata dall’utente nel banner dei cookie
                (accettazione/rifiuto delle categorie non tecniche).
            </li>
        </ul>

        <h3 class="h5 mt-3">5.2 Cookie analitici di terza parte – Google Analytics</h3>
        <p>
            Il Sito utilizza il servizio <strong>Google Analytics</strong>, fornito da Google LLC e/o
            da sue controllate, per finalità di analisi statistica aggregata sull’uso del Sito.
        </p>
        <p>
            Google Analytics impiega cookie che consentono di raccogliere informazioni, ad esempio:
        </p>
        <ul>
            <li>indirizzo IP (con eventuale anonimizzazione, se attivata);</li>
            <li>informazioni sul dispositivo e sul browser utilizzato;</li>
            <li>data e ora della visita;</li>
            <li>pagine visitate, tempo di permanenza, percorsi di navigazione;</li>
            <li>provenienza del traffico (motore di ricerca, campagne, siti di provenienza).</li>
        </ul>
        <p>
            Le informazioni generate dai cookie sull’utilizzo del Sito vengono trasmesse a Google, che le elabora
            per fornire al Titolare report statistici aggregati sull’attività svolta sul Sito.
        </p>
        <p>
            L’utilizzo di Google Analytics è subordinato al <strong>consenso</strong> dell’utente, espresso
            tramite il banner dei cookie. In assenza di consenso, i cookie analitici non vengono installati.
        </p>
        <p class="small text-muted">
            Per maggiori dettagli sul trattamento dei dati da parte di Google si rinvia alla documentazione
            ufficiale di Google Analytics e all’informativa privacy di Google.
        </p>

        <h2 class="h4 mt-4">6. Base giuridica del trattamento</h2>
        <p>
            Per i cookie:
        </p>
        <ul>
            <li>
                <strong>tecnici/necessari</strong>: la base giuridica è l’esecuzione di misure precontrattuali
                o contrattuali richieste dall’utente e il legittimo interesse del Titolare a garantire il
                corretto funzionamento del Sito;
            </li>
            <li>
                <strong>analitici non anonimizzati e cookie di profilazione/marketing</strong>:
                la base giuridica è il <strong>consenso</strong> dell’utente, liberamente prestato
                tramite il banner o il pannello di gestione dei cookie.
            </li>
        </ul>

        <h2 class="h4 mt-4">7. Gestione delle preferenze tramite banner/pannello</h2>
        <p>
            Al primo accesso al Sito (e successivamente, quando necessario), viene mostrato un banner
            che consente all’utente di:
        </p>
        <ul>
            <li>accettare tutti i cookie;</li>
            <li>rifiutare i cookie non tecnici;</li>
            <li>personalizzare le scelte selezionando le categorie di cookie da abilitare.</li>
        </ul>
        <p>
            Le preferenze espresse vengono memorizzate tramite un apposito cookie tecnico, così da non
            dover ripetere la scelta ad ogni accesso, salvo modifiche sostanziali del trattamento o della
            normativa applicabile, o scadenza del consenso.
        </p>
        <p>
            L’utente può in qualsiasi momento modificare le scelte attraverso il link o il pulsante
            dedicato alla gestione dei cookie, ove presente, o ripresentando il banner.
        </p>

        <h2 class="h4 mt-4">8. Gestione dei cookie tramite browser</h2>
        <p>
            L’utente può configurare il proprio browser in modo da:
        </p>
        <ul>
            <li>autorizzare, bloccare o eliminare i cookie (in tutto o in parte);</li>
            <li>impostare l’accettazione automatica di cookie solo da determinati siti;</li>
            <li>cancellare i cookie già memorizzati sul dispositivo.</li>
        </ul>
        <p>
            Le modalità di gestione dei cookie variano a seconda del browser utilizzato. Per istruzioni
            dettagliate, si invita a consultare la sezione “Aiuto” del proprio browser. La disattivazione
            dei cookie tecnici può compromettere il funzionamento del Sito o di alcune sue funzionalità.
        </p>

        <h2 class="h4 mt-4">9. Trasferimento dei dati verso Paesi extra UE</h2>
        <p>
            I dati raccolti tramite cookie di terza parte (ad esempio Google Analytics) possono essere
            trattati su server collocati al di fuori dello Spazio Economico Europeo. In tal caso, il
            trasferimento avviene nel rispetto della normativa applicabile, ad esempio mediante l’adozione
            di clausole contrattuali standard e/o di altre garanzie adeguate previste dalla normativa europea.
        </p>

        <h2 class="h4 mt-4">10. Diritti degli interessati</h2>
        <p>
            In qualità di interessato, l’utente può esercitare in ogni momento i diritti previsti dal
            Regolamento (UE) 2016/679 (accesso, rettifica, cancellazione, limitazione, opposizione,
            portabilità dei dati, reclamo all’Autorità di controllo, ecc.) contattando il Titolare
            ai recapiti indicati nella presente Cookie Policy e nell’Informativa Privacy.
        </p>
        <p>
            Per maggiori informazioni sul trattamento dei dati personali (non solo tramite cookie),
            si invita a consultare l’<a href="{{ route('policy.privacy') }}">Informativa Privacy</a> del Sito.
        </p>

        <h2 class="h4 mt-4">11. Aggiornamenti della presente Cookie Policy</h2>
        <p>
            La presente Cookie Policy potrà essere aggiornata nel tempo, anche in considerazione di
            eventuali modifiche normative o dell’evoluzione dei servizi offerti dal Sito. Le modifiche
            rilevanti saranno opportunamente comunicate tramite il Sito e, se necessario, verrà richiesta
            nuovamente la manifestazione del consenso.
        </p>
        <p>
            Si invita pertanto l’utente a consultare periodicamente questa pagina.
        </p>
    </div>
@endsection
