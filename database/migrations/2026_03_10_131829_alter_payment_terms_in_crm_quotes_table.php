<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            $table->longText('payment_terms')->nullable()->change();
            $table->longText('intro_text')->nullable()->change(); // già che ci sei
        });
    }

    public function down(): void
    {
        Schema::table('crm_quotes', function (Blueprint $table) {
            $table->string('payment_terms', 255)->nullable()->change();
            $table->string('intro_text', 255)->nullable()->change();
        });
    }
};
