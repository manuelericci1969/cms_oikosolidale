<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        /*
         * NOTE:
         * - Questa migration "all-in-one" è pensata per installazioni nuove.
         * - Crea tutte le tabelle e include i campi introdotti dalle migration presenti nel pacchetto.
         * - Presuppone che la tabella `users` esista già (FK verso utenti).
         */

        // -------------------------
        // Anagrafiche / base
        // -------------------------
        Schema::create('crm_customers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->index();

            // Owner (utente interno)
            $table->unsignedBigInteger('owner_id')->nullable()->index();

            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('pec_email', 255)->nullable();
            $table->string('phone')->nullable();

            $table->string('vat_number')->nullable();
            $table->string('sdi_code', 20)->nullable();
            $table->string('tax_code')->nullable();

            $table->string('billing_address')->nullable();
            $table->string('billing_zip', 20)->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_province', 10)->nullable();
            $table->string('billing_country', 2)->nullable()->default('IT');

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('owner_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        Schema::create('crm_products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->index();

            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit', 20)->default('pz');

            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(22.00);

            // extra: sconti / promo
            $table->decimal('max_discount', 5, 2)->nullable(); // 0-100
            $table->boolean('is_active')->default(true);
            $table->boolean('is_promo')->default(false);
            $table->date('promo_expires_at')->nullable();

            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'sku']);
        });

        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable()->index();

            $table->string('name', 190);
            $table->string('email', 190)->nullable();
            $table->string('phone', 50)->nullable();

            $table->string('subject', 190)->nullable();
            $table->text('message')->nullable();

            $table->string('source', 50)->nullable();
            $table->string('how_found', 50)->nullable();
            $table->string('how_found_other', 190)->nullable();

            $table->string('status', 50)->default('new');

            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason', 190)->nullable();

            $table->text('internal_notes')->nullable();

            $table->boolean('gdpr_consense')->default(false);
            $table->boolean('marketing_consense')->default(false);

            $table->timestamps();

            $table->foreign('owner_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        Schema::create('crm_lead_activities', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('type', 50);
            $table->string('subject', 190)->nullable();
            $table->text('body')->nullable();
            $table->string('outcome', 50)->nullable();

            $table->timestamp('contacted_at')->nullable();

            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // -------------------------
        // Preventivi
        // -------------------------
        Schema::create('crm_quotes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();

            $table->string('number')->index();
            $table->string('subject', 190)->nullable();

            $table->date('date');
            $table->date('valid_until')->nullable();

            $table->string('status', 20)->default('draft');
            $table->string('currency', 3)->default('EUR');

            // Acceptance / OTP
            $table->string('acceptance_token', 100)->nullable()->unique();
            $table->timestamp('acceptance_token_expires_at')->nullable();
            $table->timestamp('accept_click_at')->nullable();
            $table->string('accept_click_ip', 45)->nullable();
            $table->string('acceptance_code', 10)->nullable();
            $table->timestamp('acceptance_code_sent_at')->nullable();
            $table->timestamp('acceptance_code_expires_at')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->string('accepted_ip', 45)->nullable();
            $table->string('accepted_user_agent', 255)->nullable();

            $table->text('notes')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('payment_terms')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('crm_customers')->onDelete('cascade');
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('crm_leads')->nullOnDelete();
        });

        Schema::create('crm_quote_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quote_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();

            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->default('pz');

            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(22.00);

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('crm_quotes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('crm_products')->nullOnDelete();
        });

        // -------------------------
        // Servizi + Reminder logs
        // -------------------------
        Schema::create('crm_services', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();

            $table->string('name');
            $table->string('type', 50)->nullable();

            $table->string('provider_name')->nullable();
            $table->string('provider_website')->nullable();

            $table->string('panel_url')->nullable();
            $table->string('panel_username')->nullable();
            $table->text('panel_password')->nullable();

            $table->date('activated_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);

            // stato servizio
            $table->string('status', 20)->default('attivo');

            // campi rinnovo (versione "renew_*")
            $table->decimal('renew_price', 10, 2)->nullable();
            $table->boolean('renew_price_vat_included')->default(false);
            $table->unsignedTinyInteger('renew_price_vat_rate')->default(22);

            // campi rinnovo (versione "renewal_*")
            $table->decimal('renewal_price', 10, 2)->nullable();
            $table->decimal('renewal_vat_rate', 5, 2)->nullable();
            $table->string('renewal_vat_mode', 10)->default('excl');

            // reminder
            $table->boolean('send_reminder')->default(false);
            $table->unsignedInteger('reminder_days_before')->default(15);
            $table->text('reminder_custom_text')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')->on('crm_customers')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')->on('crm_products')
                ->nullOnDelete();
        });

        Schema::create('crm_service_reminder_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->string('to_email');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->text('body_preview')->nullable();

            $table->string('status')->default('sent');

            $table->string('tracking_hash', 64)->nullable();
            $table->string('provider_message_id')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->text('error')->nullable();

            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('crm_services')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('crm_customers')->onDelete('set null');

            $table->index(['service_id', 'sent_at']);
        });

        // -------------------------
        // Email marketing
        // -------------------------
        Schema::create('crm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();

            $table->string('name');
            $table->string('subject');

            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to_email')->nullable();

            $table->string('preheader')->nullable();

            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();

            $table->enum('status', [
                'draft', 'scheduled', 'sending', 'paused', 'completed', 'cancelled',
            ])->default('draft');

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('bounce_count')->default(0);
            $table->unsignedInteger('unsubscribe_count')->default(0);
            $table->unsignedInteger('complaint_count')->default(0);

            $table->timestamps();
        });

        Schema::create('crm_campaign_recipients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('campaign_id');
            $table->enum('contact_type', ['lead', 'customer', 'csv']);
            $table->unsignedBigInteger('contact_id')->nullable()->index();

            $table->string('email');
            $table->string('name')->nullable();

            $table->string('segment')->nullable();

            $table->enum('status', [
                'pending', 'queued', 'sent', 'bounced', 'failed', 'unsubscribed',
            ])->default('pending');

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);

            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);

            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable()->index();

            $table->string('hash', 64)->nullable()->index();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->foreign('campaign_id')
                ->references('id')->on('crm_campaigns')
                ->onDelete('cascade');
        });

        Schema::create('crm_campaign_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('recipient_id');

            // TEXT non può essere indicizzato senza prefix length in MySQL
            $table->text('url');

            $table->unsignedInteger('click_count')->default(0);

            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();

            $table->timestamps();

            // NON usare $table->index(['campaign_id', 'url']) perché url è TEXT (errore 1170)
            $table->index(['campaign_id', 'recipient_id']);

            $table->foreign('campaign_id')
                ->references('id')->on('crm_campaigns')
                ->onDelete('cascade');

            $table->foreign('recipient_id')
                ->references('id')->on('crm_campaign_recipients')
                ->onDelete('cascade');
        });

        // Indice con prefisso per url(TEXT)
        DB::statement(
            'CREATE INDEX crm_campaign_link_clicks_campaign_id_url_index
             ON crm_campaign_link_clicks (campaign_id, url(191))'
        );

        // -------------------------
        // Liste email
        // -------------------------
        Schema::create('crm_email_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 190);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_email_list_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_id');

            $table->string('contact_type', 20);
            $table->unsignedBigInteger('contact_id')->nullable();

            $table->string('email');
            $table->string('name', 255)->nullable();
            $table->string('segment', 190)->nullable();
            $table->boolean('marketing_consense')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();

            $table->foreign('list_id')
                ->references('id')->on('crm_email_lists')
                ->onDelete('cascade');

            $table->index(['list_id', 'email']);
        });

        Schema::create('crm_email_list_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('name', 190);
            $table->timestamps();

            $table->unique(['client_id', 'name']);
        });

        Schema::create('crm_email_list_contact_category', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->primary(['contact_id', 'category_id']);

            $table->foreign('contact_id')
                ->references('id')->on('crm_email_list_contacts')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')->on('crm_email_list_categories')
                ->onDelete('cascade');
        });

        // -------------------------
        // Appuntamenti
        // -------------------------
        Schema::create('crm_appointments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->index();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('location', 190)->nullable();

            $table->string('type', 50)->nullable();
            $table->string('status', 30)->default('planned')->index();

            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable()->index();
            $table->boolean('all_day')->default(false);

            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('crm_leads')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('crm_customers')->nullOnDelete();
        });

        // -------------------------
        // Tasks
        // -------------------------
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status', 20)->default('start');

            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->unsignedBigInteger('created_by_id');

            $table->nullableMorphs('taskable');

            $table->dateTime('due_at')->nullable();
            $table->tinyInteger('priority')->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assigned_to_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('created_by_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('crm_task_notes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note');

            $table->timestamps();

            $table->foreign('task_id')
                ->references('id')->on('crm_tasks')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });

        // --------------------------------------------------------------------
        // Seed permessi / ruoli / permessi utente (idempotente)
        // - Inserisce/aggiorna solo se le tabelle esistono (singolare o plurale)
        // --------------------------------------------------------------------
        $permissionsTable = Schema::hasTable('permissions') ? 'permissions' : (Schema::hasTable('permission') ? 'permission' : null);
        $rolePermTable    = Schema::hasTable('role_permissions') ? 'role_permissions' : (Schema::hasTable('role_permission') ? 'role_permission' : null);
        $userPermTable    = Schema::hasTable('user_permissions') ? 'user_permissions' : (Schema::hasTable('user_permission') ? 'user_permission' : null);

        if ($permissionsTable && $rolePermTable && $userPermTable && Schema::hasTable('users')) {
            DB::transaction(function () use ($permissionsTable, $rolePermTable, $userPermTable) {

                // ------------------------------------------------------------
                // 0) Crea/aggiorna utente admin e recupera ID
                // ------------------------------------------------------------
                $adminEmail = 'admin@app.com';
                $adminName  = 'admin';
                $adminPass  = '123456789';

                $adminId = DB::table('users')->where('email', $adminEmail)->value('id');

                $userColumns = Schema::getColumnListing('users');
                $now = now();

                if (!$adminId) {
                    $insert = [
                        'name'       => $adminName,
                        'email'      => $adminEmail,
                        'password'   => Hash::make($adminPass),
                        'role'       => 'admin',
                        'is_active'  => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'email_verified_at' => $now,
                    ];

                    // Tieni solo le colonne che esistono davvero nella tabella users
                    $insert = array_intersect_key($insert, array_flip($userColumns));

                    DB::table('users')->insert($insert);
                    $adminId = DB::table('users')->where('email', $adminEmail)->value('id');
                } else {
                    // Se esiste già, non resetto la password, aggiorno solo campi “sicuri”
                    $update = [
                        'name'       => $adminName,
                        'role'       => 'admin',
                        'is_active'  => 1,
                        'updated_at' => $now,
                    ];
                    $update = array_intersect_key($update, array_flip($userColumns));

                    DB::table('users')->where('id', $adminId)->update($update);
                }

                // ------------------------------------------------------------
                // 1) permissions
                // ------------------------------------------------------------
                $permissions = [
                    ['id'=>1,  'name'=>'view.admin',         'description'=>"Accesso all'area admin",                               'created_at'=>'2025-10-20 09:07:06', 'updated_at'=>'2025-10-20 09:07:06'],
                    ['id'=>2,  'name'=>'manage.users',       'description'=>'Gestione utenti (ruoli/stato)',                         'created_at'=>'2025-10-20 09:07:06', 'updated_at'=>'2025-10-20 09:07:06'],
                    ['id'=>3,  'name'=>'manage.roles',       'description'=>'Gestione permessi dei ruoli',                           'created_at'=>'2025-10-20 09:07:06', 'updated_at'=>'2025-10-20 09:07:06'],
                    ['id'=>4,  'name'=>'manage.permissions', 'description'=>'CRUD permessi',                                         'created_at'=>'2025-10-20 09:07:06', 'updated_at'=>'2025-10-20 09:07:06'],
                    ['id'=>5,  'name'=>'content.pages.view', 'description'=>'Vedere elenco pagine',                                  'created_at'=>'2025-10-20 09:58:10', 'updated_at'=>'2025-10-20 09:58:10'],
                    ['id'=>6,  'name'=>'content.pages.edit', 'description'=>'Creare/Modificare pagine',                              'created_at'=>'2025-10-20 09:58:10', 'updated_at'=>'2025-10-20 09:58:10'],
                    ['id'=>7,  'name'=>'content.menus.view', 'description'=>'Vedere menu',                                           'created_at'=>'2025-10-20 09:58:11', 'updated_at'=>'2025-10-20 09:58:11'],
                    ['id'=>8,  'name'=>'content.menus.edit', 'description'=>'Gestire menu',                                          'created_at'=>'2025-10-20 09:58:11', 'updated_at'=>'2025-10-20 09:58:11'],
                    ['id'=>9,  'name'=>'content.media.view', 'description'=>'Vedere media',                                          'created_at'=>'2025-10-20 09:58:11', 'updated_at'=>'2025-10-20 09:58:11'],
                    ['id'=>10, 'name'=>'content.media.edit', 'description'=>'Caricare/gestire media',                                'created_at'=>'2025-10-20 09:58:11', 'updated_at'=>'2025-10-20 09:58:11'],
                    ['id'=>11, 'name'=>'settings.view',      'description'=>'Vedere impostazioni sito/branding',                     'created_at'=>'2025-10-20 15:40:22', 'updated_at'=>'2025-10-20 15:40:22'],
                    ['id'=>12, 'name'=>'settings.manage',    'description'=>'Gestire impostazioni sito/branding/SEO/Analytics',      'created_at'=>'2025-10-20 15:40:22', 'updated_at'=>'2025-10-20 15:40:22'],
                    ['id'=>13, 'name'=>'content.create',     'description'=>'Creare contenuti generici',                             'created_at'=>'2025-10-20 17:02:45', 'updated_at'=>'2025-10-20 17:02:45'],
                    ['id'=>14, 'name'=>'content.edit',       'description'=>'Modificare contenuti generici',                         'created_at'=>'2025-10-20 17:02:45', 'updated_at'=>'2025-10-20 17:02:45'],
                    ['id'=>15, 'name'=>'content.delete',     'description'=>'Eliminare contenuti',                                   'created_at'=>'2025-10-20 17:02:45', 'updated_at'=>'2025-10-20 17:02:45'],
                    ['id'=>16, 'name'=>'content.publish',    'description'=>'Pubblicare contenuti',                                  'created_at'=>'2025-10-20 17:02:45', 'updated_at'=>'2025-10-20 17:02:45'],
                    ['id'=>17, 'name'=>'manage.plugins',     'description'=>'Gestione plugin (upload/abilita/disabilita/elimina)',   'created_at'=>'2025-10-27 12:31:39', 'updated_at'=>'2025-10-27 12:31:39'],
                    ['id'=>18, 'name'=>'crm.agent.access',   'description'=>'crm Agente',                                            'created_at'=>'2025-11-28 17:27:32', 'updated_at'=>'2025-11-28 17:27:32'],
                ];

                DB::table($permissionsTable)->upsert(
                    $permissions,
                    ['id'],
                    ['name', 'description', 'updated_at']
                );

                // ------------------------------------------------------------
                // 2) role_permissions (ruolo admin)
                // ------------------------------------------------------------
                $rolePermissions = [
                    ['id'=>21, 'role'=>'admin', 'permission_id'=>13, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>22, 'role'=>'admin', 'permission_id'=>15, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>23, 'role'=>'admin', 'permission_id'=>14, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>24, 'role'=>'admin', 'permission_id'=>10, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>25, 'role'=>'admin', 'permission_id'=>9,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>26, 'role'=>'admin', 'permission_id'=>8,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>27, 'role'=>'admin', 'permission_id'=>7,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>28, 'role'=>'admin', 'permission_id'=>6,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>29, 'role'=>'admin', 'permission_id'=>5,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>30, 'role'=>'admin', 'permission_id'=>16, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>31, 'role'=>'admin', 'permission_id'=>4,  'created_at'=>'2025-10-20 17:12:01', 'updated_at'=>'2025-10-20 17:12:01'],
                    ['id'=>32, 'role'=>'admin', 'permission_id'=>3,  'created_at'=>'2025-10-20 17:12:01', 'updated_at'=>'2025-10-20 17:12:01'],
                    ['id'=>33, 'role'=>'admin', 'permission_id'=>2,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>34, 'role'=>'admin', 'permission_id'=>12, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>35, 'role'=>'admin', 'permission_id'=>11, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>36, 'role'=>'admin', 'permission_id'=>1,  'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                    ['id'=>37, 'role'=>'admin', 'permission_id'=>17, 'created_at'=>'2025-10-27 12:31:40', 'updated_at'=>'2025-10-27 12:31:40'],
                ];

                DB::table($rolePermTable)->upsert(
                    $rolePermissions,
                    ['id'],
                    ['role', 'permission_id', 'updated_at']
                );

                // ------------------------------------------------------------
                // 3) user_permissions (usa l'ID reale dell'admin!)
                // ------------------------------------------------------------
                if ($adminId) {
                    $userPermissions = [
                        ['id'=>3,  'user_id'=>$adminId, 'permission_id'=>4,  'created_at'=>'2025-10-20 10:11:51', 'updated_at'=>'2025-10-20 10:11:51'],
                        ['id'=>4,  'user_id'=>$adminId, 'permission_id'=>3,  'created_at'=>'2025-10-20 10:11:52', 'updated_at'=>'2025-10-20 10:11:52'],
                        ['id'=>5,  'user_id'=>$adminId, 'permission_id'=>13, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>6,  'user_id'=>$adminId, 'permission_id'=>15, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>7,  'user_id'=>$adminId, 'permission_id'=>14, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>8,  'user_id'=>$adminId, 'permission_id'=>10, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>9,  'user_id'=>$adminId, 'permission_id'=>9,  'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>10, 'user_id'=>$adminId, 'permission_id'=>8,  'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>11, 'user_id'=>$adminId, 'permission_id'=>7,  'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>12, 'user_id'=>$adminId, 'permission_id'=>6,  'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>13, 'user_id'=>$adminId, 'permission_id'=>5,  'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>14, 'user_id'=>$adminId, 'permission_id'=>16, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>15, 'user_id'=>$adminId, 'permission_id'=>12, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>16, 'user_id'=>$adminId, 'permission_id'=>11, 'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>17, 'user_id'=>$adminId, 'permission_id'=>1,  'created_at'=>'2025-10-21 17:28:19', 'updated_at'=>'2025-10-21 17:28:19'],
                        ['id'=>18, 'user_id'=>$adminId, 'permission_id'=>2,  'created_at'=>'2025-10-21 17:28:31', 'updated_at'=>'2025-10-21 17:28:31'],
                    ];

                    DB::table($userPermTable)->upsert(
                        $userPermissions,
                        ['id'],
                        ['user_id', 'permission_id', 'updated_at']
                    );
                }
            });
        }

    }

    public function down(): void
    {
        // (opzionale) rimozione seed permessi se le tabelle esistono
        $permissionsTable = Schema::hasTable('permissions') ? 'permissions' : (Schema::hasTable('permission') ? 'permission' : null);
        $rolePermTable    = Schema::hasTable('role_permissions') ? 'role_permissions' : (Schema::hasTable('role_permission') ? 'role_permission' : null);
        $userPermTable    = Schema::hasTable('user_permissions') ? 'user_permissions' : (Schema::hasTable('user_permission') ? 'user_permission' : null);

        if ($permissionsTable) {
            DB::table($permissionsTable)->whereIn('id', range(1, 18))->delete();
        }
        if ($rolePermTable) {
            DB::table($rolePermTable)->whereIn('id', range(21, 37))->delete();
        }
        if ($userPermTable) {
            DB::table($userPermTable)->whereIn('id', range(3, 18))->delete();
        }

        Schema::dropIfExists('crm_task_notes');
        Schema::dropIfExists('crm_tasks');

        Schema::dropIfExists('crm_appointments');

        Schema::dropIfExists('crm_email_list_contact_category');
        Schema::dropIfExists('crm_email_list_categories');
        Schema::dropIfExists('crm_email_list_contacts');
        Schema::dropIfExists('crm_email_lists');

        Schema::dropIfExists('crm_campaign_link_clicks');
        Schema::dropIfExists('crm_campaign_recipients');
        Schema::dropIfExists('crm_campaigns');

        Schema::dropIfExists('crm_service_reminder_logs');
        Schema::dropIfExists('crm_services');

        Schema::dropIfExists('crm_quote_items');
        Schema::dropIfExists('crm_quotes');

        Schema::dropIfExists('crm_lead_activities');
        Schema::dropIfExists('crm_leads');

        Schema::dropIfExists('crm_products');
        Schema::dropIfExists('crm_customers');
    }
};
