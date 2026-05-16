<?php

namespace App\Providers;

use App\Modules\Crm\Models\Appointment;
use App\Modules\Crm\Observers\AppointmentObserver;
use App\Support\VisualEditorCssScope;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Forza i template Bootstrap 5 per la paginazione
        Paginator::useBootstrapFive();
        // Se vuoi Bootstrap 4:
        // Paginator::useBootstrapFour();

        Appointment::observe(AppointmentObserver::class);

        View::composer('page.show', function ($view) {
            $page = $view->getData()['page'] ?? null;

            if (!$page) {
                return;
            }

            $editorMode = (string) ($page->editor_mode ?? 'structured');

            if ($editorMode !== 'visual' || blank($page->visual_css ?? null)) {
                return;
            }

            $page->setAttribute(
                'visual_css',
                VisualEditorCssScope::scope((string) $page->visual_css, '.page-visual-content')
            );
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\SeoImprovePagesCommand::class,
            ]);
        }
    }
}
