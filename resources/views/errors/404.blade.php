@extends('layouts.app')

@section('title', 'Pagina non trovata')
@section('meta_description', 'La pagina che stai cercando non esiste. Scopri i nostri servizi di sviluppo software, siti web e social media.')

@section('content')
    <div class="container py-5 text-center">
        <h1 class="h2 mb-3">Ops, pagina non trovata.</h1>
        <p class="mb-4">
            La pagina che stai cercando potrebbe essere stata spostata o non esistere più.
        </p>
        <a href="{{ url('/') }}" class="btn btn-primary mb-3">Torna alla home</a>

        <p class="text-muted">
            Oppure scopri i nostri servizi:
            <a href="{{ url('/siti-web') }}">Realizzazione siti web</a>,
            <a href="{{ url('/sviluppo-software-su-misura-e-gestionali-web') }}">Sviluppo software su misura</a>,
            <a href="{{ url('/comunicazione-e-social-media') }}">Social media manager</a>.
        </p>
    </div>
@endsection
