<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_chatbot_faqs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->default(1);
            $table->string('question_pattern', 255);
            $table->text('keywords')->nullable();
            $table->string('intent', 100)->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('answer');
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            $table->index(['intent']);
            $table->index(['product_id']);
            $table->index(['priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_chatbot_faqs');
    }
};
