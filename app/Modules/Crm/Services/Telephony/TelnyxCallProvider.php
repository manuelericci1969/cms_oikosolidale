<?php

namespace App\Modules\Crm\Services\Telephony;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class TelnyxCallProvider
{
    public function __construct(
        protected HttpFactory $http
    ) {
    }

    public function createOutboundCall(array $payload): array
    {
        $connectionId = config('services.telnyx.connection_id');
        $fromNumber   = config('services.telnyx.from_number');
        $webhookUrl   = config('services.telnyx.webhook_url');
        $apiBase      = rtrim((string) config('services.telnyx.api_base', 'https://api.telnyx.com/v2'), '/');
        $apiKey       = config('services.telnyx.api_key');

        if (!$apiKey) {
            throw new RuntimeException('TELNYX_API_KEY non configurata.');
        }

        if (!$connectionId) {
            throw new RuntimeException('TELNYX_CONNECTION_ID non configurata.');
        }

        if (!$fromNumber) {
            throw new RuntimeException('TELNYX_FROM_NUMBER non configurata.');
        }

        if (!$webhookUrl) {
            throw new RuntimeException('TELNYX_WEBHOOK_URL non configurata.');
        }

        $to = $this->normalizePhone($payload['to'] ?? null);

        if (!$to) {
            throw new RuntimeException('Numero destinatario mancante o non valido nel payload Telnyx.');
        }

        $timeoutSecs = (int) ($payload['timeout_secs'] ?? 30);
        $timeoutSecs = max(10, min($timeoutSecs, 120));

        $requestBody = [
            'connection_id' => $connectionId,
            'to' => $to,
            'from' => $fromNumber,
            'webhook_url' => $webhookUrl,
            'timeout_secs' => $timeoutSecs,

            // Abilita AMD standard: umano vs macchina
            'answering_machine_detection' => 'detect',
        ];

        if (!empty($payload['client_state'])) {
            $requestBody['client_state'] = $payload['client_state'];
        }

        $response = $this->http
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeoutSecs + 10)
            ->post($apiBase . '/calls', $requestBody);

        $json = $response->json();

        if ($response->failed()) {
            throw new RuntimeException(
                'Errore Telnyx: ' . ($response->body() ?: 'risposta vuota')
            );
        }

        $data = $json['data'] ?? [];
        $errors = $json['errors'] ?? [];

        return [
            'ok' => true,
            'request' => $requestBody,
            'response' => $json,
            'call_control_id' => $data['call_control_id'] ?? null,
            'call_leg_id' => $data['call_leg_id'] ?? null,
            'call_session_id' => $data['call_session_id'] ?? null,
            'is_alive' => $data['is_alive'] ?? null,
            'errors' => $errors,
        ];
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $phone = trim($phone);

        // Mantiene il + iniziale, rimuove il resto dei caratteri non validi
        $phone = preg_replace('/(?!^\+)[^\d]/', '', $phone);

        if (!$phone) {
            return null;
        }

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        } elseif (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '3') || str_starts_with($phone, '0')) {
                $phone = '+39' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $phone) ? $phone : null;
    }
}
