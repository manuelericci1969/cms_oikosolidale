<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\TelnyxWebhookEvent;
use App\Modules\Crm\Services\CallOutcomeSyncService;
use App\Modules\Crm\Services\Telephony\TelnyxHangupOutcomeMapper;
use App\Modules\Crm\Services\Telephony\TelnyxVoiceBridgeService;
use App\Modules\Crm\Services\Telephony\TelnyxWebhookSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelnyxWebhookController extends Controller
{
    public function handle(
        Request $request,
        CallOutcomeSyncService $sync,
        TelnyxWebhookSignatureVerifier $verifier,
        TelnyxHangupOutcomeMapper $hangupMapper,
        TelnyxVoiceBridgeService $voiceBridge
    ): JsonResponse {
        $rawBody = $request->getContent();
        $signature = $request->header('telnyx-signature-ed25519')
            ?? $request->header('webhook-signature');
        $timestamp = $request->header('telnyx-timestamp')
            ?? $request->header('webhook-timestamp');

        try {
            $verifier->verify($rawBody, $signature, $timestamp);
        } catch (\Throwable $e) {
            Log::warning('Webhook Telnyx rifiutato: firma non valida', [
                'message' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'invalid_signature',
            ], 403);
        }

        $payload = $request->all();

        $eventId = data_get($payload, 'data.id');
        $eventType = data_get($payload, 'data.event_type');
        $eventData = data_get($payload, 'data.payload', []);
        $occurredAt = data_get($payload, 'data.occurred_at');

        $callControlId = data_get($eventData, 'call_control_id');
        $callLegId = data_get($eventData, 'call_leg_id');
        $callSessionId = data_get($eventData, 'call_session_id');
        $clientState = data_get($eventData, 'client_state');

        if (!$eventId || !$eventType) {
            return response()->json([
                'ok' => true,
                'ignored' => true,
                'reason' => 'missing event_id or event_type',
            ]);
        }

        try {
            $storedEvent = DB::transaction(function () use (
                $eventId,
                $eventType,
                $callControlId,
                $callLegId,
                $callSessionId,
                $occurredAt,
                $payload,
                $request,
                $clientState,
                $sync
            ) {
                $existing = TelnyxWebhookEvent::query()
                    ->where('event_id', $eventId)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return [$existing, true];
                }

                $callLogId = $this->extractCallLogIdFromClientState($clientState);

                if ($callLogId && $callControlId) {
                    try {
                        $sync->bindProviderCallByLogId($callLogId, $callControlId, [
                            'bound_from_client_state' => true,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Bind provider_call_id via client_state fallito', [
                            'event_id' => $eventId,
                            'call_log_id' => $callLogId,
                            'call_control_id' => $callControlId,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                $created = TelnyxWebhookEvent::query()->create([
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'call_control_id' => $callControlId,
                    'call_leg_id' => $callLegId,
                    'call_session_id' => $callSessionId,
                    'call_log_id' => $callLogId,
                    'occurred_at' => $occurredAt,
                    'headers' => $request->headers->all(),
                    'payload' => $payload,
                ]);

                return [$created, false];
            });

            /** @var \App\Modules\Crm\Models\TelnyxWebhookEvent $eventRow */
            [$eventRow, $duplicate] = $storedEvent;

            if ($duplicate) {
                return response()->json([
                    'ok' => true,
                    'duplicate' => true,
                    'event_id' => $eventId,
                ]);
            }

            Log::info('Telnyx webhook ricevuto', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'call_control_id' => $callControlId,
                'call_leg_id' => $callLegId,
                'call_session_id' => $callSessionId,
            ]);

            match ($eventType) {
                'call.initiated',
                'call.initiated.outbound' => null,

                'call.ringing',
                'call.ringing.outbound' => $callControlId
                    ? $sync->markRingingByProviderCallId($callControlId, [
                        'telnyx_event' => $eventType,
                        'telnyx_event_id' => $eventId,
                    ])
                    : null,

                'call.answered',
                'call.answered.outbound' => $callControlId
                    ? $sync->markAnsweredByProviderCallId(
                        $callControlId,
                        data_get($eventData, 'start_time')
                            ? \Carbon\Carbon::parse(data_get($eventData, 'start_time'))
                            : now(),
                        [
                            'telnyx_event' => $eventType,
                            'telnyx_event_id' => $eventId,
                        ]
                    )
                    : null,

                'call.machine.detection.ended',
                'call.machine.greeting.ended',
                'call.machine.premium.detection.ended' => $callControlId
                    ? $this->handleMachineDetectionEvent(
                        $callControlId,
                        $eventType,
                        $eventId,
                        $eventData,
                        $sync,
                        $voiceBridge
                    )
                    : null,

                'call.hangup',
                'call.hangup.outbound' => $this->handleHangupEvent(
                    $callControlId,
                    $callLegId,
                    $callSessionId,
                    $clientState,
                    $eventType,
                    $eventId,
                    $eventData,
                    $sync,
                    $hangupMapper,
                    $voiceBridge
                ),

                default => null,
            };

            $eventRow->update([
                'processed_at' => now(),
            ]);

            return response()->json([
                'ok' => true,
                'event_type' => $eventType,
                'event_id' => $eventId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Errore gestione webhook Telnyx', [
                'message' => $e->getMessage(),
                'event_id' => $eventId,
                'event_type' => $eventType,
                'call_control_id' => $callControlId,
                'call_leg_id' => $callLegId,
                'call_session_id' => $callSessionId,
                'payload' => $payload,
            ]);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function extractCallLogIdFromClientState(?string $clientState): ?int
    {
        if (!$clientState) {
            return null;
        }

        $decoded = base64_decode($clientState, true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return null;
        }

        $callLogId = $data['call_log_id'] ?? null;

        return is_numeric($callLogId) ? (int) $callLogId : null;
    }

    protected function handleHangupEvent(
        ?string $callControlId,
        ?string $callLegId,
        ?string $callSessionId,
        ?string $clientState,
        string $eventType,
        string $eventId,
        array $eventData,
        CallOutcomeSyncService $sync,
        TelnyxHangupOutcomeMapper $hangupMapper,
        TelnyxVoiceBridgeService $voiceBridge
    ): void {
        $log = $this->resolveCallLogForHangup(
            $callControlId,
            $callLegId,
            $callSessionId,
            $clientState
        );

        if (!$log) {
            throw new \RuntimeException(sprintf(
                'CallLog non trovato per hangup. provider_call_id=%s call_leg_id=%s call_session_id=%s',
                $callControlId ?: 'null',
                $callLegId ?: 'null',
                $callSessionId ?: 'null'
            ));
        }

        if ($callControlId && !$log->provider_call_id) {
            $sync->bindProviderCallByLogId($log->id, $callControlId, [
                'bound_during_hangup_resolution' => true,
            ]);

            $log = $log->fresh();
        }

        $providerCallId = $log->provider_call_id ?: $callControlId;

        if (!$providerCallId) {
            throw new \RuntimeException("Impossibile completare hangup: provider_call_id assente per CallLog #{$log->id}");
        }

        $mapped = $hangupMapper->map($log, $eventData);

        $voiceBridge->closeSessionByCallLog($log, [
            'closed_from' => 'telnyx_hangup',
            'hangup_cause' => data_get($eventData, 'hangup_cause'),
            'hangup_source' => data_get($eventData, 'hangup_source'),
            'telnyx_event' => $eventType,
            'telnyx_event_id' => $eventId,
        ]);

        $sync->completeByProviderCallId($providerCallId, [
            'call_status' => $mapped['call_status'],
            'technical_outcome' => $mapped['technical_outcome'],
            'business_outcome' => null,
            'operator_note' => $mapped['operator_note'],
            'duration_seconds' => $mapped['duration_seconds'],
            'answered_at' => $log->answered_at,
            'ended_at' => $mapped['ended_at'],
            'metadata' => array_merge($mapped['metadata'] ?? [], [
                'telnyx_event' => $eventType,
                'telnyx_event_id' => $eventId,
                'telnyx_call_leg_id' => $callLegId,
                'telnyx_call_session_id' => $callSessionId,
            ]),
        ]);
    }

    protected function resolveCallLogForHangup(
        ?string $callControlId,
        ?string $callLegId,
        ?string $callSessionId,
        ?string $clientState
    ): ?CallLog {
        if ($callControlId) {
            $log = CallLog::query()
                ->where('provider_call_id', $callControlId)
                ->first();

            if ($log) {
                return $log;
            }
        }

        $callLogId = $this->extractCallLogIdFromClientState($clientState);
        if ($callLogId) {
            $log = CallLog::query()->find($callLogId);

            if ($log) {
                if ($callControlId && !$log->provider_call_id) {
                    $log->update([
                        'provider_call_id' => $callControlId,
                        'metadata' => array_merge($this->arrayValue($log->metadata), [
                            'bound_from_hangup_client_state' => true,
                            'telnyx_call_leg_id' => $callLegId,
                            'telnyx_call_session_id' => $callSessionId,
                        ]),
                    ]);

                    return $log->fresh();
                }

                return $log;
            }
        }

        return null;
    }

    protected function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function handleMachineDetectionEvent(
        string $callControlId,
        string $eventType,
        string $eventId,
        array $eventData,
        CallOutcomeSyncService $sync,
        TelnyxVoiceBridgeService $voiceBridge
    ): void {
        $result = (string) (
            data_get($eventData, 'result')
            ?? data_get($eventData, 'detection_result')
            ?? 'unknown'
        );

        $sync->markMachineDetectionByProviderCallId(
            $callControlId,
            $result,
            [
                'telnyx_event' => $eventType,
                'telnyx_event_id' => $eventId,
            ]
        );

        $resultNormalized = strtolower(trim($result));

        if (!in_array($resultNormalized, ['human', 'not_sure', 'machine'], true)) {
            Log::info('Voice bridge non avviato: AMD non human', [
                'provider_call_id' => $callControlId,
                'amd_result' => $result,
                'event_type' => $eventType,
            ]);

            return;
        }

        $log = CallLog::query()
            ->where('provider_call_id', $callControlId)
            ->first();

        if (!$log) {
            Log::warning('Voice bridge: CallLog non trovato per AMD human', [
                'provider_call_id' => $callControlId,
                'amd_result' => $result,
            ]);

            return;
        }

        try {
            $voiceBridge->startStreaming($log);

            usleep(300000);

            $voiceBridge->speakText(
                $log,
                "Buongiorno, sono l'assistente virtuale di R4Software.",
                null,
                null,
                '0.90'
            );

            Log::info('Voice bridge avviato con speak test su AMD human', [
                'call_log_id' => $log->id,
                'provider_call_id' => $callControlId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Errore avvio voice bridge o speak test', [
                'call_log_id' => $log->id,
                'provider_call_id' => $callControlId,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
