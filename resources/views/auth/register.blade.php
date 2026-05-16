{{-- resources/views/auth/register.blade.php --}}
<x-guest-layout>
    <x-slot name="title">
        Registrazione | {{ config('app.name', 'R4Software') }}
    </x-slot>

    <div class="mb-4">
        <div class="badge text-bg-primary rounded-pill px-3 py-2 mb-3">
            Nuovo account
        </div>

        <h2 class="fw-bold mb-2">
            Crea il tuo account
        </h2>

        <p class="text-muted mb-0">
            Registrati per accedere ai servizi digitali R4Software.
        </p>
    </div>

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

    <form method="POST" action="{{ route('register') }}" class="needs-validation" novalidate>
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label fw-semibold">
                Nome completo
            </label>

            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="bi bi-person"></i>
                </span>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Mario Rossi"
                >
            </div>

            @error('name')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
            @enderror
        </div>

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
            <label for="password" class="form-label fw-semibold">
                Password
            </label>

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
                    autocomplete="new-password"
                    placeholder="Crea una password sicura"
                >
            </div>

            @error('password')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
            @enderror

            <div class="form-text">
                Usa almeno 8 caratteri.
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold">
                Conferma password
            </label>

            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="bi bi-shield-check"></i>
                </span>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    required
                    autocomplete="new-password"
                    placeholder="Ripeti la password"
                >
            </div>

            @error('password_confirmation')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Crea account
            <i class="bi bi-arrow-right ms-1"></i>
        </button>

        <div class="text-center mt-4">
            <span class="text-muted">Hai già un account?</span>
            <a href="{{ route('login') }}" class="auth-link">
                Accedi
            </a>
        </div>
    </form>
</x-guest-layout>
