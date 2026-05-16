<?php

namespace App\Modules\Crm\Services\Telephony;

use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallVoiceSession;
use App\Modules\Crm\Models\CallVoiceTurn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelnyxVoiceBridgeService
{
    public function createOrGetSession(CallLog $callLog): CallVoiceSession
    {
        $existing = CallVoiceSession::query()
            ->where('call_log_id', $callLog->id)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return CallVoiceSession::create([
            'client_id' => $callLog->client_id,
            'campaign_id' => $callLog->campaign_id,
            'queue_id' => $callLog->queue_id,
            'call_log_id' => $callLog->id,
            'provider' => $callLog->provider ?: 'telnyx',
            'provider_call_id' => $callLog->provider_call_id,
            'provider_leg_id' => $callLog->provider_leg_id,
            'provider_session_id' => $callLog->provider_session_id,
            'status' => 'created',
            'bridge_mode' => 'voice_bridge_v1',
            'started_at' => now(),
            'metadata' => [
                'created_from' => 'telnyx_voice_bridge_service',
            ],
        ]);
    }

    public function startStreaming(CallLog $callLog): array
    {
        if (!$callLog->provider_call_id) {
            throw new RuntimeException('provider_call_id mancante sul CallLog.');
        }

        $apiKey = (string) config('services.telnyx.api_key');
        $apiBase = rtrim((string) config('services.telnyx.api_base', 'https://api.telnyx.com/v2'), '/');
        $streamUrl = (string) config('services.telnyx.voice_bridge_ws_url', '');

        if ($apiKey === '') {
            throw new RuntimeException('TELNYX_API_KEY non configurata.');
        }

        if ($streamUrl === '') {
            throw new RuntimeException('TELNYX_VOICE_BRIDGE_WS_URL non configurata.');
        }

        $session = $this->createOrGetSession($callLog);

        $payload = [
            'stream_url' => $streamUrl,
            'stream_track' => 'both_tracks',
            'client_state' => base64_encode(json_encode([
                'call_log_id' => $callLog->id,
                'voice_session_id' => $session->id,
                'campaign_id' => $callLog->campaign_id,
                'queue_id' => $callLog->queue_id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post(
                $apiBase . '/calls/' . urlencode($callLog->provider_call_id) . '/actions/streaming_start',
                $payload
            );

        if ($response->failed()) {
            Log::error('Telnyx streaming_start failed', [
                'call_log_id' => $callLog->id,
                'provider_call_id' => $callLog->provider_call_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            throw new RuntimeException(
                'Telnyx streaming_start failed: HTTP ' . $response->status() . ' - ' . $response->body()
            );
        }

        $json = $response->json() ?? [];

        $session->update([
            'status' => 'streaming',
            'streaming_started_at' => now(),
            'stream_id' => data_get($json, 'data.stream_id'),
            'metadata' => array_merge($session->metadata ?? [], [
                'streaming_start_payload' => $payload,
                'streaming_start_response' => $json,
            ]),
        ]);

        return $json;
    }

    public function speakText(
        CallLog $callLog,
        string $text,
        ?string $voice = null,
        ?string $language = null
    ): array {
        $text = trim($text);

        if (!$callLog->provider_call_id) {
            throw new RuntimeException('provider_call_id mancante sul CallLog.');
        }

        if ($text === '') {
            throw new RuntimeException('Testo speak vuoto.');
        }

        $apiKey = (string) config('services.telnyx.api_key');
        $apiBase = rtrim((string) config('services.telnyx.api_base', 'https://api.telnyx.com/v2'), '/');
        $voice = $voice ?: (string) config('services.telnyx.tts_voice', 'Azure.it-IT-IsabellaMultilingualNeural');
        $language = $language ?: (string) config('services.telnyx.tts_language', 'it-IT');

        if ($apiKey === '') {
            throw new RuntimeException('TELNYX_API_KEY non configurata.');
        }

        $payload = [
            'payload' => $text,
            'voice' => $voice,
            'language' => $language,
            'command_id' => 'speak_' . $callLog->id . '_' . now()->format('YmdHis'),
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post(
                $apiBase . '/calls/' . urlencode($callLog->provider_call_id) . '/actions/speak',
                $payload
            );

        if ($response->failed()) {
            Log::error('Telnyx speak failed', [
                'call_log_id' => $callLog->id,
                'provider_call_id' => $callLog->provider_call_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            throw new RuntimeException(
                'Telnyx speak failed: HTTP ' . $response->status() . ' - ' . $response->body()
            );
        }

        $json = $response->json() ?? [];
        $session = $this->createOrGetSession($callLog);

        $session->update([
            'metadata' => array_merge($session->metadata ?? [], [
                'last_speak_payload' => $payload,
                'last_speak_response' => $json,
                'last_speak_at' => now()->toDateTimeString(),
            ]),
        ]);

        $this->addTurn(
            session: $session,
            role: 'assistant',
            text: $text,
            metadata: [
                'source' => 'telnyx_speak',
                'voice' => $voice,
                'language' => $language,
                'response' => $json,
            ],
            latencyMs: null,
            isFinal: true
        );

        return $json;
    }

    public function closeSessionByCallLog(CallLog $callLog, array $extra = []): void
    {
        $session = CallVoiceSession::query()
            ->where('call_log_id', $callLog->id)
            ->latest('id')
            ->first();

        if (!$session) {
            return;
        }

        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
            'metadata' => array_merge($session->metadata ?? [], $extra),
        ]);
    }

    public function addTurn(
        CallVoiceSession $session,
        string $role,
        ?string $text,
        array $metadata = [],
        ?int $latencyMs = null,
        bool $isFinal = true
    ): CallVoiceTurn {
        $turnNo = ((int) $session->turns()->max('turn_no')) + 1;

        return CallVoiceTurn::create([
            'voice_session_id' => $session->id,
            'call_log_id' => $session->call_log_id,
            'turn_no' => $turnNo,
            'role' => $role,
            'text' => $text,
            'latency_ms' => $latencyMs,
            'is_final' => $isFinal,
            'metadata' => $metadata,
        ]);
    }
}
