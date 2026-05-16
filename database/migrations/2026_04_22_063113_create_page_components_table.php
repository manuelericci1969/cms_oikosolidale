<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();

            // Schema dei campi editabili del componente
            $table->json('schema')->nullable();

            // Template del componente
            $table->longText('template_html');
            $table->longText('template_css')->nullable();
            $table->longText('template_js')->nullable();

            // Facoltativo: mini preview nel builder
            $table->longText('preview_html')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_components');
    }
};
