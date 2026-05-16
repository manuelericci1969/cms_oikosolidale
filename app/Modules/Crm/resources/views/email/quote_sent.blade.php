@php($customer = $quote->customer)
<p>Gentile {{ $customer?->name ?? 'Cliente' }},</p>

<p>
    in allegato trova il preventivo <strong>{{ $quote->number }}</strong>
    relativo ai servizi richiesti.
</p>

<p>
    Per l'accettazione della proposta è sufficiente cliccare sul seguente link
    e seguire le istruzioni:
</p>

<p>
    <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
</p>

<p style="font-size:12px;color:#666;">
    Il link di conferma è valido fino al
    <strong>{{ optional($quote->valid_until)->format('d/m/Y') ?? '...' }}</strong>.
</p>

<p>Cordiali saluti,<br>
    {{ $company['name'] ?? config('app.name') }}</p>
