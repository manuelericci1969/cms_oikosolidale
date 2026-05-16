<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    protected $table = 'crm_chatbot_conversations';

    protected $fillable = [
        'client_id',
        'lead_id',
        'customer_id',
        'owner_id',
        'session_id',
        'channel',
        'source_page',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'visitor_company',
        'status',
        'intent',
        'score',
        'last_message_at',
        'closed_at',
        'converted_at',
        'conversion_type',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'score'           => 'integer',
        'last_message_at' => 'datetime',
        'closed_at'       => 'datetime',
        'converted_at'    => 'datetime',
        'metadata'        => 'array',
    ];

    public const STATUS_OPTIONS = [
        'open'      => 'Aperta',
        'qualified' => 'Qualificata',
        'converted' => 'Convertita',
        'closed'    => 'Chiusa',
        'spam'      => 'Spam',
    ];

    public const INTENT_OPTIONS = [
        'website'   => 'Sito web',
        'crm'       => 'CRM / Gestionale',
        'app'       => 'App mobile',
        'iot'       => 'IoT / Automazione',
        'marketing' => 'Marketing',
        'generic'   => 'Generica',
    ];

    public const CHANNEL_OPTIONS = [
        'website'   => 'Sito web',
        'whatsapp'  => 'WhatsApp',
        'telegram'  => 'Telegram',
        'messenger' => 'Messenger',
        'other'     => 'Altro',
    ];

    public const CONVERSION_TYPE_OPTIONS = [
        'lead'     => 'Lead',
        'task'     => 'Task',
        'quote'    => 'Preventivo',
        'customer' => 'Cliente',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id')->orderBy('id');
    }

    public function latestMessages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id')->latest('id');
    }

    public function getHasContactDataAttribute(): bool
    {
        return !empty($this->visitor_email) || !empty($this->visitor_phone);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::STATUS_OPTIONS[$this->status] ?? (string) $this->status;
    }

    public function getScoreBadgeClassAttribute(): string
    {
        return match (true) {
            $this->score >= 80 => 'bg-success',
            $this->score >= 50 => 'bg-warning text-dark',
            $this->score >= 20 => 'bg-info text-dark',
            default            => 'bg-secondary',
        };
    }

    public function getIsLinkedToLeadAttribute(): bool
    {
        return !empty($this->lead_id);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'open'      => 'bg-primary-subtle text-primary',
            'qualified' => 'bg-info-subtle text-info',
            'converted' => 'bg-success',
            'closed'    => 'bg-secondary',
            'spam'      => 'bg-danger-subtle text-danger',
            default     => 'bg-light text-muted',
        };
    }

    public function getIntentLabelAttribute(): ?string
    {
        if (!$this->intent) {
            return null;
        }

        return static::INTENT_OPTIONS[$this->intent] ?? $this->intent;
    }

    public function getChannelLabelAttribute(): ?string
    {
        if (!$this->channel) {
            return null;
        }

        return static::CHANNEL_OPTIONS[$this->channel] ?? $this->channel;
    }

    public function getConversionTypeLabelAttribute(): ?string
    {
        if (!$this->conversion_type) {
            return null;
        }

        return static::CONVERSION_TYPE_OPTIONS[$this->conversion_type] ?? $this->conversion_type;
    }

    public function getVisitorDisplayNameAttribute(): string
    {
        if (!empty($this->visitor_name)) {
            return $this->visitor_name;
        }

        if (!empty($this->visitor_email)) {
            return $this->visitor_email;
        }

        if (!empty($this->visitor_phone)) {
            return $this->visitor_phone;
        }

        return 'Visitatore anonimo';
    }

    public function getLastMessageExcerptAttribute(): ?string
    {
        $message = $this->relationLoaded('latestMessages')
            ? $this->latestMessages->first()
            : $this->latestMessages()->first();

        if (!$message || !$message->message) {
            return null;
        }

        return mb_strimwidth(trim(strip_tags($message->message)), 0, 120, '…');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeNotConverted($query)
    {
        return $query->whereNull('converted_at')
            ->where('status', '!=', 'converted');
    }
}
