<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_service_reminder_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_service_reminder_logs', 'channel')) {
                $table->string('channel', 20)->default('email')->after('customer_id');
            }

            if (!Schema::hasColumn('crm_service_reminder_logs', 'to_phone')) {
                $table->string('to_phone', 50)->nullable()->after('to_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_service_reminder_logs', function (Blueprint $table) {
            if (Schema::hasColumn('crm_service_reminder_logs', 'to_phone')) {
                $table->dropColumn('to_phone');
            }

            if (Schema::hasColumn('crm_service_reminder_logs', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
