<?php

namespace App\Modules\Crm\Services\Telephony;

use App\Modules\Crm\Models\CallLog;
use Carbon\Carbon;

class TelnyxHangupOutcomeMapper
{
    public function map(CallLog $log, array $eventData): array
    {
        $hangupCause = strtolower((string) data_get($eventData, 'hangup_cause', ''));
        $hangupSource = strtolower((string) data_get($eventData, 'hangup_source', ''));
        $sipHangupCause = (string) data_get($eventData, 'sip_hangup_cause', '');
        $startTime = data_get($eventData, 'start_time');
        $endTime = data_get($eventData, 'end_time');

        $durationSeconds = $this->resolveDurationSeconds($eventData, $startTime, $endTime);

        $metadata = $this->arrayValue($log->metadata);
        $amdResult = strtolower((string) data_get($metadata, 'telnyx_machine_detection.result', ''));
        $answered = !empty($log->answered_at);

        // 1) Segreteria / fax / silence rilevati da AMD
        if (in_array($amdResult, ['machine', 'fax_detected', 'silence'], true)) {
            return [
                'call_status' => CallLog::CALL_STATUS_FAILED,
                'technical_outcome' => CallLog::TECH_VOICEMAIL,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Chiamata terminata su segreteria/AMD.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'amd_result' => $amdResult,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        // 2) Cause certe documentate / tipiche
        if ($hangupCause === 'timeout') {
            return [
                'call_status' => CallLog::CALL_STATUS_NO_ANSWER,
                'technical_outcome' => CallLog::TECH_NO_ANSWER,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Nessuna risposta entro timeout Telnyx.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        if ($hangupCause === 'time_limit') {
            return [
                'call_status' => CallLog::CALL_STATUS_COMPLETED,
                'technical_outcome' => CallLog::TECH_COMPLETED,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Chiamata terminata per raggiungimento time limit Telnyx.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        if (in_array($hangupCause, ['user_busy', 'busy'], true)) {
            return [
                'call_status' => CallLog::CALL_STATUS_BUSY,
                'technical_outcome' => CallLog::TECH_BUSY,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Numero occupato.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        if (in_array($hangupCause, ['rejected', 'call_rejected'], true)) {
            return [
                'call_status' => CallLog::CALL_STATUS_FAILED,
                'technical_outcome' => CallLog::TECH_REJECTED,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Chiamata rifiutata.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        if (in_array($hangupCause, ['cancel', 'cancelled', 'originator_cancel', 'caller_hangup'], true) && !$answered) {
            return [
                'call_status' => CallLog::CALL_STATUS_CANCELLED,
                'technical_outcome' => CallLog::TECH_CANCELLED,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Chiamata annullata prima della risposta.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        // 3) Caso standard: answered + normal_clearing => completed
        if ($answered && $hangupCause === 'normal_clearing') {
            return [
                'call_status' => CallLog::CALL_STATUS_COMPLETED,
                'technical_outcome' => CallLog::TECH_COMPLETED,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Chiamata completata regolarmente.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        // 4) Fallback conservativi
        if ($answered || $durationSeconds > 0) {
            return [
                'call_status' => CallLog::CALL_STATUS_COMPLETED,
                'technical_outcome' => CallLog::TECH_COMPLETED,
                'duration_seconds' => $durationSeconds,
                'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
                'operator_note' => 'Chiamata chiusa da evento Telnyx.',
                'metadata' => [
                    'hangup_cause' => $hangupCause,
                    'hangup_source' => $hangupSource,
                    'sip_hangup_cause' => $sipHangupCause,
                    'telnyx_start_time' => $startTime,
                    'telnyx_end_time' => $endTime,
                ],
            ];
        }

        return [
            'call_status' => CallLog::CALL_STATUS_FAILED,
            'technical_outcome' => CallLog::TECH_FAILED,
            'duration_seconds' => $durationSeconds,
            'ended_at' => $endTime ? Carbon::parse($endTime) : now(),
            'operator_note' => 'Chiamata fallita da evento Telnyx.',
            'metadata' => [
                'hangup_cause' => $hangupCause,
                'hangup_source' => $hangupSource,
                'sip_hangup_cause' => $sipHangupCause,
                'telnyx_start_time' => $startTime,
                'telnyx_end_time' => $endTime,
            ],
        ];
    }

    protected function resolveDurationSeconds(array $eventData, ?string $startTime, ?string $endTime): int
    {
        if ($startTime && $endTime) {
            try {
                return max(0, Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime)));
            } catch (\Throwable) {
                // fallback sotto
            }
        }

        return (int) (data_get($eventData, 'call_duration') ?? 0);
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
}
