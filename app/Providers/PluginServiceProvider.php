<?php

namespace App\Providers;

use App\Services\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, function() {
            return new PluginManager();
        });
    }

    public function boot(PluginManager $pm): void
    {
        // niente di “magico” qui: costruiamo i dati per le view quando servono
        view()->composer(['admin.pages.edit','admin.plugins.index'], function($view) use ($pm) {
            $view->with('pluginBlocksRegistry', $pm->buildBlocksRegistry());
            $view->with('pluginAdminAssets', $pm->adminAssets());
        });
    }
}
