@extends('admin.layout')

@section('title', 'Modifica campagna chiamate')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Campagna chiamate: {{ $campaign->name }}</h1>
            <div class="text-muted small">
                Provider: {{ strtoupper($campaign->provider) }} · Stato: {{ $campaign->status }}
                @if($campaign->is_active)
                    · <span class="text-success">Attiva</span>
                @else
                    · <span class="text-muted">Non attiva</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.crm.call-campaigns.show', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-eye"></i> Apri dettaglio
            </a>

            <a href="{{ route('admin.crm.call-campaigns.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Torna alle campagne
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

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

    <form method="POST" action="{{ route('admin.crm.call-campaigns.update', $campaign) }}">
        @csrf
        @method('PUT')

        @include('crm::call-campaigns._form', [
            'campaign' => $campaign,
            'emailLists' => $emailLists,
        ])
    </form>
@endsection
