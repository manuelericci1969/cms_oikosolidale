<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_call_voice_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('queue_id')->nullable();
            $table->unsignedBigInteger('call_log_id')->nullable();

            $table->string('provider', 50)->default('telnyx');
            $table->string('provider_call_id')->nullable()->index();
            $table->string('provider_leg_id')->nullable()->index();
            $table->string('provider_session_id')->nullable()->index();

            $table->string('stream_id')->nullable()->index();
            $table->string('status', 50)->default('created');
            $table->string('bridge_mode', 50)->default('voice_bridge_v1');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('streaming_started_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['campaign_id', 'queue_id']);
            $table->index(['call_log_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_voice_sessions');
    }
};
