@extends('admin.layout')

@section('title', 'Modifica preventivo')

@section('content')
    <h1 class="h3 mb-3">Modifica preventivo {{ $quote->number }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.crm.quotes.update', $quote) }}">
        @method('PUT')
        @include('crm::quotes._form')
    </form>
@endsection
