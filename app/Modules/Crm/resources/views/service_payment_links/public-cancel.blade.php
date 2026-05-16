<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagamento annullato</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f6f7fb; color:#222; margin:0; padding:40px 16px; }
        .card { max-width:640px; margin:0 auto; background:#fff; border-radius:14px; padding:28px; box-shadow:0 12px 30px rgba(0,0,0,.08); }
        .warn { color:#ffc107; font-size:42px; line-height:1; }
        h1 { margin:10px 0 8px; }
        .muted { color:#666; }
        a { color:#0d6efd; }
    </style>
</head>
<body>
    <div class="card">
        <div class="warn">!</div>
        <h1>Pagamento non completato</h1>
        <p>Il pagamento è stato annullato o non è stato completato.</p>
        @if($paymentLink->stripe_url)
            <p>
                Può riprovare da questo link:<br>
                <a href="{{ $paymentLink->stripe_url }}">{{ $paymentLink->stripe_url }}</a>
            </p>
        @endif
        <p class="muted">In caso di dubbi può contattarci rispondendo al messaggio ricevuto.</p>
    </div>
</body>
</html>
