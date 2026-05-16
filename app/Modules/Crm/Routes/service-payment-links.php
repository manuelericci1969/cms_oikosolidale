<?php

use App\Modules\Crm\Http\Controllers\ServicePaymentLinkController;
use App\Modules\Crm\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'active', 'role:admin,superadmin', 'perm:view.admin'])
    ->prefix('admin/crm')
    ->as('admin.crm.')
    ->group(function () {
        Route::get('services/{service}/payment-links', [ServicePaymentLinkController::class, 'index'])
            ->name('services.payment-links.index');

        Route::post('services/{service}/payment-links', [ServicePaymentLinkController::class, 'store'])
            ->name('services.payment-links.store');

        Route::post('services/{service}/payment-links/{paymentLink}/refresh', [ServicePaymentLinkController::class, 'refresh'])
            ->name('services.payment-links.refresh');

        Route::post('services/{service}/payment-links/{paymentLink}/email', [ServicePaymentLinkController::class, 'sendEmail'])
            ->name('services.payment-links.email');

        Route::post('services/{service}/payment-links/{paymentLink}/whatsapp', [ServicePaymentLinkController::class, 'sendWhatsapp'])
            ->name('services.payment-links.whatsapp');
    });

Route::middleware('web')
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        Route::get('service-payments/{paymentLink}/success', [ServicePaymentLinkController::class, 'success'])
            ->name('service-payments.success');

        Route::get('service-payments/{paymentLink}/cancel', [ServicePaymentLinkController::class, 'cancel'])
            ->name('service-payments.cancel');
    });

Route::post('crm/stripe/callback', [StripeWebhookController::class, 'handle'])
    ->name('crm.stripe.callback');
