<?php

namespace App\Modules\Crm\Mail;

use App\Modules\Crm\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quote $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote->load('customer', 'items');
    }

    public function build(): self
    {
        return $this
            ->subject('Preventivo '.$this->quote->number)
            ->view('crm::emails.quote');
    }
}
