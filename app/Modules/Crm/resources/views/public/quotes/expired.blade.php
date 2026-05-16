{{-- modules/Crm/resources/views/public/quotes/expired.blade.php --}}

    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Link scaduto</title>

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
                    <h1 class="h4 mb-3">Link scaduto</h1>

                    <p>
                        Il link per l'accettazione del preventivo
                        <strong>{{ $quote->number }}</strong> è scaduto.
                    </p>
                    <p class="text-muted">
                        Contatti il nostro ufficio commerciale per ricevere un nuovo preventivo aggiornato.
                    </p>

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
