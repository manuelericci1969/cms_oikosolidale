<?php

use App\Http\Controllers\Admin\ChatbotSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FooterBrandSettingsController;
use App\Http\Controllers\Admin\GoogleCalendarController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageComponentController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageVisualEditorV4Controller;
use App\Http\Controllers\Admin\PageVisualEditorV5Controller;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Models\Page;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC
|-------------------------------------------------------------------------- - - - - - - - - -
*/

// Homepage dinamica
Route::get('/', function () {
    $homepage = Page::homepage()->published()->first();

    if ($homepage) {
        return view('page.show', ['page' => $homepage]);
    }

    return view('welcome');
})->name('home');

// Redirect SEO
Route::permanentRedirect(
    '/sviluppo-siti-web-olbia-sardegna',
    '/siti-web-olbia'
);

// Pagine legali
Route::view('/privacy-policy', 'legal.privacy')->name('policy.privacy');
Route::view('/cookie-policy', 'legal.cookie')->name('policy.cookie');
// Route::view('/termini-condizioni', 'legal.terms')->name('policy.terms');

// SEO files
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

/*
|--------------------------------------------------------------------------
| DEBUG / TEST ROUTES
|--------------------------------------------------------------------------
| Attive solo in locale
*/
if (app()->environment('local')) {
    Route::get('/test-ses', function () {
        $to = 'm.ricci@r4software.it';

        try {
            Mail::raw(
                'Questo è un messaggio di test inviato tramite Amazon SES da cms.r4software.it.',
                function ($message) use ($to) {
                    $message->to($to)
                        ->subject('Test Amazon SES - cms.r4software.it');
                }
            );

            return 'SES ha accettato il messaggio. Controlla la casella: ' . $to;
        } catch (\Throwable $e) {
            return 'Errore durante l\'invio: ' . $e->getMessage();
        }
    });

    Route::get('/test-email-turbo', function () {
        try {
            Mail::mailer('smtp')->raw(
                'Questo è un test inviato tramite turboSMTP da Laravel.',
                function ($message) {
                    $message->to('manuelericciovh@gmail.com')
                        ->subject('Test turboSMTP da CMS R4Software');
                }
            );

            return 'Email di test inviata. Controlla la casella di posta.';
        } catch (\Throwable $e) {
            return 'Errore nell\'invio: ' . $e->getMessage();
        }
    });

    Route::get('/smtp2go-test', function () {
        Mail::raw('Test SMTP2GO da Laravel', function ($message) {
            $message->to('manuelericciovh@gmail.com')
                ->subject('Test SMTP2GO');
        });

        return 'Email inviata.';
    });

    Route::get('/debug-page/{slug}', function (string $slug) {
        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            return response('<h1>Debug Info</h1><p>❌ Pagina non trovata nel DB</p>');
        }

        $html = '<h1>Debug Info</h1>';
        $html .= '<p>✅ Pagina trovata nel DB</p>';
        $html .= '<ul>';
        $html .= '<li>ID: ' . e($page->id) . '</li>';
        $html .= '<li>Slug: ' . e($page->slug) . '</li>';
        $html .= '<li>Status: ' . e($page->status) . '</li>';
        $html .= '<li>Published at: ' . e($page->published_at ?? 'NULL') . '</li>';
        $html .= '<li>Created by: ' . e($page->created_by) . '</li>';
        $html .= '</ul>';

        $published = Page::where('slug', $slug)->published()->first();

        if ($published) {
            $html .= '<p>✅ Pagina passa lo scope published()</p>';
        } else {
            $html .= '<p>❌ Pagina NON passa lo scope published()</p>';
            $html .= '<p><strong>Motivo:</strong></p>';

            if ($page->status !== 'published') {
                $html .= '<p>- Status non è "published" (è "' . e($page->status) . '")</p>';
            }

            if (!$page->published_at) {
                $html .= '<p>- published_at è NULL</p>';
            }

            if ($page->published_at && $page->published_at->isFuture()) {
                $html .= '<p>- published_at è nel futuro (' . e($page->published_at) . ')</p>';
            }
        }

        return response($html);
    })->where('slug', '[a-zA-Z0-9\-]+');
}

/*
|--------------------------------------------------------------------------
| AUTHENTICATED AREA
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::prefix('profile')
        ->name('profile.')
        ->controller(ProfileController::class)
        ->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::patch('/', 'update')->name('update');
            Route::patch('/password', 'updatePassword')->name('password');
            Route::delete('/', 'destroy')->name('destroy');
        });

    Route::middleware(['role:admin,superadmin'])
        ->prefix('admin')
        ->as('admin.')
        ->group(function () {
            Route::middleware('perm:view.admin')
                ->get('/', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::middleware('perm:settings.manage')
                ->get('/seo/regenerate', [SeoController::class, 'regenerate'])
                ->name('seo.regenerate');

            Route::prefix('settings/google')
                ->as('settings.google.')
                ->middleware('perm:settings.manage')
                ->group(function () {
                    Route::get('/connect', [GoogleCalendarController::class, 'connect'])->name('connect');
                    Route::get('/callback', [GoogleCalendarController::class, 'callback'])->name('callback');
                    Route::post('/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('disconnect');
                    Route::post('/sync', [GoogleCalendarController::class, 'syncNow'])->name('sync');
                });

            Route::prefix('media')
                ->as('media.')
                ->group(function () {
                    Route::middleware('perm:content.media.view')->group(function () {
                        Route::get('/', [MediaController::class, 'index'])->name('index');
                        Route::get('/browse', [MediaController::class, 'browse'])->name('browse');
                        Route::get('/picker', [MediaController::class, 'picker'])->name('picker');
                    });

                    Route::middleware('perm:content.media.edit')->group(function () {
                        Route::post('/', [MediaController::class, 'store'])->name('store');
                        Route::patch('/{medium}', [MediaController::class, 'update'])->name('update');
                        Route::delete('/{medium}', [MediaController::class, 'destroy'])->name('destroy');
                    });
                });

            Route::prefix('plugins')
                ->as('plugins.')
                ->middleware('perm:manage.plugins')
                ->group(function () {
                    Route::get('/', [PluginController::class, 'index'])->name('index');
                    Route::post('/upload', [PluginController::class, 'upload'])->name('upload');
                    Route::patch('/{plugin}/enable', [PluginController::class, 'enable'])->name('enable');
                    Route::patch('/{plugin}/disable', [PluginController::class, 'disable'])->name('disable');
                    Route::delete('/{plugin}', [PluginController::class, 'destroy'])->name('destroy');
                });

            Route::prefix('menus')
                ->as('menus.')
                ->middleware('perm:content.create')
                ->group(function () {
                    Route::get('/', [MenuController::class, 'index'])->name('index');
                    Route::get('/create', [MenuController::class, 'create'])->name('create');
                    Route::post('/', [MenuController::class, 'store'])->name('store');
                    Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
                    Route::patch('/{menu}', [MenuController::class, 'update'])->name('update');
                    Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
                    Route::post('/{menu}/items', [MenuController::class, 'storeItem'])->name('items.store');
                    Route::patch('/items/{item}', [MenuController::class, 'updateItem'])->name('items.update');
                    Route::delete('/items/{item}', [MenuController::class, 'destroyItem'])->name('items.destroy');
                    Route::post('/{menu}/items/reorder', [MenuController::class, 'reorderItems'])->name('items.reorder');
                });

            Route::prefix('settings')
                ->as('settings.')
                ->group(function () {
                    Route::middleware('perm:settings.view')
                        ->get('/', [SettingsController::class, 'index'])
                        ->name('index');
                    Route::middleware('perm:settings.view')
                        ->get('/footer-brand', [FooterBrandSettingsController::class, 'edit'])
                        ->name('footer-brand.edit');
                    Route::middleware('perm:settings.manage')
                        ->put('/footer-brand', [FooterBrandSettingsController::class, 'update'])
                        ->name('footer-brand.update');
                    Route::middleware('perm:settings.view')
                        ->get('/chatbot/status', [ChatbotSettingsController::class, 'status'])
                        ->name('chatbot.status');
                    Route::middleware('perm:settings.manage')
                        ->put('/chatbot', [ChatbotSettingsController::class, 'update'])
                        ->name('chatbot.update');
                    Route::middleware('perm:settings.manage')
                        ->post('/', [SettingsController::class, 'update'])
                        ->name('update');
                });

            Route::prefix('users')
                ->as('users.')
                ->middleware('perm:manage.users')
                ->group(function () {
                    Route::get('/', [UserController::class, 'index'])->name('index');
                    Route::patch('/{user}', [UserController::class, 'update'])->name('update');
                    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
                    Route::post('/bulk', [UserController::class, 'bulk'])->name('bulk');
                    Route::get('/{user}/permissions', [UserController::class, 'editPermissions'])->name('permissions.edit');
                    Route::post('/{user}/permissions', [UserController::class, 'syncPermissions'])->name('permissions.sync');
                    Route::delete('/{user}/permissions', [UserController::class, 'clearPermissions'])->name('permissions.clear');
                });

            Route::prefix('roles')
                ->as('roles.')
                ->middleware('perm:manage.roles')
                ->group(function () {
                    Route::get('/', [RoleController::class, 'index'])->name('index');
                    Route::post('/sync', [RoleController::class, 'sync'])->name('sync');
                });

            Route::prefix('permissions')
                ->as('permissions.')
                ->middleware('perm:manage.permissions')
                ->group(function () {
                    Route::get('/', [PermissionController::class, 'index'])->name('index');
                    Route::post('/', [PermissionController::class, 'store'])->name('store');
                    Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
                });

            Route::prefix('pages')
                ->as('pages.')
                ->middleware('perm:content.create')
                ->group(function () {
                    Route::get('/', [PageController::class, 'index'])->name('index');
                    Route::get('/create', [PageController::class, 'create'])->name('create');
                    Route::post('/', [PageController::class, 'store'])->name('store');

                    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
                    Route::get('/{page}/edit-v2', [PageController::class, 'editV2'])->name('edit_v2');
                    Route::get('/{page}/edit-v3', [PageController::class, 'editV3'])->name('edit_v3');

                    Route::get('/{page}/edit-v4', [PageVisualEditorV4Controller::class, 'edit'])->name('edit_v4');
                    Route::patch('/{page}/update-v4', [PageVisualEditorV4Controller::class, 'update'])->name('update_v4');
                    Route::get('/{page}/preview-v4', [PageVisualEditorV4Controller::class, 'preview'])->name('preview_v4');

                    Route::get('/{page}/edit-v5', [PageVisualEditorV5Controller::class, 'edit'])->name('edit_v5');
                    Route::patch('/{page}/update-v5', [PageVisualEditorV5Controller::class, 'update'])->name('update_v5');
                    Route::get('/{page}/preview-v5', [PageVisualEditorV5Controller::class, 'preview'])->name('preview_v5');

                    Route::patch('/{page}', [PageController::class, 'update'])->name('update');
                    Route::patch('/{page}/update-v3', [PageController::class, 'updateV3'])->name('update_v3');

                    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
                    Route::post('/{page}/duplicate', [PageController::class, 'duplicate'])->name('duplicate');
                });

            Route::prefix('api/page-components')
                ->as('api.page-components.')
                ->middleware('perm:content.create')
                ->group(function () {
                    Route::get('/', [PageComponentController::class, 'index'])->name('index');
                    Route::post('/', [PageComponentController::class, 'store'])->name('store');
                    Route::patch('/{pageComponent}', [PageComponentController::class, 'update'])->name('update');
                    Route::delete('/{pageComponent}', [PageComponentController::class, 'destroy'])->name('destroy');
                });
        });
});

require __DIR__ . '/r4-footer-chatbot.php';
require __DIR__ . '/auth.php';

Route::get('/{slug}', function (string $slug) {
    $page = Page::where('slug', $slug)
        ->published()
        ->firstOrFail();

    return view('page.show', compact('page'));
})
    ->name('page.show')
    ->where('slug', '[a-zA-Z0-9\-]+');
