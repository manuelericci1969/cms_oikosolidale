@php($customer = $quote->customer)
<p>Gentile {{ $customer?->name ?? 'Cliente' }},</p>

<p>
    per confermare l'accettazione del preventivo
    <strong>{{ $quote->number }}</strong>
    utilizzi il seguente codice di verifica:
</p>

<p style="text-align:center;font-size:22px;letter-spacing:4px;margin:20px 0;">
    <strong>{{ $code }}</strong>
</p>

<p>
    Inserisca il codice nella pagina di conferma che ha aperto tramite il link ricevuto.
</p>

<p style="font-size:12px;color:#666;">
    Il codice è valido per 30 minuti.
</p>

<p>Cordiali saluti,<br>
    {{ $company['name'] ?? config('app.name') }}</p>
