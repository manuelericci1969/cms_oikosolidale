{{-- modules/Crm/resources/views/public/quotes/accept.blade.php --}}

    <!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Conferma preventivo {{ $quote->number }}</title>

    {{-- Se usi già Bootstrap nel progetto pubblico puoi anche togliere questo CDN --}}
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

            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">
                        Conferma preventivo {{ $quote->number }}
                    </h1>

                    {{-- Messaggi di errore / successo --}}
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <p>
                        Abbiamo inviato un codice di conferma all'indirizzo
                        <strong>{{ $quote->customer?->email }}</strong>.
                    </p>

                    <p>
                        Inserisca il codice qui sotto per confermare l'accettazione del preventivo.
                    </p>

                    <form method="POST"
                          action="{{ route('crm.quotes.accept.confirm', $quote->acceptance_token) }}"
                          class="mt-4">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Codice di conferma</label>
                            <input type="text"
                                   name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   autocomplete="one-time-code"
                                   autofocus>
                            @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button class="btn btn-primary">
                            Conferma accettazione
                        </button>
                    </form>

                    <p class="text-muted mt-3" style="font-size:12px;">
                        Il preventivo è valido fino al
                        <strong>{{ optional($quote->valid_until)->format('d/m/Y') }}</strong>.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
