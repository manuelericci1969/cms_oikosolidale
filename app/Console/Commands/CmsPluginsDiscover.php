<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\Plugins\PluginManager;

class CmsPluginsDiscover extends Command
{
    protected $signature = 'cms:plugins:discover';
    protected $description = 'Genera resources/js/admin/plugins-entries.js con gli import dei plugin attivi';

    public function handle(PluginManager $pm): int
    {
        $entries = $pm->adminEntries();

        $root = rtrim(base_path(), DIRECTORY_SEPARATOR);
        $toRel = fn(string $p) => '/' . str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($root, '', $p), '/'));

        $lines = [];
        $lines[] = "export default async function loadPlugins(API){";
        $lines[] = "  const mods = [];";

        foreach ($entries as $e) {
            if (!empty($e['css'])) {
                $css = $toRel($e['dir'].'/'.$e['css']);
                // importa subito il CSS (side-effect)
                $lines[] = "  await import('{$css}');";
            }
            if (!empty($e['js'])) {
                $js  = $toRel($e['dir'].'/'.$e['js']);
                // dynamic import con stringa LITERALE (Vite lo bundle-izza)
                $lines[] = "  mods.push(import('{$js}'));";
            }
        }

        $lines[] = "  for (const m of mods) (await m).default?.(API);";
        $lines[] = "}";

        file_put_contents(base_path('resources/js/admin/plugins-entries.js'), implode("\n", $lines));
        $this->info('Aggiornato: resources/js/admin/plugins-entries.js');
        return self::SUCCESS;
    }

}
