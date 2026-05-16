<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CallOutcomeSyncService
{
    public function bindProviderCall(
        int $callLogId,
        string $providerCallId,
        array $metadata = []
    ): CallLog {
        $log = CallLog::findOrFail($callLogId);

        $log->update([
            'provider_call_id' => $providerCallId,
            'metadata' => array_merge($this->arrayValue($log->metadata), $metadata),
        ]);

        return $log->fresh();
    }

    public function markRingingByProviderCallId(string $providerCallId, array $metadata = []): void
    {
        $log = $this->findLogByProviderCallId($providerCallId);

        $log->update([
            'call_status' => CallLog::CALL_STATUS_RINGING,
            'metadata' => array_merge($this->arrayValue($log->metadata), $metadata),
        ]);
    }

    public function markAnsweredByProviderCallId(
        string $providerCallId,
        ?Carbon $answeredAt = null,
        array $metadata = []
    ): void {
        $log = $this->findLogByProviderCallId($providerCallId);

        if ($log->ended_at) {
            return;
        }

        $log->update([
            'call_status' => CallLog::CALL_STATUS_ANSWERED,
            'answered_at' => $log->answered_at ?? ($answeredAt ?? now()),
            'metadata' => array_merge($this->arrayValue($log->metadata), $metadata),
        ]);
    }

    public function completeByProviderCallId(
        string $providerCallId,
        array $data
    ): void {
        $log = $this->findLogByProviderCallId($providerCallId);

        if ($log->ended_at) {
            return;
        }

        $queueItem = CallQueue::findOrFail($log->queue_id);

        app(CallExecutionService::class)->completeCall($queueItem, $log, [
            'call_status' => $data['call_status'] ?? CallLog::CALL_STATUS_COMPLETED,
            'technical_outcome' => $data['technical_outcome'] ?? CallLog::TECH_COMPLETED,
            'business_outcome' => $data['business_outcome'] ?? null,
            'operator_note' => $data['operator_note'] ?? null,
            'ai_summary' => $data['ai_summary'] ?? null,
            'transcript' => $data['transcript'] ?? null,
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 0),
            'answered_at' => $data['answered_at'] ?? $log->answered_at,
            'ended_at' => $data['ended_at'] ?? now(),
            'callback_at' => $data['callback_at'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    public function failByProviderCallId(
        string $providerCallId,
        string $technicalOutcome = CallLog::TECH_ERROR,
        ?string $note = null,
        array $metadata = []
    ): void {
        $log = $this->findLogByProviderCallId($providerCallId);
        $queueItem = CallQueue::findOrFail($log->queue_id);

        app(CallExecutionService::class)->completeCall($queueItem, $log, [
            'call_status' => CallLog::CALL_STATUS_FAILED,
            'technical_outcome' => $technicalOutcome,
            'business_outcome' => null,
            'operator_note' => $note,
            'duration_seconds' => 0,
            'ended_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    protected function findLogByProviderCallId(string $providerCallId): CallLog
    {
        $log = CallLog::where('provider_call_id', $providerCallId)->first();

        if (!$log) {
            throw new RuntimeException("CallLog non trovato per provider_call_id={$providerCallId}");
        }

        return $log;
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

    public function bindProviderCallByLogId(
        int $callLogId,
        string $providerCallId,
        array $metadata = []
    ): CallLog {
        $log = CallLog::findOrFail($callLogId);

        if (!$log->provider_call_id) {
            $log->update([
                'provider_call_id' => $providerCallId,
                'metadata' => array_merge($this->arrayValue($log->metadata), $metadata),
            ]);
        }

        return $log->fresh();
    }

    public function markMachineDetectionByProviderCallId(
        string $providerCallId,
        string $result,
        array $metadata = []
    ): void {
        $log = $this->findLogByProviderCallId($providerCallId);

        $currentMetadata = $this->arrayValue($log->metadata);

        $log->update([
            'metadata' => array_merge($currentMetadata, [
                'telnyx_machine_detection' => [
                    'result' => $result,
                    'detected_at' => now()->toDateTimeString(),
                ],
            ], $metadata),
        ]);
    }
}
