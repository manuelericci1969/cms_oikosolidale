<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_whatsapp_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->default(1);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 50);
            $table->string('normalized_phone', 50)->nullable();

            $table->text('message');

            $table->string('status', 30)->default('pending');
            // pending | sent | failed

            $table->json('api_response')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('user_id');
            $table->index('lead_id');
            $table->index('customer_id');
            $table->index('recipient_phone');
            $table->index('normalized_phone');
            $table->index('sent_at');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('lead_id')
                ->references('id')
                ->on('crm_leads')
                ->nullOnDelete();

            $table->foreign('customer_id')
                ->references('id')
                ->on('crm_customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_whatsapp_messages');
    }
};
