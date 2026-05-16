<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_appointment_google_events', function (Blueprint $table) {

            // ✅ richiesto da te: evita duplicati dello stesso event_id Google
            $table->unique(
                ['google_calendar_account_id', 'calendar_id', 'event_id'],
                'uniq_gcal_event'
            );

            // ✅ richiesto da te: accelera match su iCalUID
            $table->index(
                ['google_calendar_account_id', 'calendar_id', 'ical_uid'],
                'idx_gcal_icaluid'
            );

            /**
             * (OPZIONALE ma CONSIGLIATO per bloccare duplicati in DB sul mapping per appointment)
             * Se NON lo vuoi, commenta/elimina queste 5 righe.
             */
            $table->unique(
                ['google_calendar_account_id', 'calendar_id', 'appointment_id'],
                'uniq_gcal_appt'
            );
        });
    }

    public function down(): void
    {
        Schema::table('crm_appointment_google_events', function (Blueprint $table) {
            // drop nell'ordine inverso

            // opzionale
            $table->dropUnique('uniq_gcal_appt');

            $table->dropIndex('idx_gcal_icaluid');
            $table->dropUnique('uniq_gcal_event');
        });
    }
};
