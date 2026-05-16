@extends('admin.layout')

@section('title', 'Liste email')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Liste email</h1>

        <a href="{{ route('admin.crm.email-lists.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nuova lista
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>Contatti</th>
                        <th>Creata il</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($lists as $list)
                        <tr>
                            <td>
                                <a href="{{ route('admin.crm.email-lists.edit', $list) }}">
                                    {{ $list->name }}
                                </a>
                            </td>
                            <td>
                                {{ \Illuminate\Support\Str::limit($list->description, 80) }}
                            </td>
                            <td>
                                {{ $list->contacts_count }}
                            </td>
                            <td>
                                {{ $list->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.email-lists.edit', $list) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('admin.crm.email-lists.destroy', $list) }}"
                                      method="POST"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Eliminare definitivamente questa lista (e tutti i contatti)?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Nessuna lista creata.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($lists->hasPages())
            <div class="card-footer">
                {{ $lists->links() }}
            </div>
        @endif
    </div>
@endsection
