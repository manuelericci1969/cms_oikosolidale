<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_call_conversation_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('queue_id')->nullable();
            $table->unsignedBigInteger('call_log_id');

            $table->string('role', 20); // system, user, assistant, tool
            $table->text('message');
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('call_log_id');
            $table->index('queue_id');
            $table->index('campaign_id');
            $table->index('client_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_conversation_messages');
    }
};
