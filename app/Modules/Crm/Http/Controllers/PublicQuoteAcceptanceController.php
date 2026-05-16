<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Mail\QuoteAcceptanceCodeMail;
use App\Modules\Crm\Mail\QuoteContractMail;
use App\Modules\Crm\Models\Quote;
use App\Modules\Crm\Services\ContractGeneratorService;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicQuoteAcceptanceController extends Controller
{
    public function show(Request $request, string $token)
    {
        $quote = Quote::with('customer')
            ->where('acceptance_token', $token)
            ->firstOrFail();

        // Link scaduto (validità = valid_until)
        if ($quote->valid_until && now()->greaterThan($quote->valid_until)) {
            return view('crm::public.quotes.expired', compact('quote'));
        }

        // Già accettato
        if ($quote->accepted_at) {
            return view('crm::public.quotes.already_accepted', compact('quote'));
        }

        // Primo click -> registro IP + data
        if (!$quote->accept_click_at) {
            $quote->accept_click_at = now();
            $quote->accept_click_ip = $request->ip();
        }

        // Genero/rigenero codice se mancante o scaduto
        if (
            !$quote->acceptance_code ||
            !$quote->acceptance_code_expires_at ||
            now()->greaterThan($quote->acceptance_code_expires_at)
        ) {
            $code = (string) random_int(100000, 999999);

            $quote->acceptance_code            = $code;
            $quote->acceptance_code_sent_at    = now();
            $quote->acceptance_code_expires_at = now()->addMinutes(30);

            $company = [
                'name'     => Setting::get('company.name'),
                'vat'      => Setting::get('company.vat'),
                'address'  => Setting::get('company.address'),
                'city'     => Setting::get('company.city'),
                'zip'      => Setting::get('company.zip'),
                'province' => Setting::get('company.province'),
                'country'  => Setting::get('company.country'),
                'email'    => Setting::get('company.email'),
                'phone'    => Setting::get('company.phone'),
            ];

            if ($quote->customer && $quote->customer->email) {
                Mail::to($quote->customer->email)
                    ->send(new QuoteAcceptanceCodeMail($quote, $code, $company));
            }
        }

        $quote->save();

        return view('crm::public.quotes.accept', compact('quote'));
    }

    public function confirm(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        $quote = Quote::with('customer', 'items')
            ->where('acceptance_token', $token)
            ->firstOrFail();

        // Link scaduto
        if ($quote->valid_until && now()->greaterThan($quote->valid_until)) {
            return back()
                ->withErrors(['code' => 'Il link di conferma è scaduto.'])
                ->withInput();
        }

        // Già accettato
        if ($quote->accepted_at) {
            return view('crm::public.quotes.already_accepted', compact('quote'));
        }

        // Codice scaduto o mancante
        if (
            !$quote->acceptance_code ||
            !$quote->acceptance_code_expires_at ||
            now()->greaterThan($quote->acceptance_code_expires_at)
        ) {
            return back()
                ->withErrors(['code' => 'Il codice è scaduto: riapra il link per riceverne uno nuovo.'])
                ->withInput();
        }

        // Codice errato
        if (trim($data['code']) !== trim($quote->acceptance_code)) {
            return back()
                ->withErrors(['code' => 'Il codice inserito non è corretto.'])
                ->withInput();
        }

        // OK: accetto il preventivo e salvo IP / user agent
        $quote->accepted_at         = now();
        $quote->accepted_ip         = $request->ip();
        $quote->accepted_user_agent = substr((string) $request->userAgent(), 0, 255);
        $quote->status              = 'accepted';
        $quote->save();

        /**
         * Dopo l’accettazione:
         *  - genero e salvo il PDF del contratto nell'archivio CRM
         *  - lo invio al cliente via email
         */

        // Dati azienda
        $company = [
            'name'     => Setting::get('company.name'),
            'vat'      => Setting::get('company.vat'),
            'address'  => Setting::get('company.address'),
            'city'     => Setting::get('company.city'),
            'zip'      => Setting::get('company.zip'),
            'province' => Setting::get('company.province'),
            'country'  => Setting::get('company.country', 'IT'),
            'email'    => Setting::get('company.email'),
            'phone'    => Setting::get('company.phone'),
            'bank'     => Setting::get('company.bank'),
            'iban'     => Setting::get('company.iban'),
            'bic'      => Setting::get('company.bic'),
            'pec'      => Setting::get('company.pec'),
            'sdi'      => Setting::get('company.sdi'),
        ];

        // Se vuoi il logo nel PDF puoi passare un data URI,
        // qui per semplicità lo lasciamo nullo:
        $logoDataUri = null;

        try {
            $generator = app(ContractGeneratorService::class);
            $contract = $generator->generateFromQuote($quote);
            $pdfBinary = $generator->pdfBinary($contract);
        } catch (\Throwable $e) {
            // Non blocchiamo il cliente se l'archiviazione fallisce:
            // manteniamo il vecchio comportamento e inviamo comunque il PDF via email.
            report($e);

            $pdf = Pdf::loadView('crm::contracts.pdf', [
                'quote'       => $quote,
                'company'     => $company,
                'logoDataUri' => $logoDataUri,
            ])->setPaper('a4', 'portrait');

            $pdfBinary = $pdf->output();
        }

        // Invia email con contratto allegato (se c'è l'indirizzo)
        if ($quote->customer && $quote->customer->email) {
            try {
                Mail::to($quote->customer->email)->send(
                    new QuoteContractMail($quote, $company, $pdfBinary)
                );
            } catch (\Throwable $e) {
                // non blocchiamo l'utente, ma logghiamo l'errore
                report($e);
            }
        }

        return view('crm::public.quotes.accepted', compact('quote'));
    }
}
