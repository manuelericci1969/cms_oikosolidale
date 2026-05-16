{{-- modules/Crm/resources/views/email/contract.blade.php --}}
@php($customer = $quote->customer)

<p>Gentile {{ $customer?->name ?? 'Cliente' }},</p>

<p>
    la informiamo che il preventivo <strong>{{ $quote->number }}</strong>,
    da Lei appena accettato, è stato confermato con successo.
</p>

<p>
    In allegato trova il <strong>contratto</strong> relativo ai servizi oggetto
    del preventivo. La invitiamo a leggere attentamente tutte le condizioni,
    firmare il documento e restituircelo secondo le modalità concordate.
</p>

<p>
    Per qualsiasi chiarimento può contattarci ai seguenti recapiti:<br>
    @if(!empty($company['email']))
        Email: {{ $company['email'] }}<br>
    @endif
    @if(!empty($company['phone']))
        Tel: {{ $company['phone'] }}<br>
    @endif
</p>

<p>Cordiali saluti,<br>
    {{ $company['name'] ?? config('app.name') }}</p>
