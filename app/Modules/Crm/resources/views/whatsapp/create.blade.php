@extends('admin.layout')

@section('title', 'Nuovo messaggio WhatsApp')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Nuovo messaggio WhatsApp</h1>

        <a href="{{ route('admin.crm.whatsapp.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.crm.whatsapp.store') }}">
                @csrf

                <input type="hidden" name="lead_id" value="{{ old('lead_id', $lead?->id) }}">
                <input type="hidden" name="customer_id" value="{{ old('customer_id', $customer?->id) }}">

                @if($lead)
                    <div class="alert alert-info">
                        <div class="fw-semibold">Invio collegato a lead</div>
                        <div>
                            Lead #{{ $lead->id }} — {{ $lead->name }}
                            @if($lead->phone)
                                | {{ $lead->phone }}
                            @endif
                        </div>
                    </div>
                @endif

                @if($customer)
                    <div class="alert alert-info">
                        <div class="fw-semibold">Invio collegato a cliente</div>
                        <div>
                            Cliente #{{ $customer->id }} — {{ $customer->name }}
                            @if($customer->phone)
                                | {{ $customer->phone }}
                            @endif
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome destinatario</label>
                        <input type="text"
                               name="recipient_name"
                               class="form-control @error('recipient_name') is-invalid @enderror"
                               value="{{ old('recipient_name', $recipientName) }}"
                               placeholder="Es. Mario Rossi">
                        @error('recipient_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Numero WhatsApp *</label>
                        <input type="text"
                               name="recipient_phone"
                               class="form-control @error('recipient_phone') is-invalid @enderror"
                               value="{{ old('recipient_phone', $recipientPhone) }}"
                               placeholder="Es. 3472713283 oppure +393472713283"
                               required>
                        @error('recipient_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Puoi inserire il numero in formato nazionale o internazionale.
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Messaggio *</label>
                    <textarea name="message"
                              rows="10"
                              class="form-control @error('message') is-invalid @enderror"
                              required>{{ old('message', $messageText) }}</textarea>
                    @error('message')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-whatsapp"></i> Invia messaggio
                    </button>

                    <a href="{{ route('admin.crm.whatsapp.index') }}" class="btn btn-secondary">
                        Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
