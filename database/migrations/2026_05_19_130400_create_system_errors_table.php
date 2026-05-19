<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_errors', function (Blueprint $table) {
            $table->id();
            $table->string('environment', 32)->nullable();
            $table->string('level', 24)->index();
            $table->string('channel', 64)->nullable();
            $table->string('message', 1024);
            $table->string('exception_class', 255)->nullable();
            $table->string('file', 255)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('trace')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('method', 12)->nullable();
            $table->string('route_name', 191)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->json('context')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->index(['level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_errors');
    }
};
