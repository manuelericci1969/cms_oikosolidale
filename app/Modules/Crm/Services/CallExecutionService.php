<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Crm\Services\Telephony\TelnyxCallProvider;
use RuntimeException;

class CallExecutionService
{
    /**
     * Prende il prossimo contatto utile da chiamare per una campagna
     * e lo marca come "calling", creando il log iniziale.
     */
    public function acquireNextForCampaign(CallCampaign $campaign): ?array
    {
        return DB::transaction(function () use ($campaign) {
            $queueItem = CallQueue::query()
                ->where('campaign_id', $campaign->id)
                ->where('client_id', $campaign->client_id)
                ->where('do_not_call', false)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereIn('status', [
                    CallQueue::STATUS_PENDING,
                    CallQueue::STATUS_RETRY,
                    CallQueue::STATUS_CALLBACK,
                ])
                ->where(function ($q) {
                    $q->whereNull('next_attempt_at')
                        ->orWhere('next_attempt_at', '<=', now());
                })
                ->orderByRaw("
                    CASE status
                        WHEN 'callback' THEN 1
                        WHEN 'retry' THEN 2
                        WHEN 'pending' THEN 3
                        ELSE 99
                    END
                ")
                ->orderBy('scheduled_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$queueItem) {
                return null;
            }

            if ($queueItem->attempts >= $queueItem->max_attempts) {
                $queueItem->update([
                    'status' => CallQueue::STATUS_FAILED,
                    'last_outcome' => 'max_attempts_reached',
                    'last_outcome_note' => 'Numero massimo tentativi raggiunto automaticamente.',
                    'completed_at' => now(),
                ]);

                return null;
            }

            $queueItem->update([
                'status' => CallQueue::STATUS_CALLING,
                'attempts' => $queueItem->attempts + 1,
                'last_attempt_at' => now(),
            ]);

            $log = CallLog::create([
                'client_id' => $queueItem->client_id,
                'campaign_id' => $queueItem->campaign_id,
                'queue_id' => $queueItem->id,
                'owner_id' => $queueItem->owner_id,
                'source_type' => $queueItem->source_type,
                'source_id' => $queueItem->source_id,
                'provider' => $campaign->provider,
                'phone' => $queueItem->phone,
                'direction' => CallLog::DIRECTION_OUTBOUND,
                'call_status' => 'initiated',
                'started_at' => now(),
                'metadata' => [
                    'execution_mode' => 'pre-provider',
                    'campaign_name' => $campaign->name,
                ],
            ]);

            app(CallConversationService::class)->addSystemMessage(
                $log,
                'Inizio chiamata reale campagna #' . $campaign->id . ' verso ' . ($queueItem->contact_name ?: $queueItem->phone),
                [
                    'source' => 'call_execution_service',
                    'campaign_id' => $campaign->id,
                    'queue_id' => $queueItem->id,
                    'call_log_id' => $log->id,
                    'mode' => 'real_call_bootstrap',
                ]
            );

            return [
                'campaign' => $campaign,
                'queue_item' => $queueItem->fresh(),
                'call_log' => $log,
                'provider_payload' => $this->buildProviderPayload($campaign, $queueItem, $log),
                'ai_context' => $this->buildAiContext($campaign, $queueItem),
            ];
        });
    }

    /**
     * Aggancia il provider_call_id quando la chiamata viene realmente creata dal provider.
     */
    public function attachProviderCallId(CallLog $log, string $providerCallId, array $extraMetadata = []): CallLog
    {
        $metadata = array_merge($log->metadata ?? [], $extraMetadata);

        $log->update([
            'provider_call_id' => $providerCallId,
            'metadata' => $metadata,
        ]);

        return $log->fresh();
    }

    /**
     * Marca fallimento immediato di avvio chiamata lato provider/API.
     */
    public function markStartFailed(
        CallQueue $queueItem,
        CallLog $log,
        string $technicalOutcome = CallLog::TECH_ERROR,
        ?string $note = null,
        array $metadata = []
    ): void {
        DB::transaction(function () use ($queueItem, $log, $technicalOutcome, $note, $metadata) {
            $shouldRetry = $queueItem->attempts < $queueItem->max_attempts;

            $queueItem->update([
                'status' => $shouldRetry ? CallQueue::STATUS_RETRY : CallQueue::STATUS_FAILED,
                'last_outcome' => $technicalOutcome,
                'last_outcome_note' => $note,
                'next_attempt_at' => $shouldRetry ? $this->computeRetryAt($queueItem) : null,
                'completed_at' => $shouldRetry ? null : now(),
            ]);

            $log->update([
                'call_status' => 'failed',
                'technical_outcome' => $technicalOutcome,
                'operator_note' => $note,
                'ended_at' => now(),
                'metadata' => array_merge($log->metadata ?? [], $metadata),
            ]);
        });
    }

    /**
     * Completa la chiamata con esiti tecnici/business.
     */
    public function completeCall(
        CallQueue $queueItem,
        CallLog $log,
        array $data
    ): void {
        DB::transaction(function () use ($queueItem, $log, $data) {
            $technicalOutcome = $data['technical_outcome'] ?? null;
            $businessOutcome  = $data['business_outcome'] ?? null;
            $operatorNote     = $data['operator_note'] ?? null;
            $aiSummary        = $data['ai_summary'] ?? null;
            $transcript       = $data['transcript'] ?? null;
            $durationSeconds  = (int) ($data['duration_seconds'] ?? 0);
            $answeredAt       = $data['answered_at'] ?? null;
            $endedAt          = $data['ended_at'] ?? now();
            $metadata         = $data['metadata'] ?? [];

            $queueStatus = $this->mapQueueStatus($technicalOutcome, $businessOutcome, $queueItem);
            $nextAttemptAt = $this->determineNextAttemptAt($queueStatus, $queueItem, $data);

            $queueItem->update([
                'status' => $queueStatus,
                'last_outcome' => $businessOutcome ?: $technicalOutcome,
                'last_outcome_note' => $operatorNote,
                'next_attempt_at' => $nextAttemptAt,
                'completed_at' => in_array($queueStatus, [
                    CallQueue::STATUS_COMPLETED,
                    CallQueue::STATUS_FAILED,
                    CallQueue::STATUS_SKIPPED,
                    CallQueue::STATUS_CANCELLED,
                ], true) ? $endedAt : null,
                'do_not_call' => $businessOutcome === CallLog::BUSINESS_DO_NOT_CALL,
                'do_not_call_at' => $businessOutcome === CallLog::BUSINESS_DO_NOT_CALL ? now() : null,
            ]);

            $log->update([
                'call_status' => $data['call_status'] ?? 'completed',
                'technical_outcome' => $technicalOutcome,
                'business_outcome' => $businessOutcome,
                'operator_note' => $operatorNote,
                'ai_summary' => $aiSummary,
                'transcript' => $transcript,
                'duration_seconds' => $durationSeconds,
                'answered_at' => $answeredAt,
                'ended_at' => $endedAt,
                'metadata' => array_merge($log->metadata ?? [], $metadata),
            ]);
        });
    }

    protected function buildProviderPayload(CallCampaign $campaign, CallQueue $queueItem, CallLog $log): array
    {
        return [
            'provider' => $campaign->provider,
            'to' => $queueItem->phone,
            'campaign_id' => $campaign->id,
            'queue_id' => $queueItem->id,
            'call_log_id' => $log->id,
            'client_id' => $queueItem->client_id,
            'owner_id' => $queueItem->owner_id,
            'webhook_context' => [
                'campaign_id' => $campaign->id,
                'queue_id' => $queueItem->id,
                'call_log_id' => $log->id,
                'source_type' => $queueItem->source_type,
                'source_id' => $queueItem->source_id,
            ],
        ];
    }

    protected function buildAiContext(CallCampaign $campaign, CallQueue $queueItem): array
    {
        $payload = $queueItem->payload ?? [];

        return [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'script_prompt' => $campaign->script_prompt,
                'provider' => $campaign->provider,
                'source_mode' => $campaign->source_mode,
            ],
            'contact' => [
                'name' => $queueItem->contact_name,
                'phone' => $queueItem->phone,
                'email' => $queueItem->email,
                'source_type' => $queueItem->source_type,
                'source_id' => $queueItem->source_id,
            ],
            'business_context' => [
                'list_id' => $payload['list_id'] ?? null,
                'segment' => $payload['segment'] ?? null,
                'city' => $payload['city'] ?? null,
                'province' => $payload['province'] ?? null,
                'region' => $payload['region'] ?? null,
                'country' => $payload['country'] ?? null,
                'business_type' => $payload['business_type'] ?? null,
                'contact_role' => $payload['contact_role'] ?? null,
                'commercial_potential' => $payload['commercial_potential'] ?? null,
                'site_rating' => $payload['site_rating'] ?? null,
                'seo_score' => $payload['seo_score'] ?? null,
                'source_type_original' => $payload['source_type_original'] ?? null,
                'source_url' => $payload['source_url'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ],
        ];
    }

    protected function computeRetryAt(CallQueue $queueItem): Carbon
    {
        return match ($queueItem->attempts) {
            1 => now()->addMinutes(15),
            2 => now()->addHours(4),
            default => now()->addDay(),
        };
    }

    protected function mapQueueStatus(?string $technicalOutcome, ?string $businessOutcome, CallQueue $queueItem): string
    {
        if ($businessOutcome === CallLog::BUSINESS_CALLBACK_REQUESTED) {
            return CallQueue::STATUS_CALLBACK;
        }

        if ($businessOutcome === CallLog::BUSINESS_DO_NOT_CALL) {
            return CallQueue::STATUS_COMPLETED;
        }

        if (in_array($businessOutcome, [
            CallLog::BUSINESS_INTERESTED,
            CallLog::BUSINESS_NOT_INTERESTED,
            CallLog::BUSINESS_QUALIFIED,
            CallLog::BUSINESS_APPOINTMENT_SET,
            CallLog::BUSINESS_ALREADY_CUSTOMER,
            CallLog::BUSINESS_WRONG_CONTACT,
            CallLog::BUSINESS_NO_DECISION,
        ], true)) {
            return CallQueue::STATUS_COMPLETED;
        }

        if (in_array($technicalOutcome, [
            CallLog::TECH_BUSY,
            CallLog::TECH_NO_ANSWER,
            CallLog::TECH_VOICEMAIL,
            CallLog::TECH_ERROR,
        ], true)) {
            return $queueItem->attempts < $queueItem->max_attempts
                ? CallQueue::STATUS_RETRY
                : CallQueue::STATUS_FAILED;
        }

        if (in_array($technicalOutcome, [
            CallLog::TECH_INVALID_NUMBER,
            CallLog::TECH_REJECTED,
            CallLog::TECH_CANCELLED,
            CallLog::TECH_FAILED,
        ], true)) {
            return CallQueue::STATUS_FAILED;
        }

        return CallQueue::STATUS_COMPLETED;
    }

    protected function determineNextAttemptAt(string $queueStatus, CallQueue $queueItem, array $data): ?Carbon
    {
        if ($queueStatus === CallQueue::STATUS_CALLBACK) {
            if (!empty($data['callback_at'])) {
                return Carbon::parse($data['callback_at']);
            }

            return now()->addDay();
        }

        if ($queueStatus === CallQueue::STATUS_RETRY) {
            return $this->computeRetryAt($queueItem);
        }

        return null;
    }

    public function executeNextForCampaign(CallCampaign $campaign): ?array
    {
        $acquired = $this->acquireNextForCampaign($campaign);

        if (!$acquired) {
            return null;
        }

        /** @var \App\Modules\Crm\Models\CallQueue $queueItem */
        $queueItem = $acquired['queue_item'];

        /** @var \App\Modules\Crm\Models\CallLog $log */
        $log = $acquired['call_log'];

        $clientState = base64_encode(json_encode([
            'campaign_id' => $campaign->id,
            'queue_id' => $queueItem->id,
            'call_log_id' => $log->id,
            'source_type' => $queueItem->source_type,
            'source_id' => $queueItem->source_id,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            $providerResult = app(TelnyxCallProvider::class)->createOutboundCall([
                'to' => $queueItem->phone,
                'client_state' => $clientState,
            ]);

            $providerCallId = $providerResult['call_control_id'] ?? null;

            if (!$providerCallId) {
                throw new RuntimeException('Telnyx non ha restituito call_control_id.');
            }

            $updatedLog = $this->attachProviderCallId(
                $log,
                $providerCallId,
                [
                    'provider_result' => $providerResult['response'] ?? [],
                ]
            );

            return [
                ...$acquired,
                'call_log' => $updatedLog,
                'provider_result' => $providerResult,
            ];
        } catch (\Throwable $e) {
            $this->markStartFailed(
                $queueItem,
                $log,
                technicalOutcome: \App\Modules\Crm\Models\CallLog::TECH_ERROR,
                note: $e->getMessage(),
                metadata: [
                    'exception' => get_class($e),
                ]
            );

            throw $e;
        }
    }
}
