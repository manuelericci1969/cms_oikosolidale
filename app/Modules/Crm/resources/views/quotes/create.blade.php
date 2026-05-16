@extends('admin.layout')

@section('title', 'Nuovo preventivo')

@section('content')
    <h1 class="h3 mb-3">Nuovo preventivo</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.crm.quotes.store') }}">
        @include('crm::quotes._form')
    </form>
@endsection
