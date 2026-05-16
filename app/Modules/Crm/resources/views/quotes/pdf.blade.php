<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Preventivo {{ $quote->number }}</title>
    <style>
        @page { margin: 155px 40px 90px 40px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: -110px;
            left: 0;
            right: 0;
            height: 82px;
        }

        .header-left { float: left; width: 42%; }

        .header-right {
            float: right;
            width: 56%;
            text-align: right;
            font-size: 11px;
            line-height: 1.35;
        }

        .footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #555;
            padding-top: 5px;
        }

        .footer .line { text-align: center; }

        h1, h2, h3, h4 { margin: 0 0 6px 0; padding: 0; }
        main { margin: 0; padding: 0; }
        .quote-title { margin: 0 0 14px 0; }

        .meta-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            font-size: 12px;
        }

        .meta-title {
            text-transform: uppercase;
            font-size: 10px;
            color: #777;
            letter-spacing: .05em;
            margin-bottom: 4px;
        }

        .items-table,
        .payment-schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: auto;
        }

        .items-table thead,
        .payment-schedule-table thead { display: table-header-group; }

        .items-table tfoot,
        .payment-schedule-table tfoot { display: table-row-group; }

        .items-table tr,
        .payment-schedule-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .items-table th,
        .items-table td,
        .payment-schedule-table th,
        .payment-schedule-table td {
            border-bottom: 1px solid #eee;
            padding: 6px 4px;
            vertical-align: top;
        }

        .items-table th,
        .payment-schedule-table th {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 11px;
        }

        .payment-schedule-table tfoot th {
            background: #f8f8f8;
        }

        .text-right { text-align: right; }
        .text-left  { text-align: left; }

        .totals {
            width: 40%;
            margin-left: 60%;
            margin-top: 10px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .totals th,
        .totals td {
            padding: 4px;
            font-size: 12px;
        }

        .totals tr:nth-child(odd) { background: #f8f8f8; }

        .totals tr:last-child th,
        .totals tr:last-child td {
            font-weight: bold;
            font-size: 13px;
        }

        .intro {
            margin-top: 18px;
            font-size: 11px;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .section-block {
            margin-top: 15px;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .section-text {
            margin: 0;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .payment-terms {
            font-size: 12.5px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .notes-text {
            font-size: 11px;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .clearfix::after {
            content: "";
            display: block;
            clear: both;
        }
    </style>
</head>
<body>

<div class="header clearfix">
    <div class="header-left">
        @if(!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" style="max-height: 52px; max-width: 220px;">
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
            {{ $company['zip'] }} {{ $company['city'] }}
            @if(!empty($company['province']))
                ({{ $company['province'] }})
            @endif
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

<div class="footer">
    <div class="line">
        @if(!empty($company['name']))
            <strong>{{ $company['name'] }}</strong>
        @endif
        @if(!empty($company['vat']))
            · P.IVA {{ $company['vat'] }}
        @endif
        @if(!empty($company['address']) || !empty($company['city']) || !empty($company['zip']))
            · {{ $company['address'] }}, {{ $company['zip'] }} {{ $company['city'] }}
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
    <div class="quote-title">
        <h2>Preventivo {{ $quote->number }}</h2>
        <div style="font-size:11px; color:#555;">
            Data: {{ $quote->date?->format('d/m/Y') }}
            @if($quote->valid_until)
                · Valido fino al: {{ $quote->valid_until?->format('d/m/Y') }}
            @endif
            · Stato: {{ $quote->status }}
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 55%;">
                <div class="meta-title">Destinatario</div>
                @if($quote->customer)
                    <strong>{{ $quote->customer->name }}</strong><br>
                    @if($quote->customer->billing_address)
                        {{ $quote->customer->billing_address }}<br>
                    @endif
                    @if($quote->customer->billing_zip || $quote->customer->billing_city)
                        {{ $quote->customer->billing_zip }} {{ $quote->customer->billing_city }}
                        @if($quote->customer->billing_province)
                            ({{ $quote->customer->billing_province }})
                        @endif
                        <br>
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

            <td style="width: 45%;">
                <div class="meta-title">Dettagli preventivo</div>
                Numero: <strong>{{ $quote->number }}</strong><br>
                Data: {{ $quote->date?->format('d/m/Y') }}<br>
                @if($quote->valid_until)
                    Valido fino al: {{ $quote->valid_until?->format('d/m/Y') }}<br>
                @endif
                Valuta: {{ $quote->currency }}
            </td>
        </tr>
    </table>

    @if(!empty($quote->intro_text))
        <div class="intro">{{ $quote->intro_text }}</div>
    @endif

    <table class="items-table">
        <thead>
        <tr>
            <th class="text-left">Descrizione</th>
            <th class="text-right" style="width: 10%;">Qtà</th>
            <th class="text-right" style="width: 15%;">Prezzo</th>
            <th class="text-right" style="width: 10%;">Sconto</th>
            <th class="text-right" style="width: 10%;">IVA</th>
            <th class="text-right" style="width: 15%;">Totale</th>
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
                    <span style="font-size:10px; color:#777;">
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

    <table class="totals">
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
            <th class="text-left">Totale</th>
            <td class="text-right">{{ number_format($quote->total, 2, ',', '.') }} €</td>
        </tr>
    </table>

    @include('crm::quotes.partials.payment-schedule-pdf', ['quote' => $quote])

    @if(!empty($quote->payment_terms))
        <div class="section-block">
            <div class="meta-title">Condizioni di pagamento</div>
            <div class="section-text payment-terms">{{ $quote->payment_terms }}</div>
        </div>
    @endif

    @if(!empty($quote->notes))
        <div class="section-block">
            <div class="meta-title">Note</div>
            <div class="section-text notes-text">{{ $quote->notes }}</div>
        </div>
    @endif
</main>

</body>
</html>
