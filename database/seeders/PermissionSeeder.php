<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // --- Permessi base di sistema / amministrazione
        $system = [
            ['name'=>'view.admin',         'description'=>'Accesso all’area admin'],
            ['name'=>'manage.users',       'description'=>'Gestione utenti (ruoli/stato)'],
            ['name'=>'manage.roles',       'description'=>'Gestione permessi dei ruoli'],
            ['name'=>'manage.permissions', 'description'=>'CRUD permessi'],
            ['name'=>'manage.plugins',     'description'=>'Gestione plugin (upload/abilita/disabilita/elimina)'],
        ];

        // --- Permessi contenuti (CMS)
        $content = [
            ['name'=>'content.create',     'description'=>'Creare contenuti generici'],
            ['name'=>'content.edit',       'description'=>'Modificare contenuti generici'],
            ['name'=>'content.delete',     'description'=>'Eliminare contenuti'],
            ['name'=>'content.publish',    'description'=>'Pubblicare contenuti'],

            ['name'=>'content.pages.view', 'description'=>'Vedere elenco pagine'],
            ['name'=>'content.pages.edit', 'description'=>'Creare/Modificare pagine'],
            ['name'=>'content.menus.view', 'description'=>'Vedere menu'],
            ['name'=>'content.menus.edit', 'description'=>'Gestire menu'],
            ['name'=>'content.media.view', 'description'=>'Vedere media'],
            ['name'=>'content.media.edit', 'description'=>'Caricare/gestire media'],
        ];

        // --- Permessi impostazioni / branding / SEO / analytics
        $settings = [
            ['name'=>'settings.view',   'description'=>'Vedere impostazioni sito/branding'],
            ['name'=>'settings.manage', 'description'=>'Gestire impostazioni sito/branding/SEO/Analytics'],
        ];

        // Crea/aggiorna tutti i permessi
        foreach (array_merge($system, $content, $settings) as $p) {
            Permission::updateOrCreate(
                ['name' => $p['name']],
                ['description' => $p['description'] ?? null]
            );
        }

        // Mappa permessi → id
        $ids = Permission::pluck('id', 'name');

        // Concessioni per ruolo "admin"
        $grantToAdmin = [
            // accesso admin + gestione utenti base
            'view.admin', 'manage.users',

            // gestione plugin
            'manage.plugins',

            // contenuti
            'content.create','content.edit','content.delete','content.publish',
            'content.pages.view','content.pages.edit',
            'content.menus.view','content.menus.edit',
            'content.media.view','content.media.edit',

            // impostazioni (branding/seo/analytics)
            'settings.view','settings.manage',

            // Se vuoi che l'Admin gestisca ruoli/permessi, sblocca anche:
            // 'manage.roles','manage.permissions',
        ];

        $now = now();
        foreach ($grantToAdmin as $name) {
            if (! isset($ids[$name])) continue;

            DB::table('role_permissions')->updateOrInsert(
                ['role' => Role::Admin->value, 'permission_id' => $ids[$name]],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        // SuperAdmin ha tutto by-code (non occorrono record specifici).
    }
}
