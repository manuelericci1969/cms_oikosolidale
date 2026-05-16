<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeepSeekChatService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.deepseek.enabled', false);
    }

    public function chat(array $payload): array
    {
        if (!$this->isEnabled()) {
            return [
                'ok'      => false,
                'message' => 'DeepSeek disabilitato.',
                'reply'   => null,
                'raw'     => null,
            ];
        }

        $baseUrl = rtrim((string) config('services.deepseek.base_url', 'https://api.deepseek.com'), '/');
        $apiKey  = (string) config('services.deepseek.api_key', '');
        $model   = (string) config('services.deepseek.model', 'deepseek-chat');
        $timeout = (int) config('services.deepseek.timeout', 30);

        if ($apiKey === '') {
            return [
                'ok'      => false,
                'message' => 'DEEPSEEK_API_KEY non configurata.',
                'reply'   => null,
                'raw'     => null,
            ];
        }

        $url = $baseUrl . '/chat/completions';

        $requestPayload = [
            'model' => $model,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => (string) ($payload['system_prompt'] ?? 'Sei l’assistente commerciale di R4Software.'),
                ],
                [
                    'role'    => 'user',
                    'content' => (string) ($payload['message'] ?? ''),
                ],
            ],
            'stream' => false,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout($timeout)
                ->post($url, $requestPayload);

            $rawBody = $response->body();
            $contentType = (string) $response->header('Content-Type', '');

            Log::info('DeepSeek raw response', [
                'url'          => $url,
                'status'       => $response->status(),
                'content_type' => $contentType,
                'payload'      => $requestPayload,
                'body_preview' => mb_substr($rawBody, 0, 3000),
            ]);

            if (!$response->successful()) {
                return [
                    'ok'      => false,
                    'message' => 'DeepSeek HTTP ' . $response->status(),
                    'reply'   => null,
                    'raw'     => $rawBody,
                ];
            }

            if (!str_contains(mb_strtolower($contentType), 'json')) {
                return [
                    'ok'      => false,
                    'message' => 'DeepSeek ha restituito una risposta non JSON.',
                    'reply'   => null,
                    'raw'     => $rawBody,
                ];
            }

            $json = $response->json();

            if (!is_array($json)) {
                return [
                    'ok'      => false,
                    'message' => 'JSON DeepSeek non valido.',
                    'reply'   => null,
                    'raw'     => $rawBody,
                ];
            }

            $reply = $this->extractReplyText($json);

            if (!$reply) {
                Log::warning('DeepSeek chat: reply vuota o non riconosciuta', [
                    'payload'  => $requestPayload,
                    'response' => $json,
                ]);

                return [
                    'ok'      => false,
                    'message' => 'Risposta DeepSeek non riconosciuta.',
                    'reply'   => null,
                    'raw'     => $json,
                ];
            }

            return [
                'ok'      => true,
                'message' => null,
                'reply'   => trim($reply),
                'raw'     => $json,
            ];
        } catch (Throwable $e) {
            Log::error('DeepSeek chat exception', [
                'url'     => $url ?? null,
                'error'   => $e->getMessage(),
                'payload' => $requestPayload,
            ]);

            return [
                'ok'      => false,
                'message' => $e->getMessage(),
                'reply'   => null,
                'raw'     => null,
            ];
        }
    }

    protected function extractReplyText($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }

        $candidate = data_get($json, 'choices.0.message.content');

        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }

        return null;
    }
}
