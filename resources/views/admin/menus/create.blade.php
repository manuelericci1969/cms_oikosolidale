@extends('admin.layout')
@section('title', 'Nuovo Menu')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Nuovo Menu</h1>
        <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">← Torna all'elenco</a>
    </div>

    <form method="POST" action="{{ route('admin.menus.store') }}">
        @csrf

        {{-- usa la partial; in create passa un modello "vuoto" --}}
        @include('admin.menus._form', ['menu' => new \App\Models\Menu])

        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary">Crea</button>
            <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">Annulla</a>
        </div>
    </form>
@endsection
