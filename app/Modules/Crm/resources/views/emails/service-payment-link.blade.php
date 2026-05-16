<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Pagamento rinnovo servizio</title>
</head>
<body style="font-family: Arial, sans-serif; color:#222; line-height:1.5;">
    <p>Gentile {{ $customer?->name }},</p>

    <p>
        le inviamo il link sicuro per effettuare il pagamento del rinnovo del servizio:
    </p>

    <p>
        <strong>{{ $service->name ?: optional($service->product)->name ?: 'Servizio' }}</strong><br>
        @if($service->expires_at)
            Scadenza: <strong>{{ $service->expires_at->format('d/m/Y') }}</strong><br>
        @endif
        Importo: <strong>{{ number_format((float) $paymentLink->amount, 2, ',', '.') }} {{ strtoupper($paymentLink->currency) }}</strong>
    </p>

    <p style="margin: 24px 0;">
        <a href="{{ $paymentLink->stripe_url }}"
           style="display:inline-block; padding:12px 18px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;">
            Paga ora con carta
        </a>
    </p>

    <p style="font-size:13px; color:#666;">
        Se il pulsante non dovesse funzionare, copi e incolli questo link nel browser:<br>
        <a href="{{ $paymentLink->stripe_url }}">{{ $paymentLink->stripe_url }}</a>
    </p>

    <p>Cordiali saluti.</p>
</body>
</html>
