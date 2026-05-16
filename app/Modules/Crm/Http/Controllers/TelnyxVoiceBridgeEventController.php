<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use App\Modules\Crm\Models\CallVoiceSession;
use App\Modules\Crm\Services\AiCallAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelnyxVoiceBridgeEventController extends Controller
{
    public function store(Request $request, AiCallAgentService $agent): JsonResponse
    {
        $data = $request->validate([
            'voice_session_id' => ['nullable', 'integer'],
            'call_log_id' => ['nullable', 'integer'],
            'event_type' => ['required', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
            'source' => ['nullable', 'string', 'max:50'],

            'stream_id' => ['nullable', 'string', 'max:100'],
            'transcript_file' => ['nullable', 'string', 'max:255'],
            'wav_file' => ['nullable', 'string', 'max:255'],
            'audio_file' => ['nullable', 'string', 'max:255'],

            'wav_url' => ['nullable', 'string', 'max:1000'],
            'transcript_url' => ['nullable', 'string', 'max:1000'],
            'audio_url' => ['nullable', 'string', 'max:1000'],

            'transcript_text' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        $voiceSession = null;

        if (!empty($data['voice_session_id'])) {
            $voiceSession = CallVoiceSession::query()->find($data['voice_session_id']);
        }

        if (!$voiceSession && !empty($data['call_log_id'])) {
            $voiceSession = CallVoiceSession::query()
                ->where('call_log_id', $data['call_log_id'])
                ->latest('id')
                ->first();
        }

        Log::info('Telnyx Voice Bridge event ricevuto', [
            'voice_session_id' => $data['voice_session_id'] ?? null,
            'call_log_id' => $data['call_log_id'] ?? null,
            'event_type' => $data['event_type'],
            'source' => $data['source'] ?? 'node_bridge',
            'stream_id' => $data['stream_id'] ?? null,
            'wav_url' => $data['wav_url'] ?? null,
            'transcript_url' => $data['transcript_url'] ?? null,
            'audio_url' => $data['audio_url'] ?? null,
            'payload_preview' => $data['payload'] ?? null,
        ]);

        if ($voiceSession) {
            $metadata = is_array($voiceSession->metadata) ? $voiceSession->metadata : [];

            $eventPayload = $data['payload'] ?? [
                'stream_id' => $data['stream_id'] ?? null,
                'audio_file' => $data['audio_file'] ?? null,
                'wav_file' => $data['wav_file'] ?? null,
                'transcript_file' => $data['transcript_file'] ?? null,
                'audio_url' => $data['audio_url'] ?? null,
                'wav_url' => $data['wav_url'] ?? null,
                'transcript_url' => $data['transcript_url'] ?? null,
                'transcript_text' => $data['transcript_text'] ?? null,
                'meta' => $data['meta'] ?? null,
            ];

            $events = $metadata['bridge_events'] ?? [];
            $events[] = [
                'event_type' => $data['event_type'],
                'source' => $data['source'] ?? 'node_bridge',
                'received_at' => now()->toDateTimeString(),
                'payload' => $eventPayload,
            ];

            $voiceSession->update([
                'metadata' => array_merge($metadata, [
                    'bridge_events' => array_slice($events, -50),
                    'last_bridge_event_type' => $data['event_type'],
                    'last_bridge_event_at' => now()->toDateTimeString(),

                    'last_stream_id' => $data['stream_id'] ?? ($metadata['last_stream_id'] ?? null),

                    'last_audio_file' => $data['audio_file'] ?? ($metadata['last_audio_file'] ?? null),
                    'last_wav_file' => $data['wav_file'] ?? ($metadata['last_wav_file'] ?? null),
                    'last_transcript_file' => $data['transcript_file'] ?? ($metadata['last_transcript_file'] ?? null),

                    'last_audio_url' => $data['audio_url'] ?? ($metadata['last_audio_url'] ?? null),
                    'last_wav_url' => $data['wav_url'] ?? ($metadata['last_wav_url'] ?? null),
                    'last_transcript_url' => $data['transcript_url'] ?? ($metadata['last_transcript_url'] ?? null),

                    'last_transcript_text' => $data['transcript_text'] ?? ($metadata['last_transcript_text'] ?? null),
                    'last_transcript_meta' => $data['meta'] ?? ($metadata['last_transcript_meta'] ?? null),
                ]),
            ]);

            if (
                $data['event_type'] === 'whisper.transcription.completed' &&
                !empty($data['transcript_text'])
            ) {
                $callLog = $voiceSession->callLog()->first();
                $queueItem = $voiceSession->queueItem()->first();
                $campaign = $voiceSession->campaign()->first();

                if ($callLog && $queueItem && $campaign) {
                    try {
                        $agentResult = $agent->processTranscriptForCall(
                            campaign: $campaign,
                            queueItem: $queueItem,
                            callLog: $callLog,
                            transcriptText: (string) $data['transcript_text']
                        );

                        Log::info('AI Call Agent post-call completato', [
                            'voice_session_id' => $voiceSession->id,
                            'call_log_id' => $callLog->id,
                            'queue_id' => $queueItem->id,
                            'campaign_id' => $campaign->id,
                            'ok' => $agentResult['ok'] ?? false,
                            'suggested_outcome' => $agentResult['suggested_outcome'] ?? null,
                            'ai_mode' => $agentResult['ai_mode'] ?? null,
                            'message' => $agentResult['message'] ?? null,
                        ]);
                    } catch (\Throwable $e) {
                        report($e);

                        Log::error('AI Call Agent post-call errore', [
                            'voice_session_id' => $voiceSession->id,
                            'call_log_id' => $callLog->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::warning('AI Call Agent post-call saltato: relazioni mancanti', [
                        'voice_session_id' => $voiceSession->id,
                        'has_call_log' => (bool) $callLog,
                        'has_queue_item' => (bool) $queueItem,
                        'has_campaign' => (bool) $campaign,
                    ]);
                }
            }
        }

        return response()->json([
            'ok' => true,
            'voice_session_found' => (bool) $voiceSession,
            'voice_session_id' => $voiceSession?->id,
        ]);
    }
}
