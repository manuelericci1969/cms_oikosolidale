<?php

use App\Http\Middleware\HtmlMinifyMiddleware;
use App\Http\Middleware\InjectPageSeoHeadMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\PluginServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/r4-editor-v5-seo.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias personalizzati
        $middleware->alias([
            'role'   => \App\Http\Middleware\RoleMiddleware::class,
            'perm'   => \App\Http\Middleware\PermissionMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            // qui puoi aggiungere altri alias in futuro
        ]);

        // Aggiungo il minify HTML e l'iniezione SEO al gruppo "web" (frontend)
        $middleware->web(append: [
            HtmlMinifyMiddleware::class,
            InjectPageSeoHeadMiddleware::class,
        ]);

        // Esempi (facoltativi) per modificare gruppi:
        // $middleware->api(append: [ /* ... */ ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
