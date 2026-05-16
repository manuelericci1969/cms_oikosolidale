<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_chatbot_unknown_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->default(1);
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->text('question');
            $table->string('intent_detected', 100)->nullable();
            $table->string('source_page', 500)->nullable();
            $table->string('status', 30)->default('new');
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['conversation_id']);
            $table->index(['message_id']);
            $table->index(['intent_detected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_chatbot_unknown_questions');
    }
};
