<?php

// database/migrations/2025_10_20_000000_create_media_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('disk')->default('public');
            $t->string('path');                 // es. uploads/2025/10/foo.jpg
            $t->string('original_name');
            $t->string('mime', 100)->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->string('alt')->nullable();
            $t->string('title')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['disk','path']);
        });
    }
    public function down(): void { Schema::dropIfExists('media'); }
};
