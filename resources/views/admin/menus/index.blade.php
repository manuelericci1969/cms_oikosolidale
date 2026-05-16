@extends('admin.layout')
@section('title', 'Menu')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Menu</h1>
        <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">+ Nuovo Menu</a>
    </div>

    @if($menus->isEmpty())
        <div class="alert alert-info">Nessun menu creato.</div>
    @else
        <div class="list-group">
            @foreach($menus as $menu)
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   href="{{ route('admin.menus.edit', $menu) }}">
                    <span>{{ $menu->name }} <small class="text-muted">({{ $menu->slug }})</small></span>
                    <span class="badge text-bg-{{ $menu->is_active ? 'success' : 'secondary' }}">
                        {{ $menu->is_active ? 'Attivo' : 'Bozza' }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
