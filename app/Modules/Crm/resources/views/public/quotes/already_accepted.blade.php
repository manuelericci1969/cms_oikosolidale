{{-- modules/Crm/resources/views/public/quotes/already_accepted.blade.php --}}

    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Preventivo già accettato</title>

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
                    <h1 class="h4 mb-3">Preventivo già accettato</h1>
                    <p>
                        Il preventivo
                        <strong>{{ $quote->number }}</strong>
                        risulta già accettato in data
                        <strong>{{ $quote->accepted_at?->format('d/m/Y H:i') }}</strong>.
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
