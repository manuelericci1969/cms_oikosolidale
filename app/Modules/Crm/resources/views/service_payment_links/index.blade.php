@extends('admin.layout')

@section('title', 'Link pagamento servizio')

@section('content')
    @php
        $defaultAmount = $service->renew_price_gross ? number_format((float) $service->renew_price_gross, 2, '.', '') : '';
        $defaultDescription = 'Rinnovo ' . ($service->name ?: optional($service->product)->name ?: 'servizio');
        if ($service->expires_at) {
            $defaultDescription .= ' - scadenza ' . $service->expires_at->format('d/m/Y');
        }
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Link pagamento servizio</h1>
            <div class="text-muted small">
                Cliente: <strong>{{ optional($service->customer)->name }}</strong> ·
                Servizio: <strong>{{ $service->name ?: optional($service->product)->name ?: '—' }}</strong>
                @if($service->expires_at)
                    · Scadenza attuale: <strong>{{ $service->expires_at->format('d/m/Y') }}</strong>
                @endif
            </div>
        </div>

        <a href="{{ route('admin.crm.services.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Servizi
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('payment_url'))
        <div class="alert alert-info">
            <div class="fw-semibold mb-1">Ultimo link generato</div>
            <div class="input-group">
                <input type="text" class="form-control" id="last-payment-url" value="{{ session('payment_url') }}" readonly>
                <button type="button" class="btn btn-outline-primary" onclick="navigator.clipboard.writeText(document.getElementById('last-payment-url').value)">
                    Copia
                </button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Crea nuovo link Stripe</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.services.payment-links.store', $service) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Importo</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.50" name="amount" class="form-control" value="{{ old('amount', $defaultAmount) }}">
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="form-text">
                                Di default usa il prezzo rinnovo lordo del servizio.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrizione</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $defaultDescription) }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-credit-card"></i> Crea link pagamento
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Link generati</strong>
                    <span class="badge bg-light text-dark">{{ $paymentLinks->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Data</th>
                                <th>Importo</th>
                                <th>Stato</th>
                                <th>Rinnovo</th>
                                <th>Link</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($paymentLinks as $paymentLink)
                                @php
                                    $renewal = data_get($paymentLink->metadata, 'renewal');
                                @endphp
                                <tr>
                                    <td>
                                        {{ $paymentLink->created_at?->format('d/m/Y H:i') }}
                                        @if($paymentLink->paid_at)
                                            <div class="small text-success">Pagato il {{ $paymentLink->paid_at->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        {{ number_format((float) $paymentLink->amount, 2, ',', '.') }} {{ strtoupper($paymentLink->currency) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $paymentLink->status_badge_class }}">
                                            {{ $paymentLink->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($renewal)
                                            <div class="small text-success fw-semibold">
                                                Scadenza aggiornata
                                            </div>
                                            <div class="small text-muted">
                                                {{ data_get($renewal, 'old_expires_at') ?: '—' }} → {{ data_get($renewal, 'new_expires_at') ?: '—' }}
                                            </div>
                                        @else
                                            <span class="small text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 260px;">
                                        @if($paymentLink->stripe_url)
                                            <input type="text" class="form-control form-control-sm" id="payment-url-{{ $paymentLink->id }}" value="{{ $paymentLink->stripe_url }}" readonly>
                                        @else
                                            <span class="text-muted">N/D</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @if($paymentLink->stripe_url)
                                                <button type="button" class="btn btn-outline-secondary" title="Copia link" onclick="navigator.clipboard.writeText(document.getElementById('payment-url-{{ $paymentLink->id }}').value)">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                                <a href="{{ $paymentLink->stripe_url }}" class="btn btn-outline-primary" target="_blank" rel="noopener" title="Apri link">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            @endif

                                            <form method="POST" action="{{ route('admin.crm.services.payment-links.refresh', [$service, $paymentLink]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Verifica stato Stripe">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.crm.services.payment-links.email', [$service, $paymentLink]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary" title="Invia email">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.crm.services.payment-links.whatsapp', [$service, $paymentLink]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Invia WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Nessun link pagamento generato per questo servizio.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($paymentLinks->hasPages())
                    <div class="card-footer">
                        {{ $paymentLinks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
