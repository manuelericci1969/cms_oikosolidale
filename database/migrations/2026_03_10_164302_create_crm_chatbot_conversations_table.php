<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_chatbot_conversations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->index();

            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable()->index();

            $table->string('session_id', 190)->unique();
            $table->string('channel', 50)->default('website')->index();

            $table->string('source_page', 500)->nullable();

            $table->string('visitor_name', 190)->nullable();
            $table->string('visitor_email', 190)->nullable()->index();
            $table->string('visitor_phone', 50)->nullable()->index();
            $table->string('visitor_company', 190)->nullable();

            $table->string('status', 50)->default('open')->index();
            $table->string('intent', 100)->nullable()->index();

            $table->unsignedInteger('score')->default(0);

            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->string('conversion_type', 50)->nullable();
            $table->text('notes')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')
                ->references('id')
                ->on('crm_leads')
                ->nullOnDelete();

            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->nullOnDelete();

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_chatbot_conversations');
    }
};
