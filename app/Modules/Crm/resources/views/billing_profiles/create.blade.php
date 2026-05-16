@extends('admin.layout')

@section('title', 'Nuovo profilo di fatturazione')

@section('content')
    <h1 class="h3 mb-3">Nuovo profilo di fatturazione</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.crm.billing-profiles.store') }}" class="card card-body">
        @include('crm::billing_profiles._form')
    </form>
@endsection
