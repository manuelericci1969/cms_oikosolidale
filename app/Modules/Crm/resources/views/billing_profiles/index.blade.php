@extends('admin.layout')

@section('title', 'Profili di fatturazione')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Profili di fatturazione</h1>
            <div class="text-muted small">Gestisci i soggetti emittenti dei preventivi e dei contratti.</div>
        </div>
        <a href="{{ route('admin.crm.billing-profiles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuovo profilo
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
                    <thead>
                    <tr>
                        <th>Profilo</th>
                        <th>P.IVA / CF</th>
                        <th>Contatti</th>
                        <th>Stato</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($profiles as $profile)
                        <tr>
                            <td>
                                <strong>{{ $profile->legal_name ?: $profile->name }}</strong>
                                <div class="small text-muted">{{ $profile->address }} {{ $profile->zip }} {{ $profile->city }} @if($profile->province) ({{ $profile->province }}) @endif</div>
                                @if($profile->is_default)
                                    <span class="badge bg-primary mt-1">Predefinito</span>
                                @endif
                            </td>
                            <td>
                                @if($profile->vat)
                                    <div>P.IVA: {{ $profile->vat }}</div>
                                @endif
                                @if($profile->tax_code)
                                    <div>C.F.: {{ $profile->tax_code }}</div>
                                @endif
                            </td>
                            <td>
                                @if($profile->email)<div>{{ $profile->email }}</div>@endif
                                @if($profile->pec)<div class="small text-muted">PEC: {{ $profile->pec }}</div>@endif
                                @if($profile->sdi)<div class="small text-muted">SDI: {{ $profile->sdi }}</div>@endif
                            </td>
                            <td>
                                @if($profile->is_active)
                                    <span class="badge bg-success">Attivo</span>
                                @else
                                    <span class="badge bg-secondary">Disattivato</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.billing-profiles.edit', $profile) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.crm.billing-profiles.destroy', $profile) }}" class="d-inline-block" onsubmit="return confirm('Eliminare questo profilo?');">
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
                                Nessun profilo configurato. Crea almeno il profilo società e/o consulente.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($profiles->hasPages())
            <div class="card-footer">{{ $profiles->links() }}</div>
        @endif
    </div>
@endsection
