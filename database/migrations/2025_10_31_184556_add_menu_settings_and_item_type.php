<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            if (!Schema::hasColumn('menus', 'settings')) {
                $table->json('settings')->nullable()->after('is_active');
            }
        });


        Schema::table('menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_items', 'type')) {
                $table->string('type', 20)->default('link')->after('icon'); // link | separator
            }
            if (!Schema::hasColumn('menu_items', 'settings')) {
                $table->json('settings')->nullable()->after('type');
            }
        });
    }


    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            if (Schema::hasColumn('menus', 'settings')) {
                $table->dropColumn('settings');
            }
        });
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'settings')) {
                $table->dropColumn('settings');
            }
            if (Schema::hasColumn('menu_items', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
