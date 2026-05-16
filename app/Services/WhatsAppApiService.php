<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WhatsAppApiService
{
    public function send(string $phone, string $message): array
    {
        $url    = (string) config('services.whatsapp.url');
        $apiKey = (string) config('services.whatsapp.api_key');

        if ($url === '' || $apiKey === '') {
            throw new \RuntimeException('Configurazione WhatsApp API incompleta.');
        }

        $normalized = $this->normalizePhone($phone);

        if (!$normalized) {
            throw new \RuntimeException('Numero WhatsApp non valido.');
        }

        // L'endpoint richiede il numero senza "+"
        $apiPhone = ltrim($normalized, '+');

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                ])
                ->post($url, [
                    'number'  => $apiPhone,
                    'message' => trim($message),
                ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    'API WhatsApp error: HTTP ' . $response->status() . ' - ' . $response->body()
                );
            }

            return $response->json() ?? [
                'ok'  => true,
                'raw' => $response->body(),
            ];
        } catch (RequestException $e) {
            throw new \RuntimeException('Errore richiesta WhatsApp API: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Invio WhatsApp fallito: ' . $e->getMessage(), 0, $e);
        }
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $phone = trim($phone);

        // Tiene solo cifre e +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (!$phone) {
            return null;
        }

        // 0039... -> +39...
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        // Se già internazionale
        if (str_starts_with($phone, '+')) {
            $digits = preg_replace('/[^\d]/', '', $phone);

            return $digits !== '' ? '+' . $digits : null;
        }

        $defaultPrefix = (string) config('services.whatsapp.prefix', '39');

        // Mobile italiano senza prefisso, es. 347xxxxxxx
        if (preg_match('/^3\d{8,12}$/', $phone)) {
            return '+' . $defaultPrefix . $phone;
        }

        // Fisso o altro numero nazionale senza prefisso
        $phone = ltrim($phone, '0');

        if ($phone === '') {
            return null;
        }

        return '+' . $defaultPrefix . $phone;
    }
}
