<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenClawCallAgentService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.openclaw.enabled', false);
    }

    public function chat(array $payload): array
    {
        if (!$this->isEnabled()) {
            return [
                'ok' => false,
                'message' => 'OpenClaw disabilitato.',
                'reply' => null,
                'raw' => null,
            ];
        }

        $baseUrl = rtrim((string) config('services.openclaw.base_url', ''), '/');
        $endpoint = (string) config('services.openclaw.endpoint', '/v1/chat/completions');
        $gatewayToken = (string) config('services.openclaw.gateway_token', '');
        $timeout = (int) config('services.openclaw.timeout', 20);
        $verifySsl = (bool) config('services.openclaw.verify_ssl', true);
        $agentId = trim((string) config('services.openclaw.agent_id', ''));

        if ($baseUrl === '') {
            return [
                'ok' => false,
                'message' => 'OPENCLAW_BASE_URL non configurato.',
                'reply' => null,
                'raw' => null,
            ];
        }

        if ($gatewayToken === '') {
            return [
                'ok' => false,
                'message' => 'OPENCLAW_GATEWAY_TOKEN non configurato.',
                'reply' => null,
                'raw' => null,
            ];
        }

        if ($agentId === '') {
            return [
                'ok' => false,
                'message' => 'OPENCLAW_AGENT_ID non configurato.',
                'reply' => null,
                'raw' => null,
            ];
        }

        $url = $baseUrl . $endpoint;

        $messages = [
            [
                'role' => 'system',
                'content' => (string) ($payload['system_prompt'] ?? 'Sei l’assistente telefonico AI di R4Software.'),
            ],
        ];

        if (!empty($payload['context_message'])) {
            $messages[] = [
                'role' => 'system',
                'content' => (string) $payload['context_message'],
            ];
        }

        if (!empty($payload['history']) && is_array($payload['history'])) {
            foreach ($payload['history'] as $historyMessage) {
                $role = (string) ($historyMessage['role'] ?? '');
                $content = trim((string) ($historyMessage['content'] ?? ''));

                if ($content === '' || !in_array($role, ['system', 'user', 'assistant'], true)) {
                    continue;
                }

                $messages[] = [
                    'role' => $role,
                    'content' => $content,
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => (string) ($payload['message'] ?? ''),
        ];

        $requestPayload = [
            'model' => 'openclaw/' . $agentId,
            'messages' => $messages,
            'stream' => false,
        ];

        try {
            $response = Http::withOptions([
                'verify' => $verifySsl,
            ])
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->withToken($gatewayToken)
                ->post($url, $requestPayload);

            $rawBody = $response->body();
            $contentType = (string) $response->header('Content-Type', '');

            Log::info('OpenClaw CALL raw response', [
                'url' => $url,
                'status' => $response->status(),
                'content_type' => $contentType,
                'payload' => $requestPayload,
                'body_preview' => mb_substr($rawBody, 0, 3000),
            ]);

            if (!$response->successful()) {
                return [
                    'ok' => false,
                    'message' => 'OpenClaw HTTP ' . $response->status(),
                    'reply' => null,
                    'raw' => $rawBody,
                ];
            }

            if (!str_contains(mb_strtolower($contentType), 'json')) {
                return [
                    'ok' => false,
                    'message' => 'OpenClaw ha restituito una risposta non JSON.',
                    'reply' => null,
                    'raw' => $rawBody,
                ];
            }

            $json = $response->json();

            if (!is_array($json)) {
                return [
                    'ok' => false,
                    'message' => 'JSON OpenClaw non valido.',
                    'reply' => null,
                    'raw' => $rawBody,
                ];
            }

            $reply = $this->extractReplyText($json);

            if (!$reply) {
                return [
                    'ok' => false,
                    'message' => 'Risposta OpenClaw non riconosciuta.',
                    'reply' => null,
                    'raw' => $json,
                ];
            }

            return [
                'ok' => true,
                'message' => null,
                'reply' => $reply,
                'raw' => $json,
            ];
        } catch (Throwable $e) {
            Log::error('OpenClaw CALL exception', [
                'url' => $url ?? null,
                'error' => $e->getMessage(),
                'payload' => $requestPayload ?? null,
            ]);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'reply' => null,
                'raw' => null,
            ];
        }
    }

    protected function extractReplyText($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }

        $candidates = [
            data_get($json, 'choices.0.message.content'),
            data_get($json, 'choices.0.text'),
            data_get($json, 'reply'),
            data_get($json, 'message'),
            data_get($json, 'text'),
            data_get($json, 'response'),
            data_get($json, 'data.reply'),
            data_get($json, 'data.message'),
            data_get($json, 'data.text'),
            data_get($json, 'output'),
            data_get($json, 'output_text'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }
}
