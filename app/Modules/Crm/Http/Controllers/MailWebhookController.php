<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\CampaignRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailWebhookController extends Controller
{
    public function handle(Request $request, string $provider)
    {
        $payload = $request->all();

        Log::info("Mail webhook received", [
            'provider' => $provider,
            'payload'  => $payload,
        ]);

        switch ($provider) {
            case 'smtp2go':
                return $this->handleSmtp2Go($payload);

            case 'ses':
                return $this->handleSes($payload);

            default:
                return response()->json(['status' => 'unknown provider'], 400);
        }
    }

    protected function handleSmtp2Go(array $payload)
    {
        // ⚠️ esempio generico: da adattare alla struttura reale del JSON SMTP2Go
        foreach ($payload['emails'] ?? [] as $emailEvent) {
            $headers     = $emailEvent['headers'] ?? [];
            $recipientId = null;

            foreach ($headers as $header) {
                if (($header['name'] ?? '') === 'X-CRM-Recipient-ID') {
                    $recipientId = $header['value'] ?? null;
                    break;
                }
            }

            if (!$recipientId) {
                continue;
            }

            $recipient = CampaignRecipient::find($recipientId);
            if (!$recipient) {
                continue;
            }

            $event  = $emailEvent['event'] ?? null;  // delivered, bounce, spam, ...
            $reason = $emailEvent['reason'] ?? null;

            match ($event) {
                'delivered' => $this->markDelivered($recipient),
                'bounce'    => $this->markBounced($recipient, $reason),
                'spam'      => $this->markComplained($recipient),
                default     => null,
            };
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleSes(array $payload)
    {
        // qui in futuro parse payload SES
        return response()->json(['status' => 'ok']);
    }

    protected function markDelivered(CampaignRecipient $recipient): void
    {
        if (!$recipient->delivered_at) {
            $recipient->update([
                'delivered_at' => now(),
                'status'       => 'sent',
            ]);

            $recipient->campaign()->increment('delivered_count');
        }
    }

    protected function markBounced(CampaignRecipient $recipient, ?string $reason): void
    {
        if (!$recipient->bounced_at) {
            $recipient->update([
                'bounced_at' => now(),
                'status'     => 'bounced',
                'last_error' => $reason,
            ]);

            $recipient->campaign()->increment('bounce_count');
        }
    }

    protected function markComplained(CampaignRecipient $recipient): void
    {
        if (!$recipient->complained_at) {
            $recipient->update([
                'complained_at' => now(),
                'status'        => 'unsubscribed',
            ]);

            $recipient->campaign()->increment('complaint_count');
        }
    }
}
