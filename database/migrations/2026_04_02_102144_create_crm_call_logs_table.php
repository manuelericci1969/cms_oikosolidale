<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_call_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('queue_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');

            $table->string('provider', 50)->default('telnyx');
            $table->string('provider_call_id')->nullable();

            $table->string('phone', 50)->nullable();
            $table->string('direction', 20)->default('outbound');

            $table->string('call_status', 50)->nullable();
            $table->string('technical_outcome', 50)->nullable();
            $table->string('business_outcome', 50)->nullable();

            $table->unsignedInteger('duration_seconds')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->text('operator_note')->nullable();
            $table->longText('ai_summary')->nullable();
            $table->longText('transcript')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['campaign_id']);
            $table->index(['queue_id']);
            $table->index(['client_id', 'provider']);
            $table->index(['source_type', 'source_id']);
            $table->index(['provider_call_id']);
            $table->index(['technical_outcome']);
            $table->index(['business_outcome']);
            $table->index(['started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_logs');
    }
};
