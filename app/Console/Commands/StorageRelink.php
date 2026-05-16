<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StorageRelink extends Command
{
    protected $signature = 'storage:relink {--copy : Usa copia invece del symlink (fallback se symlink bloccati)}';
    protected $description = 'Ricrea public/storage -> storage/app/public (o copia se richiesto)';

    public function handle(): int
    {
        $publicStorage = public_path('storage');
        $target        = storage_path('app/public');

        $this->info('base_path:    ' . base_path());
        $this->info('public_path:  ' . public_path());
        $this->info('storage_path: ' . storage_path());
        $this->line('');
        $this->info('public/storage: ' . $publicStorage);
        $this->info('target:         ' . $target);

        // Target deve esistere
        if (!is_dir($target)) {
            $this->error("Target non trovato: {$target}");
            return self::FAILURE;
        }

        // Se esiste qualcosa in public/storage, lo rimuoviamo/rinominiamo
        if (is_link($publicStorage)) {
            $this->warn('public/storage è un symlink esistente -> ' . (readlink($publicStorage) ?: ''));
            @unlink($publicStorage);
            $this->warn('Symlink rimosso.');
        } elseif (file_exists($publicStorage)) {
            // cartella o file reale: rinomino per non perdere nulla
            $backup = $publicStorage . '_old_' . date('Ymd_His');
            if (@rename($publicStorage, $backup)) {
                $this->warn("Esistente rinominato in: {$backup}");
            } else {
                $this->error("Non riesco a rinominare {$publicStorage}. Permessi?");
                return self::FAILURE;
            }
        } else {
            $this->info('public/storage non esiste: ok, lo creo ora.');
        }

        // Crea symlink o copia
        if ($this->option('copy')) {
            File::ensureDirectoryExists($publicStorage);
            if (!File::copyDirectory($target, $publicStorage)) {
                $this->error('Copia fallita (File::copyDirectory).');
                return self::FAILURE;
            }
            $this->info('OK: Copiato storage/app/public -> public/storage');
        } else {
            // symlink assoluto (più robusto su hosting)
            try {
                // assicurati che la cartella public esista (di solito sì)
                File::ensureDirectoryExists(dirname($publicStorage));

                if (!@symlink($target, $publicStorage)) {
                    $this->error('Symlink fallito (symlink()). Probabile blocco symlink su hosting.');
                    $this->warn('Riprova con: php artisan storage:relink --copy');
                    return self::FAILURE;
                }
                $this->info('OK: Creato symlink public/storage -> storage/app/public');
            } catch (\Throwable $e) {
                $this->error('Symlink exception: ' . $e->getMessage());
                $this->warn('Riprova con: php artisan storage:relink --copy');
                return self::FAILURE;
            }
        }

        // Esito
        $this->line('');
        $this->info('Check finale:');
        $this->info('exists: ' . (file_exists($publicStorage) ? 'yes' : 'no'));
        $this->info('is_link: ' . (is_link($publicStorage) ? 'yes' : 'no'));
        if (is_link($publicStorage)) {
            $this->info('link->  ' . (readlink($publicStorage) ?: ''));
        }

        return self::SUCCESS;
    }
}
