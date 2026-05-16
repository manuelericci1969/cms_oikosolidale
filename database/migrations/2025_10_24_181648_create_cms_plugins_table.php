<?php

// database/migrations/2024_01_01_000000_create_cms_plugins_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cms_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('meta')->nullable(); // opzionale, per salvare info extra
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cms_plugins'); }
};
