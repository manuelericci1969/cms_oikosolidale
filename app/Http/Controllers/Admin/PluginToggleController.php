<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPlugin;
use App\Support\Plugins\PluginManager;
use Illuminate\Http\Request;

class PluginToggleController extends Controller
{
    public function index(PluginManager $pm)
    {
        $plugins = $pm->allWithState();
        return view('admin.plugins.index', compact('plugins'));
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'slug'    => 'required|string',
            'enabled' => 'required|boolean',
            'name'    => 'nullable|string',
        ]);

        CmsPlugin::updateOrCreate(
            ['slug' => $data['slug']],
            ['enabled' => $data['enabled'], 'name' => $data['name'] ?? $data['slug']]
        );

        return back()->with('status', 'Stato plugin aggiornato.');
    }

    /**
     * Endpoint JSON per il loader JS in admin:
     * restituisce gli entry pubblici (es. /plugins/{slug}/admin.js) dei plugin abilitati.
     */
    public function entriesJson(PluginManager $pm)
    {
        $entries = [];

        foreach ($pm->enabled() as $p) {
            // preferisci admin_entry da plugin.json, altrimenti prova fallback a public/admin.js
            $slug     = $p['slug'];
            $baseUrl  = url('plugins/'.$slug); // presuppone che ogni plugin pubblichi i suoi asset in public/plugins/{slug}
            $adminRel = $p['admin_entry'] ?? 'public/admin.js';

            // normalizza: se l’entry inizia con "public/", togli e mappa in /plugins/{slug}/...
            $adminRel = ltrim(preg_replace('#^public/#', '', $adminRel), '/');
            $entries[] = $baseUrl . '/' . $adminRel;
        }

        return response()->json(['entries' => array_values(array_unique($entries))]);
    }
}
