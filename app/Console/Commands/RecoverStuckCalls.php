<?php

namespace App\Console\Commands;

use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverStuckCalls extends Command
{
    protected $signature = 'crm:calls:recover-stuck
        {--minutes=5 : Soglia minima in minuti per considerare stuck una calling}
        {--delay=5 : Minuti di attesa prima del retry}
        {--campaign_id= : Limita il recovery a una specifica campagna}';

    protected $description = 'Recupera le chiamate rimaste bloccate in stato calling oltre una soglia temporale.';

    public function handle(): int
    {
        $minutes = max((int) $this->option('minutes'), 1);
        $delay = max((int) $this->option('delay'), 0);
        $campaignId = $this->option('campaign_id');
        $now = now();
        $threshold = $now->copy()->subMinutes($minutes);

        $this->info("Cerco chiamate stuck in calling prima di: {$threshold->toDateTimeString()}");

        $query = CallQueue::query()
            ->where('status', CallQueue::STATUS_CALLING)
            ->whereNull('completed_at')
            ->whereNotNull('last_attempt_at')
            ->where('last_attempt_at', '<=', $threshold)
            ->orderBy('id');

        if ($campaignId) {
            $query->where('campaign_id', (int) $campaignId);
        }

        $itemIds = $query->pluck('id');

        if ($itemIds->isEmpty()) {
            $this->info('Nessuna chiamata bloccata trovata.');
            return self::SUCCESS;
        }

        $recovered = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($itemIds as $itemId) {
            DB::transaction(function () use ($itemId, $delay, $minutes, $now, &$recovered, &$failed, &$skipped) {
                $queueItem = CallQueue::query()
                    ->whereKey($itemId)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$queueItem ||
                    $queueItem->status !== CallQueue::STATUS_CALLING ||
                    $queueItem->completed_at !== null
                ) {
                    $skipped++;
                    return;
                }

                $latestLog = CallLog::query()
                    ->where('queue_id', $queueItem->id)
                    ->latest('id')
                    ->first();

                $hasAttemptsLeft = $queueItem->attempts < $queueItem->max_attempts;

                if ($hasAttemptsLeft) {
                    $queueItem->update([
                        'status' => CallQueue::STATUS_RETRY,
                        'last_outcome' => CallLog::TECH_ERROR,
                        'last_outcome_note' => 'Recupero automatico: chiamata rimasta in calling oltre soglia.',
                        'next_attempt_at' => $delay > 0 ? $now->copy()->addMinutes($delay) : null,
                        'completed_at' => null,
                    ]);

                    if ($latestLog && !$latestLog->ended_at) {
                        $latestLog->update([
                            'call_status' => CallLog::CALL_STATUS_FAILED,
                            'technical_outcome' => CallLog::TECH_ERROR,
                            'operator_note' => 'Recupero automatico: chiamata bloccata in calling.',
                            'ended_at' => $now,
                            'metadata' => array_merge($latestLog->metadata ?? [], [
                                'auto_recovered' => true,
                                'auto_recovered_at' => $now->toDateTimeString(),
                                'recover_threshold_minutes' => $minutes,
                                'queue_status_before' => CallQueue::STATUS_CALLING,
                            ]),
                        ]);
                    }

                    Log::warning('CallQueue recuperata da stato calling', [
                        'queue_id' => $queueItem->id,
                        'campaign_id' => $queueItem->campaign_id,
                        'attempts' => $queueItem->attempts,
                        'max_attempts' => $queueItem->max_attempts,
                        'next_attempt_at' => $queueItem->next_attempt_at,
                    ]);

                    $recovered++;
                    return;
                }

                $queueItem->update([
                    'status' => CallQueue::STATUS_FAILED,
                    'last_outcome' => 'max_attempts_reached',
                    'last_outcome_note' => 'Recupero automatico: esauriti i tentativi disponibili.',
                    'completed_at' => $now,
                    'next_attempt_at' => null,
                ]);

                if ($latestLog && !$latestLog->ended_at) {
                    $latestLog->update([
                        'call_status' => CallLog::CALL_STATUS_FAILED,
                        'technical_outcome' => CallLog::TECH_ERROR,
                        'operator_note' => 'Recupero automatico finale: max tentativi raggiunti.',
                        'ended_at' => $now,
                        'metadata' => array_merge($latestLog->metadata ?? [], [
                            'auto_failed' => true,
                            'auto_failed_at' => $now->toDateTimeString(),
                            'recover_threshold_minutes' => $minutes,
                            'queue_status_before' => CallQueue::STATUS_CALLING,
                        ]),
                    ]);
                }

                Log::warning('CallQueue chiusa come failed dopo recovery', [
                    'queue_id' => $queueItem->id,
                    'campaign_id' => $queueItem->campaign_id,
                    'attempts' => $queueItem->attempts,
                    'max_attempts' => $queueItem->max_attempts,
                ]);

                $failed++;
            });
        }

        $this->info("Recuperate: {$recovered}");
        $this->info("Fallite definitivamente: {$failed}");
        $this->info("Saltate: {$skipped}");

        return self::SUCCESS;
    }
}
