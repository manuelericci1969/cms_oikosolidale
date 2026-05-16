@extends('admin.layout')

@section('title', 'Nuovo prodotto')

@section('content')
    <h1 class="h3 mb-3">Nuovo prodotto</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.crm.products.store') }}">
        @include('crm::products._form')
    </form>
@endsection
