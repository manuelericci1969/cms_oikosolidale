{{-- resources/views/auth/forgot-password.blade.php --}}
<x-guest-layout>
    <x-slot name="title">
        Recupero password | {{ config('app.name', 'R4Software') }}
    </x-slot>

    <div class="mb-4">
        <div class="badge text-bg-primary rounded-pill px-3 py-2 mb-3">
            Recupero accesso
        </div>

        <h2 class="fw-bold mb-2">
            Password dimenticata?
        </h2>

        <p class="text-muted mb-0">
            Inserisci la tua email e riceverai un link per impostare una nuova password.
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

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
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
                    placeholder="nome@email.it"
                >
            </div>

            @error('email')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Invia link di recupero
            <i class="bi bi-send ms-1"></i>
        </button>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="auth-link">
                Torna al login
            </a>
        </div>
    </form>
</x-guest-layout>
