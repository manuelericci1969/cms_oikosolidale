{{-- modules/Crm/resources/views/public/quotes/accepted.blade.php --}}

    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Preventivo accettato</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h1 class="h3 mb-3">Grazie!</h1>

                    <p>
                        Il preventivo
                        <strong>{{ $quote->number }}</strong>
                        è stato correttamente accettato.
                    </p>

                    <p class="text-muted mt-3" style="font-size:12px;">
                        Data e ora di accettazione:
                        <strong>{{ $quote->accepted_at?->format('d/m/Y H:i') }}</strong><br>
                        IP registrato:
                        <strong>{{ $quote->accepted_ip }}</strong>
                    </p>

                    {{-- opzionale: link di cortesia al sito principale --}}
                    <p class="mt-4">
                        <a href="{{ config('app.url') }}" class="btn btn-outline-primary btn-sm">
                            Torna al sito
                        </a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
