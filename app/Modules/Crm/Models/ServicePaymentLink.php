<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePaymentLink extends Model
{
    protected $table = 'crm_service_payment_links';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'client_id',
        'service_id',
        'customer_id',
        'amount',
        'currency',
        'description',
        'status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_url',
        'expires_at',
        'paid_at',
        'cancelled_at',
        'sent_email_at',
        'sent_whatsapp_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'sent_email_at' => 'datetime',
        'sent_whatsapp_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Pagato',
            self::STATUS_EXPIRED => 'Scaduto',
            self::STATUS_CANCELLED => 'Annullato',
            self::STATUS_FAILED => 'Fallito',
            default => 'In attesa',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_EXPIRED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
            self::STATUS_FAILED => 'danger',
            default => 'warning',
        };
    }
}
