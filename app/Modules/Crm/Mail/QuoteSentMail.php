<?php

namespace App\Modules\Crm\Mail;

use App\Modules\Crm\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quote $quote;
    public string $acceptUrl;
    public array $company;
    public string $pdfContent;

    public function __construct(Quote $quote, string $pdfContent, string $acceptUrl, array $company)
    {
        $this->quote      = $quote;
        $this->pdfContent = $pdfContent;
        $this->acceptUrl  = $acceptUrl;
        $this->company    = $company;
    }

    public function build()
    {
        return $this->subject('Offerta ' . $this->quote->number)
            ->view('crm::email.quote_sent')
            ->attachData(
                $this->pdfContent,
                'Offerta-' . $this->quote->number . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
