<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallQueue extends Model
{
    protected $table = 'crm_call_queue';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_RETRY     = 'retry';
    public const STATUS_CALLING   = 'calling';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CALLBACK  = 'callback';
    public const STATUS_SKIPPED   = 'skipped';

    public const OUTCOME_COMPLETED            = 'completed';
    public const OUTCOME_NO_ANSWER            = 'no_answer';
    public const OUTCOME_BUSY                 = 'busy';
    public const OUTCOME_VOICEMAIL            = 'voicemail';
    public const OUTCOME_FAILED               = 'failed';
    public const OUTCOME_CANCELLED            = 'cancelled';
    public const OUTCOME_CALLBACK             = 'callback';
    public const OUTCOME_SKIPPED              = 'skipped';
    public const OUTCOME_TECHNICAL_TIMEOUT    = 'technical_timeout';
    public const OUTCOME_MAX_ATTEMPTS_REACHED = 'max_attempts_reached';

    public const SOURCE_EMAIL_LIST_CONTACT = 'email_list_contact';
    public const SOURCE_LEAD               = 'lead';
    public const SOURCE_CUSTOMER           = 'customer';
    public const SOURCE_MANUAL             = 'manual';
    public const SOURCE_IMPORT             = 'import';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_RETRY     => 'Retry',
        self::STATUS_CALLING   => 'Calling',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_FAILED    => 'Failed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_CALLBACK  => 'Callback',
        self::STATUS_SKIPPED   => 'Skipped',
    ];

    public const SOURCE_OPTIONS = [
        self::SOURCE_EMAIL_LIST_CONTACT => 'Email list contact',
        self::SOURCE_LEAD               => 'Lead',
        self::SOURCE_CUSTOMER           => 'Customer',
        self::SOURCE_MANUAL             => 'Manual',
        self::SOURCE_IMPORT             => 'Import',
    ];

    protected $fillable = [
        'client_id',
        'owner_id',
        'campaign_id',
        'contact_id',
        'contact_type',
        'contact_name',
        'email',
        'phone',
        'source_type',
        'source_id',
        'status',
        'attempts',
        'max_attempts',
        'last_attempt_at',
        'next_attempt_at',
        'completed_at',
        'last_outcome',
        'last_outcome_note',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'client_id'        => 'integer',
        'owner_id'         => 'integer',
        'attempts'         => 'integer',
        'max_attempts'     => 'integer',
        'last_attempt_at'  => 'datetime',
        'next_attempt_at'  => 'datetime',
        'completed_at'     => 'datetime',
        'payload'          => 'array',
        'metadata'         => 'array',
    ];

    protected $appends = [
        'status_label',
        'source_label',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CallCampaign::class, 'campaign_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CallLog::class, 'queue_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCE_OPTIONS[$this->source_type] ?? ucfirst((string) $this->source_type);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_RETRY,
        ], true);
    }

    public function isCallable(): bool
    {
        return in_array($this->status, [
                self::STATUS_PENDING,
                self::STATUS_RETRY,
                self::STATUS_CALLBACK,
            ], true)
            && !$this->completed_at
            && !empty($this->phone)
            && $this->hasAttemptsLeft();
    }

    public function hasAttemptsLeft(): bool
    {
        return (int) $this->attempts < (int) $this->max_attempts;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRetry($query)
    {
        return $query->where('status', self::STATUS_RETRY);
    }

    public function scopeCalling($query)
    {
        return $query->where('status', self::STATUS_CALLING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
