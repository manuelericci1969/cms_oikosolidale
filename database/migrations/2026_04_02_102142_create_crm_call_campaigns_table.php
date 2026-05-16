<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_call_campaigns', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('name');
            $table->string('provider', 50)->default('telnyx'); // telnyx, twilio, plivo
            $table->string('status', 30)->default('draft');    // draft, active, paused, completed, archived
            $table->string('source_mode', 50)->default('email_list_contacts'); // email_list_contacts, leads, customers, mixed

            $table->text('description')->nullable();
            $table->longText('script_prompt')->nullable();

            $table->json('filters')->nullable();   // snapshot dei filtri usati per costruire la campagna
            $table->json('settings')->nullable();  // retry, limiti orari, modelli voce, ecc.

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['owner_id']);
            $table->index(['provider']);
            $table->index(['source_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_campaigns');
    }
};
