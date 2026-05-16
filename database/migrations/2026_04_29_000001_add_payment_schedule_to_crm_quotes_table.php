<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_quotes', 'payment_type')) {
                $table->string('payment_type')->default('free_text')->after('payment_terms');
            }

            if (!Schema::hasColumn('crm_quotes', 'payment_schedule')) {
                $table->json('payment_schedule')->nullable()->after('payment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('crm_quotes', 'payment_schedule')) {
                $table->dropColumn('payment_schedule');
            }

            if (Schema::hasColumn('crm_quotes', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};
