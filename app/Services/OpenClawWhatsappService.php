<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenClawWhatsappService
{
    public function send(string $number, string $message): array
    {
        $url      = config('services.openclaw_whatsapp.url');
        $apiKey   = config('services.openclaw_whatsapp.api_key');
        $username = config('services.openclaw_whatsapp.username');
        $password = config('services.openclaw_whatsapp.password');

        if (!$url) {
            return [
                'ok' => false,
                'error' => 'URL OpenClaw WhatsApp non configurato.',
            ];
        }

        if (!$apiKey) {
            return [
                'ok' => false,
                'error' => 'API key OpenClaw WhatsApp non configurata.',
            ];
        }

        $payload = [
            'number'  => $this->normalizeNumber($number),
            'message' => $message,
        ];

        try {
            $request = Http::timeout(20)
                ->acceptJson()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                ]);

            if (!empty($username) && !empty($password)) {
                $request = $request->withBasicAuth($username, $password);
            }

            $response = $request->post($url, $payload);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            Log::warning('OpenClaw WhatsApp send failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return [
                'ok' => false,
                'status' => $response->status(),
                'error' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('OpenClaw WhatsApp exception', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function normalizeNumber(string $number): string
    {
        $number = trim($number);
        $number = preg_replace('/\D+/', '', $number);

        if ($number === '') {
            return $number;
        }

        // Se il numero inizia con 0 o non ha prefisso internazionale,
        // per l'Italia prependiamo 39
        if (!str_starts_with($number, '39')) {
            $number = '39' . ltrim($number, '0');
        }

        return $number;
    }
}
