<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\Contract;
use App\Modules\Crm\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractGeneratorService
{
    public function __construct(
        protected BillingProfileDataService $billingData
    ) {}

    public function generateFromQuote(Quote $quote, bool $force = false): Contract
    {
        $quote->loadMissing('customer', 'items', 'billingProfile');

        if ($quote->status !== 'accepted' || !$quote->accepted_at) {
            throw new \RuntimeException('Il contratto può essere generato solo per preventivi accettati.');
        }

        if (!$quote->customer) {
            throw new \RuntimeException('Preventivo senza cliente associato.');
        }

        $contract = Contract::firstOrNew([
            'client_id' => (int) $quote->client_id,
            'quote_id'  => (int) $quote->id,
        ]);

        $hasValidPdf = $contract->exists
            && $contract->pdf_path
            && Storage::disk('local')->exists($contract->pdf_path);

        if ($hasValidPdf && !$force) {
            return $contract;
        }

        $company = $this->billingData->companyDataForQuote($quote);

        $pdf = Pdf::loadView('crm::contracts.contract_pdf', [
            'quote'       => $quote,
            'company'     => $company,
            'logoDataUri' => null,
        ])->setPaper('a4', 'portrait');

        $pdfBinary = $pdf->output();

        $folder = 'crm/contracts/' . now()->format('Y/m');
        $safeNumber = Str::slug((string) ($quote->number ?: $quote->id));
        $fileName = 'contratto-preventivo-' . $safeNumber . '-' . now()->format('YmdHis') . '.pdf';
        $path = $folder . '/' . $fileName;

        Storage::disk('local')->put($path, $pdfBinary);

        $contract->fill([
            'client_id'           => (int) $quote->client_id,
            'customer_id'         => (int) $quote->customer_id,
            'quote_id'            => (int) $quote->id,
            'service_id'          => null,
            'number'              => $quote->number ? 'CTR-' . $quote->number : null,
            'title'               => 'Contratto da preventivo ' . ($quote->number ?: '#' . $quote->id),
            'type'                => 'digital',
            'status'              => 'accepted',
            'pdf_path'            => $path,
            'generated_at'        => now(),
            'accepted_at'         => $quote->accepted_at,
            'accepted_ip'         => $quote->accepted_ip,
            'accepted_user_agent' => $quote->accepted_user_agent,
        ]);

        $contract->save();

        return $contract;
    }

    public function pdfBinary(Contract $contract): string
    {
        if (!$contract->pdf_path || !Storage::disk('local')->exists($contract->pdf_path)) {
            throw new \RuntimeException('File PDF del contratto non trovato.');
        }

        return Storage::disk('local')->get($contract->pdf_path);
    }
}
