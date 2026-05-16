<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $table = 'crm_chatbot_messages';

    protected $fillable = [
        'conversation_id',
        'direction',
        'sender_type',
        'message_type',
        'message',
        'model',
        'token_usage_input',
        'token_usage_output',
        'metadata',
    ];

    protected $casts = [
        'token_usage_input'  => 'integer',
        'token_usage_output' => 'integer',
        'metadata'           => 'array',
    ];

    public const DIRECTION_OPTIONS = [
        'in'     => 'Entrata',
        'out'    => 'Uscita',
        'system' => 'Sistema',
    ];

    public const SENDER_TYPE_OPTIONS = [
        'visitor' => 'Visitatore',
        'ai'      => 'AI',
        'agent'   => 'Operatore',
        'system'  => 'Sistema',
    ];

    public const MESSAGE_TYPE_OPTIONS = [
        'text'         => 'Testo',
        'cta'          => 'CTA',
        'form_request' => 'Richiesta dati',
        'event'        => 'Evento',
        'note'         => 'Nota',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    public function getDirectionLabelAttribute(): string
    {
        return static::DIRECTION_OPTIONS[$this->direction] ?? (string) $this->direction;
    }

    public function getSenderTypeLabelAttribute(): string
    {
        return static::SENDER_TYPE_OPTIONS[$this->sender_type] ?? (string) $this->sender_type;
    }

    public function getMessageTypeLabelAttribute(): ?string
    {
        if (!$this->message_type) {
            return null;
        }

        return static::MESSAGE_TYPE_OPTIONS[$this->message_type] ?? $this->message_type;
    }

    public function getBubbleClassAttribute(): string
    {
        return match ($this->sender_type) {
            'visitor' => 'bg-light border',
            'ai'      => 'bg-primary-subtle border border-primary-subtle',
            'agent'   => 'bg-success-subtle border border-success-subtle',
            'system'  => 'bg-warning-subtle border border-warning-subtle',
            default   => 'bg-light border',
        };
    }

    public function getSenderIconAttribute(): string
    {
        return match ($this->sender_type) {
            'visitor' => 'bi-person',
            'ai'      => 'bi-robot',
            'agent'   => 'bi-person-badge',
            'system'  => 'bi-gear',
            default   => 'bi-chat-dots',
        };
    }
}
