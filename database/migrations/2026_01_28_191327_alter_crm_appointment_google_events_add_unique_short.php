<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'crm_appointment_google_events';

    // indice già esistente che ti sta dando errore
    private string $idxName = 'uq_gcal_acc_event';

    // colonne desiderate per l'indice
    private array $idxCols = ['google_calendar_account_id', 'event_id'];

    public function up(): void
    {
        // Se l'indice esiste già:
        // - se è uguale (stesse colonne nello stesso ordine) => non fare nulla
        // - se è diverso => droppa e ricrea
        if ($this->indexExists($this->idxName)) {
            if (! $this->indexMatches($this->idxName, $this->idxCols)) {
                Schema::table($this->table, function (Blueprint $table) {
                    $table->dropUnique($this->idxName);
                });

                Schema::table($this->table, function (Blueprint $table) {
                    $table->unique($this->idxCols, $this->idxName);
                });
            }

            return;
        }

        // Non esiste => crea
        Schema::table($this->table, function (Blueprint $table) {
            $table->unique($this->idxCols, $this->idxName);
        });
    }

    public function down(): void
    {
        if ($this->indexExists($this->idxName)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropUnique($this->idxName);
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $rows = DB::select(
            "SELECT COLUMN_NAME AS column_name
     FROM information_schema.statistics
     WHERE TABLE_SCHEMA = ?
       AND TABLE_NAME = ?
       AND INDEX_NAME = ?
     ORDER BY SEQ_IN_INDEX ASC",
            [$db, $this->table, $indexName]
        );

        return ! empty($rows);
    }

    private function indexMatches(string $indexName, array $expectedColumns): bool
    {
        $db = DB::getDatabaseName();

        $rows = DB::select(
            "SELECT COLUMN_NAME AS column_name
     FROM information_schema.statistics
     WHERE TABLE_SCHEMA = ?
       AND TABLE_NAME = ?
       AND INDEX_NAME = ?
     ORDER BY SEQ_IN_INDEX ASC",
            [$db, $this->table, $indexName]
        );

        if (empty($rows)) return false;

        $cols = array_map(fn($r) => $r->column_name ?? $r->COLUMN_NAME ?? null, $rows);
        $cols = array_values(array_filter($cols));

        return $cols === array_values($expectedColumns);
    }
};
