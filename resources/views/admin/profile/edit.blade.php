@extends('admin.layout')

@section('title', 'Profilo utente')

@section('content')
    <div class="container py-4">

        <h1 class="h3 mb-4">Profilo utente</h1>

        @if (session('ok'))
            <div class="alert alert-success">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Controlla i campi:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- FORM DATI PROFILO --}}
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <h2 class="h5 mb-3">Dati personali</h2>

            {{-- Nome --}}
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name', auth()->user()->name) }}"
                    required
                >
            </div>

            {{-- Email --}}
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="{{ old('email', auth()->user()->email) }}"
                    required
                >
            </div>

            {{-- TELEFONO --}}
            <div class="mb-3">
                <label class="form-label">Numero di telefono</label>

                <input
                    type="text"
                    name="phone"
                    class="form-control"
                    value="{{ old('phone', auth()->user()->phone) }}"
                    placeholder="+393331234567"
                >

                <div class="form-text">
                    Inserisci il numero con prefisso internazionale per poter ricevere comunicazioni WhatsApp.
                </div>
            </div>


            {{-- CONSENSO WHATSAPP --}}
            <div class="form-check form-switch mb-4">

                <input type="hidden" name="whatsapp_opt_in" value="0">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="whatsapp_opt_in"
                    value="1"
                    id="whatsapp_opt_in"
                    @checked(old('whatsapp_opt_in', auth()->user()->whatsapp_opt_in))
                >

                <label class="form-check-label" for="whatsapp_opt_in">
                    Acconsento a ricevere comunicazioni via WhatsApp
                </label>

            </div>


            <button class="btn btn-primary">
                Aggiorna profilo
            </button>

        </form>

        <hr class="my-5">




        {{-- FORM CAMBIO PASSWORD --}}
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PATCH')

            <h2 class="h5 mb-3">Cambia password</h2>

            {{-- Password attuale --}}
            <div class="mb-3">
                <label class="form-label">Password attuale</label>

                <input
                    type="password"
                    name="current_password"
                    class="form-control @error('current_password') is-invalid @enderror"
                    required
                >

                @error('current_password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>


            {{-- Nuova password --}}
            <div class="mb-3">
                <label class="form-label">Nuova password</label>

                <input
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required
                >

                @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>


            {{-- Conferma password --}}
            <div class="mb-3">
                <label class="form-label">Conferma nuova password</label>

                <input
                    type="password"
                    name="password_confirmation"
                    class="form-control"
                    required
                >
            </div>


            <button class="btn btn-primary">
                Aggiorna password
            </button>

        </form>

    </div>
@endsection
