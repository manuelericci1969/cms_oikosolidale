@extends('admin.layout')

@section('title', 'Nuova campagna chiamate')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Nuova campagna chiamate</h1>
            <div class="text-muted small">
                Crea una campagna telefonica partendo da una lista email con numeri associati.
            </div>
        </div>

        <a href="{{ route('admin.crm.call-campaigns.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Torna alle campagne
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Attenzione:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.crm.call-campaigns.store') }}">
        @csrf

        @include('crm::call-campaigns._form', [
            'campaign' => $campaign,
            'emailLists' => $emailLists,
        ])
    </form>
@endsection
