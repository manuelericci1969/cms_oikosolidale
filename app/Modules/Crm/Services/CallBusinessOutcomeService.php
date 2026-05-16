<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallBusinessOutcomeService
{
    public function applySuggestedOutcome(
        CallLog $callLog,
        string $suggestedOutcome,
        ?string $note = null,
        ?string $callbackAt = null
    ): void {
        $suggestedOutcome = trim($suggestedOutcome);

        if ($suggestedOutcome === '' || $suggestedOutcome === 'continue') {
            return;
        }

        $queueItem = CallQueue::query()->find($callLog->queue_id);

        if (!$queueItem) {
            return;
        }

        DB::transaction(function () use ($callLog, $queueItem, $suggestedOutcome, $note, $callbackAt) {
            $businessOutcome = $this->mapSuggestedToBusinessOutcome($suggestedOutcome);
            $normalizedNote = trim((string) $note) ?: $this->defaultNote($suggestedOutcome);
            $callbackDate = $callbackAt ? Carbon::parse($callbackAt) : null;

            $callLog->update([
                'business_outcome' => $businessOutcome,
                'operator_note' => $normalizedNote,
                'callback_at' => $callbackDate,
            ]);

            match ($suggestedOutcome) {
                'interested' => $queueItem->update([
                    'status' => CallQueue::STATUS_COMPLETED,
                    'last_outcome' => $businessOutcome,
                    'last_outcome_note' => $normalizedNote,
                    'next_attempt_at' => null,
                    'completed_at' => $callLog->ended_at ?? now(),
                ]),

                'not_interested' => $queueItem->update([
                    'status' => CallQueue::STATUS_COMPLETED,
                    'last_outcome' => $businessOutcome,
                    'last_outcome_note' => $normalizedNote,
                    'next_attempt_at' => null,
                    'completed_at' => $callLog->ended_at ?? now(),
                ]),

                'callback_requested' => $queueItem->update([
                    'status' => CallQueue::STATUS_CALLBACK,
                    'last_outcome' => $businessOutcome,
                    'last_outcome_note' => $normalizedNote,
                    'next_attempt_at' => $callbackDate ?? now()->addDay(),
                    'completed_at' => null,
                ]),

                'do_not_call' => $queueItem->update([
                    'status' => CallQueue::STATUS_COMPLETED,
                    'last_outcome' => $businessOutcome,
                    'last_outcome_note' => $normalizedNote,
                    'next_attempt_at' => null,
                    'completed_at' => $callLog->ended_at ?? now(),
                ]),

                'appointment_set' => $queueItem->update([
                    'status' => CallQueue::STATUS_COMPLETED,
                    'last_outcome' => $businessOutcome,
                    'last_outcome_note' => $normalizedNote,
                    'next_attempt_at' => null,
                    'completed_at' => $callLog->ended_at ?? now(),
                ]),

                default => null,
            };
        });
    }

    protected function mapSuggestedToBusinessOutcome(string $suggestedOutcome): ?string
    {
        return match ($suggestedOutcome) {
            'interested' => CallLog::BUSINESS_INTERESTED,
            'not_interested' => CallLog::BUSINESS_NOT_INTERESTED,
            'callback_requested' => CallLog::BUSINESS_CALLBACK_REQUESTED,
            'do_not_call' => CallLog::BUSINESS_DO_NOT_CALL,
            'appointment_set' => CallLog::BUSINESS_APPOINTMENT_SET,
            default => null,
        };
    }

    protected function defaultNote(string $suggestedOutcome): string
    {
        return match ($suggestedOutcome) {
            'interested' => 'Interlocutore interessato.',
            'not_interested' => 'Interlocutore non interessato.',
            'callback_requested' => 'Richiesto ricontatto.',
            'do_not_call' => 'Richiesta di non ricontattare.',
            'appointment_set' => 'Appuntamento concordato.',
            default => 'Esito business aggiornato.',
        };
    }
}
