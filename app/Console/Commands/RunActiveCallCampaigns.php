<?php

namespace App\Console\Commands;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Services\CallExecutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunActiveCallCampaigns extends Command
{
    protected $signature = 'crm:calls:run-active {--campaign_id=} {--limit=10}';

    protected $description = 'Esegue una chiamata per ciascuna campagna attiva, fino a un limite massimo.';

    public function handle(CallExecutionService $executor): int
    {
        $campaignId = $this->option('campaign_id');
        $limit = max((int) $this->option('limit'), 1);

        $query = CallCampaign::query()
            ->where('status', CallCampaign::STATUS_ACTIVE)
            ->where('is_active', true)
            ->orderBy('id');

        if ($campaignId) {
            $query->where('id', (int) $campaignId);
        }

        $campaigns = $query->limit($limit)->get();

        if ($campaigns->isEmpty()) {
            $this->info('Nessuna campagna attiva trovata.');
            return self::SUCCESS;
        }

        $processed = 0;
        $errors = 0;
        $empty = 0;

        foreach ($campaigns as $campaign) {
            try {
                $result = $executor->executeNextForCampaign($campaign);

                if (!$result) {
                    $empty++;
                    $this->line("Campagna {$campaign->id} ({$campaign->name}): nessun contatto da chiamare.");
                    continue;
                }

                $processed++;

                $queueItem = $result['queue_item'] ?? null;
                $callLog = $result['call_log'] ?? null;
                $providerResult = $result['provider_result'] ?? [];

                $this->info(sprintf(
                    'Campagna %d: chiamata avviata. Queue #%s, Log #%s, ProviderCallId=%s',
                    $campaign->id,
                    $queueItem?->id ?? '-',
                    $callLog?->id ?? '-',
                    $providerResult['call_control_id'] ?? '-'
                ));
            } catch (\Throwable $e) {
                $errors++;

                Log::error('Errore esecuzione campagna chiamate attive', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);

                $this->error("Campagna {$campaign->id} ({$campaign->name}): errore {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Campagne elaborate con chiamata avviata: {$processed}");
        $this->line("Campagne senza contatti utili: {$empty}");
        $this->line("Campagne con errore: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
