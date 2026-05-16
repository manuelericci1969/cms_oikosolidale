{{-- app/Modules/Crm/resources/views/campaigns/unsubscribe-ok.blade.php --}}

@extends('layouts.app')

@section('title', 'Disiscrizione completata')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">

                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            </div>

                            <h1 class="h4 mb-3">Disiscrizione avvenuta con successo</h1>

                            @isset($recipient)
                                <p class="mb-3">
                                    L'indirizzo <strong>{{ $recipient->email }}</strong> è stato
                                    rimosso dalla lista per questa campagna.
                                </p>
                            @else
                                <p class="mb-3">
                                    Il tuo indirizzo è stato rimosso dalla lista per questa campagna.
                                </p>
                            @endisset

                            <p class="text-muted small mb-4">
                                Potrebbero volerci alcuni minuti perché la modifica sia effettiva
                                su tutte le nostre comunicazioni.
                            </p>

                            <a href="{{ url('/') }}" class="btn btn-primary btn-sm">
                                Torna al sito
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
