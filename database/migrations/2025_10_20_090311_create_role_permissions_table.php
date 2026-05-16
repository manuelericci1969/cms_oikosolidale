<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('role'); // 'superadmin' | 'admin' | 'user' (enum nel dominio, string a DB)
            $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $t->unique(['role','permission_id']);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('role_permissions'); }
};
