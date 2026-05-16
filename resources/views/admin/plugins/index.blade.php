@extends('admin.layout')
@section('title','Plugin')

@section('content')
    <div class="container-fluid py-3">
        <h1 class="h4 mb-3"><i class="bi bi-puzzle me-1"></i> Plugin</h1>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any()) <div class="alert alert-danger">{{ implode(' ', $errors->all()) }}</div> @endif

        <form class="card mb-3" method="POST" enctype="multipart/form-data" action="{{ route('admin.plugins.upload') }}">
            @csrf
            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                <input type="file" name="zip" accept=".zip" class="form-control w-auto" required>
                <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Carica ZIP</button>
                <div class="text-muted small">
                    Lo ZIP deve contenere <code>plugin.json</code> e opzionalmente <code>public/</code> con asset.
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Nome</th><th>Slug</th><th>Versione</th><th>Autore</th><th>Stato</th><th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($plugins as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td><code>{{ $p->slug }}</code></td>
                            <td>{{ $p->version }}</td>
                            <td>{{ $p->author }}</td>
                            <td>
                            <span class="badge text-bg-{{ $p->enabled ? 'success':'secondary' }}">
                                {{ $p->enabled ? 'Abilitato':'Disabilitato' }}
                            </span>
                                @php $blocks = $p->manifest['blocks'] ?? []; @endphp
                                @if(!empty($blocks))
                                    <span class="badge text-bg-info ms-1" title="Contiene blocchi per Page Builder">
                                    PB
                                </span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($p->enabled)
                                    <form class="d-inline" method="POST" action="{{ route('admin.plugins.disable',$p) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-outline-secondary btn-sm">Disabilita</button>
                                    </form>
                                @else
                                    <form class="d-inline" method="POST" action="{{ route('admin.plugins.enable',$p) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-outline-success btn-sm">Abilita</button>
                                    </form>
                                @endif
                                <form class="d-inline" method="POST" action="{{ route('admin.plugins.destroy',$p) }}"
                                      onsubmit="return confirm('Eliminare definitivamente?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted p-3">Nessun plugin.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
