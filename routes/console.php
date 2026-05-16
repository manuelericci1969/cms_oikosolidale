<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\Setting;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
| Comandi Artisan inline + scheduler Laravel 12.
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/**
 * Diagnostica rapida: stampa i path e lo stato di public/storage.
 */
Artisan::command('storage:diag', function () {
    $publicStorage = public_path('storage');
    $target        = storage_path('app/public');

    $this->info('base_path:    ' . base_path());
    $this->info('public_path:  ' . public_path());
    $this->info('storage_path: ' . storage_path());
    $this->line('');
    $this->info('public/storage => ' . $publicStorage);
    $this->info('target         => ' . $target);

    $this->line('');
    $this->info('Exists public/storage: ' . (file_exists($publicStorage) ? 'yes' : 'no'));
    $this->info('Is symlink:            ' . (is_link($publicStorage) ? 'yes' : 'no'));
    $this->info('Is dir:                ' . (is_dir($publicStorage) ? 'yes' : 'no'));

    if (is_link($publicStorage)) {
        $this->info('Symlink -> ' . (readlink($publicStorage) ?: '(unknown)'));
    }

    $this->line('');
    $this->info('Target exists:         ' . (is_dir($target) ? 'yes' : 'no'));

    return 0;
})->purpose('Diagnostica storage link (public/storage)');


/**
 * Ricrea public/storage -> storage/app/public.
 * Se symlink non permessi su hosting, usa --copy per copiare i file.
 */
Artisan::command('storage:relink {--copy : Copia invece del symlink}', function () {
    $publicStorage = public_path('storage');
    $target        = storage_path('app/public');

    $this->info('public/storage => ' . $publicStorage);
    $this->info('target         => ' . $target);

    if (!is_dir($target)) {
        $this->error("Target non trovato: {$target}");
        return 1;
    }

    if (is_link($publicStorage)) {
        $this->warn('public/storage è un symlink esistente -> ' . (readlink($publicStorage) ?: ''));
        @unlink($publicStorage);
        $this->warn('Symlink rimosso.');
    } elseif (file_exists($publicStorage)) {
        $backup = $publicStorage . '_old_' . date('Ymd_His');

        if (@rename($publicStorage, $backup)) {
            $this->warn("Esistente rinominato in: {$backup}");
        } else {
            $this->error("Non riesco a rinominare {$publicStorage} (permessi?).");
            return 1;
        }
    }

    if ($this->option('copy')) {
        File::ensureDirectoryExists($publicStorage);

        if (!File::copyDirectory($target, $publicStorage)) {
            $this->error('Copia fallita (copyDirectory).');
            return 1;
        }

        $this->info('OK: Copiato storage/app/public -> public/storage');
    } else {
        File::ensureDirectoryExists(dirname($publicStorage));

        if (!@symlink($target, $publicStorage)) {
            $this->error('Symlink fallito (symlink()). Probabile blocco symlink su hosting.');
            $this->warn('Riprova con: php artisan storage:relink --copy');
            return 1;
        }

        $this->info('OK: Creato symlink public/storage -> storage/app/public');
    }

    $this->line('');
    $this->info('Check finale:');
    $this->info('exists:  ' . (file_exists($publicStorage) ? 'yes' : 'no'));
    $this->info('is_link: ' . (is_link($publicStorage) ? 'yes' : 'no'));

    if (is_link($publicStorage)) {
        $this->info('link->   ' . (readlink($publicStorage) ?: ''));
    }

    return 0;
})->purpose('Ricrea public/storage verso storage/app/public (o copia con --copy)');


/**
 * Sync Google Calendar <-> CRM.
 */
Artisan::command('crm:google-sync {--force : Ignora blocchi/limiti interni}', function () {
    $enabled = (bool) Setting::get('calendar.google.enabled', false);

    if (!$enabled) {
        $this->warn('Google Calendar sync DISABILITATO (calendar.google.enabled = false).');
        return 0;
    }

    $direction = (string) Setting::get('calendar.sync.direction', 'two_way');
    $this->info('Avvio sync Google Calendar. Direzione: ' . $direction);

    $serviceClass = \App\Services\GoogleCalendarSyncService::class;

    if (!class_exists($serviceClass)) {
        $this->warn("Service non trovato: {$serviceClass}");
        $this->warn("Crea il service oppure sostituisci questa chiamata con la tua logica di sync.");
        return 0;
    }

    try {
        $svc = app($serviceClass);

        if (method_exists($svc, 'syncAll')) {
            $svc->syncAll($direction, (bool) $this->option('force'));
        } elseif (method_exists($svc, 'sync')) {
            $svc->sync($direction, (bool) $this->option('force'));
        } else {
            $this->error("Il service {$serviceClass} non espone metodi syncAll() o sync().");
            return 1;
        }

        $this->info('Sync completato.');
        return 0;
    } catch (\Throwable $e) {
        $this->error('Errore sync: ' . $e->getMessage());
        return 1;
    }
})->purpose('Sincronizza eventi tra CRM e Google Calendar');


/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
*/
(function () {
    /**
     * CRM Call Campaigns
     * - Esegue le campagne attive ogni minuto
     * - Recupera le calling stuck ogni minuto
     */
    Schedule::command('crm:calls:run-active --limit=10')
        ->everyMinute()
        ->withoutOverlapping(1);

    Schedule::command('crm:calls:recover-stuck --minutes=2 --delay=5')
        ->everyMinute()
        ->withoutOverlapping(1);

    /**
     * Google Calendar sync
     */
    try {
        $enabled = (bool) Setting::get('calendar.google.enabled', false);

        if ($enabled) {
            $minutes = (int) Setting::get('calendar.sync.interval_minutes', 5);
            $minutes = max(1, min(1440, $minutes));

            $cron = null;

            if ($minutes <= 60 && (60 % $minutes === 0)) {
                $cron = "*/{$minutes} * * * *";
            } elseif ($minutes % 60 === 0) {
                $hours = (int) ($minutes / 60);
                $hours = max(1, min(24, $hours));
                $cron  = "0 */{$hours} * * *";
            } else {
                $cron = '*/5 * * * *';
            }

            Schedule::command('crm:google-sync')
                ->cron($cron)
                ->withoutOverlapping(10);
        }
    } catch (\Throwable $e) {
        Log::error('Errore scheduler Google Calendar', [
            'message' => $e->getMessage(),
        ]);
    }

    /**
     * SEO
     */
    Schedule::command('seo:improve-pages --location=Olbia')
        ->dailyAt('02:30')
        ->withoutOverlapping(30);
})();
