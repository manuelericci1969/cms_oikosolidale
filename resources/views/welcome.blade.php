@extends('layouts.app')

@section('title', 'Home - ' . config('app.name'))

@section('content')
    <div class="text-center py-5">
        <h1 class="display-4 fw-bold mb-4">Benvenuto su {{ config('app.name') }}</h1>
        <p class="lead text-muted mb-4">
            La tua nuova home page è pronta! TEST
        </p>

        @auth
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    Vai alla Dashboard
                </a>
                @if(auth()->user()->hasPermission('view.admin'))
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                        Area Admin
                    </a>
                @endif
            </div>
        @else
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    Accedi
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary">
                        Registrati
                    </a>
                @endif
            </div>
        @endauth
    </div>

    {{-- Esempio di sezione features --}}
    <div class="row mt-5 g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="card-title h5">✨ Moderno</h3>
                    <p class="card-text text-muted">
                        Costruito con Laravel e Bootstrap
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="card-title h5">🚀 Performante</h3>
                    <p class="card-text text-muted">
                        Ottimizzato per velocità e SEO
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="card-title h5">🔧 Flessibile</h3>
                    <p class="card-text text-muted">
                        Completamente personalizzabile
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
