<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Quote;
use App\Modules\Crm\Models\QuotePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotePaymentController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function store(Request $request, Quote $quote)
    {
        $clientId = $this->clientId($request);

        if ((int) $quote->client_id !== $clientId) {
            abort(403);
        }

        if ($quote->status !== 'accepted') {
            return back()->with('error', 'Puoi registrare pagamenti solo su preventivi accettati.');
        }

        $data = $request->validate([
            'payment_date'   => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference'      => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $quote, $clientId) {
            QuotePayment::create([
                'client_id'      => $clientId,
                'quote_id'       => $quote->id,
                'payment_date'   => $data['payment_date'],
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Pagamento registrato con successo.');
    }

    public function update(Request $request, Quote $quote, QuotePayment $payment)
    {
        $clientId = $this->clientId($request);

        if ((int) $quote->client_id !== $clientId || (int) $payment->client_id !== $clientId) {
            abort(403);
        }

        if ((int) $payment->quote_id !== (int) $quote->id) {
            abort(404);
        }

        if ($quote->status !== 'accepted') {
            return back()->with('error', 'Puoi modificare pagamenti solo su preventivi accettati.');
        }

        $data = $request->validate([
            'payment_date'   => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference'      => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $payment) {
            $payment->update([
                'payment_date'   => $data['payment_date'],
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Pagamento aggiornato con successo.');
    }

    public function destroy(Request $request, Quote $quote, QuotePayment $payment)
    {
        $clientId = $this->clientId($request);

        if ((int) $quote->client_id !== $clientId || (int) $payment->client_id !== $clientId) {
            abort(403);
        }

        if ((int) $payment->quote_id !== (int) $quote->id) {
            abort(404);
        }

        if ($quote->status !== 'accepted') {
            return back()->with('error', 'Puoi eliminare pagamenti solo su preventivi accettati.');
        }

        $payment->delete();

        return back()->with('success', 'Pagamento eliminato con successo.');
    }
}
