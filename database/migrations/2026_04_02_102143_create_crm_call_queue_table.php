<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_call_queue', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('source_type', 50); // lead, email_list_contact, customer
            $table->unsignedBigInteger('source_id');

            $table->string('contact_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();

            $table->string('status', 30)->default('pending');
            // pending, scheduled, calling, completed, failed, retry, callback, skipped, cancelled

            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('last_outcome', 50)->nullable();
            $table->text('last_outcome_note')->nullable();

            $table->boolean('do_not_call')->default(false);
            $table->timestamp('do_not_call_at')->nullable();

            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index(['scheduled_at']);
            $table->index(['next_attempt_at']);
            $table->index(['do_not_call']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_queue');
    }
};
