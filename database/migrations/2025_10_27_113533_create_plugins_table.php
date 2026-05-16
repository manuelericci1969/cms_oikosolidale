<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('plugins', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('version')->default('1.0.0');
            $t->string('author')->nullable();
            $t->json('manifest')->nullable();
            $t->boolean('enabled')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('plugins');
    }
};
