@php
    $customer = $customer ?? optional($service->customer);
@endphp
    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Promemoria scadenza servizio</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5;">
<p>{!! nl2br(e($bodyText)) !!}</p>

<p style="font-size: 12px; color: #777; margin-top: 30px;">
    Questo è un promemoria automatico inviato tramite CRM.
</p>

{{-- Pixel di tracciamento apertura (lettura) --}}
@if($log->tracking_hash)
    <img src="{{ route('crm.service-reminders.open', ['log' => $log->id, 'hash' => $log->tracking_hash]) }}"
         alt=""
         width="1"
         height="1"
         style="display:none;">
@endif
</body>
</html>
