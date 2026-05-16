<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CallExecutionService
{
    public function executeNextForCampaign(CallCampaign $campaign): ?array
    {
        if (!$campaign->isRunnable()) {
            return null;
        }

        return DB::transaction(function () use ($campaign) {
            $queueItem = CallQueue::query()
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', [
                    CallQueue::STATUS_PENDING,
                    CallQueue::STATUS_RETRY,
                ])
                ->whereNull('completed_at')
                ->where(function ($q) {
                    $q->whereNull('next_attempt_at')
                        ->orWhere('next_attempt_at', '<=', now());
                })
                ->orderByRaw("CASE WHEN status = 'retry' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$queueItem) {
                return null;
            }

            return $this->executeQueueItem($campaign, $queueItem);
        }, 3);
    }

    public function executeQueueItem(CallCampaign $campaign, CallQueue $queueItem): array
    {
        if ((int) $queueItem->campaign_id !== (int) $campaign->id) {
            throw new RuntimeException('Queue item non appartenente alla campagna.');
        }

        if (!$queueItem->isCallable()) {
            throw new RuntimeException("Queue item #{$queueItem->id} non chiamabile.");
        }

        $log = null;

        try {
            DB::transaction(function () use ($queueItem, &$log, $campaign) {
                $queueItem->refresh();

                $queueItem->update([
                    'status' => CallQueue::STATUS_CALLING,
                    'attempts' => (int) $queueItem->attempts + 1,
                    'last_attempt_at' => now(),
                    'next_attempt_at' => null,
                    'completed_at' => null,
                    'last_outcome' => null,
                    'last_outcome_note' => null,
                    'metadata' => array_merge($this->arrayValue($queueItem->metadata), [
                        'last_execution_started_at' => now()->toDateTimeString(),
                    ]),
                ]);

                $log = CallLog::create([
                    'campaign_id' => $campaign->id,
                    'queue_id' => $queueItem->id,
                    'owner_id' => $campaign->owner_id,
                    'provider' => $campaign->provider,
                    'phone' => $queueItem->phone,
                    'call_status' => CallLog::CALL_STATUS_INITIATED,
                    'technical_outcome' => null,
                    'business_outcome' => null,
                    'operator_note' => null,
                    'ai_summary' => null,
                    'transcript' => null,
                    'duration_seconds' => 0,
                    'answered_at' => null,
                    'ended_at' => null,
                    'callback_at' => null,
                    'metadata' => [
                        'queue_item_id' => $queueItem->id,
                        'contact_name' => $queueItem->contact_name,
                        'contact_type' => $queueItem->contact_type,
                        'contact_id' => $queueItem->contact_id,
                        'source_type' => $queueItem->source_type,
                        'source_id' => $queueItem->source_id,
                        'campaign_name' => $campaign->name,
                    ],
                ]);
            }, 3);

            $providerResult = $this->startProviderCall($campaign, $queueItem, $log);

            $providerCallId = data_get($providerResult, 'call_control_id')
                ?? data_get($providerResult, 'data.call_control_id')
                ?? data_get($providerResult, 'id');

            if (!$providerCallId) {
                throw new RuntimeException('ProviderCallId non restituito dal provider.');
            }

            $boundLog = app(CallOutcomeSyncService::class)->bindProviderCallByLogId(
                $log->id,
                $providerCallId,
                [
                    'provider_response' => $providerResult,
                ]
            );

            return [
                'queue_item' => $queueItem->fresh(),
                'log' => $boundLog,
                'provider_result' => $providerResult,
            ];
        } catch (Throwable $e) {
            if ($log) {
                try {
                    $this->handleExecutionFailure($campaign, $queueItem->fresh(), $log->fresh(), $e->getMessage());
                } catch (Throwable $inner) {
                    report($inner);
                }
            } else {
                try {
                    $this->restoreQueueAfterEarlyFailure($queueItem->fresh(), $e->getMessage());
                } catch (Throwable $inner) {
                    report($inner);
                }
            }

            throw $e;
        }
    }

    public function completeCall(CallQueue $queueItem, CallLog $log, array $data): void
    {
        DB::transaction(function () use ($queueItem, $log, $data) {
            $queueItem->refresh();
            $log->refresh();

            $callStatus = $data['call_status'] ?? CallLog::CALL_STATUS_COMPLETED;
            $technicalOutcome = $data['technical_outcome'] ?? CallLog::TECH_COMPLETED;
            $businessOutcome = $data['business_outcome'] ?? null;
            $operatorNote = $data['operator_note'] ?? null;
            $aiSummary = $data['ai_summary'] ?? null;
            $transcript = $data['transcript'] ?? null;
            $durationSeconds = (int) ($data['duration_seconds'] ?? 0);
            $answeredAt = $data['answered_at'] ?? $log->answered_at;
            $endedAt = $data['ended_at'] ?? now();
            $callbackAt = $data['callback_at'] ?? null;
            $metadata = $data['metadata'] ?? [];

            $log->update([
                'call_status' => $callStatus,
                'technical_outcome' => $technicalOutcome,
                'business_outcome' => $businessOutcome,
                'operator_note' => $operatorNote,
                'ai_summary' => $aiSummary,
                'transcript' => $transcript,
                'duration_seconds' => $durationSeconds,
                'answered_at' => $answeredAt,
                'ended_at' => $endedAt,
                'callback_at' => $callbackAt,
                'metadata' => array_merge($this->arrayValue($log->metadata), $metadata),
            ]);

            $hasAttemptsLeft = $queueItem->hasAttemptsLeft();

            if ($this->isSuccessfulOutcome($technicalOutcome, $callStatus)) {
                $queueItem->update([
                    'status' => CallQueue::STATUS_COMPLETED,
                    'last_outcome' => $technicalOutcome,
                    'last_outcome_note' => $operatorNote,
                    'next_attempt_at' => null,
                    'completed_at' => $endedAt,
                    'metadata' => array_merge($this->arrayValue($queueItem->metadata), [
                        'last_execution_completed_at' => now()->toDateTimeString(),
                    ]),
                ]);

                return;
            }

            if ($technicalOutcome === CallQueue::OUTCOME_CALLBACK || $callbackAt) {
                $queueItem->update([
                    'status' => CallQueue::STATUS_CALLBACK,
                    'last_outcome' => $technicalOutcome,
                    'last_outcome_note' => $operatorNote,
                    'next_attempt_at' => $callbackAt ?: now()->addMinutes(30),
                    'completed_at' => null,
                ]);

                return;
            }

            if ($hasAttemptsLeft) {
                $queueItem->update([
                    'status' => CallQueue::STATUS_RETRY,
                    'last_outcome' => $technicalOutcome,
                    'last_outcome_note' => $operatorNote,
                    'next_attempt_at' => $this->computeNextRetryAt($queueItem),
                    'completed_at' => null,
                ]);

                return;
            }

            $queueItem->update([
                'status' => CallQueue::STATUS_FAILED,
                'last_outcome' => $technicalOutcome ?: CallQueue::OUTCOME_FAILED,
                'last_outcome_note' => $operatorNote ?: 'Tentativi esauriti.',
                'next_attempt_at' => null,
                'completed_at' => $endedAt,
            ]);
        }, 3);
    }

    protected function startProviderCall(CallCampaign $campaign, CallQueue $queueItem, CallLog $log): array
    {
        return match ($campaign->provider) {
            CallCampaign::PROVIDER_TELNYX => app(TelnyxCallService::class)->startCall(
                phone: $queueItem->phone,
                queueItem: $queueItem,
                campaign: $campaign,
                log: $log
            ),
            default => throw new RuntimeException("Provider non supportato: {$campaign->provider}"),
        };
    }

    protected function handleExecutionFailure(CallCampaign $campaign, CallQueue $queueItem, CallLog $log, string $message): void
    {
        DB::transaction(function () use ($queueItem, $log, $message) {
            $log->update([
                'call_status' => CallLog::CALL_STATUS_FAILED,
                'technical_outcome' => CallLog::TECH_ERROR,
                'operator_note' => $message,
                'ended_at' => now(),
                'metadata' => array_merge($this->arrayValue($log->metadata), [
                    'execution_error' => true,
                ]),
            ]);

            if ($queueItem->hasAttemptsLeft()) {
                $queueItem->update([
                    'status' => CallQueue::STATUS_RETRY,
                    'last_outcome' => CallQueue::OUTCOME_TECHNICAL_TIMEOUT,
                    'last_outcome_note' => $message,
                    'next_attempt_at' => $this->computeNextRetryAt($queueItem),
                    'completed_at' => null,
                ]);
            } else {
                $queueItem->update([
                    'status' => CallQueue::STATUS_FAILED,
                    'last_outcome' => CallQueue::OUTCOME_FAILED,
                    'last_outcome_note' => $message,
                    'next_attempt_at' => null,
                    'completed_at' => now(),
                ]);
            }
        }, 3);
    }

    protected function restoreQueueAfterEarlyFailure(CallQueue $queueItem, string $message): void
    {
        DB::transaction(function () use ($queueItem, $message) {
            if ($queueItem->hasAttemptsLeft()) {
                $queueItem->update([
                    'status' => CallQueue::STATUS_RETRY,
                    'last_outcome' => CallQueue::OUTCOME_FAILED,
                    'last_outcome_note' => $message,
                    'next_attempt_at' => $this->computeNextRetryAt($queueItem),
                    'completed_at' => null,
                ]);
            } else {
                $queueItem->update([
                    'status' => CallQueue::STATUS_FAILED,
                    'last_outcome' => CallQueue::OUTCOME_FAILED,
                    'last_outcome_note' => $message,
                    'next_attempt_at' => null,
                    'completed_at' => now(),
                ]);
            }
        }, 3);
    }

    protected function isSuccessfulOutcome(?string $technicalOutcome, ?string $callStatus): bool
    {
        return $technicalOutcome === CallLog::TECH_COMPLETED
            || $callStatus === CallLog::CALL_STATUS_COMPLETED;
    }

    protected function computeNextRetryAt(CallQueue $queueItem)
    {
        $attempt = max(1, (int) $queueItem->attempts);

        return now()->addMinutes(match (true) {
            $attempt <= 1 => 5,
            $attempt === 2 => 15,
            default => 30,
        });
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
