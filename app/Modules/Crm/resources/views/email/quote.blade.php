<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Preventivo {{ $quote->number }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size:14px; color:#222;">
<h2 style="margin-top:0;">Preventivo {{ $quote->number }}</h2>

<p>
    Gentile {{ $quote->customer?->name }},<br>
    ti inviamo il preventivo relativo ai servizi richiesti.
</p>

<p style="margin-bottom:4px;">
    <strong>Data:</strong> {{ $quote->date?->format('d/m/Y') }}<br>
    @if($quote->valid_until)
        <strong>Valido fino al:</strong> {{ $quote->valid_until?->format('d/m/Y') }}<br>
    @endif
    <strong>Totale:</strong> {{ number_format($quote->total, 2, ',', '.') }} € (IVA inclusa)
</p>

<table width="100%" cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse; margin-top:15px;">
    <thead>
    <tr>
        <th align="left" style="border-bottom:1px solid #ccc;">Descrizione</th>
        <th align="right" style="border-bottom:1px solid #ccc;">Qtà</th>
        <th align="right" style="border-bottom:1px solid #ccc;">Prezzo</th>
        <th align="right" style="border-bottom:1px solid #ccc;">Totale</th>
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
            <td>{{ $item->description }}</td>
            <td align="right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
            <td align="right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
            <td align="right">{{ number_format($lineTotal, 2, ',', '.') }} €</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p style="margin-top:15px;">
    <strong>Imponibile:</strong> {{ number_format($quote->subtotal, 2, ',', '.') }} €<br>
    <strong>Sconti:</strong> - {{ number_format($quote->discount_total, 2, ',', '.') }} €<br>
    <strong>Imposte:</strong> {{ number_format($quote->tax_total, 2, ',', '.') }} €<br>
    <strong>Totale:</strong> {{ number_format($quote->total, 2, ',', '.') }} €
</p>

@if($quote->notes)
    <p style="margin-top:15px;">
        <strong>Note:</strong><br>
        {{ $quote->notes }}
    </p>
@endif

<p style="margin-top:20px;">
    Cordiali saluti,<br>
    {{ config('app.name') }}
</p>
</body>
</html>
