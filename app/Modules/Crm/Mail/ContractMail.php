<?php

namespace App\Modules\Crm\Mail;

use App\Modules\Crm\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quote $quote;
    public array $company;
    public string $pdfContent;

    public function __construct(Quote $quote, array $company, string $pdfContent)
    {
        $this->quote      = $quote->load('customer'); // ok: carichi già il cliente
        $this->company    = $company;
        $this->pdfContent = $pdfContent;
    }

    public function build(): self
    {
        return $this
            ->subject('Contratto relativo al preventivo '.$this->quote->number)
            // corretto: namespace del modulo + cartella "email" singolare
            ->view('crm::email.contract')
            ->attachData(
                $this->pdfContent,
                'Contratto-'.$this->quote->number.'.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
