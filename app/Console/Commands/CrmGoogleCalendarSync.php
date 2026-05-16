<?php

namespace App\Console\Commands;

use App\Models\GoogleCalendarAccount;
use App\Models\Setting;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Console\Command;

class CrmGoogleCalendarSync extends Command
{
    protected $signature = 'crm:google-calendar-sync';
    protected $description = 'Sincronizza eventi Google Calendar <-> CRM';

    public function handle(GoogleCalendarSyncService $sync): int
    {
        // usa SOLO la chiave corretta (dot). Il service già supporta compat, ma qui puliamo.
        if (!(bool) Setting::get('calendar.google.enabled', false)) {
            $this->info('Sync disattivata.');
            return self::SUCCESS;
        }

        $tz = (string) Setting::get('calendar.timezone', config('app.timezone', 'Europe/Rome'));
        $pastDays   = (int) Setting::get('calendar.sync.past_days', 30);
        $futureDays = (int) Setting::get('calendar.sync.future_days', 180);

        $from = \Carbon\Carbon::now($tz)->subDays($pastDays)->startOfDay();
        $to   = \Carbon\Carbon::now($tz)->addDays($futureDays)->endOfDay();

        GoogleCalendarAccount::where('enabled', true)->chunkById(50, function($chunk) use ($sync, $from, $to) {
            foreach ($chunk as $acc) {
                try {
                    // pull only per account (niente push per evitare cross-calendari)
                    $sync->syncAccountRange($acc, 'pull_only', $from, $to);
                    $this->info("Account {$acc->id}: OK");
                } catch (\Throwable $e) {
                    $this->error("Account {$acc->id}: ".$e->getMessage());
                }
            }
        });

        return self::SUCCESS;
    }

}
