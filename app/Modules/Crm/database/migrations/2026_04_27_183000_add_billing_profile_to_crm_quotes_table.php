<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            $table->foreignId('billing_profile_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('crm_billing_profiles')
                ->nullOnDelete();

            $table->json('billing_profile_snapshot')->nullable()->after('billing_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_profile_id');
            $table->dropColumn('billing_profile_snapshot');
        });
    }
};
