<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    protected $table = 'crm_whatsapp_messages';

    protected $fillable = [
        'client_id',
        'user_id',
        'lead_id',
        'customer_id',
        'recipient_name',
        'recipient_phone',
        'normalized_phone',
        'message',
        'status',
        'api_response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'api_response' => 'array',
        'sent_at'      => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING => 'In attesa',
        self::STATUS_SENT    => 'Inviato',
        self::STATUS_FAILED  => 'Fallito',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENT    => 'bg-success',
            self::STATUS_FAILED  => 'bg-danger',
            self::STATUS_PENDING => 'bg-warning text-dark',
            default              => 'bg-secondary',
        };
    }

    public function getRecipientDisplayAttribute(): string
    {
        if (!empty($this->recipient_name) && !empty($this->recipient_phone)) {
            return $this->recipient_name . ' (' . $this->recipient_phone . ')';
        }

        if (!empty($this->recipient_name)) {
            return $this->recipient_name;
        }

        return $this->recipient_phone ?: '—';
    }
}
