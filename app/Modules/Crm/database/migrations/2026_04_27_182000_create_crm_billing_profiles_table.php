<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->default(1)->index();

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('vat')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('province', 20)->nullable();
            $table->string('country', 2)->default('IT');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('pec')->nullable();
            $table->string('sdi')->nullable();
            $table->text('bank_details')->nullable();

            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_billing_profiles');
    }
};
