<?php

// database/migrations/2025_10_20_000100_create_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();        // es: branding.logo_id, company.name
            $t->json('value')->nullable();      // JSON per valori semplici o complessi
            $t->string('group')->nullable();    // branding | company | seo | analytics | footer
            $t->boolean('autoload')->default(true);
            $t->timestamps();
            $t->index('group');
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};
