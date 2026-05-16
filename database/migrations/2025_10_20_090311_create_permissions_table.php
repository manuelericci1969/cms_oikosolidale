<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();       // es. manage.users
            $t->string('description')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('permissions'); }
};
