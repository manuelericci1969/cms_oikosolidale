<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            $table->text('bank_details')->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            $table->dropColumn('bank_details');
        });
    }
};
