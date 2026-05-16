@if(($quote->payment_type ?? 'free_text') === 'structured' && count($quote->payment_schedule_rows ?? []))
    <div class="section-block">
        <div class="meta-title">Piano di pagamento</div>

        <table class="payment-schedule-table">
            <thead>
            <tr>
                <th class="text-left">Voce</th>
                <th class="text-left">Scadenza</th>
                <th class="text-right">Importo</th>
            </tr>
            </thead>
            <tbody>
            @foreach($quote->payment_schedule_rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['due_date_label'] }}</td>
                    <td class="text-right">{{ number_format($row['amount'], 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th colspan="2" class="text-right">Totale piano pagamenti</th>
                <th class="text-right">{{ number_format($quote->payment_schedule_total, 2, ',', '.') }} €</th>
            </tr>
            </tfoot>
        </table>
    </div>
@endif
