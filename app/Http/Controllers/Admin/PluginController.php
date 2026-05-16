<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PluginController extends Controller
{
    public function index()
    {
        $plugins = Plugin::orderByDesc('enabled')->orderBy('name')->get();
        return view('admin.plugins.index', compact('plugins'));
    }

    public function upload(Request $req, PluginManager $pm)
    {
        $req->validate(['zip' => ['required','file','mimes:zip','max:51200']]); // 50MB
        $plugin = $pm->installFromZip($req->file('zip'));
        return redirect()->route('admin.plugins.index')->with('success', "Plugin {$plugin->name} caricato.");
    }

    public function enable(Plugin $plugin)
    {
        $plugin->update(['enabled' => true]);
        return back()->with('success', "Plugin {$plugin->name} abilitato.");
    }

    public function disable(Plugin $plugin)
    {
        $plugin->update(['enabled' => false]);
        return back()->with('success', "Plugin {$plugin->name} disabilitato.");
    }

    public function destroy(Plugin $plugin, PluginManager $pm)
    {
        // rimuovi cartelle
        $base = config('plugins.path').'/'.$plugin->slug;
        $pub  = config('plugins.public_path').'/'.$plugin->slug;
        if (File::isDirectory($base)) File::deleteDirectory($base);
        if (File::isDirectory($pub))  File::deleteDirectory($pub);
        $name = $plugin->name;
        $plugin->delete();
        return back()->with('success', "Plugin {$name} rimosso.");
    }
}


