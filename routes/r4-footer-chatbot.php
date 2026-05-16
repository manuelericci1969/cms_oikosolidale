<?php

use App\Http\Controllers\Admin\ChatbotSettingsController;
use App\Http\Controllers\Admin\CrmCallAutomationSettingsController;
use App\Http\Controllers\Admin\FooterBrandSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:admin,superadmin'])
    ->prefix('admin/settings')
    ->as('admin.settings.')
    ->group(function () {
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

        Route::middleware('perm:settings.view')
            ->get('/crm-call-automation', [CrmCallAutomationSettingsController::class, 'edit'])
            ->name('crm-call-automation.edit');

        Route::middleware('perm:settings.manage')
            ->put('/crm-call-automation', [CrmCallAutomationSettingsController::class, 'update'])
            ->name('crm-call-automation.update');
    });
