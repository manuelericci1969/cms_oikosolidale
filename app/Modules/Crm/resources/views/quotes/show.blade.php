@extends('admin.layout')

@section('title', 'Preventivo '.$quote->number)

@section('content')
    @php
        $statusBadgeClass = match($quote->status) {
            'accepted' => 'success',
            'sent' => 'info',
            'draft' => 'secondary',
            'rejected' => 'danger',
            default => 'secondary',
        };

        $statusLabel = match($quote->status) {
            'accepted' => 'Accettato',
            'sent' => 'Inviato',
            'draft' => 'Bozza',
            'rejected' => 'Rifiutato',
            default => ucfirst($quote->status),
        };

        $paymentBadgeClass = match($quote->payment_status) {
            'paid' => 'success',
            'partial' => 'warning',
            default => 'secondary',
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Preventivo {{ $quote->number }}</h1>
            <div class="text-muted small">
                Data: {{ $quote->date?->format('d/m/Y') }}
                @if($quote->valid_until)
                    · Valido fino al: {{ $quote->valid_until?->format('d/m/Y') }}
                @endif
                · Stato: <span class="badge bg-{{ $statusBadgeClass }}">{{ $statusLabel }}</span>
            </div>

            @if($quote->intro_text)
                <div class="mt-3 mb-3">
                    {!! nl2br(e($quote->intro_text)) !!}
                </div>
            @endif
        </div>

        <div class="d-print-none">
            <a href="{{ route('admin.crm.quotes.pdf', $quote) }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>

            <form action="{{ route('admin.crm.quotes.send', $quote) }}" method="post" class="d-inline">
                @csrf
                <button class="btn btn-primary">
                    <i class="bi bi-envelope"></i> Invia al Cliente
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-print-none">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger d-print-none">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <h6 class="text-uppercase text-muted small">Destinatario</h6>
            <p class="mb-2">
                <strong>{{ $quote->customer?->name }}</strong><br>
                @if($quote->customer?->billing_address)
                    {{ $quote->customer->billing_address }}<br>
                @endif
                @if($quote->customer?->billing_zip || $quote->customer?->billing_city)
                    {{ $quote->customer->billing_zip }} {{ $quote->customer->billing_city }}<br>
                @endif
                @if($quote->customer?->billing_province)
                    ({{ $quote->customer->billing_province }})<br>
                @endif
                @if($quote->customer?->vat_number)
                    P.IVA: {{ $quote->customer->vat_number }}<br>
                @endif
                @if($quote->customer?->tax_code)
                    C.F.: {{ $quote->customer->tax_code }}<br>
                @endif
            </p>

            @if($quote->customer?->email)
                <p class="mb-0 small text-muted">Email: {{ $quote->customer->email }}</p>
            @endif
        </div>

        <div class="col-md-6 text-md-end">
            <h6 class="text-uppercase text-muted small">Dati preventivo</h6>
            <p class="mb-2">
                Numero: <strong>{{ $quote->number }}</strong><br>
                Data: <strong>{{ $quote->date?->format('d/m/Y') }}</strong><br>
                @if($quote->valid_until)
                    Valido fino al: <strong>{{ $quote->valid_until?->format('d/m/Y') }}</strong><br>
                @endif
                Valuta: <strong>{{ $quote->currency }}</strong>
            </p>
        </div>
    </div>

    <hr>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
            <tr>
                <th>Descrizione</th>
                <th class="text-end" style="width:8%">Qtà</th>
                <th class="text-end" style="width:10%">Prezzo</th>
                <th class="text-end" style="width:10%">Sconto</th>
                <th class="text-end" style="width:10%">IVA</th>
                <th class="text-end" style="width:12%">Totale</th>
            </tr>
            </thead>
            <tbody>
            @foreach($quote->items as $item)
                @php
                    $lineBase     = $item->quantity * $item->unit_price;
                    $lineDiscount = $lineBase * ($item->discount_percent / 100);
                    $lineNet      = $lineBase - $lineDiscount;
                    $lineTax      = $lineNet * ($item->tax_rate / 100);
                    $lineTotal    = $lineNet + $lineTax;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong><br>
                        <span class="text-muted small">
                            {{ $item->quantity }} {{ $item->unit }} x
                            {{ number_format($item->unit_price, 2, ',', '.') }} €
                        </span>
                    </td>
                    <td class="text-end">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td class="text-end">
                        @if($item->discount_percent > 0)
                            {{ number_format($item->discount_percent, 2, ',', '.') }} %
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($item->tax_rate, 2, ',', '.') }} %</td>
                    <td class="text-end">{{ number_format($lineTotal, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="row justify-content-end mt-3">
        <div class="col-md-4">
            <table class="table table-sm">
                <tr>
                    <th>Imponibile</th>
                    <td class="text-end">{{ number_format($quote->subtotal, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <th>Sconti</th>
                    <td class="text-end">- {{ number_format($quote->discount_total, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <th>Imposte</th>
                    <td class="text-end">{{ number_format($quote->tax_total, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <th>Totale</th>
                    <td class="text-end fw-bold">{{ number_format($quote->total, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
    </div>

    @include('crm::quotes.partials.payment-schedule', ['quote' => $quote])

    @if($quote->payment_terms)
        <div class="mt-4">
            <h6 class="text-uppercase text-muted small">Condizioni di pagamento</h6>
            <p class="mb-0">{!! nl2br(e($quote->payment_terms)) !!}</p>
        </div>
    @endif

    @if($quote->notes)
        <div class="mt-4">
            <h6 class="text-uppercase text-muted small">Note</h6>
            <p class="mb-0">{{ $quote->notes }}</p>
        </div>
    @endif

    <hr class="my-4">

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Totale preventivo</div>
                    <div class="fs-5 fw-bold">{{ number_format($quote->total, 2, ',', '.') }} {{ $quote->currency }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Totale incassato</div>
                    <div class="fs-5 fw-bold text-success">{{ number_format($quote->paid_total, 2, ',', '.') }} {{ $quote->currency }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Residuo</div>
                    <div class="fs-5 fw-bold text-danger">{{ number_format($quote->remaining_total, 2, ',', '.') }} {{ $quote->currency }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Stato pagamento</div>
                    <div><span class="badge bg-{{ $paymentBadgeClass }}">{{ $quote->payment_status_label }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 d-print-none">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Pagamenti ricevuti</strong>

            @if($quote->status === 'accepted')
                <span class="badge bg-success">Preventivo accettato</span>
            @else
                <span class="badge bg-secondary">Registrazione pagamenti disponibile solo su preventivi accettati</span>
            @endif
        </div>

        <div class="card-body">
            @if($quote->status === 'accepted')
                <form method="POST" action="{{ route('admin.crm.quotes.payments.store', $quote) }}" class="row g-3 mb-4">
                    @csrf

                    <div class="col-md-2">
                        <label class="form-label">Data *</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Importo *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Metodo</label>
                        <select name="payment_method" class="form-select">
                            <option value="">-- seleziona --</option>
                            <option value="bonifico" {{ old('payment_method') === 'bonifico' ? 'selected' : '' }}>Bonifico</option>
                            <option value="contanti" {{ old('payment_method') === 'contanti' ? 'selected' : '' }}>Contanti</option>
                            <option value="carta" {{ old('payment_method') === 'carta' ? 'selected' : '' }}>Carta</option>
                            <option value="assegno" {{ old('payment_method') === 'assegno' ? 'selected' : '' }}>Assegno</option>
                            <option value="paypal" {{ old('payment_method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                            <option value="altro" {{ old('payment_method') === 'altro' ? 'selected' : '' }}>Altro</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Riferimento</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="CRO, TRN, ID transazione...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Note interne sul pagamento">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Registra pagamento
                        </button>
                    </div>
                </form>
            @else
                <div class="alert alert-info mb-4">
                    Per registrare pagamenti il preventivo deve essere nello stato <strong>Accettato</strong>.
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Importo</th>
                        <th>Metodo</th>
                        <th>Riferimento</th>
                        <th>Note</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($quote->payments as $payment)
                        <tr>
                            <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                            <td class="fw-semibold text-success">{{ number_format($payment->amount, 2, ',', '.') }} {{ $quote->currency }}</td>
                            <td>{{ $payment->payment_method ?: '—' }}</td>
                            <td>{{ $payment->reference ?: '—' }}</td>
                            <td>{{ $payment->notes ?: '—' }}</td>
                            <td class="text-end">
                                @if($quote->status === 'accepted')
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $payment->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form method="POST" action="{{ route('admin.crm.quotes.payments.destroy', [$quote, $payment]) }}" class="d-inline-block" onsubmit="return confirm('Eliminare questo pagamento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-lock"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessun pagamento registrato.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($quote->status === 'accepted' && $quote->payments->count())
        @foreach($quote->payments as $payment)
            <div class="modal fade" id="editPaymentModal{{ $payment->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.crm.quotes.payments.update', [$quote, $payment]) }}">
                            @csrf
                            @method('PUT')

                            <div class="modal-header">
                                <h5 class="modal-title">Modifica pagamento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Data *</label>
                                        <input type="date" name="payment_date" class="form-control" value="{{ optional($payment->payment_date)->format('Y-m-d') }}" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Importo *</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ number_format($payment->amount, 2, '.', '') }}" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Metodo</label>
                                        <select name="payment_method" class="form-select">
                                            <option value="">-- seleziona --</option>
                                            <option value="bonifico" {{ $payment->payment_method === 'bonifico' ? 'selected' : '' }}>Bonifico</option>
                                            <option value="contanti" {{ $payment->payment_method === 'contanti' ? 'selected' : '' }}>Contanti</option>
                                            <option value="carta" {{ $payment->payment_method === 'carta' ? 'selected' : '' }}>Carta</option>
                                            <option value="assegno" {{ $payment->payment_method === 'assegno' ? 'selected' : '' }}>Assegno</option>
                                            <option value="paypal" {{ $payment->payment_method === 'paypal' ? 'selected' : '' }}>PayPal</option>
                                            <option value="altro" {{ $payment->payment_method === 'altro' ? 'selected' : '' }}>Altro</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Riferimento</label>
                                        <input type="text" name="reference" class="form-control" value="{{ $payment->reference }}">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Note</label>
                                        <input type="text" name="notes" class="form-control" value="{{ $payment->notes }}">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Salva modifica</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection
