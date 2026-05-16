<?php

// database/migrations/2025_10_20_000001_add_variants_to_media_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('media', function (Blueprint $t) {
            $t->unsignedInteger('width')->nullable()->after('size');
            $t->unsignedInteger('height')->nullable()->after('width');
            $t->json('variants')->nullable()->after('height'); // {key:{path,width,height,size}}
        });
    }
    public function down(): void {
        Schema::table('media', function (Blueprint $t) {
            $t->dropColumn(['width','height','variants']);
        });
    }
};
