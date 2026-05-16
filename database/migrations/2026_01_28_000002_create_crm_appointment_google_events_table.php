<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_appointment_google_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_id')
                ->constrained('crm_appointments')
                ->cascadeOnDelete();

            $table->foreignId('google_calendar_account_id')
                ->constrained('google_calendar_accounts')
                ->cascadeOnDelete();

            $table->string('calendar_id', 191)->default('primary');

            // Google IDs sono tipicamente corti, ma teniamo margine.
            $table->string('event_id', 191);     // Google event id
            $table->string('ical_uid', 191)->nullable();
            $table->string('etag', 191)->nullable();

            $table->timestamp('google_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // ✅ Nome indice corto per evitare MySQL 64-char limit
            $table->unique(
                ['google_calendar_account_id', 'event_id'],
                'uq_gcal_acc_event'
            );

            // Indici utili per lookup rapidi
            $table->index(['appointment_id'], 'ix_appt');
            $table->index(['google_calendar_account_id', 'calendar_id'], 'ix_gcal_acc_cal');
            $table->index(['ical_uid'], 'ix_ical_uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_appointment_google_events');
    }
};
