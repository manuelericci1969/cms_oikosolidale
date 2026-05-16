<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Contract;
use App\Modules\Crm\Models\Quote;
use App\Modules\Crm\Services\ContractGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function download(Request $request, Contract $contract)
    {
        $clientId = $this->clientId($request);

        if ((int) $contract->client_id !== $clientId) {
            abort(403);
        }

        if (!$contract->pdf_path || !Storage::disk('local')->exists($contract->pdf_path)) {
            return back()->with('error', 'Il file PDF del contratto non esiste o non è stato salvato.');
        }

        $fileName = ($contract->number ?: 'contratto-' . $contract->id) . '.pdf';

        return Storage::disk('local')->download($contract->pdf_path, $fileName);
    }

    public function acceptPaper(Request $request, Quote $quote, ContractGeneratorService $generator)
    {
        $clientId = $this->clientId($request);

        if ((int) $quote->client_id !== $clientId) {
            abort(403);
        }

        if (!$quote->customer_id) {
            return back()->with('error', 'Impossibile accettare manualmente: il preventivo non ha un cliente associato.');
        }

        try {
            if ($quote->status !== 'accepted') {
                $quote->accepted_at = now();
                $quote->accepted_ip = $request->ip();
                $quote->accepted_user_agent = 'Accettazione manuale/cartacea da area admin - ' . substr((string) $request->userAgent(), 0, 180);
                $quote->status = 'accepted';
                $quote->save();
            }

            $contract = $generator->generateFromQuote($quote, true);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore durante l\'accettazione manuale e la generazione del contratto: ' . $e->getMessage());
        }

        return back()->with('success', 'Preventivo accettato manualmente. Contratto generato e pronto per la stampa: ' . ($contract->number ?: '#' . $contract->id));
    }

    public function regenerateFromQuote(Request $request, Quote $quote, ContractGeneratorService $generator)
    {
        $clientId = $this->clientId($request);

        if ((int) $quote->client_id !== $clientId) {
            abort(403);
        }

        if ($quote->status !== 'accepted' || !$quote->accepted_at) {
            return back()->with('error', 'Puoi rigenerare il contratto solo per preventivi accettati.');
        }

        try {
            $contract = $generator->generateFromQuote($quote, true);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Errore durante la rigenerazione del contratto: ' . $e->getMessage());
        }

        return back()->with('success', 'Contratto rigenerato correttamente: ' . ($contract->number ?: '#' . $contract->id));
    }

    public function regenerateMissing(Request $request, ContractGeneratorService $generator)
    {
        $clientId = $this->clientId($request);

        $quotes = Quote::with('customer', 'items')
            ->where('client_id', $clientId)
            ->where('status', 'accepted')
            ->whereNotNull('accepted_at')
            ->orderBy('id')
            ->get();

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($quotes as $quote) {
            try {
                $existing = Contract::where('client_id', $clientId)
                    ->where('quote_id', $quote->id)
                    ->first();

                $hasPdf = $existing
                    && $existing->pdf_path
                    && Storage::disk('local')->exists($existing->pdf_path);

                if ($hasPdf) {
                    $skipped++;
                    continue;
                }

                $generator->generateFromQuote($quote, false);
                $generated++;
            } catch (\Throwable $e) {
                $errors++;
                report($e);
            }
        }

        return back()->with(
            'success',
            "Rigenerazione completata. Generati: {$generated}. Già presenti: {$skipped}. Errori: {$errors}."
        );
    }
}
