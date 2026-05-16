<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_quote_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('quote_id');

            $table->date('payment_date');
            $table->decimal('amount', 12, 2)->default(0);

            $table->string('payment_method', 50)->nullable(); // bonifico, contanti, carta, assegno...
            $table->string('reference', 255)->nullable();      // CRO, TRN, ID transazione, ecc.
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('quote_id')
                ->references('id')
                ->on('crm_quotes')
                ->cascadeOnDelete();

            $table->index(['client_id', 'quote_id']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_quote_payments');
    }
};
