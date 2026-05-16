<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_chatbot_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->default(1);
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->boolean('is_helpful')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id']);
            $table->index(['conversation_id']);
            $table->index(['message_id']);
            $table->index(['is_helpful']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_chatbot_feedback');
    }
};
