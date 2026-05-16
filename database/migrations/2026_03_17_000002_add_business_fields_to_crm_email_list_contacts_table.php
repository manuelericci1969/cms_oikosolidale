<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_email_list_contacts', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('postal_code');
            $table->string('whatsapp', 50)->nullable()->after('phone');

            $table->string('website_url')->nullable()->after('whatsapp');
            $table->string('contact_page_url')->nullable()->after('website_url');

            $table->string('address')->nullable()->after('contact_page_url');
            $table->string('business_type', 100)->nullable()->after('address')->index();
            $table->unsignedTinyInteger('stars')->nullable()->after('business_type')->index();

            $table->string('vat_number', 50)->nullable()->after('stars')->index();
            $table->string('cin_code', 100)->nullable()->after('vat_number')->index();

            $table->string('contact_role', 50)->nullable()->after('cin_code')->index();
            $table->string('email_status', 30)->nullable()->after('contact_role')->index();

            $table->string('source_type', 50)->nullable()->after('email_status')->index();
            $table->text('source_url')->nullable()->after('source_type');

            $table->string('site_rating', 50)->nullable()->after('source_url')->index();
            $table->string('commercial_potential', 30)->nullable()->after('site_rating')->index();
            $table->decimal('seo_score', 5, 2)->nullable()->after('commercial_potential');

            $table->timestamp('last_verified_at')->nullable()->after('seo_score')->index();
            $table->text('notes')->nullable()->after('last_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('crm_email_list_contacts', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'whatsapp',
                'website_url',
                'contact_page_url',
                'address',
                'business_type',
                'stars',
                'vat_number',
                'cin_code',
                'contact_role',
                'email_status',
                'source_type',
                'source_url',
                'site_rating',
                'commercial_potential',
                'seo_score',
                'last_verified_at',
                'notes',
            ]);
        });
    }
};
