{{-- modules/Crm/resources/views/contracts/pdf.blade.php --}}
@php
    use App\Models\Setting;

    // Testi legali presi dalle impostazioni (da compilare in admin)
    $contractTerms      = Setting::get('crm.contract_terms', null);
    $privacyGdprTerms   = Setting::get('crm.contract_privacy_gdpr', null);
@endphp

    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Contratto - Preventivo {{ $quote->number }}</title>
    <style>
        @page { margin: 80px 40px 80px 40px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .header, .footer {
            position: fixed;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #666;
        }
        .header { top: -60px; height: 60px; }
        .footer { bottom: -50px; height: 40px; border-top: 1px solid #ccc; padding-top: 5px; }

        .header-left { float: left; }
        .header-right { float: right; text-align: right; }

        h1, h2, h3, h4 {
            margin: 0 0 6px 0;
            padding: 0;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        .small-label {
            text-transform: uppercase;
            font-size: 9px;
            color: #777;
            letter-spacing: .05em;
        }

        .meta-table {
            width: 100%;
            margin-top: 10px;
        }
        .meta-table td {
            vertical-align: top;
            font-size: 11px;
        }

        p {
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        ul {
            margin: 0 0 6px 18px;
            padding: 0;
        }

        .signature-blocks {
            margin-top: 40px;
            width: 100%;
        }
        .signature-blocks td {
            width: 50%;
            vertical-align: top;
            font-size: 11px;
        }
        .sig-line {
            margin-top: 35px;
            border-top: 1px solid #000;
            width: 80%;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        @if(!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" alt="Logo" style="height: 40px; margin-bottom: 4px;">
            <br>
        @endif

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
            P.IVA {{ $company['vat'] }}<br>
        @endif
    </div>
    <div class="header-right">
        <div class="small-label">Contratto relativo al preventivo</div>
        <strong>{{ $quote->number }}</strong><br>
        Data preventivo: {{ $quote->date?->format('d/m/Y') }}<br>
        @if($quote->valid_until)
            Validità offerta: {{ $quote->valid_until?->format('d/m/Y') }}<br>
        @endif
    </div>
</div>

{{-- FOOTER --}}
<div class="footer">
    <div style="text-align:center;">
        {{ $company['name'] ?? '' }}
        @if(!empty($company['vat'])) · P.IVA {{ $company['vat'] }} @endif
        @if(!empty($company['email'])) · {{ $company['email'] }} @endif
        @if(!empty($company['phone'])) · Tel. {{ $company['phone'] }} @endif
    </div>
</div>

<main>
    {{-- INTESTAZIONE CONTRATTO --}}
    <h1 style="margin-top:0; margin-bottom:10px;">Contratto di fornitura servizi</h1>

    <table class="meta-table">
        <tr>
            <td style="width: 55%;">
                <div class="small-label">Fornitore</div>
                <p>
                    <strong>{{ $company['name'] ?? '' }}</strong><br>
                    @if(!empty($company['address']))
                        {{ $company['address'] }}<br>
                    @endif
                    @if(!empty($company['zip']) || !empty($company['city']))
                        {{ $company['zip'] }} {{ $company['city'] }}
                        @if(!empty($company['province'])) ({{ $company['province'] }}) @endif
                        <br>
                    @endif
                    @if(!empty($company['vat']))
                        P.IVA {{ $company['vat'] }}<br>
                    @endif
                    @if(!empty($company['email']))
                        Email: {{ $company['email'] }}<br>
                    @endif
                    @if(!empty($company['phone']))
                        Tel: {{ $company['phone'] }}
                    @endif
                </p>
            </td>
            <td style="width: 45%;">
                <div class="small-label">Cliente</div>
                @if($quote->customer)
                    <p>
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
                    </p>
                @endif
            </td>
        </tr>
    </table>

    {{-- OGGETTO DEL CONTRATTO --}}
    <div class="section-title">Art. 1 – Oggetto</div>
    <p>
        Il presente contratto disciplina la fornitura dei servizi descritti nel
        preventivo n. <strong>{{ $quote->number }}</strong> del
        <strong>{{ $quote->date?->format('d/m/Y') }}</strong>,
        che si intende qui integralmente richiamato.
    </p>

    {{-- CORRISPETTIVI E PAGAMENTI --}}
    <div class="section-title">Art. 2 – Corrispettivi e condizioni di pagamento</div>
    <p>
        Il corrispettivo complessivo per i servizi oggetto del presente contratto
        è pari a <strong>{{ number_format($quote->total, 2, ',', '.') }} €</strong>,
        come dettagliato nel preventivo richiamato (imponibile, sconti e imposte).
    </p>

    @if(!empty($quote->payment_terms))
        <p>
            Le condizioni di pagamento concordate sono le seguenti:
        </p>
        <p>
            {!! nl2br(e($quote->payment_terms)) !!}
        </p>
    @else
        <p>
            Le modalità e le scadenze di pagamento saranno definite tra le parti
            e formalizzate nel relativo documento contabile (ordine/fattura).
        </p>
    @endif

    {{-- ESECUZIONE DEI SERVIZI --}}
    <div class="section-title">Art. 3 – Esecuzione dei servizi</div>
    <p>
        Il Fornitore si impegna a svolgere i servizi con la diligenza professionale
        richiesta dalla natura dell’incarico, nel rispetto delle tempistiche
        indicate nel preventivo o successivamente concordate per iscritto tra le parti.
    </p>

    {{-- OBBLIGHI DEL CLIENTE --}}
    <div class="section-title">Art. 4 – Obblighi del Cliente</div>
    <p>
        Il Cliente si impegna a:
    </p>
    <ul>
        <li>fornire tutte le informazioni e la documentazione necessarie
            per la corretta esecuzione dei servizi;</li>
        <li>collaborare in buona fede con il Fornitore per la risoluzione
            di eventuali criticità operative;</li>
        <li>provvedere al pagamento dei corrispettivi dovuti nei termini
            e con le modalità stabilite.</li>
    </ul>

    {{-- DURATA E RECESSO --}}
    <div class="section-title">Art. 5 – Durata, recesso e risoluzione</div>
    <p>
        Il presente contratto ha durata limitata al completamento delle attività
        oggetto del preventivo, salvo diversi accordi scritti tra le parti.
        Eventuali condizioni di recesso anticipato, penali o clausole particolari
        potranno essere previste in specifici accordi aggiuntivi sottoscritti
        tra le parti.
    </p>

    {{-- TRATTAMENTO DATI (BASE) --}}
    <div class="section-title">Art. 6 – Trattamento dei dati personali</div>
    <p>
        Le parti dichiarano di essere reciprocamente informate che i dati personali
        raccolti in esecuzione del presente contratto saranno trattati nel rispetto
        della normativa vigente in materia di protezione dei dati personali
        (Regolamento UE 2016/679 – GDPR) e dell’informativa resa dal Fornitore.
    </p>

    {{-- NORME FINALI --}}
    <div class="section-title">Art. 7 – Disposizioni finali</div>
    <p>
        Per quanto non espressamente previsto nel presente contratto si fa riferimento
        alle disposizioni del Codice Civile e alla normativa vigente.
        Eventuali modifiche o integrazioni saranno valide solo se concordate
        per iscritto tra le parti.
    </p>

    {{-- CONDIZIONI GENERALI DI VENDITA (da settings) --}}
    @if(!empty($contractTerms))
        <div class="section-title">Art. 8 – Condizioni generali di vendita</div>
        <p>{!! nl2br(e($contractTerms)) !!}</p>
    @else
        {{-- Testo minimo di fallback, in attesa che tu compili crm.contract_terms --}}
        <div class="section-title">Art. 8 – Condizioni generali di vendita</div>
        <p>
            Le condizioni generali di vendita applicabili al presente contratto
            sono quelle comunicate dal Fornitore al Cliente e disponibili su richiesta.
            Si raccomanda di inserire qui il testo completo tramite le impostazioni CRM.
        </p>
    @endif

    {{-- PRIVACY / GDPR (da settings) --}}
    @if(!empty($privacyGdprTerms))
        <div class="section-title">Art. 9 – Privacy e GDPR</div>
        <p>{!! nl2br(e($privacyGdprTerms)) !!}</p>
    @else
        {{-- Testo minimo di fallback, in attesa che tu compili crm.contract_privacy_gdpr --}}
        <div class="section-title">Art. 9 – Privacy e GDPR</div>
        <p>
            Il trattamento dei dati personali avviene secondo l’informativa privacy
            resa dal Fornitore e nel rispetto del Regolamento UE 2016/679 (GDPR).
            Si raccomanda di inserire qui il testo dettagliato dell’informativa
            e delle clausole GDPR tramite le impostazioni CRM.
        </p>
    @endif

    {{-- FIRME --}}
    <table class="signature-blocks">
        <tr>
            <td>
                <p><strong>Per il Fornitore</strong></p>
                <p>{{ $company['name'] ?? '' }}</p>
                <div class="sig-line"></div>
                <p style="font-size:10px;">Firma</p>
            </td>
            <td>
                <p><strong>Per il Cliente</strong></p>
                <p>{{ $quote->customer->name ?? '' }}</p>
                <div class="sig-line"></div>
                <p style="font-size:10px;">Firma</p>
            </td>
        </tr>
    </table>
</main>

</body>
</html>
