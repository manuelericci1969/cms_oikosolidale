<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contracts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->default(1);
            $table->foreignId('customer_id')->constrained('crm_customers')->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('crm_quotes')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('crm_services')->nullOnDelete();

            $table->string('number')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('type')->default('digital');
            $table->string('status')->default('generated');

            $table->string('pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->string('accepted_ip', 64)->nullable();
            $table->string('accepted_user_agent')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'customer_id']);
            $table->index(['client_id', 'quote_id']);
            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contracts');
    }
};
