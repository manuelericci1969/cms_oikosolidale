@extends('admin.layout')

@section('title', 'Modifica prodotto')

@section('content')
    <h1 class="h3 mb-3">Modifica prodotto</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.crm.products.update', $product) }}">
        @method('PUT')
        @include('crm::products._form')
    </form>
@endsection
