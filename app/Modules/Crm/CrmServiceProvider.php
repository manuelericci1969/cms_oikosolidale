<?php

namespace App\Modules\Crm;

use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Migrazioni del modulo
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Views del modulo
        $this->loadViewsFrom(__DIR__.'/resources/views', 'crm');

        // Rotte del modulo
        $this->loadRoutesFrom(__DIR__.'/Routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/contracts.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/service-payment-links.php');
    }
}
