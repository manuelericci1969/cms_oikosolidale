<?php

namespace App\Modules\Crm\Mail;

use App\Modules\Crm\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuoteAcceptanceCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quote $quote;
    public string $code;
    public array $company;

    public function __construct(Quote $quote, string $code, array $company)
    {
        $this->quote   = $quote;
        $this->code    = $code;
        $this->company = $company;
    }

    public function build()
    {
        return $this->subject('Codice conferma preventivo ' . $this->quote->number)
            ->view('crm::email.quote_acceptance_code');
    }
}
