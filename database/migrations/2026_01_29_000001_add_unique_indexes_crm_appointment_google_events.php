<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'crm_appointment_google_events';

    public function up(): void
    {
        // 1) UNIQUE: stesso evento Google non può essere salvato due volte (stesso account+calendario)
        if (! $this->indexExists('crm_ag_ev_unique_event')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->unique(
                    ['google_calendar_account_id', 'calendar_id', 'event_id'],
                    'crm_ag_ev_unique_event'
                );
            });
        }

        // 2) UNIQUE: stesso appuntamento CRM non può essere linkato a più event_id (stesso account+calendario)
        if (! $this->indexExists('crm_ag_ev_unique_appt')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->unique(
                    ['google_calendar_account_id', 'calendar_id', 'appointment_id'],
                    'crm_ag_ev_unique_appt'
                );
            });
        }

        // 3) INDEX: ical_uid per aiutare dedup (quando presente)
        if (! $this->indexExists('crm_ag_ev_idx_icaluid')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->index(
                    ['google_calendar_account_id', 'calendar_id', 'ical_uid'],
                    'crm_ag_ev_idx_icaluid'
                );
            });
        }
    }

    public function down(): void
    {
        // drop "safe" (non esplode se non esiste)
        if ($this->indexExists('crm_ag_ev_unique_event')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropUnique('crm_ag_ev_unique_event');
            });
        }

        if ($this->indexExists('crm_ag_ev_unique_appt')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropUnique('crm_ag_ev_unique_appt');
            });
        }

        if ($this->indexExists('crm_ag_ev_idx_icaluid')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropIndex('crm_ag_ev_idx_icaluid');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        // MySQL/MariaDB: SHOW INDEX
        $rows = DB::select(
            "SHOW INDEX FROM `{$this->table}` WHERE Key_name = ?",
            [$indexName]
        );

        return !empty($rows);
    }
};
