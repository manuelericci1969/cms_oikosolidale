<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelnyxCallService
{
    public function startCall(
        string $phone,
        CallQueue $queueItem,
        CallCampaign $campaign,
        CallLog $log
    ): array {
        $apiKey = (string) config('services.telnyx.api_key');
        $apiBase = rtrim((string) config('services.telnyx.api_base', 'https://api.telnyx.com/v2'), '/');
        $fromNumber = (string) config('services.telnyx.from_number');
        $connectionId = (string) config('services.telnyx.connection_id');
        $webhookUrl = (string) config('services.telnyx.webhook_url');

        if ($apiKey === '') {
            throw new RuntimeException('Configurazione Telnyx mancante: services.telnyx.api_key');
        }

        if ($fromNumber === '') {
            throw new RuntimeException('Configurazione Telnyx mancante: services.telnyx.from_number');
        }

        if ($connectionId === '') {
            throw new RuntimeException('Configurazione Telnyx mancante: services.telnyx.connection_id');
        }

        if ($webhookUrl === '') {
            throw new RuntimeException('Configurazione Telnyx mancante: services.telnyx.webhook_url');
        }

        $normalizedPhone = $this->normalizePhone($phone);

        if ($normalizedPhone === null) {
            throw new RuntimeException('Numero di telefono non valido per Telnyx.');
        }

        $payload = [
            'to' => $normalizedPhone,
            'from' => $fromNumber,
            'connection_id' => $connectionId,
            'webhook_url' => $webhookUrl,
            'client_state' => $this->makeClientState($campaign, $queueItem, $log),
        ];

        $timeoutSecs = max(10, (int) data_get($campaign->settings, 'timeout_secs', 30));

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout($timeoutSecs)
            ->post($apiBase . '/calls', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Telnyx call failed: HTTP ' . $response->status() . ' - ' . $response->body()
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException('Risposta Telnyx non valida: body non JSON.');
        }

        return $json;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (!$phone) {
            return null;
        }

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (!str_starts_with($phone, '+')) {
            $digits = preg_replace('/\D/', '', $phone);

            if (!$digits) {
                return null;
            }

            if (strlen($digits) === 9 || strlen($digits) === 10) {
                $phone = '+39' . $digits;
            } else {
                $phone = '+' . $digits;
            }
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $phone;
    }

    protected function makeClientState(CallCampaign $campaign, CallQueue $queueItem, CallLog $log): string
    {
        $payload = [
            'campaign_id' => $campaign->id,
            'queue_id' => $queueItem->id,
            'log_id' => $log->id,
        ];

        return base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
