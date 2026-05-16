<?php

namespace App\Modules\Crm\Mail;

use App\Modules\Crm\Models\ServicePaymentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServicePaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServicePaymentLink $paymentLink)
    {
        $this->paymentLink->loadMissing('service.customer', 'service.product', 'customer');
    }

    public function build(): self
    {
        $service = $this->paymentLink->service;
        $serviceName = $service->name ?: optional($service->product)->name ?: 'servizio';

        return $this
            ->subject('Link pagamento rinnovo servizio: ' . $serviceName)
            ->view('crm::emails.service-payment-link')
            ->with([
                'paymentLink' => $this->paymentLink,
                'service' => $service,
                'customer' => $this->paymentLink->customer ?: $service->customer,
            ]);
    }
}
