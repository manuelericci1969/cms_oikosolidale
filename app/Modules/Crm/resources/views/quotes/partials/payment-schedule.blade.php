@if(($quote->payment_type ?? 'free_text') === 'structured' && count($quote->payment_schedule_rows ?? []))
    <div class="mt-4">
        <h6 class="text-uppercase text-muted small">Piano di pagamento</h6>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Voce</th>
                    <th>Scadenza</th>
                    <th class="text-end">Importo</th>
                </tr>
                </thead>
                <tbody>
                @foreach($quote->payment_schedule_rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['due_date_label'] }}</td>
                        <td class="text-end fw-semibold">
                            {{ number_format($row['amount'], 2, ',', '.') }} {{ $quote->currency }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Totale piano pagamenti</th>
                    <th class="text-end">
                        {{ number_format($quote->payment_schedule_total, 2, ',', '.') }} {{ $quote->currency }}
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif
