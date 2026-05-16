<?php

use App\Modules\Crm\Http\Controllers\AiGatewayController;
use App\Modules\Crm\Http\Controllers\AiEmailListController;
use App\Modules\Crm\Http\Controllers\AiSeoController;
use App\Modules\Crm\Http\Controllers\CallAiAgentTestController;
use App\Modules\Crm\Http\Controllers\TelnyxVoiceBridgeEventController;
use App\Modules\Crm\Http\Controllers\TelnyxWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telnyx/webhook', [TelnyxWebhookController::class, 'handle']);
Route::post('/telnyx/voice-bridge/event', [TelnyxVoiceBridgeEventController::class, 'store']);

Route::prefix('ai')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | AI GATEWAY
    |--------------------------------------------------------------------------
    */
    Route::get('/products/search', [AiGatewayController::class, 'searchProducts']);
    Route::get('/faqs/search', [AiGatewayController::class, 'searchFaqs']);
    Route::get('/appointments/availability', [AiGatewayController::class, 'appointmentAvailability']);
    Route::post('/appointments/request', [AiGatewayController::class, 'requestAppointment']);

    //Chiamata Agente
    Route::post('/call-agent/reply', [CallAiAgentTestController::class, 'reply']);
    Route::post('/call-agent/post-call', [CallAiAgentTestController::class, 'postCall']);

    /*
    |--------------------------------------------------------------------------
    | EMAIL LISTS
    |--------------------------------------------------------------------------
    */
    Route::get('/email-lists', [AiEmailListController::class, 'index']);
    Route::post('/email-lists', [AiEmailListController::class, 'store']);
    Route::get('/email-lists/{list}', [AiEmailListController::class, 'show']);
    Route::put('/email-lists/{list}', [AiEmailListController::class, 'update']);
    Route::patch('/email-lists/{list}', [AiEmailListController::class, 'update']);
    Route::delete('/email-lists/{list}', [AiEmailListController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | CONTATTI DELLA LISTA
    |--------------------------------------------------------------------------
    */
    Route::get('/email-lists/{list}/contacts', [AiEmailListController::class, 'contacts']);
    Route::post('/email-lists/{list}/contacts', [AiEmailListController::class, 'storeContact']);
    Route::get('/email-lists/{list}/contacts/{contact}', [AiEmailListController::class, 'showContact']);
    Route::put('/email-lists/{list}/contacts/{contact}', [AiEmailListController::class, 'updateContact']);
    Route::patch('/email-lists/{list}/contacts/{contact}', [AiEmailListController::class, 'updateContact']);
    Route::delete('/email-lists/{list}/contacts/{contact}', [AiEmailListController::class, 'destroyContact']);

    /*
    |--------------------------------------------------------------------------
    | BULK
    |--------------------------------------------------------------------------
    */
    Route::post('/email-lists/{list}/contacts/bulk-delete', [AiEmailListController::class, 'bulkDeleteContacts']);
    Route::post('/email-lists/{list}/contacts/bulk-upsert', [AiEmailListController::class, 'bulkUpsertContacts']);

    /*
    |--------------------------------------------------------------------------
    | SYNC CRM
    |--------------------------------------------------------------------------
    */
    Route::post('/email-lists/{list}/sync-from-crm', [AiEmailListController::class, 'syncFromCrm']);

    /*
    |--------------------------------------------------------------------------
    | CATEGORIE
    |--------------------------------------------------------------------------
    */
    Route::get('/email-categories', [AiEmailListController::class, 'categories']);
    Route::post('/email-categories', [AiEmailListController::class, 'storeCategory']);
    Route::put('/email-categories/{category}', [AiEmailListController::class, 'updateCategory']);
    Route::patch('/email-categories/{category}', [AiEmailListController::class, 'updateCategory']);
    Route::delete('/email-categories/{category}', [AiEmailListController::class, 'destroyCategory']);

    /*
    |--------------------------------------------------------------------------
    | AZIONI CONTATTO
    |--------------------------------------------------------------------------
    */
    Route::post('/email-lists/{list}/contacts/{contact}/unsubscribe', [AiEmailListController::class, 'unsubscribeContact']);
    Route::post('/email-lists/{list}/contacts/{contact}/resubscribe', [AiEmailListController::class, 'resubscribeContact']);

    /*
    |--------------------------------------------------------------------------
    | SEO AGENT
    |--------------------------------------------------------------------------
    */
    Route::get('/seo/pages', [AiSeoController::class, 'pages']);
    Route::post('/seo/audit', [AiSeoController::class, 'audit']);
    Route::post('/seo/improve', [AiSeoController::class, 'improve']);
    Route::post('/seo/save-suggestion', [AiSeoController::class, 'saveSuggestion']);
    Route::post('/seo/improve-save', [AiSeoController::class, 'improveAndSave']);
    Route::post('/seo/images/audit', [AiSeoController::class, 'auditImages']);
    Route::post('/seo/images/save-alt-suggestion', [AiSeoController::class, 'saveAltSuggestion']);
    Route::post('/seo/images/apply-alt', [AiSeoController::class, 'applyAltSuggestion']);
});

/*
|--------------------------------------------------------------------------
| TEST
|--------------------------------------------------------------------------
*/
Route::post('/ai/test-post', function () {
    return response()->json([
        'ok' => true,
        'message' => 'POST funzionante',
    ]);
});
