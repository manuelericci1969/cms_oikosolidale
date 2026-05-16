<?php

namespace App\Support\Plugins;

use App\Models\CmsPlugin;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class PluginManager
{
    protected string $basePath;

    public function __construct(string $pluginsPath = 'plugins')
    {
        // es: config('cms.plugins_path', 'plugins')
        $this->basePath = base_path(trim($pluginsPath ?: 'plugins', '/'));
    }

    /**
     * Scansiona la cartella plugin e produce metadati base.
     * Cerca prima plugin.json, altrimenti prova a dedurre dal composer.json interno.
     *
     * Ritorna array di meta:
     * [
     *   'slug' => 'acme-hero',
     *   'name' => 'Acme Hero',
     *   'version' => '1.0.0',
     *   'provider' => 'Plugins\\AcmeHero\\PluginServiceProvider',
     *   'path' => '/var/www/app/plugins/acme-hero',
     *   'manifest' => [...],              // contenuto plugin.json o composer.json
     * ]
     */
    public function scan(): array
    {
        if (!is_dir($this->basePath)) {
            return [];
        }

        $dirs = collect(File::directories($this->basePath));
        $plugins = [];

        foreach ($dirs as $pluginDir) {
            $dirName = basename($pluginDir);

            $meta = [
                'slug'     => str($dirName)->slug('-')->toString(),
                'name'     => $dirName,
                'version'  => '0.0.0',
                'provider' => null,
                'path'     => $pluginDir,
                'manifest' => null,
            ];

            $pluginJson   = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.json';
            $composerJson = $pluginDir . DIRECTORY_SEPARATOR . 'composer.json';

            if (is_file($pluginJson)) {
                $json = json_decode(file_get_contents($pluginJson), true) ?: [];
                $meta['manifest'] = $json;
                $meta['slug']     = $json['slug']     ?? $meta['slug'];
                $meta['name']     = $json['name']     ?? $meta['name'];
                $meta['version']  = $json['version']  ?? $meta['version'];
                $meta['provider'] = $json['provider'] ?? $meta['provider'];
                // facoltativi utili per admin loader:
                $meta['admin_entry'] = $json['admin_entry'] ?? null;   // es: public/admin.js (relativo al plugin)
            } elseif (is_file($composerJson)) {
                $json = json_decode(file_get_contents($composerJson), true) ?: [];
                $meta['manifest'] = $json;
                $meta['name']     = $json['name']     ?? $meta['name'];
                $meta['version']  = $json['version']  ?? $meta['version'];
                $meta['provider'] = Arr::get($json, 'extra.laravel.providers.0', $meta['provider']);
            }

            $plugins[] = $meta;
        }

        return $plugins;
    }

    /**
     * Legge i toggle da DB. Durante migrate, se la tabella non esiste, ritorna [].
     * Ritorna mappa: [ 'slug' => bool enabled ]
     */
    public function toggles(): array
    {
        try {
            if (!Schema::hasTable('cms_plugins')) {
                return [];
            }

            return CmsPlugin::query()
                ->select(['slug', 'enabled'])
                ->get()
                ->keyBy('slug')
                ->map(fn ($row) => (bool) $row->enabled)
                ->all();
        } catch (\Throwable $e) {
            // in fase di bootstrap/migrate preferiamo non bloccare l'app
            return [];
        }
    }

    /**
     * Lista dei plugin abilitati (meta completi).
     * Default: se non c'è riga in DB => considerato abilitato.
     */
    public function enabled(): array
    {
        $found   = $this->scan();
        $toggles = $this->toggles();

        $enabled = [];
        foreach ($found as $meta) {
            $slug = $meta['slug'];
            $isOn = $toggles[$slug] ?? true;
            if ($isOn) {
                $enabled[] = $meta;
            }
        }
        return $enabled;
    }

    /**
     * Solo i nomi delle classi ServiceProvider dei plugin abilitati, se presenti.
     */
    public function providers(): array
    {
        return array_values(array_filter(array_map(
            fn ($m) => $m['provider'] ?? null,
            $this->enabled()
        )));
    }

    /**
     * Tutti i plugin trovati, con stato enabled dedotto/DB.
     */
    public function allWithState(): array
    {
        $found   = $this->scan();
        $toggles = $this->toggles();

        return array_map(function ($meta) use ($toggles) {
            $slug = $meta['slug'];
            $meta['enabled'] = $toggles[$slug] ?? true;
            return $meta;
        }, $found);
    }
}
