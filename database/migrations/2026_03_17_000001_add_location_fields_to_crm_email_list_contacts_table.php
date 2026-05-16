<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_email_list_contacts', function (Blueprint $table) {
            $table->string('city')->nullable()->after('segment')->index();
            $table->string('province', 10)->nullable()->after('city')->index();
            $table->string('region')->nullable()->after('province')->index();
            $table->string('country')->nullable()->after('region')->index();
            $table->string('postal_code', 20)->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('crm_email_list_contacts', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'province',
                'region',
                'country',
                'postal_code',
            ]);
        });
    }
};
