<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_call_voice_turns', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('voice_session_id');
            $table->unsignedBigInteger('call_log_id')->nullable();

            $table->unsignedInteger('turn_no')->default(1);
            $table->string('role', 20);
            $table->text('text')->nullable();

            $table->string('stt_provider', 50)->nullable();
            $table->string('tts_provider', 50)->nullable();

            $table->integer('latency_ms')->nullable();
            $table->boolean('is_final')->default(true);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('voice_session_id')
                ->references('id')
                ->on('crm_call_voice_sessions')
                ->cascadeOnDelete();

            $table->index(['voice_session_id', 'turn_no']);
            $table->index(['call_log_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_voice_turns');
    }
};
