{{-- resources/views/admin/account/edit.blade.php --}}
@extends('admin.layouts.app') {{-- cambia questo col tuo layout admin --}}

@section('title', 'Account personale')

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">Account personale</h1>

        @if (session('ok'))
            <div class="alert alert-success">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Info base utente (se ti servono) --}}
        <div class="mb-4">
            <p class="mb-1"><strong>Nome:</strong> {{ $user->name }}</p>
            <p class="mb-1"><strong>Email:</strong> {{ $user->email }}</p>
        </div>

        <hr>

        {{-- Form cambio password --}}
        <form method="POST" action="{{ route('admin.account.password') }}" class="mt-4">
            @csrf
            @method('PATCH')

            <h2 class="h5 mb-3">Cambia password</h2>

            {{-- Password attuale --}}
            <div class="mb-3">
                <label for="current_password" class="form-label">Password attuale</label>
                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    class="form-control @error('current_password') is-invalid @enderror"
                    required
                    autocomplete="current-password"
                >
                @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Nuova password --}}
            <div class="mb-3">
                <label for="password" class="form-label">Nuova password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required
                    autocomplete="new-password"
                >
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Conferma nuova password --}}
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Conferma nuova password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="form-control"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Aggiorna password
            </button>
        </form>
    </div>
@endsection
