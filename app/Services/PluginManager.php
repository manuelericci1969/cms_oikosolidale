<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PluginManager
{
    // =========================
    // CONFIG & PATHS
    // =========================
    public function basePath(): string      { return rtrim((string) config('plugins.path'), DIRECTORY_SEPARATOR); }
    public function publicBase(): string    { return rtrim((string) config('plugins.public_path'), DIRECTORY_SEPARATOR); }
    public function manifestName(): string  { return (string) config('plugins.manifest', 'plugin.json'); }
    public function publicDirName(): string { return (string) config('plugins.public_dir', 'public'); }
    public function adminEntryName(): string{ return (string) config('plugins.admin_entry', 'admin.js'); }
    public function viewEntryName(): string { return (string) config('plugins.view_entry', 'view.js'); }
    public function webBase(): string       { return rtrim((string) config('plugins.web_path', '/plugins'), '/'); }

    public function ensureDirs(): void
    {
        if (!File::exists($this->basePath()))   File::makeDirectory($this->basePath(), 0755, true);
        if (!File::exists($this->publicBase())) File::makeDirectory($this->publicBase(), 0755, true);
    }

    // Normalizza i path dichiarati nel manifest:
    // - rimuove eventuale prefisso "public/"
    // - rimuove leading slash
    protected function normalizeRelative(string $relative): string
    {
        $relative = ltrim(trim($relative), '/');
        if (str_starts_with($relative, 'public/')) {
            $relative = substr($relative, 7); // drop "public/"
        }
        return $relative;
    }

    // =========================
    // INSTALLER
    // =========================
    /** Carica uno zip, valida manifest e registra in DB. */
    public function installFromZip(\SplFileInfo $zipFile): Plugin
    {
        $this->ensureDirs();

        $tmpDir = $this->basePath() . '/__tmp_' . uniqid('', true);
        File::makeDirectory($tmpDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new \RuntimeException('Impossibile aprire ZIP');
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        // Manifest
        $manifestPath = $this->findManifest($tmpDir);
        if (!$manifestPath) {
            File::deleteDirectory($tmpDir);
            throw new \RuntimeException('Manifest non trovato (' . $this->manifestName() . ')');
        }

        $manifestRaw = File::get($manifestPath);
        $manifest    = json_decode($manifestRaw, true);
        if (!is_array($manifest)) {
            File::deleteDirectory($tmpDir);
            throw new \RuntimeException('Manifest non valido (JSON)');
        }

        $slug = $manifest['slug'] ?? null;
        $name = $manifest['name'] ?? $slug;
        if (!$slug || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
            File::deleteDirectory($tmpDir);
            throw new \RuntimeException('Slug mancante o non valido [a-z0-9-]');
        }

        // Sposta codice plugin nella cartella definitiva
        $dest = $this->basePath() . '/' . $slug;
        if (File::exists($dest)) File::deleteDirectory($dest);
        File::moveDirectory($this->pluginRootFrom($manifestPath), $dest);

        // Copia asset pubblici:  {plugin}/public/*  ->  /public/plugins/{slug}/*
        $pubIn  = $dest . '/' . $this->publicDirName();
        $pubOut = $this->publicBase() . '/' . $slug;
        if (File::exists($pubOut)) File::deleteDirectory($pubOut);
        if (File::isDirectory($pubIn)) File::copyDirectory($pubIn, $pubOut);

        // Upsert DB (+ abilita subito)
        $plugin = Plugin::updateOrCreate(
            ['slug' => $slug],
            [
                'name'     => $name ?? $slug,
                'version'  => $manifest['version'] ?? '1.0.0',
                'author'   => $manifest['author'] ?? null,
                'manifest' => $manifest,
                'enabled'  => true, // <— abilita al volo
            ]
        );

        File::deleteDirectory($tmpDir);
        return $plugin;
    }

    /** Trova file manifest in una estrazione */
    protected function findManifest(string $dir): ?string
    {
        $target = strtolower($this->manifestName());
        foreach (File::allFiles($dir) as $f) {
            if (strtolower($f->getFilename()) === $target) {
                return $f->getRealPath();
            }
        }
        return null;
    }

    /** Root del plugin a partire dal manifest */
    protected function pluginRootFrom(string $manifestPath): string
    {
        return dirname($manifestPath);
    }

    // =========================
    // QUERY PLUGINS
    // =========================
    /** Plugin abilitati */
    public function enabled()
    {
        return Plugin::where('enabled', true)->get();
    }

    /** Normalizza il manifest (array) */
    protected function manifestArray($mf): array
    {
        if (is_array($mf)) return $mf;
        if (is_string($mf)) {
            $j = json_decode($mf, true);
            return is_array($j) ? $j : [];
        }
        return [];
    }

    // =========================
    // ASSET HELPERS
    // =========================
    /** FS path assoluto a /public/plugins/{slug}/{pathRelativo} */
    public function assetFsPath(Plugin $plugin, string $relative): string
    {
        $relative = $this->normalizeRelative($relative);
        return $this->publicBase()
            . DIRECTORY_SEPARATOR . $plugin->slug
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    /** URL pubblico (accetta già http(s) o path assoluti /...) */
    public function assetUrl(Plugin $plugin, string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';

        if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        $path = $this->normalizeRelative($path);
        return $this->webBase() . '/' . $plugin->slug . '/' . ltrim($path, '/');
    }

    /** URL con cache-busting se il file esiste nel FS pubblico */
    public function assetUrlVersioned(Plugin $plugin, string $relative): string
    {
        $relative = $this->normalizeRelative($relative);
        $url = $this->assetUrl($plugin, $relative);

        if (preg_match('#^https?://#i', $url)) return $url;

        $fs = $this->assetFsPath($plugin, $relative);
        if (is_file($fs)) {
            $ver = @filemtime($fs);
            if ($ver) return $url . '?v=' . $ver;
        }
        return $url;
    }

    // =========================
    // REGISTRI & ASSET LISTS
    // =========================
    /** Registry blocchi per il Builder */
    public function buildBlocksRegistry(): array
    {
        $out = [];
        foreach ($this->enabled() as $p) {
            $m = $this->manifestArray($p->manifest);
            $blocks = $m['blocks'] ?? [];
            foreach ($blocks as $b) {
                $out[] = [
                    'plugin'      => $p->slug,
                    'type'        => $b['type'] ?? null,
                    'label'       => $b['label'] ?? ($b['type'] ?? 'Plugin'),
                    'icon'        => $b['icon'] ?? 'bi-puzzle',
                    'add_columns' => $b['add_columns'] ?? 12,
                ];
            }
        }
        return $out;
    }

    /** Asset per l’AREA ADMIN */
    public function adminAssets(): array
    {
        $css = [];
        $js  = [];

        foreach ($this->enabled() as $p) {
            $mf = $this->manifestArray($p->manifest);

            // 1) admin_entry PRIMA (se esiste)
            $entry = $mf['admin_entry'] ?? $this->adminEntryName();
            if (is_string($entry) && $entry !== '') {
                $fs = $this->assetFsPath($p, $entry);
                if (is_file($fs)) {
                    $js[] = $this->assetUrlVersioned($p, $entry);
                }
            }

            // 2) assets.front poi (CSS e JS)
            $front = $mf['assets']['front'] ?? [];
            if (is_array($front)) {
                foreach ($front as $rel) {
                    $url = $this->assetUrlVersioned($p, $rel);
                    if (!$url) continue;
                    if (preg_match('/\.css(\?.*)?$/i', $rel)) $css[] = $url; else $js[] = $url;
                }
            }
        }

        $css = array_values(array_unique(array_filter($css)));
        $js  = array_values(array_unique(array_filter($js)));

        return ['css' => $css, 'js' => $js];
    }

    /** Front assets flat */
    public function frontAssets(): array
    {
        $assets = [];
        foreach ($this->enabled() as $p) {
            $mf = $this->manifestArray($p->manifest);
            $front = $mf['assets']['front'] ?? [];
            if (!is_array($front)) continue;
            foreach ($front as $rel) {
                $assets[] = $this->assetUrlVersioned($p, $rel);
            }
        }
        return array_values(array_unique(array_filter($assets)));
    }

    /** Entry frontend opzionale */
    public function frontendAssets(): array
    {
        $assets = [];
        $entry = $this->viewEntryName(); // es: "view.js"
        foreach ($this->enabled() as $p) {
            $fs = $this->assetFsPath($p, $entry);
            if (is_file($fs)) {
                $assets[] = $this->assetUrlVersioned($p, $entry);
            }
        }
        return array_values(array_unique($assets));
    }
}
