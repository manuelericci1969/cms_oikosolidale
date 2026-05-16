<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_telnyx_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type')->nullable();
            $table->string('call_control_id')->nullable()->index();
            $table->string('call_leg_id')->nullable()->index();
            $table->string('call_session_id')->nullable()->index();
            $table->unsignedBigInteger('call_log_id')->nullable()->index();
            $table->timestamp('occurred_at')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_telnyx_webhook_events');
    }
};
