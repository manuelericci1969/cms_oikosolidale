<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('editor_mode', 20)
                ->default('structured')
                ->after('meta');

            $table->longText('visual_html')
                ->nullable()
                ->after('content');

            $table->longText('visual_css')
                ->nullable()
                ->after('visual_html');

            $table->longText('visual_json')
                ->nullable()
                ->after('visual_css');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'editor_mode',
                'visual_html',
                'visual_css',
                'visual_json',
            ]);
        });
    }
};
