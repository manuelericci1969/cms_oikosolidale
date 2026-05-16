<?php

namespace App\Mail;

use App\Modules\Crm\Models\Campaign;
use App\Modules\Crm\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient
    ) {}

    public function build()
    {
        $mail = $this->subject($this->campaign->subject);

        if ($this->campaign->from_email) {
            $mail->from(
                $this->campaign->from_email,
                $this->campaign->from_name ?: config('mail.from.name')
            );
        }

        if ($this->campaign->reply_to_email) {
            $mail->replyTo($this->campaign->reply_to_email);
        }

        return $mail->view('crm::email.campaigns.default', [
            'campaign'  => $this->campaign,
            'recipient' => $this->recipient,
        ]);
    }
}
