<?php

use App\Modules\Crm\Http\Controllers\BillingProfileController;
use App\Modules\Crm\Http\Controllers\ContractController;
use App\Modules\Crm\Http\Controllers\QuoteBillingDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'active', 'role:admin,superadmin', 'perm:view.admin'])
    ->prefix('admin/crm')
    ->as('admin.crm.')
    ->group(function () {
        Route::resource('billing-profiles', BillingProfileController::class)->except(['show']);

        Route::get('quotes/{quote}/billing-data', [QuoteBillingDataController::class, 'edit'])
            ->name('quotes.billing-data.edit');

        Route::put('quotes/{quote}/billing-data', [QuoteBillingDataController::class, 'update'])
            ->name('quotes.billing-data.update');

        Route::post('quotes/{quote}/contract/accept-paper', [ContractController::class, 'acceptPaper'])
            ->name('quotes.contract.accept-paper');

        Route::post('quotes/{quote}/contract/regenerate', [ContractController::class, 'regenerateFromQuote'])
            ->name('quotes.contract.regenerate');

        Route::post('contracts/regenerate-missing', [ContractController::class, 'regenerateMissing'])
            ->name('contracts.regenerate-missing');

        Route::get('contracts/{contract}/download', [ContractController::class, 'download'])
            ->name('contracts.download');
    });
