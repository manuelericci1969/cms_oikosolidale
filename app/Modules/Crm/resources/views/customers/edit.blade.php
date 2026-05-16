@extends('admin.layout')

@section('title', 'Modifica cliente')

@section('content')
    <h1 class="h3 mb-3">Modifica cliente ADMIN</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.crm.customers.update', $customer) }}">
        @csrf
        @method('PUT')
        @include('crm::customers._form')
    </form>
@endsection
