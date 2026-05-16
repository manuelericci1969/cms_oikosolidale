{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
    <x-slot name="title">
        Accesso | {{ config('app.name', 'Oikos Solidale') }}
    </x-slot>

    <div class="mb-4">
        <div class="badge text-bg-primary rounded-pill px-3 py-2 mb-3">
            Area riservata
        </div>

        <h2 class="fw-bold mb-2">
            Accedi al tuo account
        </h2>

        <p class="text-muted mb-0">
            Entra nell’area riservata Oikos Solidale per gestire contenuti, servizi, attività formative e percorsi di accompagnamento.
        </p>
    </div>

    @if (session('status'))
        <div class="alert alert-success rounded-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger rounded-4" role="alert">
            <div class="fw-semibold mb-1">Controlla i dati inseriti</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">
                Email
            </label>

            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="bi bi-envelope"></i>
                </span>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nome@email.it"
                >
            </div>

            @error('email')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label fw-semibold">
                    Password
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link small">
                        Password dimenticata?
                    </a>
                @endif
            </div>

            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="bi bi-lock"></i>
                </span>

                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required
                    autocomplete="current-password"
                    placeholder="Inserisci la password"
                >
            </div>

            @error('password')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="form-check-input"
                    name="remember"
                >

                <label class="form-check-label text-muted" for="remember_me">
                    Ricordami
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Accedi
            <i class="bi bi-arrow-right ms-1"></i>
        </button>

        @if (Route::has('register'))
            <div class="text-center mt-4">
                <span class="text-muted">Non hai ancora un account?</span>
                <a href="{{ route('register') }}" class="auth-link">
                    Registrati
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
