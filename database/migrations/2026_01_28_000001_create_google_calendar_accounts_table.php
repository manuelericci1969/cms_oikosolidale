<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('google_calendar_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('google_email')->nullable();
            $table->string('calendar_id')->default('primary');

            // token JSON (access_token, expires_in, created, refresh_token...)
            $table->longText('token_json');
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_accounts');
    }
};
