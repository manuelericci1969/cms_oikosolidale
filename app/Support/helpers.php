<?php

use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\Menu;

/**
 * setting($key, $default)
 * ----------------------------------------------------------------
 * Legge i valori passando SEMPRE dal model Setting::get()
 * che decodifica il JSON e usa la sua cache interna.
 * Qui facciamo solo una cache per-chiave a breve TTL.
 * Setting::put() invalida sia "settings.kv" che "setting:{$key}".
 */
if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "setting:{$key}",
            now()->addMinutes(10),
            function () use ($key, $default) {
                // Usa il model (decodifica JSON, fallback default)
                $val = Setting::get($key, $default);
                // Se per qualunque motivo è null e c'è un default, ritorna il default
                return $val ?? $default;
            }
        );
    }
}

/**
 * renderMenu($location = 'header')
 * ----------------------------------------------------------------
 * Rende il menu attivo per una location (header/footer…).
 * Carica solo item attivi e figli di 1° livello ordinati.
 */
if (! function_exists('renderMenu')) {
    function renderMenu(string $location = 'header'): string
    {
        $menu = Menu::active()
            ->byLocation($location)
            ->with([
                'items' => fn($q) => $q->active()->orderBy('order'),
                'items.children' => fn($q) => $q->active()->orderBy('order'),
                'items.page',
            ])
            ->first();

        if (!$menu || $menu->items->isEmpty()) {
            return '';
        }

        return view('partials.menu', ['menu' => $menu])->render();
    }
}

/**
 * getHomepage()
 * ----------------------------------------------------------------
 * Restituisce la pagina impostata come homepage (cache breve).
 */
if (! function_exists('getHomepage')) {
    function getHomepage(): ?\App\Models\Page
    {
        return Cache::remember(
            'homepage',
            now()->addMinutes(10),
            fn() => \App\Models\Page::homepage()->published()->first()
        );
    }
}

/**
 * isActiveMenu($url)
 * ----------------------------------------------------------------
 * True se l’URL del menu corrisponde (o è prefisso) al path corrente.
 */
if (! function_exists('isActiveMenu')) {
    function isActiveMenu(string $url): bool
    {
        $menuPath = parse_url($url, PHP_URL_PATH) ?? '/';
        $menuPath = '/' . ltrim($menuPath, '/'); // normalizza

        $currentPath = '/' . ltrim(request()->path(), '/');

        return $currentPath === $menuPath
            || str_starts_with($currentPath, rtrim($menuPath, '/') . '/');
    }
}
