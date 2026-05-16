@extends('admin.layout')

@section('title', 'Modifica FAQ Chatbot')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Modifica FAQ Chatbot</h1>
        <a href="{{ route('admin.crm.chatbot-faqs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alle FAQ
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.crm.chatbot-faqs.update', $faq) }}" method="post">
                @method('PUT')
                @include('crm::chatbot_faqs._form')
            </form>
        </div>
    </div>
@endsection
