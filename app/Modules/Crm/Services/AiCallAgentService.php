<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallCampaign;
use App\Modules\Crm\Models\CallLog;
use App\Modules\Crm\Models\CallQueue;
use App\Services\OpenClawCallAgentService;
use Illuminate\Support\Facades\Log;

class AiCallAgentService
{
    public function __construct(
        protected OpenClawCallAgentService $openClaw,
        protected CallConversationService $conversationService,
        protected CallBusinessOutcomeService $businessOutcomeService
    ) {
    }

    public function replyForCall(
        CallCampaign $campaign,
        CallQueue $queueItem,
        CallLog $callLog,
        string $userText
    ): array {
        return $this->runAgentFlow(
            campaign: $campaign,
            queueItem: $queueItem,
            callLog: $callLog,
            userText: $userText,
            source: 'call_input'
        );
    }

    public function processTranscriptForCall(
        CallCampaign $campaign,
        CallQueue $queueItem,
        CallLog $callLog,
        string $transcriptText
    ): array {
        $transcriptText = trim($transcriptText);

        if ($transcriptText === '') {
            return [
                'ok' => false,
                'reply' => null,
                'suggested_outcome' => null,
                'message' => 'Transcript vuoto.',
                'raw' => null,
                'ai_mode' => $this->agentMode(),
            ];
        }

        if ($this->looksLikeVoicemailTranscript($transcriptText)) {
            $this->markVoicemailTranscript($callLog, $transcriptText);

            return [
                'ok' => true,
                'reply' => null,
                'suggested_outcome' => 'voicemail_detected',
                'message' => 'Transcript classificato come segreteria telefonica.',
                'raw' => null,
                'ai_mode' => $this->agentMode(),
            ];
        }

        if (
            !empty($callLog->business_outcome) ||
            in_array($queueItem->status, [
                CallQueue::STATUS_COMPLETED,
                CallQueue::STATUS_CALLBACK,
                CallQueue::STATUS_FAILED,
                CallQueue::STATUS_CANCELLED,
            ], true)
        ) {
            return [
                'ok' => false,
                'reply' => null,
                'suggested_outcome' => null,
                'message' => 'Call log già chiuso o queue item già finalizzato.',
                'raw' => null,
                'ai_mode' => $this->agentMode(),
            ];
        }

        return $this->runAgentFlow(
            campaign: $campaign,
            queueItem: $queueItem,
            callLog: $callLog,
            userText: $transcriptText,
            source: 'whisper_transcript'
        );
    }

    protected function runAgentFlow(
        CallCampaign $campaign,
        CallQueue $queueItem,
        CallLog $callLog,
        string $userText,
        string $source
    ): array {
        $userText = trim($userText);

        if ($userText === '') {
            return [
                'ok' => false,
                'reply' => null,
                'suggested_outcome' => null,
                'message' => 'Testo utente vuoto.',
                'raw' => null,
                'ai_mode' => $this->agentMode(),
            ];
        }

        if (!$this->isEnabled()) {
            return [
                'ok' => false,
                'reply' => null,
                'suggested_outcome' => null,
                'message' => 'AI Call Agent disabilitato da configurazione.',
                'raw' => null,
                'ai_mode' => $this->agentMode(),
            ];
        }

        $this->conversationService->addUserMessage($callLog, $userText, [
            'source' => $source,
            'ai_mode' => $this->agentMode(),
        ]);

        $systemPrompt = $this->systemPrompt($campaign);
        $contextMessage = $this->contextMessage($campaign, $queueItem, $callLog);
        $history = $this->conversationService->buildHistory($callLog);

        $response = $this->openClaw->chat([
            'system_prompt' => $systemPrompt,
            'context_message' => $contextMessage,
            'history' => $history,
            'message' => $this->buildUserMessage($userText),
            'conversation_id' => 'call_log_' . $callLog->id,
            'session_id' => 'call_' . $callLog->id,
        ]);

        $reply = trim((string) ($response['reply'] ?? ''));

        if ($reply !== '') {
            $this->conversationService->addAssistantMessage($callLog, $reply, [
                'source' => 'openclaw_call_agent',
                'ok' => (bool) ($response['ok'] ?? false),
                'ai_mode' => $this->agentMode(),
            ]);
        }

        $suggestedOutcome = $this->detectSuggestedOutcome($reply, $userText);

        if (($response['ok'] ?? false) && $suggestedOutcome !== 'continue') {
            if ($this->mustBlockBusinessOutcome($callLog)) {
                Log::info('AI Call Agent outcome bloccato da esito tecnico', [
                    'campaign_id' => $campaign->id,
                    'queue_id' => $queueItem->id,
                    'call_log_id' => $callLog->id,
                    'technical_outcome' => $callLog->technical_outcome,
                    'call_status' => $callLog->call_status,
                    'suggested_outcome' => $suggestedOutcome,
                    'user_text' => $userText,
                    'reply' => $reply,
                    'ai_mode' => $this->agentMode(),
                ]);
            } elseif ($this->isLiveMode()) {
                $this->businessOutcomeService->applySuggestedOutcome(
                    callLog: $callLog->fresh(),
                    suggestedOutcome: $suggestedOutcome,
                    note: $this->buildOutcomeNote($suggestedOutcome, $reply, $userText),
                    callbackAt: $suggestedOutcome === 'callback_requested'
                        ? now()
                            ->addDay()
                            ->setTime(
                                $this->defaultCallbackHour(),
                                $this->defaultCallbackMinute()
                            )
                            ->toDateTimeString()
                        : null
                );
            } else {
                Log::info('AI Call Agent shadow outcome', [
                    'campaign_id' => $campaign->id,
                    'queue_id' => $queueItem->id,
                    'call_log_id' => $callLog->id,
                    'technical_outcome' => $callLog->technical_outcome,
                    'call_status' => $callLog->call_status,
                    'suggested_outcome' => $suggestedOutcome,
                    'user_text' => $userText,
                    'reply' => $reply,
                    'ai_mode' => $this->agentMode(),
                ]);
            }
        }

        return [
            'ok' => (bool) ($response['ok'] ?? false),
            'reply' => $reply !== '' ? $reply : null,
            'suggested_outcome' => $suggestedOutcome,
            'message' => $response['message'] ?? null,
            'raw' => $response['raw'] ?? null,
            'ai_mode' => $this->agentMode(),
        ];
    }

    protected function looksLikeVoicemailTranscript(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        $patterns = [
            'dopo il segnale',
            'dopo il segnale acustico',
            'registra il tuo messaggio',
            'registra tu un messaggio',
            'lascia un messaggio',
            'segreteria telefonica',
            'ha raggiunto la lunghezza massima',
            'lunghezza massima disponibile',
            'non è al momento raggiungibile',
        ];

        $hits = 0;

        foreach ($patterns as $pattern) {
            if (str_contains($normalized, $pattern)) {
                $hits++;
            }
        }

        return $hits >= 2;
    }

    protected function markVoicemailTranscript(CallLog $callLog, string $transcriptText): void
    {
        $metadata = is_array($callLog->metadata) ? $callLog->metadata : [];

        $callLog->update([
            'technical_outcome' => $callLog->technical_outcome ?: CallLog::TECH_VOICEMAIL,
            'operator_note' => 'Transcript rilevato come segreteria telefonica.',
            'transcript' => $transcriptText,
            'metadata' => array_merge($metadata, [
                'transcript_detected_kind' => 'voicemail',
                'voicemail_detected' => true,
                'voicemail_transcript_excerpt' => mb_substr(trim($transcriptText), 0, 1000),
                'voicemail_detected_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    protected function isEnabled(): bool
    {
        return (bool) config('services.ai_call_agent.enabled', false);
    }

    protected function agentMode(): string
    {
        return (string) config('services.ai_call_agent.mode', 'disabled');
    }

    protected function isLiveMode(): bool
    {
        return $this->agentMode() === 'live';
    }

    protected function defaultCallbackHour(): int
    {
        return (int) config('services.ai_call_agent.default_callback_hour', 10);
    }

    protected function defaultCallbackMinute(): int
    {
        return (int) config('services.ai_call_agent.default_callback_minute', 0);
    }

    protected function mustBlockBusinessOutcome(CallLog $callLog): bool
    {
        if (in_array($callLog->technical_outcome, [
            CallLog::TECH_VOICEMAIL,
            CallLog::TECH_FAILED,
            CallLog::TECH_INVALID_NUMBER,
            CallLog::TECH_REJECTED,
            CallLog::TECH_CANCELLED,
            CallLog::TECH_ERROR,
        ], true)) {
            return true;
        }

        if (in_array($callLog->call_status, [
            CallLog::CALL_STATUS_FAILED,
            CallLog::CALL_STATUS_CANCELLED,
            CallLog::CALL_STATUS_BUSY,
            CallLog::CALL_STATUS_NO_ANSWER,
        ], true)) {
            return true;
        }

        return false;
    }

    protected function systemPrompt(CallCampaign $campaign): string
    {
        $campaignPrompt = trim((string) ($campaign->script_prompt ?? ''));

        $base = [
            "Sei l'assistente telefonico AI di R4Software.",
            "Parli sempre in italiano.",
            "Il tuo tono deve essere naturale, professionale, sintetico e cortese.",
            "Stai facendo una chiamata outbound commerciale.",
            "Non fare monologhi lunghi.",
            "Fai una domanda per volta.",
            "Non inventare prezzi, funzionalità, tempi o promesse non confermate.",
            "Se l'interlocutore non è interessato, chiudi con educazione.",
            "Se chiede di essere richiamato, proponi di indicare giorno o fascia oraria.",
            "Se mostra interesse concreto, accompagna verso un approfondimento commerciale.",
            "Se capisci che sta parlando una segreteria o che non c'è interazione reale, non insistere.",
            "Le tue risposte devono essere brevi e adatte a una conversazione telefonica reale.",
        ];

        if ($campaignPrompt !== '') {
            $base[] = '';
            $base[] = 'Indicazioni specifiche della campagna:';
            $base[] = $campaignPrompt;
        }

        return implode("\n", $base);
    }

    protected function contextMessage(
        CallCampaign $campaign,
        CallQueue $queueItem,
        CallLog $callLog
    ): string {
        $payload = is_array($queueItem->payload) ? $queueItem->payload : [];

        $lines = [
            'Contesto chiamata:',
            '- Campagna ID: ' . $campaign->id,
            '- Nome campagna: ' . ($campaign->name ?? 'N/D'),
            '- Provider: ' . ($campaign->provider ?? 'N/D'),
            '- Call log ID: ' . $callLog->id,
            '- Queue ID: ' . $queueItem->id,
            '- Contatto: ' . ($queueItem->contact_name ?? 'N/D'),
            '- Telefono: ' . ($queueItem->phone ?? 'N/D'),
            '- Email: ' . ($queueItem->email ?? 'N/D'),
            '- Origine: ' . ($queueItem->source_type ?? 'N/D'),
        ];

        $optional = [
            'city' => 'Città',
            'province' => 'Provincia',
            'region' => 'Regione',
            'country' => 'Paese',
            'business_type' => 'Tipo attività',
            'contact_role' => 'Ruolo contatto',
            'segment' => 'Segmento',
            'commercial_potential' => 'Potenziale commerciale',
            'site_rating' => 'Valutazione sito',
            'seo_score' => 'SEO score',
            'notes' => 'Note',
        ];

        foreach ($optional as $key => $label) {
            $value = $payload[$key] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                $lines[] = "- {$label}: " . trim((string) $value);
            }
        }

        $lines[] = '';
        $lines[] = 'Obiettivo:';
        $lines[] = '- capire se l\'interlocutore è interessato';
        $lines[] = '- se non è interessato, chiudere in modo educato';
        $lines[] = '- se chiede richiamo, raccogliere l\'indicazione';
        $lines[] = '- se mostra interesse, preparare il passaggio commerciale';

        return implode("\n", $lines);
    }

    protected function buildUserMessage(string $userText): string
    {
        return implode("\n", [
            "Testo ricevuto dall'interlocutore durante la chiamata:",
            $userText,
            '',
            'Rispondi come assistente telefonico AI di R4Software in modo breve e naturale.',
        ]);
    }

    protected function detectSuggestedOutcome(?string $reply, string $userText): string
    {
        $text = mb_strtolower(trim($userText . ' ' . ($reply ?? '')));

        if ($text === '') {
            return 'continue';
        }

        foreach ([
                     'non mi interessa',
                     'non interessa',
                     'non sono interessato',
                     'non siamo interessati',
                     'non mi serve',
                     'non ci serve',
                 ] as $needle) {
            if (str_contains($text, $needle)) {
                return 'not_interested';
            }
        }

        foreach ([
                     'richiamami',
                     'richiamatemi',
                     'mi richiami',
                     'mi richiamate',
                     'più tardi',
                     'in un altro momento',
                     'domani',
                     'settimana prossima',
                 ] as $needle) {
            if (str_contains($text, $needle)) {
                return 'callback_requested';
            }
        }

        foreach ([
                     'non chiamatemi più',
                     'non richiamatemi',
                     'non contattatemi più',
                     'cancellami',
                     'toglietemi dalla lista',
                 ] as $needle) {
            if (str_contains($text, $needle)) {
                return 'do_not_call';
            }
        }

        foreach ([
                     'appuntamento',
                     'fissiamo',
                     'demo',
                     'sentiamoci',
                     'incontriamoci',
                 ] as $needle) {
            if (str_contains($text, $needle)) {
                return 'appointment_set';
            }
        }

        foreach ([
                     'interessato',
                     'mi interessa',
                     'potrebbe interessarmi',
                     'mandami informazioni',
                     'inviami informazioni',
                 ] as $needle) {
            if (str_contains($text, $needle)) {
                return 'interested';
            }
        }

        return 'continue';
    }

    protected function buildOutcomeNote(string $suggestedOutcome, ?string $reply, string $userText): string
    {
        return match ($suggestedOutcome) {
            'interested' => 'Outcome AI: contatto interessato. Ultimo input: ' . $userText,
            'not_interested' => 'Outcome AI: contatto non interessato. Ultimo input: ' . $userText,
            'callback_requested' => 'Outcome AI: richiesto ricontatto. Ultimo input: ' . $userText,
            'do_not_call' => 'Outcome AI: richiesta di non ricontattare. Ultimo input: ' . $userText,
            'appointment_set' => 'Outcome AI: disponibilità ad appuntamento. Ultimo input: ' . $userText,
            default => trim((string) $reply) !== '' ? trim((string) $reply) : 'Outcome AI applicato.',
        };
    }
}
