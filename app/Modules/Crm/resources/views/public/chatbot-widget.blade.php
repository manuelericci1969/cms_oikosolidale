<div id="r4-chatbot-widget"
     data-start-url="{{ route('crm.chatbot.start') }}"
     data-message-url="{{ route('crm.chatbot.message') }}"
     data-feedback-url="{{ \Illuminate\Support\Facades\Route::has('crm.chatbot.feedback') ? route('crm.chatbot.feedback') : '' }}"
     data-capture-url="{{ route('crm.chatbot.capture_lead') }}"
     data-source-page="{{ url()->current() }}"
     data-channel="website">

    <div id="r4-chatbot-floating-area">
        <div id="r4-chatbot-teaser" class="r4-chatbot-teaser r4-chatbot-teaser-hidden">
            <button id="r4-chatbot-teaser-close" type="button" class="r4-chatbot-teaser-close" aria-label="Chiudi suggerimento">×</button>
            <div class="r4-chatbot-teaser-title">Hai bisogno di aiuto?</div>
            <div class="r4-chatbot-teaser-text">
                Posso aiutarti a capire quale soluzione R4Software è più adatta al tuo progetto.
            </div>
        </div>

        <button id="r4-chatbot-toggle" type="button" aria-label="Apri chat">
            <span class="r4-chatbot-online-dot"></span>

            <img src="{{ asset('vendor/crm/chatbot-avatar.png') }}"
                 class="r4-chatbot-toggle-avatar"
                 alt="Chat R4Software">
        </button>
    </div>

    <div id="r4-chatbot-window" class="r4-chatbot-hidden">

        <div class="r4-chatbot-header">
            <div class="r4-chatbot-header-left">
                <img src="{{ asset('vendor/crm/chatbot-avatar.png') }}"
                     class="r4-chatbot-avatar"
                     width="40"
                     height="40"
                     alt="Assistente R4Software">

                <div>
                    <div class="r4-chatbot-title">Assistente R4Software</div>
                    <div class="r4-chatbot-subtitle">
                        Ti aiutiamo a capire la soluzione più adatta al tuo progetto
                    </div>
                </div>
            </div>

            <div class="r4-chatbot-header-actions">
                <button id="r4-chatbot-reset" class="r4-chatbot-icon-btn" type="button" aria-label="Nuova chat" title="Nuova chat">
                    <span class="r4-chatbot-icon">↺</span>
                </button>

                <button id="r4-chatbot-close" class="r4-chatbot-icon-btn r4-chatbot-close-btn" type="button" aria-label="Chiudi chat" title="Chiudi chat">
                    <span class="r4-chatbot-icon">×</span>
                </button>
            </div>
        </div>

        <div id="r4-chatbot-messages" class="r4-chatbot-messages"></div>

        <div class="r4-chatbot-quick-actions">
            <button type="button" class="r4-chatbot-quick-btn"
                    data-message="Vorrei un sito web professionale per la mia attività">
                Sito web
            </button>

            <button type="button" class="r4-chatbot-quick-btn"
                    data-message="Mi serve un CRM per gestire clienti, lead e preventivi">
                CRM
            </button>

            <button type="button" class="r4-chatbot-quick-btn"
                    data-message="Vorrei sviluppare un'app mobile per la mia attività">
                App
            </button>

            <button type="button" class="r4-chatbot-quick-btn"
                    data-message="Mi interessa una soluzione IoT o automazione">
                IoT
            </button>
        </div>

        <form id="r4-chatbot-form" class="r4-chatbot-form">
            <textarea id="r4-chatbot-input"
                      rows="2"
                      placeholder="Descrivi in breve il tuo progetto o la tua esigenza..."></textarea>

            <button type="submit" id="r4-chatbot-send">Invia</button>
        </form>

        <div class="r4-chatbot-capture">
            <details>
                <summary>Richiedi una consulenza o lascia i tuoi contatti</summary>

                <form id="r4-chatbot-lead-form" class="r4-chatbot-lead-form">
                    <input type="text" id="r4-chatbot-name" placeholder="Nome">
                    <input type="email" id="r4-chatbot-email" placeholder="Email">
                    <input type="text" id="r4-chatbot-phone" placeholder="Telefono">
                    <input type="text" id="r4-chatbot-company" placeholder="Azienda">

                    <textarea id="r4-chatbot-notes"
                              rows="3"
                              placeholder="Descrivi brevemente la richiesta"></textarea>

                    <button type="submit" id="r4-chatbot-capture-btn">
                        Richiedi una consulenza
                    </button>
                </form>
            </details>
        </div>

    </div>
</div>

<link rel="stylesheet" href="{{ asset('vendor/crm/chatbot-widget.css') }}">
<script src="{{ asset('vendor/crm/chatbot-widget.js') }}" defer></script>
