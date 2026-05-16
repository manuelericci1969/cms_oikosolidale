{{-- resources/views/auth/verify-email.blade.php --}}
<x-guest-layout>
    <x-slot name="title">
        Verifica email | {{ config('app.name', 'Oikos Solidale') }}
    </x-slot>

    <div class="mb-4">
        <div class="badge text-bg-primary rounded-pill px-3 py-2 mb-3">
            Verifica account
        </div>

        <h2 class="fw-bold mb-2">
            Verifica la tua email
        </h2>

        <p class="text-muted mb-0">
            Ti abbiamo inviato un link di verifica all’indirizzo usato in fase di registrazione.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success rounded-4" role="alert">
            Un nuovo link di verifica è stato inviato alla tua email.
        </div>
    @endif

    <div class="d-grid gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="btn btn-primary w-100">
                Reinvia email di verifica
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn btn-outline-secondary w-100">
                Esci
            </button>
        </form>
    </div>
</x-guest-layout>
