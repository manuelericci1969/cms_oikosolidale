<?php

namespace App\Modules\Crm\Mail;

use App\Modules\Crm\Models\Service;
use App\Modules\Crm\Models\ServiceReminderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceExpirationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Service $service;
    public ?object $customer;
    public ServiceReminderLog $log;
    public string $bodyText;
    public string $subjectLine;

    public function __construct(Service $service, ServiceReminderLog $log, string $bodyText, string $subjectLine)
    {
        $this->service     = $service;
        $this->customer    = $service->customer;
        $this->log         = $log;
        $this->bodyText    = $bodyText;
        $this->subjectLine = $subjectLine;
    }

    public function build(): self
    {
        // non tocchiamo il log qui, subject/body sono già salvati nel controller
        return $this->subject($this->subjectLine)
            ->view('crm::email.service_expiration_reminder');
    }
}
