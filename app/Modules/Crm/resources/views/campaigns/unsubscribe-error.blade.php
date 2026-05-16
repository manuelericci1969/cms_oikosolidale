@extends('layouts.app')

@section('title', 'Link non valido')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                            </div>

                            <h1 class="h4 mb-3">Link non valido o già utilizzato</h1>
                            <p class="text-muted mb-4">
                                Il link di disiscrizione che hai utilizzato non è più valido
                                oppure è già stato utilizzato in precedenza.
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
