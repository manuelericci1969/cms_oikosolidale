<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagamento ricevuto</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f6f7fb; color:#222; margin:0; padding:40px 16px; }
        .card { max-width:640px; margin:0 auto; background:#fff; border-radius:14px; padding:28px; box-shadow:0 12px 30px rgba(0,0,0,.08); }
        .ok { color:#198754; font-size:42px; line-height:1; }
        h1 { margin:10px 0 8px; }
        .muted { color:#666; }
    </style>
</head>
<body>
    <div class="card">
        <div class="ok">✓</div>
        <h1>Pagamento ricevuto</h1>
        <p>Grazie, il pagamento è stato registrato correttamente.</p>
        <p class="muted">
            Servizio: <strong>{{ optional($paymentLink->service)->name ?: optional(optional($paymentLink->service)->product)->name }}</strong><br>
            Importo: <strong>{{ number_format((float) $paymentLink->amount, 2, ',', '.') }} {{ strtoupper($paymentLink->currency) }}</strong>
        </p>
        <p class="muted">Può chiudere questa pagina.</p>
    </div>
</body>
</html>
