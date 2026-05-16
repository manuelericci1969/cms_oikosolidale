@extends('admin.layout')

@section('title', 'Preventivi CRM')

@section('content')
    @php
        $mode = $mode ?? 'accepted';
        $isAcceptedMode = $mode === 'accepted';
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Preventivi</h1>
            <div class="text-muted small">
                {{ $isAcceptedMode ? 'Elenco preventivi accettati con stato pagamenti' : 'Elenco preventivi inviati, bozze e rifiutati' }}
            </div>
        </div>

        <div class="d-flex gap-2">
            @if($isAcceptedMode)
                <form method="POST"
                      action="{{ route('admin.crm.contracts.regenerate-missing') }}"
                      onsubmit="return confirm('Rigenerare i contratti mancanti per tutti i preventivi accettati?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="bi bi-arrow-clockwise"></i> Rigenera contratti mancanti
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.crm.quotes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nuovo preventivo
            </a>
        </div>
    </div>

    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('admin.crm.quotes.index', ['mode' => 'accepted']) }}"
           class="btn {{ $isAcceptedMode ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-cash-coin"></i> Accettati / Incassi
        </a>

        <a href="{{ route('admin.crm.quotes.index', ['mode' => 'pending']) }}"
           class="btn {{ !$isAcceptedMode ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-hourglass-split"></i> Inviati / Bozze / Rifiutati
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Totale</th>

                        @if($isAcceptedMode)
                            <th>Incassato</th>
                            <th>Residuo</th>
                            <th>Pagamento</th>
                            <th>Contratto</th>
                        @else
                            <th>Stato</th>
                            <th>Validità</th>
                        @endif

                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($quotes as $quote)
                        @php
                            $isAccepted = $quote->status === 'accepted';
                            $latestContract = $isAccepted ? $quote->latestContract : null;

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

                        <tr>
                            <td>{{ $quote->number }}</td>
                            <td>{{ optional($quote->customer)->name }}</td>
                            <td>{{ $quote->date?->format('d/m/Y') }}</td>
                            <td>{{ number_format($quote->total, 2, ',', '.') }} {{ $quote->currency }}</td>

                            @if($isAcceptedMode)
                                <td class="text-success fw-semibold">
                                    {{ number_format($quote->paid_total, 2, ',', '.') }} {{ $quote->currency }}
                                </td>
                                <td class="fw-semibold {{ $quote->remaining_total > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($quote->remaining_total, 2, ',', '.') }} {{ $quote->currency }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $paymentBadgeClass }}">
                                        {{ $quote->payment_status_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($latestContract)
                                        <span class="badge bg-success">Salvato</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Da generare</span>
                                    @endif
                                </td>
                            @else
                                <td>
                                    <span class="badge bg-{{ $statusBadgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    @if($quote->valid_until)
                                        {{ $quote->valid_until->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif

                            <td class="text-end">
                                @if(!$isAccepted)
                                    <a href="{{ route('admin.crm.quotes.edit', $quote) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Modifica preventivo">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.crm.quotes.contract.accept-paper', $quote) }}"
                                          class="d-inline-block"
                                          onsubmit="return confirm('Confermi l\'accettazione manuale/cartacea di questo preventivo? Verrà generato il contratto PDF da stampare.');">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-success"
                                                title="Accetta manualmente / firma cartacea">
                                            <i class="bi bi-pen"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.crm.quotes.destroy', $quote) }}"
                                          method="post"
                                          class="d-inline-block"
                                          onsubmit="return confirm('Eliminare questo preventivo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"
                                                title="Elimina preventivo">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    @if($latestContract)
                                        <a href="{{ route('admin.crm.contracts.download', $latestContract) }}"
                                           class="btn btn-sm btn-outline-success"
                                           title="Scarica contratto">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('admin.crm.quotes.billing-data.edit', $quote) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Modifica dati emittente e coordinate">
                                        <i class="bi bi-buildings"></i>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.crm.quotes.contract.regenerate', $quote) }}"
                                          class="d-inline-block"
                                          onsubmit="return confirm('Rigenerare il contratto di questo preventivo?');">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Rigenera contratto">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </form>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            disabled
                                            title="Preventivo accettato: non può essere eliminato">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                @endif

                                <a href="{{ route('admin.crm.quotes.show', $quote) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="Visualizza preventivo">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAcceptedMode ? '9' : '7' }}" class="text-center text-muted py-4">
                                Nessun preventivo trovato.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($quotes->hasPages())
            <div class="card-footer">
                {{ $quotes->links() }}
            </div>
        @endif
    </div>
@endsection
