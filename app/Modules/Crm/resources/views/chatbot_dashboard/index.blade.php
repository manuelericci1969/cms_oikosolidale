@extends('admin.layout')

@section('title','Dashboard Chatbot AI')

@section('content')

    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Dashboard Chatbot AI</h1>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Conversazioni</h6>
                        <h2>{{ $conversations }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">FAQ Attive</h6>
                        <h2>{{ $faqActive }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Domande Nuove</h6>
                        <h2 class="text-danger">{{ $unknownQuestions }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Domande Risolte</h6>
                        <h2 class="text-success">{{ $resolvedQuestions }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Feedback 👍</h6>
                        <h2 class="text-success">{{ $feedbackPositive }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Feedback 👎</h6>
                        <h2 class="text-danger">{{ $feedbackNegative }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Lead Generati</h6>
                        <h2 class="text-primary">{{ $leads }}</h2>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
