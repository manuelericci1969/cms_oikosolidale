<?php

namespace App\Jobs;

use App\Mail\CampaignEmail;
use App\Modules\Crm\Models\Campaign;
use App\Modules\Crm\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $campaignId,
        public int $recipientId
    ) {}

    public function handle(): void
    {
        // Recupero destinatario
        $recipient = CampaignRecipient::find($this->recipientId);
        if (!$recipient || !in_array($recipient->status, ['pending', 'queued'], true)) {
            // Se non esiste o è già in altro stato, non faccio nulla
            return;
        }

        // Recupero campagna
        $campaign = Campaign::find($this->campaignId);
        if (!$campaign) {
            return;
        }

        try {
            Mail::to($recipient->email)
                ->send(
                    (new CampaignEmail($campaign, $recipient))
                        ->withSymfonyMessage(function ($message) use ($recipient) {
                            // Header personalizzato per tracciare l'ID destinatario
                            $message->getHeaders()->addTextHeader(
                                'X-CRM-Recipient-ID',
                                (string) $recipient->id
                            );

                            // Message-ID generato dal mailer
                            $messageIdHeader = $message->getHeaders()->get('Message-ID');
                            $messageId = $messageIdHeader
                                ? $messageIdHeader->getBodyAsString()
                                : null;

                            $recipient->update([
                                'provider'            => 'smtp2go',   // o 'ses', ecc.
                                'provider_message_id' => $messageId,
                            ]);
                        })
                );

            // Se arrivo qui, l'email è stata inviata senza eccezioni
            $recipient->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

            $campaign->increment('sent_count');
        } catch (\Throwable $e) {
            // Loggo l'errore
            report($e);

            $dataError = [
                'status' => 'failed',
            ];

            // Se hai aggiunto la colonna last_error la usiamo, altrimenti evitiamo SQL error
            if (Schema::hasColumn('crm_campaign_recipients', 'last_error')) {
                $dataError['last_error'] = substr($e->getMessage(), 0, 1000);
            }

            $recipient->update($dataError);
        }
    }
}
