{{-- app/Modules/Crm/resources/views/public/leads/thankyou.blade.php --}}
@extends('layouts.app')

@section('title', 'Grazie per averci contattato')
@section('meta_description', 'La tua richiesta è stata inviata correttamente, ti contatteremo al più presto.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-lg-5 text-center">

                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            </div>

                            <h1 class="h3 mb-3">Grazie per averci contattato</h1>

                            <p class="text-muted mb-4">
                                La tua richiesta è stata inviata correttamente.<br>
                                Ti risponderemo il prima possibile ai recapiti che ci hai fornito.
                            </p>

                            <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                                <a href="{{ url('/') }}" class="btn btn-primary">
                                    Torna alla homepage
                                </a>

                                <a href="{{ route('crm.leads.form') }}" class="btn btn-outline-secondary">
                                    Invia un&#39;altra richiesta
                                </a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
