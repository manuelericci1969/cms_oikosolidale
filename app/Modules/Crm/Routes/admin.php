<?php

use App\Http\Controllers\Admin\GoogleCalendarController;
use App\Modules\Crm\Http\Controllers\AdminChatbotConversationController;
use App\Modules\Crm\Http\Controllers\Agent\AgentChatbotConversationController;
use App\Modules\Crm\Http\Controllers\Agent\AgentTaskController;
use App\Modules\Crm\Http\Controllers\CalendarController;
use App\Modules\Crm\Http\Controllers\CallCampaignController;
use App\Modules\Crm\Http\Controllers\ChatbotDashboardController;
use App\Modules\Crm\Http\Controllers\ChatbotFaqController;
use App\Modules\Crm\Http\Controllers\ChatbotFeedbackController;
use App\Modules\Crm\Http\Controllers\ChatbotUnknownQuestionController;
use App\Modules\Crm\Http\Controllers\PublicChatbotController;
use App\Modules\Crm\Http\Controllers\QuotePaymentController;
use App\Modules\Crm\Http\Controllers\WhatsappMessageController;
use Illuminate\Support\Facades\Route;

use App\Modules\Crm\Http\Controllers\AdminLeadController;
use App\Modules\Crm\Http\Controllers\AdminTaskController;
use App\Modules\Crm\Http\Controllers\Agent\AgentLeadController;
use App\Modules\Crm\Http\Controllers\Agent\AgentQuoteController;
use App\Modules\Crm\Http\Controllers\CampaignController;
use App\Modules\Crm\Http\Controllers\CustomerController;
use App\Modules\Crm\Http\Controllers\EmailListController;
use App\Modules\Crm\Http\Controllers\LeadController;
use App\Modules\Crm\Http\Controllers\MailWebhookController;
use App\Modules\Crm\Http\Controllers\ProductController;
use App\Modules\Crm\Http\Controllers\PublicCampaignTrackingController;
use App\Modules\Crm\Http\Controllers\PublicLeadController;
use App\Modules\Crm\Http\Controllers\PublicQuoteAcceptanceController;
use App\Modules\Crm\Http\Controllers\QuoteController;
use App\Modules\Crm\Http\Controllers\ServiceController;
use App\Modules\Crm\Http\Controllers\ServiceReminderController;

/*
|--------------------------------------------------------------------------
| Pattern globali per i parametri
|--------------------------------------------------------------------------
| Evita che "sort" venga interpretato come {task}
*/
Route::pattern('task', '[0-9]+');

/*
|--------------------------------------------------------------------------
| Rotte CRM area admin
|--------------------------------------------------------------------------
| Prefisso: /admin/crm
| Nome: admin.crm.*
*/

Route::middleware(['web', 'auth', 'verified', 'active', 'role:admin,superadmin', 'perm:view.admin'])
    ->prefix('admin/crm')
    ->as('admin.crm.')
    ->group(function () {

        // Dashboard CRM → redirect alla lista clienti
        Route::get('/', function () {
            return redirect()->route('admin.crm.customers.index');
        })->name('dashboard');

        // Clienti (no show)
        Route::resource('customers', CustomerController::class)->except(['show']);

        // Prodotti (no show)
        Route::resource('products', ProductController::class)->except(['show']);

        // FAQ Chatbot
        Route::resource('chatbot-faqs', ChatbotFaqController::class)->except(['show']);

        // Domande chatbot non riconosciute
        Route::get('chatbot-unknown-questions', [ChatbotUnknownQuestionController::class, 'index'])
            ->name('chatbot-unknown-questions.index');

        Route::patch('chatbot-unknown-questions/{chatbotUnknownQuestion}/status', [ChatbotUnknownQuestionController::class, 'updateStatus'])
            ->name('chatbot-unknown-questions.status.update');

        Route::delete('chatbot-unknown-questions/{chatbotUnknownQuestion}', [ChatbotUnknownQuestionController::class, 'destroy'])
            ->name('chatbot-unknown-questions.destroy');

        Route::post('chatbot/feedback', [PublicChatbotController::class, 'feedback'])
            ->name('chatbot.feedback')
            ->middleware('throttle:20,1');

        // Preventivi (tutte le azioni REST)
        Route::resource('quotes', QuoteController::class);

        // Invio preventivo via email
        Route::post('quotes/{quote}/send', [QuoteController::class, 'send'])
            ->name('quotes.send');

        // PDF preventivo
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])
            ->name('quotes.pdf');

        // Pagamenti preventivi
        Route::post('quotes/{quote}/payments', [QuotePaymentController::class, 'store'])
            ->name('quotes.payments.store');

        Route::put('quotes/{quote}/payments/{payment}', [QuotePaymentController::class, 'update'])
            ->name('quotes.payments.update');

        Route::delete('quotes/{quote}/payments/{payment}', [QuotePaymentController::class, 'destroy'])
            ->name('quotes.payments.destroy');

        // Servizi clienti (no show – usiamo index/edit)
        Route::resource('services', ServiceController::class)->except(['show']);

        // Invio manuale promemoria scadenza per un servizio
        Route::post('services/{service}/send-reminder', [ServiceReminderController::class, 'send'])
            ->name('services.send-reminder');

        Route::post('services/{service}/send-whatsapp-reminder', [ServiceReminderController::class, 'sendWhatsapp'])
            ->name('services.send-whatsapp-reminder');

        // === LEAD (area admin) ============================================
        Route::resource('leads', LeadController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::post('leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])
            ->name('leads.convert');

        Route::post('leads/{lead}/activities', [LeadController::class, 'storeActivity'])
            ->name('leads.activities.store');

        Route::post('leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])
            ->name('leads.status.update');

        // Campagne mail marketing
        Route::resource('campaigns', CampaignController::class);

        // Aggiorna destinatari da lead/clienti
        Route::post('campaigns/{campaign}/recipients', [CampaignController::class, 'updateRecipients'])
            ->name('campaigns.recipients.update');

        // ✅ STIMA destinatari (prima di rigenerare)
        Route::post('campaigns/{campaign}/recipients/estimate', [CampaignController::class, 'estimateRecipients'])
            ->name('campaigns.recipients.estimate');

        // Import CSV
        Route::post('campaigns/{campaign}/import-csv', [CampaignController::class, 'importCsv'])
            ->name('campaigns.import_csv');

        // Metti in coda invio
        Route::post('campaigns/{campaign}/queue', [CampaignController::class, 'queue'])
            ->name('campaigns.queue');

        // Invio campagna in modalità "sincrona" con progress bar
        Route::post('campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])
            ->name('campaigns.send_now');

        Route::post('campaigns/{campaign}/retry-errors', [CampaignController::class, 'retryErrors'])
            ->name('campaigns.retry_errors');

        // Cancella utenti campagna o email scelte
        Route::post('campaigns/{campaign}/recipients/clear', [CampaignController::class, 'clearRecipients'])
            ->name('campaigns.recipients.clear');

        // LISTE EMAIL (archivio)
        Route::resource('email-lists', EmailListController::class)
            ->parameters(['email-lists' => 'list'])
            ->except(['show']);

        Route::post('email-lists/{list}/import-csv', [EmailListController::class, 'importCsv'])
            ->name('email-lists.import_csv');

        Route::post('email-lists/{list}/sync-from-crm', [EmailListController::class, 'syncFromCrm'])
            ->name('email-lists.sync_from_crm');

        // Contatti manuali della lista
        Route::post('email-lists/{list}/contacts', [EmailListController::class, 'storeContact'])
            ->name('email-lists.contacts.store');

        Route::delete('email-lists/{list}/contacts/{contact}', [EmailListController::class, 'destroyContact'])
            ->name('email-lists.contacts.destroy');

        // Modifica contatti lista
        Route::get('email-lists/{list}/contacts/{contact}/edit', [EmailListController::class, 'editContact'])
            ->name('email-lists.contacts.edit');

        Route::patch('email-lists/{list}/contacts/{contact}', [EmailListController::class, 'updateContact'])
            ->name('email-lists.contacts.update');

        // Categorie contatti (globali per client_id)
        Route::post('email-lists/categories', [EmailListController::class, 'storeCategory'])
            ->name('email-lists.categories.store');

        Route::delete('email-lists/categories/{category}', [EmailListController::class, 'destroyCategory'])
            ->name('email-lists.categories.destroy');

        // === TASKS (area admin) ==========================================

        // ⚠️ Rotta sort SENZA {task} - con pattern globale su {task},
        // "sort" non può più essere interpretato come ID.
        Route::patch('tasks/sort', [AdminTaskController::class, 'sort'])
            ->name('tasks.sort');

        // Rotte extra su singolo task
        Route::patch('tasks/{task}/status', [AdminTaskController::class, 'updateStatus'])
            ->name('tasks.status.update');

        Route::post('tasks/{task}/notes', [AdminTaskController::class, 'storeNote'])
            ->name('tasks.notes.store');

        Route::patch('tasks/{task}/assign', [AdminTaskController::class, 'assignUser'])
            ->name('tasks.assign');

        // Vista Kanban + CRUD base
        Route::resource('tasks', AdminTaskController::class)->except(['show']);

        // === CALENDARIO (admin) =========================================
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::post('calendar/appointments', [CalendarController::class, 'store'])->name('calendar.appointments.store');
        Route::patch('calendar/appointments/{appointment}', [CalendarController::class, 'update'])->name('calendar.appointments.update');
        Route::delete('calendar/appointments/{appointment}', [CalendarController::class, 'destroy'])->name('calendar.appointments.destroy');

        // ✅ GOOGLE CALENDAR actions per pulsanti
        Route::prefix('calendar/google')->as('calendar.google.')->group(function () {
            Route::post('sync', [GoogleCalendarController::class, 'sync'])->name('sync');
            Route::post('dedupe-google', [GoogleCalendarController::class, 'dedupeGoogle'])->name('dedupe_google');
            Route::post('dedupe-db', [GoogleCalendarController::class, 'dedupeDb'])->name('dedupe_db');
        });


        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/', [WhatsappMessageController::class, 'index'])->name('index');
            Route::get('/create', [WhatsappMessageController::class, 'create'])->name('create');
            Route::post('/', [WhatsappMessageController::class, 'store'])->name('store');
        });

        // === CHATBOT AI (area admin) =====================================

        Route::get('chatbot-conversations', [AdminChatbotConversationController::class, 'index'])
            ->name('chatbot-conversations.index');

        Route::get('chatbot-conversations/{conversation}', [AdminChatbotConversationController::class, 'show'])
            ->name('chatbot-conversations.show');

        Route::post('chatbot-conversations/{conversation}/assign', [AdminChatbotConversationController::class, 'assign'])
            ->name('chatbot-conversations.assign');

        Route::post('chatbot-conversations/{conversation}/close', [AdminChatbotConversationController::class, 'close'])
            ->name('chatbot-conversations.close');

        Route::post('chatbot-conversations/{conversation}/reopen', [AdminChatbotConversationController::class, 'reopen'])
            ->name('chatbot-conversations.reopen');

        Route::post('chatbot-conversations/{conversation}/mark-spam', [AdminChatbotConversationController::class, 'markSpam'])
            ->name('chatbot-conversations.mark_spam');

        Route::post('chatbot-conversations/{conversation}/convert-to-lead', [AdminChatbotConversationController::class, 'convertToLead'])
            ->name('chatbot-conversations.convert_to_lead');

        Route::delete('chatbot-conversations/{conversation}', [AdminChatbotConversationController::class, 'destroy'])
            ->name('chatbot-conversations.destroy');

        Route::get('chatbot-dashboard', [ChatbotDashboardController::class, 'index'])
            ->name('chatbot-dashboard.index');

        Route::get('chatbot-feedback', [ChatbotFeedbackController::class, 'index'])
            ->name('chatbot-feedback.index');

        Route::delete('chatbot-feedback/{chatbotFeedback}', [ChatbotFeedbackController::class, 'destroy'])
            ->name('chatbot-feedback.destroy');


        // === CALL CAMPAIGNS (campagne chiamate) ============================
        Route::resource('call-campaigns', CallCampaignController::class);

        Route::post('call-campaigns/{callCampaign}/build-queue', [CallCampaignController::class, 'buildQueue'])
            ->name('call-campaigns.build_queue');

        Route::post('call-campaigns/{callCampaign}/activate', [CallCampaignController::class, 'activate'])
            ->name('call-campaigns.activate');

        Route::post('call-campaigns/{callCampaign}/pause', [CallCampaignController::class, 'pause'])
            ->name('call-campaigns.pause');

        Route::post('call-campaigns/{callCampaign}/run-now', [CallCampaignController::class, 'runNow'])
            ->name('call-campaigns.run_now');

        Route::post('call-campaigns/{callCampaign}/rebuild-queue', [CallCampaignController::class, 'rebuildQueue'])
            ->name('call-campaigns.rebuild_queue');

        Route::post('call-campaigns/{callCampaign}/clear-queue', [CallCampaignController::class, 'clearQueue'])
            ->name('call-campaigns.clear_queue');

        Route::post('call-campaigns/{callCampaign}/reset-queue-item/{queueItem}', [CallCampaignController::class, 'resetQueueItem'])
            ->name('call-campaigns.reset_queue_item');

        Route::post('call-campaigns/{callCampaign}/reset-failed-items', [CallCampaignController::class, 'resetFailedItems'])
            ->name('call-campaigns.reset_failed_items');

    });

/*
|--------------------------------------------------------------------------
| Area CRM Agenti
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'verified', 'active', 'role:agent'])
    ->prefix('agent/crm')
    ->as('agent.crm.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('agent.crm.leads.index');
        })->name('dashboard');

        Route::get('leads', [AgentLeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}/edit', [AgentLeadController::class, 'edit'])->name('leads.edit');
        Route::put('leads/{lead}', [AgentLeadController::class, 'update'])->name('leads.update');

        Route::post('leads/{lead}/activities', [AgentLeadController::class, 'storeActivity'])
            ->name('leads.activities.store');

        // QUOTES agent
        Route::get('quotes', [AgentQuoteController::class, 'index'])->name('quotes.index');
        Route::get('quotes/create-from-lead/{lead}', [AgentQuoteController::class, 'createFromLead'])->name('quotes.create_from_lead');
        Route::post('quotes', [AgentQuoteController::class, 'store'])->name('quotes.store');
        Route::get('quotes/{quote}/edit', [AgentQuoteController::class, 'edit'])->name('quotes.edit');
        Route::put('quotes/{quote}', [AgentQuoteController::class, 'update'])->name('quotes.update');
        Route::get('quotes/{quote}/pdf', [AgentQuoteController::class, 'downloadPdf'])->name('quotes.pdf');
        Route::post('quotes/{quote}/send-email', [AgentQuoteController::class, 'sendEmail'])->name('quotes.send_email');

        Route::get('quotes/create', [AgentQuoteController::class, 'create'])->name('quotes.create');

        /*
        |--------------------------------------------------------------------------
        | TASKS area AGENT
        |--------------------------------------------------------------------------
        | - index: lista / kanban dei task assegnati all’agente
        | - updateStatus: può cambiare solo lo stato
        | - storeNote: può aggiungere note
        */

        Route::get('tasks', [AgentTaskController::class, 'index'])
            ->name('tasks.index');

        Route::patch('tasks/{task}/status', [AgentTaskController::class, 'updateStatus'])
            ->name('tasks.status.update');

        Route::post('tasks/{task}/notes', [AgentTaskController::class, 'storeNote'])
            ->name('tasks.notes.store');

        // === CALENDARIO (agent) =========================================
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::post('calendar/appointments', [CalendarController::class, 'store'])->name('calendar.appointments.store');
        Route::patch('calendar/appointments/{appointment}', [CalendarController::class, 'update'])->name('calendar.appointments.update');
        Route::delete('calendar/appointments/{appointment}', [CalendarController::class, 'destroy'])->name('calendar.appointments.destroy');

        // ✅ GOOGLE CALENDAR actions per pulsanti
        Route::prefix('calendar/google')->as('calendar.google.')->group(function () {
            Route::post('sync', [GoogleCalendarController::class, 'sync'])->name('sync');
            Route::post('dedupe-google', [GoogleCalendarController::class, 'dedupeGoogle'])->name('dedupe_google');
            Route::post('dedupe-db', [GoogleCalendarController::class, 'dedupeDb'])->name('dedupe_db');
        });


        // === CHATBOT AI (area agent) =====================================

        Route::get('chatbot-conversations', [AgentChatbotConversationController::class, 'index'])
            ->name('chatbot-conversations.index');

        Route::get('chatbot-conversations/{conversation}', [AgentChatbotConversationController::class, 'show'])
            ->name('chatbot-conversations.show');

        Route::post('chatbot-conversations/{conversation}/close', [AgentChatbotConversationController::class, 'close'])
            ->name('chatbot-conversations.close');

        Route::post('chatbot-conversations/{conversation}/reopen', [AgentChatbotConversationController::class, 'reopen'])
            ->name('chatbot-conversations.reopen');


    });

/*
|--------------------------------------------------------------------------
| Rotte pubbliche CRM
|--------------------------------------------------------------------------
| Prefisso: /crm
| Nome: crm.*
*/

Route::middleware('web')
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {

        // GET: pagina dove il cliente visualizza/accetta il preventivo
        Route::get('quotes/{token}/accetta', [PublicQuoteAcceptanceController::class, 'show'])
            ->name('quotes.accept.show');

        // POST: conferma accettazione
        Route::post('quotes/{token}/accetta', [PublicQuoteAcceptanceController::class, 'confirm'])
            ->name('quotes.accept.confirm');

        // Form contatti pubblico / lead
        Route::get('contatti', [PublicLeadController::class, 'create'])
            ->name('leads.form');

        // Form contatti pubblico / lead SOCIAL
        Route::get('contatti_social', [PublicLeadController::class, 'create_social'])
            ->name('leads.form_social');

        Route::post('contatti', [PublicLeadController::class, 'store'])
            ->name('leads.store')
            ->middleware('throttle:10,1');

        Route::get('contatti/grazie', [PublicLeadController::class, 'thankyou'])
            ->name('leads.thankyou');

        // Pixel di apertura promemoria (lettura)
        Route::get('service-reminders/open/{log}/{hash}', [ServiceReminderController::class, 'trackOpen'])
            ->name('service-reminders.open');

        // Tracking campagne (open / click / unsubscribe)
        Route::get('campaigns/unsubscribe/{recipient}/{hash}', [PublicCampaignTrackingController::class, 'unsubscribe'])
            ->name('campaigns.unsubscribe');

        Route::get('campaigns/open/{recipient}/{hash}', [PublicCampaignTrackingController::class, 'open'])
            ->name('campaigns.open');

        Route::get('campaigns/click/{recipient}/{hash}', [PublicCampaignTrackingController::class, 'click'])
            ->name('campaigns.click');

        // Webhook provider mail (SMTP2Go, SES, ecc.)
        Route::post('mail/webhook/{provider}', [MailWebhookController::class, 'handle'])
            ->name('mail.webhook');

        // === CHATBOT AI pubblico =========================================

        // Avvio conversazione chatbot
        Route::post('chatbot/start', [PublicChatbotController::class, 'start'])
            ->name('chatbot.start')
            ->middleware('throttle:20,1');

        // Invio messaggio chatbot
        Route::post('chatbot/message', [PublicChatbotController::class, 'message'])
            ->name('chatbot.message')
            ->middleware('throttle:30,1');

        // Feedback risposta chatbot 👍 👎
        Route::post('chatbot/feedback', [PublicChatbotController::class, 'feedback'])
            ->name('chatbot.feedback')
            ->middleware('throttle:20,1');

        // Acquisizione lead dal chatbot
        Route::post('chatbot/lead-capture', [PublicChatbotController::class, 'captureLead'])
            ->name('chatbot.capture_lead')
            ->middleware('throttle:10,1');

    });
