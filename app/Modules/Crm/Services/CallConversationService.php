<?php

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\CallConversationMessage;
use App\Modules\Crm\Models\CallLog;

class CallConversationService
{
    public function addSystemMessage(CallLog $callLog, string $message, array $metadata = []): CallConversationMessage
    {
        return $this->store($callLog, 'system', $message, $metadata);
    }

    public function addUserMessage(CallLog $callLog, string $message, array $metadata = []): CallConversationMessage
    {
        return $this->store($callLog, 'user', $message, $metadata);
    }

    public function addAssistantMessage(CallLog $callLog, string $message, array $metadata = []): CallConversationMessage
    {
        return $this->store($callLog, 'assistant', $message, $metadata);
    }

    public function buildHistory(CallLog $callLog, int $limit = 12): array
    {
        return $callLog->conversationMessages()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function (CallConversationMessage $message) {
                return [
                    'role' => $message->role,
                    'content' => trim((string) $message->message),
                ];
            })
            ->filter(function (array $item) {
                return $item['content'] !== ''
                    && in_array($item['role'], ['system', 'user', 'assistant'], true);
            })
            ->values()
            ->all();
    }

    protected function store(CallLog $callLog, string $role, string $message, array $metadata = []): CallConversationMessage
    {
        $message = trim($message);

        if ($message === '') {
            throw new \InvalidArgumentException('Il messaggio della conversazione chiamata non può essere vuoto.');
        }

        return CallConversationMessage::create([
            'client_id' => $callLog->client_id,
            'campaign_id' => $callLog->campaign_id,
            'queue_id' => $callLog->queue_id,
            'call_log_id' => $callLog->id,
            'role' => $role,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }
}
