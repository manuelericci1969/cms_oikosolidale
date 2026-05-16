@extends('admin.layout')
@section('title','Ruoli')
@section('content')
    <h1 class="h4 mb-3">Ruoli</h1>

    <div class="alert alert-info">
        <strong>SuperAdmin</strong> ha tutti i permessi per definizione (non modificabile qui).
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6">Permessi ruolo: <code>admin</code></h2>
            <form method="POST" action="{{ route('admin.roles.sync') }}">
                @csrf
                <input type="hidden" name="role" value="admin">
                <div class="row">
                    @foreach($permissions as $perm)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                       value="{{ $perm->id }}" id="p{{ $perm->id }}"
                                    @checked(in_array($perm->id, $byRole['admin'] ?? []))>
                                <label class="form-check-label" for="p{{ $perm->id }}">
                                    <code>{{ $perm->name }}</code> — <span class="text-muted">{{ $perm->description }}</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary">Salva permessi</button>
                </div>
            </form>
        </div>
    </div>
@endsection
