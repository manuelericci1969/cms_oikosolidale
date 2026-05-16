{{-- resources/views/auth/confirm-password.blade.php --}}
<x-guest-layout>
    <x-slot name="title">
        Conferma password | {{ config('app.name', 'Oikos Solidale') }}
    </x-slot>

    <div class="mb-4">
        <div class="badge text-bg-primary rounded-pill px-3 py-2 mb-3">
            Area protetta
        </div>

        <h2 class="fw-bold mb-2">
            Conferma la password
        </h2>

        <p class="text-muted mb-0">
            Questa è un’area riservata di Oikos Solidale. Conferma la password per continuare.
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

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4">
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

        <button type="submit" class="btn btn-primary w-100">
            Conferma
        </button>
    </form>
</x-guest-layout>
