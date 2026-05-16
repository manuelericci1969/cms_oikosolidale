<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\ChatbotConversation;
use App\Modules\Crm\Models\ChatbotFeedback;
use App\Modules\Crm\Models\ChatbotUnknownQuestion;
use App\Services\OpenClawChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Modules\Crm\Models\ChatbotFaq;
use App\Modules\Crm\Models\Product;

class PublicChatbotController extends Controller
{
    protected function clientId(Request $request): int
    {
        return 1;
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'   => 'nullable|string|max:190',
            'source_page'  => 'nullable|string|max:500',
            'channel'      => 'nullable|string|max:50',
            'visitor_name' => 'nullable|string|max:190',
            'email'        => 'nullable|email|max:190',
            'phone'        => 'nullable|string|max:50',
            'company'      => 'nullable|string|max:190',
        ]);

        $clientId  = $this->clientId($request);
        $sessionId = $data['session_id'] ?? $this->generateSessionId();

        $conversation = ChatbotConversation::query()
            ->where('client_id', $clientId)
            ->where('session_id', $sessionId)
            ->first();

        if (!$conversation) {
            $conversation = ChatbotConversation::create([
                'client_id'       => $clientId,
                'session_id'      => $sessionId,
                'channel'         => $data['channel'] ?? 'website',
                'source_page'     => $data['source_page'] ?? $request->headers->get('referer'),
                'visitor_name'    => $data['visitor_name'] ?? null,
                'visitor_email'   => $data['email'] ?? null,
                'visitor_phone'   => $data['phone'] ?? null,
                'visitor_company' => $data['company'] ?? null,
                'status'          => 'open',
                'intent'          => 'generic',
                'score'           => 0,
                'last_message_at' => now(),
                'metadata'        => $this->buildConversationMetadata($request),
            ]);

            $welcomeMessage = $this->welcomeMessage();

            $conversation->messages()->create([
                'direction'    => 'out',
                'sender_type'  => 'ai',
                'message_type' => 'text',
                'message'      => $welcomeMessage,
                'model'        => 'chatbot-welcome',
                'metadata'     => [
                    'event' => 'conversation_started',
                ],
            ]);
        }

        $conversation->load([
            'messages' => function ($q) {
                $q->orderBy('id');
            }
        ]);

        $firstBotMessage = $conversation->messages
            ->where('sender_type', 'ai')
            ->first();

        return response()->json([
            'ok'           => true,
            'session_id'   => $conversation->session_id,
            'conversation' => [
                'id'              => $conversation->id,
                'status'          => $conversation->status,
                'status_label'    => $conversation->status_label,
                'intent'          => $conversation->intent,
                'intent_label'    => $conversation->intent_label,
                'score'           => $conversation->score,
                'visitor_name'    => $conversation->visitor_name,
                'visitor_email'   => $conversation->visitor_email,
                'visitor_phone'   => $conversation->visitor_phone,
                'visitor_company' => $conversation->visitor_company,
            ],
            'welcome_message' => $firstBotMessage?->message ?? $this->welcomeMessage(),
        ]);
    }

    public function message(Request $request, OpenClawChatService $openClaw): JsonResponse
    {
        $data = $request->validate([
            'session_id'  => 'required|string|max:190',
            'message'     => 'required|string|max:5000',
            'source_page' => 'nullable|string|max:500',
        ]);

        $clientId = $this->clientId($request);

        $conversation = ChatbotConversation::query()
            ->where('client_id', $clientId)
            ->where('session_id', $data['session_id'])
            ->first();

        if (!$conversation) {
            return response()->json([
                'ok'      => false,
                'message' => 'Conversazione non trovata.',
            ], 404);
        }

        $userMessage = trim((string) $data['message']);

        if ($userMessage === '') {
            return response()->json([
                'ok'      => false,
                'message' => 'Messaggio vuoto.',
            ], 422);
        }

        $userMessageRecord = $conversation->messages()->create([
            'direction'    => 'in',
            'sender_type'  => 'visitor',
            'message_type' => 'text',
            'message'      => $userMessage,
            'metadata'     => [
                'source_page' => $data['source_page'] ?? null,
            ],
        ]);

        $analysis = $this->analyzeMessage($userMessage, $conversation);

        $directReply = $this->buildDirectReply($userMessage);
        if ($directReply !== null) {
            return $this->storeAndReturnBotReply(
                conversation: $conversation,
                sourcePage: $data['source_page'] ?? null,
                analysis: $analysis,
                message: $directReply,
                aiMode: 'direct-rule',
                model: 'direct-rule'
            );
        }

        try {
            $prompt = $this->buildOpenClawPrompt($conversation, $analysis, $userMessage);

            $contextConversation = $conversation->fresh(['latestMessages']);
            $contextConversation->setAttribute('context_user_message', $userMessage);

            $aiResponse = $openClaw->chat([
                'message'         => $prompt,
                'system_prompt'   => $this->systemPrompt(),
                'context_message' => $this->contextMessage(
                    $contextConversation,
                    $analysis
                ),
                'history'         => $this->buildStructuredHistory($conversation),
                'conversation_id' => (string) $conversation->id,
                'session_id'      => $conversation->session_id,
            ]);
        } catch (\Throwable $e) {
            \Log::error('OpenClaw chatbot exception', [
                'conversation_id' => $conversation->id,
                'session_id'      => $conversation->session_id,
                'error'           => $e->getMessage(),
            ]);

            $aiResponse = [
                'ok'      => false,
                'reply'   => null,
                'message' => $e->getMessage(),
                'raw'     => null,
            ];
        }

        \Log::info('OpenClaw chatbot response', [
            'conversation_id' => $conversation->id,
            'session_id'      => $conversation->session_id,
            'openclaw_ok'     => $aiResponse['ok'] ?? false,
            'openclaw_error'  => $aiResponse['message'] ?? null,
        ]);

        $usedOpenClaw = !empty($aiResponse['ok']) && !empty($aiResponse['reply']);

        $botReply = $usedOpenClaw
            ? trim((string) $aiResponse['reply'])
            : $this->buildFallbackReply(
                message: $userMessage,
                intent: $analysis['intent'],
                score: $analysis['score'],
                conversation: $conversation
            );

        $shouldStoreUnknown = false;

        if (!$usedOpenClaw) {
            $shouldStoreUnknown = true;
        }

        if (($analysis['intent'] ?? 'generic') === 'generic') {
            $shouldStoreUnknown = true;
        }

        if ($shouldStoreUnknown) {
            $this->storeUnknownQuestion(
                clientId: $clientId,
                conversation: $conversation,
                messageRecord: $userMessageRecord,
                question: $userMessage,
                intent: $analysis['intent'],
                sourcePage: $data['source_page'] ?? null
            );
        }

        return $this->storeAndReturnBotReply(
            conversation: $conversation,
            sourcePage: $data['source_page'] ?? null,
            analysis: $analysis,
            message: $botReply,
            aiMode: $usedOpenClaw ? 'openclaw' : 'fallback',
            model: $usedOpenClaw
                ? (string) config('services.openclaw.model', 'openclaw')
                : 'fallback-local',
            providerOk: $aiResponse['ok'] ?? false,
            providerError: $aiResponse['message'] ?? null
        );
    }

    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'  => 'required|string|max:190',
            'message_id'  => 'required|integer',
            'is_helpful'  => 'required|boolean',
            'notes'       => 'nullable|string|max:2000',
        ]);

        $clientId = $this->clientId($request);

        $conversation = ChatbotConversation::query()
            ->where('client_id', $clientId)
            ->where('session_id', $data['session_id'])
            ->first();

        if (!$conversation) {
            return response()->json([
                'ok'      => false,
                'message' => 'Conversazione non trovata.',
            ], 404);
        }

        $message = $conversation->messages()
            ->where('id', $data['message_id'])
            ->where('sender_type', 'ai')
            ->first();

        if (!$message) {
            return response()->json([
                'ok'      => false,
                'message' => 'Messaggio non trovato.',
            ], 404);
        }

        ChatbotFeedback::updateOrCreate(
            [
                'client_id'       => $clientId,
                'conversation_id' => $conversation->id,
                'message_id'      => $message->id,
            ],
            [
                'is_helpful' => (bool) $data['is_helpful'],
                'notes'      => trim((string) ($data['notes'] ?? '')) ?: null,
            ]
        );

        return response()->json([
            'ok'      => true,
            'message' => 'Feedback registrato correttamente.',
        ]);
    }

    public function captureLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'    => 'required|string|max:190',
            'visitor_name'  => 'nullable|string|max:190',
            'email'         => 'nullable|email|max:190',
            'phone'         => 'nullable|string|max:50',
            'company'       => 'nullable|string|max:190',
            'notes'         => 'nullable|string|max:5000',
        ]);

        $clientId = $this->clientId($request);

        $conversation = ChatbotConversation::query()
            ->where('client_id', $clientId)
            ->where('session_id', $data['session_id'])
            ->first();

        if (!$conversation) {
            return response()->json([
                'ok'      => false,
                'message' => 'Conversazione non trovata.',
            ], 404);
        }

        $conversation->update([
            'visitor_name'    => $data['visitor_name'] ?? $conversation->visitor_name,
            'visitor_email'   => $data['email'] ?? $conversation->visitor_email,
            'visitor_phone'   => $data['phone'] ?? $conversation->visitor_phone,
            'visitor_company' => $data['company'] ?? $conversation->visitor_company,
            'intent'          => $conversation->intent === 'generic' ? 'contact' : $conversation->intent,
            'notes'           => $this->mergeNotes($conversation->notes, $data['notes'] ?? null),
            'score'           => max((int) $conversation->score, 80),
            'status'          => in_array($conversation->status, ['converted', 'spam'], true)
                ? $conversation->status
                : 'qualified',
            'last_message_at' => now(),
        ]);

        $leadCaptureSummary = $this->buildLeadCaptureSummary($data);

        if ($leadCaptureSummary !== null) {
            $conversation->messages()->create([
                'direction'    => 'system',
                'sender_type'  => 'system',
                'message_type' => 'form_request',
                'message'      => $leadCaptureSummary,
                'metadata'     => [
                    'event' => 'lead_capture',
                ],
            ]);
        }

        return response()->json([
            'ok' => true,
            'conversation' => [
                'id'              => $conversation->id,
                'status'          => $conversation->status,
                'status_label'    => $conversation->status_label,
                'score'           => $conversation->score,
                'visitor_name'    => $conversation->visitor_name,
                'visitor_email'   => $conversation->visitor_email,
                'visitor_phone'   => $conversation->visitor_phone,
                'visitor_company' => $conversation->visitor_company,
            ],
            'message' => 'Dati acquisiti correttamente.',
        ]);
    }

    protected function storeAndReturnBotReply(
        ChatbotConversation $conversation,
        ?string $sourcePage,
        array $analysis,
        string $message,
        string $aiMode,
        string $model,
        ?bool $providerOk = null,
        ?string $providerError = null
    ): JsonResponse {
        $botMessage = $conversation->messages()->create([
            'direction'          => 'out',
            'sender_type'        => 'ai',
            'message_type'       => 'text',
            'message'            => $message,
            'model'              => $model,
            'token_usage_input'  => null,
            'token_usage_output' => null,
            'metadata'           => [
                'intent_detected' => $analysis['intent'],
                'score_detected'  => $analysis['score'],
                'provider_ok'     => $providerOk,
                'provider_error'  => $providerError,
                'ai_mode'         => $aiMode,
            ],
        ]);

        $newScore  = max((int) $conversation->score, (int) $analysis['score']);
        $newStatus = $conversation->status;

        if (!in_array($conversation->status, ['converted', 'spam'], true)) {
            if ($analysis['intent'] === 'contact' || $analysis['intent'] === 'appointment') {
                $newStatus = 'qualified';
            } else {
                $newStatus = $newScore >= 60 ? 'qualified' : 'open';
            }
        }

        $conversation->update([
            'source_page'     => $sourcePage ?? $conversation->source_page,
            'intent'          => ($analysis['intent'] ?? 'generic') !== 'generic'
                ? $analysis['intent']
                : $conversation->intent,
            'score'           => $newScore,
            'status'          => $newStatus,
            'last_message_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'reply' => [
                'message'        => $message,
                'message_id'     => $botMessage->id,
                'intent'         => $analysis['intent'],
                'score'          => $newScore,
                'status'         => $newStatus,
                'ai_mode'        => $aiMode,
                'deepseek_ok'    => $providerOk,
                'deepseek_error' => $providerError,
            ],
        ]);
    }

    protected function storeUnknownQuestion(
        int $clientId,
        ChatbotConversation $conversation,
        $messageRecord,
        string $question,
        string $intent,
        ?string $sourcePage = null
    ): void {
        $question = trim($question);

        if ($question === '') {
            return;
        }

        $alreadyExists = ChatbotUnknownQuestion::query()
            ->where('client_id', $clientId)
            ->where('conversation_id', $conversation->id)
            ->where('question', $question)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        ChatbotUnknownQuestion::create([
            'client_id'       => $clientId,
            'conversation_id' => $conversation->id,
            'message_id'      => $messageRecord?->id,
            'question'        => $question,
            'intent_detected' => $intent,
            'source_page'     => $sourcePage,
            'status'          => 'new',
        ]);
    }

    protected function generateSessionId(): string
    {
        return 'chat_' . Str::uuid()->toString();
    }

    protected function welcomeMessage(): string
    {
        return "Ciao! Sono l’assistente virtuale di R4Software.\nPosso aiutarti a capire la soluzione più adatta per sito web, CRM, app mobile, IoT, automazione e marketing.\nSe vuoi, descrivimi in breve il tuo progetto oppure scegli uno dei pulsanti qui sotto.";
    }

    protected function systemPrompt(): string
    {
        return "Sei l’assistente virtuale commerciale di R4Software. Rispondi sempre in italiano, in modo naturale, chiaro, utile e sintetico. Devi rispondere prima di tutto all’ultimo messaggio dell’utente. Non inventare prezzi, disponibilità, funzionalità, tempistiche o informazioni non confermate. Usa solo dati reali forniti dal gateway OpenClaw, dalle API interne e dal contesto ricevuto. Non fare premesse tecniche, non parlare del tuo stato interno, non dire quali strumenti stai usando. Se la richiesta è chiara, rispondi subito. Se mancano dettagli essenziali, fai una sola domanda utile. Quando opportuno, accompagna l’utente verso il contatto o la richiesta di appuntamento, senza essere insistente.";
    }

    protected function contextMessage(ChatbotConversation $conversation, array $analysis): string
    {
        $intent = (string) ($analysis['intent'] ?? 'generic');

        $base = [
            "Informazioni commerciali R4Software:",
            "- R4Software si occupa di siti web professionali, CRM e gestionali su misura, app mobile, soluzioni IoT, automazione e marketing digitale.",
            "- Non inventare prezzi o funzionalità non confermate.",
            "- Se l'utente chiede costi, dai solo indicazioni prudenti e invita a specificare meglio il progetto.",
            "- Se l'utente mostra interesse concreto, puoi accompagnarlo verso contatto o appuntamento in modo naturale.",
        ];

        $intentHints = match ($intent) {
            'website' => [
                "- Area di interesse rilevata: siti web, restyling, landing page, e-commerce, performance e conversione contatti.",
            ],
            'crm' => [
                "- Area di interesse rilevata: CRM e gestionali su misura per lead, clienti, preventivi, pipeline, task e processi aziendali.",
            ],
            'app' => [
                "- Area di interesse rilevata: app mobile iOS/Android, aree riservate, integrazioni con CRM o sistemi interni.",
            ],
            'iot' => [
                "- Area di interesse rilevata: IoT, monitoraggio, sensori, automazione e integrazione hardware/software.",
            ],
            'marketing' => [
                "- Area di interesse rilevata: SEO, social media, campagne digitali, visibilità online e lead generation.",
            ],
            'appointment' => [
                "- Se l'utente vuole un appuntamento, sii pratico e guidalo verso giorno, contatti o richiesta di ricontatto.",
            ],
            'contact' => [
                "- Se l'utente chiede contatti o vuole essere richiamato, rispondi in modo diretto e professionale.",
            ],
            default => [
                "- Se la richiesta è generica, aiuta l'utente a chiarire il tipo di progetto o l'obiettivo.",
            ],
        };

        $contextUserMessage = (string) ($conversation->getAttribute('context_user_message') ?? '');

        $faqItems = $this->findRelevantFaqs(
            message: $contextUserMessage,
            intent: $analysis['intent'] ?? 'generic',
            limit: 3
        );

        if (!empty($faqItems)) {
            $base[] = "- FAQ rilevanti trovate:";
            foreach ($faqItems as $faq) {
                $base[] = "  Domanda: " . $faq['question'];
                $base[] = "  Risposta: " . $faq['answer'];
            }
        }

        $productItems = $this->findRelevantProducts(
            message: $contextUserMessage,
            limit: 3
        );

        if (!empty($productItems)) {
            $base[] = "- Prodotti o servizi rilevanti trovati:";
            foreach ($productItems as $product) {
                $line = "  Nome: " . $product['name'];

                if (!empty($product['price'])) {
                    $line .= " | Prezzo: " . $product['price'];
                }

                if (!empty($product['sku'])) {
                    $line .= " | SKU: " . $product['sku'];
                }

                $base[] = $line;

                if (!empty($product['description'])) {
                    $base[] = "  Descrizione: " . $product['description'];
                }

                if (!empty($product['website_url'])) {
                    $base[] = "  URL: " . $product['website_url'];
                }
            }
        }

        return implode("\n", array_merge($base, $intentHints));
    }

    protected function buildStructuredHistory(ChatbotConversation $conversation, int $limit = 8): array
    {
        $messages = $conversation->messages()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $history = [];

        foreach ($messages as $msg) {
            $content = trim((string) $msg->message);

            if ($content === '') {
                continue;
            }

            $role = match ($msg->sender_type) {
                'ai'      => 'assistant',
                'visitor' => 'user',
                'agent'   => 'assistant',
                'system'  => 'system',
                default   => null,
            };

            if (!$role) {
                continue;
            }

            $history[] = [
                'role'    => $role,
                'content' => $content,
            ];
        }

        return $history;
    }

    protected function findRelevantFaqs(string $message, ?string $intent = null, int $limit = 3): array
    {
        $clientId = 1;
        $text = mb_strtolower(trim($message));

        $tokens = array_values(array_filter(
            preg_split('/\s+/', $text) ?: [],
            fn ($token) => mb_strlen(trim($token)) >= 3
        ));

        $query = ChatbotFaq::query()
            ->where('client_id', $clientId)
            ->where('is_active', true);

        if (!empty($intent) && $intent !== 'generic') {
            $query->where(function ($q) use ($intent) {
                $q->where('intent', $intent)
                    ->orWhereNull('intent')
                    ->orWhere('intent', '');
            });
        }

        $items = $query
            ->orderBy('priority')
            ->orderByDesc('id')
            ->get()
            ->filter(function ($faq) use ($text, $tokens) {
                $haystack = mb_strtolower(
                    trim(
                        ($faq->question_pattern ?? '') . ' ' .
                        ($faq->keywords ?? '') . ' ' .
                        ($faq->answer ?? '')
                    )
                );

                if ($haystack === '') {
                    return false;
                }

                if ($text !== '' && str_contains($haystack, $text)) {
                    return true;
                }

                foreach ($tokens as $token) {
                    if (str_contains($haystack, $token)) {
                        return true;
                    }
                }

                return false;
            })
            ->take($limit)
            ->values();

        return $items->map(function ($faq) {
            return [
                'question' => (string) $faq->question_pattern,
                'answer'   => trim((string) $faq->answer),
                'intent'   => $faq->intent,
            ];
        })->all();
    }

    protected function findRelevantProducts(string $message, int $limit = 3): array
    {
        $clientId = 1;
        $text = mb_strtolower(trim($message));

        $tokens = array_values(array_filter(
            preg_split('/\s+/', $text) ?: [],
            fn ($token) => mb_strlen(trim($token)) >= 3
        ));

        $items = Product::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function ($product) use ($text, $tokens) {
                $haystack = mb_strtolower(
                    trim(
                        ($product->name ?? '') . ' ' .
                        ($product->sku ?? '') . ' ' .
                        ($product->description ?? '')
                    )
                );

                if ($haystack === '') {
                    return false;
                }

                if ($text !== '' && str_contains($haystack, $text)) {
                    return true;
                }

                foreach ($tokens as $token) {
                    if (str_contains($haystack, $token)) {
                        return true;
                    }
                }

                return false;
            })
            ->take($limit)
            ->values();

        return $items->map(function ($product) {
            return [
                'name'        => (string) $product->name,
                'sku'         => (string) $product->sku,
                'price'       => $product->price,
                'description' => trim((string) $product->description),
                'website_url' => (string) $product->website_url,
            ];
        })->all();
    }

    protected function buildOpenClawPrompt(ChatbotConversation $conversation, array $analysis, string $userMessage): string
    {
        $history = $conversation->messages()
            ->latest('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $historyLines = [];

        foreach ($history as $msg) {
            $role = match ($msg->sender_type) {
                'visitor' => 'Utente',
                'ai'      => 'Assistente',
                'agent'   => 'Operatore',
                'system'  => 'Sistema',
                default   => 'Messaggio',
            };

            $historyLines[] = $role . ': ' . trim((string) $msg->message);
        }

        return implode("\n", [
            "Contesto conversazione chatbot R4Software.",
            "Rispondi in italiano.",
            "Sei un assistente commerciale chiaro, naturale e professionale.",
            "Devi rispondere prima di tutto all’ultimo messaggio dell’utente.",
            "Segui il nuovo argomento dell'utente se cambia tema.",
            "Se la richiesta riguarda prodotti, FAQ, prezzi o appuntamenti, usa solo i dati reali che il gateway ha a disposizione.",
            "Non inventare prezzi, disponibilità, tempi o funzionalità.",
            "Se la richiesta è chiara, rispondi subito.",
            "Se mancano dettagli essenziali, fai una sola domanda utile.",
            "Se l’utente mostra interesse concreto, puoi accompagnarlo verso contatto o appuntamento in modo naturale.",
            "",
            "Intento rilevato localmente: " . ($analysis['intent'] ?? 'generic'),
            "Score locale: " . ($analysis['score'] ?? 0),
            "",
            "Cronologia recente:",
            !empty($historyLines) ? implode("\n", $historyLines) : 'Nessuna cronologia disponibile.',
            "",
            "ULTIMO MESSAGGIO UTENTE:",
            trim($userMessage),
        ]);
    }

    protected function buildConversationMetadata(Request $request): array
    {
        return [
            'ip'         => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'referer'    => $request->headers->get('referer'),
            'url'        => $request->fullUrl(),
        ];
    }

    protected function analyzeMessage(string $message, ChatbotConversation $conversation): array
    {
        $text = mb_strtolower(trim($message));

        $intent = 'generic';
        $score  = max(10, (int) $conversation->score);

        if (
            str_contains($text, 'come ti chiami') ||
            str_contains($text, 'chi sei')
        ) {
            return [
                'intent' => 'identity',
                'score'  => min(max($score, 20), 100),
            ];
        }

        if (
            str_contains($text, 'come stai') ||
            str_contains($text, 'come va')
        ) {
            return [
                'intent' => 'greeting',
                'score'  => min(max($score, 15), 100),
            ];
        }

        if (
            in_array($text, ['ciao', 'salve', 'buongiorno', 'buonasera'], true) ||
            str_starts_with($text, 'ciao ')
        ) {
            return [
                'intent' => 'greeting',
                'score'  => min(max($score, 15), 100),
            ];
        }

        if (
            str_contains($text, 'appuntamento') ||
            str_contains($text, 'disponibilità') ||
            str_contains($text, 'disponibilita') ||
            str_contains($text, 'prenotare') ||
            str_contains($text, 'prenota') ||
            str_contains($text, 'call') ||
            str_contains($text, 'demo')
        ) {
            return [
                'intent' => 'appointment',
                'score'  => min(max($score, 85), 100),
            ];
        }

        if (
            str_contains($text, 'contatto') ||
            str_contains($text, 'contatti') ||
            str_contains($text, 'telefono') ||
            str_contains($text, 'numero') ||
            str_contains($text, 'email') ||
            str_contains($text, 'richiamatemi') ||
            str_contains($text, 'richiamami') ||
            str_contains($text, 'come vi contatto') ||
            str_contains($text, 'come posso mettermi in contatto')
        ) {
            return [
                'intent' => 'contact',
                'score'  => min(max($score, 80), 100),
            ];
        }

        if ($this->isPriceRequest($text)) {
            $intent = 'pricing';
            $score  = min(max($score, 75), 100);
        }

        $map = [
            'website' => [
                'keywords' => [
                    'sito',
                    'sito web',
                    'sito vetrina',
                    'landing page',
                    'restyling',
                    'rifare il sito',
                    'nuovo sito',
                    'wordpress',
                    'ecommerce',
                    'e-commerce',
                    'shop online',
                    'prenotazioni online'
                ],
                'score'    => 40,
            ],
            'crm' => [
                'keywords' => [
                    'crm',
                    'gestionale',
                    'erp',
                    'anagrafica clienti',
                    'clienti',
                    'lead',
                    'preventivi',
                    'pipeline',
                    'offerte',
                    'agenti',
                    'opportunità',
                    'opportunita',
                    'automazione commerciale',
                    'gestione commerciale'
                ],
                'score'    => 60,
            ],
            'app' => [
                'keywords' => [
                    'app',
                    'app mobile',
                    'applicazione',
                    'android',
                    'ios',
                    'flutter',
                    'app clienti',
                    'app prenotazioni',
                    'app interna',
                    'area riservata'
                ],
                'score'    => 55,
            ],
            'iot' => [
                'keywords' => ['iot', 'sensore', 'monitoraggio', 'flussimetro', 'hmfluxus', 'ble', 'automazione', 'device', 'chiave ble', 'hmobile', 'fluxus'],
                'score'    => 60,
            ],
            'marketing' => [
                'keywords' => [
                    'marketing',
                    'seo',
                    'social',
                    'social media',
                    'linkedin',
                    'instagram',
                    'facebook',
                    'campagna',
                    'campagne',
                    'google ads',
                    'advertising',
                    'lead generation',
                    'visibilità online',
                    'visibilita online',
                    'posizionamento'
                ],
                'score'    => 45,
            ],
        ];

        if ($intent !== 'pricing') {
            foreach ($map as $candidateIntent => $config) {
                foreach ($config['keywords'] as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $intent = $candidateIntent;
                        $score  = max($score, $config['score']);
                    }
                }
            }
        }

        if (in_array($text, ['crm', 'gestionale', 'erp'], true)) {
            return [
                'intent' => 'crm',
                'score'  => min(max($score, 60), 100),
            ];
        }

        if (in_array($text, ['sito', 'sito web', 'ecommerce', 'e-commerce'], true)) {
            return [
                'intent' => 'website',
                'score'  => min(max($score, 50), 100),
            ];
        }

        if (in_array($text, ['seo', 'marketing', 'social'], true)) {
            return [
                'intent' => 'marketing',
                'score'  => min(max($score, 50), 100),
            ];
        }

        if (in_array($text, ['iot', 'automazione', 'sensori'], true)) {
            return [
                'intent' => 'iot',
                'score'  => min(max($score, 60), 100),
            ];
        }

        foreach ([
                     'consulenza',
                     'contatto',
                     'preventivo',
                     'richiamatemi',
                     'richiamami',
                     'vorrei informazioni',
                     'mi interessa',
                     'parlare con voi'
                 ] as $signal) {
            if (str_contains($text, $signal)) {
                $score = max($score, 80);
            }
        }

        if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
            return [
                'intent' => $conversation->intent !== 'generic' ? $conversation->intent : 'contact',
                'score'  => min(max($score, 90), 100),
            ];
        }

        if (
            $intent === 'pricing' &&
            in_array($conversation->intent, ['website', 'crm', 'app', 'iot', 'marketing'], true)
        ) {
            $intent = $conversation->intent;
        }

        return [
            'intent' => $intent,
            'score'  => min($score, 100),
        ];
    }

    protected function buildFallbackReply(
        string $message,
        string $intent,
        int $score,
        ChatbotConversation $conversation
    ): string {
        return match ($intent) {
            'contact' => "Puoi contattarci direttamente a info@r4software.it oppure al numero +39 328 0439803. Se preferisci, lasciami anche i tuoi dati e la tua richiesta: il team può ricontattarti.",
            'identity' => "Ciao! Sono l’assistente virtuale di R4Software e posso aiutarti su siti web, CRM, app, IoT e consulenza digitale.",
            'greeting' => "Ciao! Sono l’assistente virtuale di R4Software. Dimmi pure di cosa hai bisogno e ti aiuto volentieri.",
            'appointment' => "Certo. Possiamo verificare la disponibilità per un appuntamento dal lunedì al giovedì tra le 10:00 e le 12:30. Se vuoi, indicami il giorno che preferisci oppure lasciami i tuoi contatti e la tua richiesta.",
            'pricing' => "Posso aiutarti a capire il range più adatto, ma il costo dipende dal tipo di progetto, dalle funzionalità richieste e dalle eventuali integrazioni. Se mi dici se ti serve un sito web, un CRM, un’app o una soluzione IoT, ti do un’indicazione più precisa.",
            'website' => "Possiamo realizzare siti web professionali, restyling, landing page, e-commerce e soluzioni orientate a performance e conversione contatti. Se vuoi, dimmi se parti da zero o se hai già un sito da migliorare.",
            'crm' => "Possiamo sviluppare o personalizzare CRM e gestionali su misura per lead, clienti, preventivi, task, pipeline commerciali e processi aziendali. Se vuoi, dimmi oggi come gestite contatti, offerte e attività, così posso orientarti meglio.",
            'app' => "Possiamo realizzare app mobile personalizzate per iOS e Android, anche integrate con CRM, gestionali, aree riservate o sistemi interni. Se vuoi, raccontami che tipo di app hai in mente e a chi è destinata.",
            'iot' => "Possiamo supportarti su progetti IoT, automazione, monitoraggio, sensori e integrazione tra hardware e software. Se vuoi, descrivimi il caso d’uso o il problema che vuoi risolvere.",
            'marketing' => "Possiamo aiutarti su SEO, social media, campagne digitali, automazioni e strategie orientate alla visibilità e alla generazione di contatti. Se vuoi, spiegami il tuo obiettivo principale o il canale su cui vuoi migliorare.",
            default => "Posso aiutarti a capire quale soluzione digitale è più adatta alla tua attività. Se vuoi, descrivimi in breve il tuo obiettivo oppure il tipo di progetto che hai in mente.",
        };
    }

    protected function buildLeadCaptureSummary(array $data): ?string
    {
        $lines = [];

        if (!empty($data['visitor_name'])) {
            $lines[] = 'Nome: ' . $data['visitor_name'];
        }
        if (!empty($data['email'])) {
            $lines[] = 'Email: ' . $data['email'];
        }
        if (!empty($data['phone'])) {
            $lines[] = 'Telefono: ' . $data['phone'];
        }
        if (!empty($data['company'])) {
            $lines[] = 'Azienda: ' . $data['company'];
        }
        if (!empty($data['notes'])) {
            $lines[] = 'Note: ' . $data['notes'];
        }

        if (empty($lines)) {
            return null;
        }

        array_unshift($lines, 'Nuovo contatto acquisito dal widget chatbot:');

        return implode("\n", $lines);
    }

    protected function buildDirectReply(string $message): ?string
    {
        $text = mb_strtolower(trim($message));

        if (
            str_contains($text, 'contatto') ||
            str_contains($text, 'contatti') ||
            str_contains($text, 'telefono') ||
            str_contains($text, 'numero') ||
            str_contains($text, 'email') ||
            str_contains($text, 'richiamatemi') ||
            str_contains($text, 'richiamami') ||
            str_contains($text, 'come posso mettermi in contatto') ||
            str_contains($text, 'come vi contatto')
        ) {
            return "Puoi contattarci direttamente a info@r4software.it oppure al numero +39 328 0439803. Se preferisci, puoi anche lasciarmi i tuoi dati e la tua richiesta: il team R4Software può ricontattarti.";
        }

        if (
            str_contains($text, 'come stai') ||
            str_contains($text, 'come va')
        ) {
            return "Sto bene, grazie! Dimmi pure di cosa hai bisogno e ti aiuto volentieri.";
        }

        if (
            str_contains($text, 'come ti chiami') ||
            str_contains($text, 'chi sei') ||
            str_contains($text, 'cosa fai') ||
            str_contains($text, 'di cosa vi occupate')
        ) {
            return "Ciao! Sono l’assistente virtuale di R4Software. Posso aiutarti su siti web, CRM e gestionali, app mobile, IoT, automazione, marketing e consulenza digitale.";
        }

        if (
            in_array($text, ['ciao', 'salve', 'buongiorno', 'buonasera', 'hey', 'ehi'], true) ||
            str_starts_with($text, 'ciao ') ||
            str_starts_with($text, 'salve ')
        ) {
            return "Ciao! Sono l’assistente virtuale di R4Software. Posso aiutarti su siti web, CRM, app, IoT, marketing e consulenza digitale.";
        }

        if (
            $text === 'grazie' ||
            $text === 'ti ringrazio' ||
            str_contains($text, 'grazie mille') ||
            str_contains($text, 'perfetto grazie')
        ) {
            return "Con piacere! Se vuoi, puoi spiegarmi in breve la tua esigenza e ti aiuto a capire la soluzione più adatta.";
        }

        return null;
    }

    protected function mergeNotes(?string $existingNotes, ?string $newNotes): ?string
    {
        $existingNotes = trim((string) $existingNotes);
        $newNotes      = trim((string) $newNotes);

        if ($existingNotes === '' && $newNotes === '') {
            return null;
        }
        if ($existingNotes === '') {
            return $newNotes;
        }
        if ($newNotes === '') {
            return $existingNotes;
        }

        return $existingNotes . "\n\n" . $newNotes;
    }

    protected function isPriceRequest(string $message): bool
    {
        $text = mb_strtolower(trim($message));

        foreach ([
                     'quanto costa',
                     'quanto potrebbe costare',
                     'quanto mi costa',
                     'quanto verrebbe',
                     'quanto viene',
                     'prezzo',
                     'prezzi',
                     'costo',
                     'costi',
                     'range',
                     'fascia di prezzo',
                     'preventivo',
                     'quotazione',
                     'stima',
                     'budget',
                     'ora di sviluppo',
                     'ora sviluppo',
                     'costo orario',
                     'tariffa oraria',
                 ] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
