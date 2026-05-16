<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_service_payment_links')) {
            return;
        }

        Schema::create('crm_service_payment_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->default(1)->index();
            $table->foreignId('service_id')->constrained('crm_services')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('crm_customers')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('eur');
            $table->string('description');
            $table->string('status', 30)->default('pending')->index();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->text('stripe_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('sent_email_at')->nullable();
            $table->timestamp('sent_whatsapp_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_service_payment_links');
    }
};
