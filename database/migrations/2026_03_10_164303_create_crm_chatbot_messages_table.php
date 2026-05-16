<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_chatbot_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id')->index();

            $table->string('direction', 20)->default('in')->index(); // in, out, system
            $table->string('sender_type', 20)->default('visitor')->index(); // visitor, ai, agent, system
            $table->string('message_type', 50)->nullable()->index(); // text, cta, form_request, event...

            $table->longText('message');

            $table->string('model', 100)->nullable();

            $table->unsignedInteger('token_usage_input')->nullable();
            $table->unsignedInteger('token_usage_output')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('crm_chatbot_conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_chatbot_messages');
    }
};
