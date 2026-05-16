<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallLog extends Model
{
    protected $table = 'crm_call_logs';

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND  = 'inbound';

    public const CALL_STATUS_INITIATED = 'initiated';
    public const CALL_STATUS_RINGING = 'ringing';
    public const CALL_STATUS_ANSWERED = 'answered';
    public const CALL_STATUS_COMPLETED = 'completed';
    public const CALL_STATUS_FAILED = 'failed';
    public const CALL_STATUS_BUSY = 'busy';
    public const CALL_STATUS_NO_ANSWER = 'no_answer';
    public const CALL_STATUS_CANCELLED = 'cancelled';

    public const TECH_COMPLETED = 'completed';
    public const TECH_BUSY = 'busy';
    public const TECH_NO_ANSWER = 'no_answer';
    public const TECH_VOICEMAIL = 'voicemail';
    public const TECH_FAILED = 'failed';
    public const TECH_INVALID_NUMBER = 'invalid_number';
    public const TECH_REJECTED = 'rejected';
    public const TECH_CANCELLED = 'cancelled';
    public const TECH_ERROR = 'technical_error';

    public const BUSINESS_INTERESTED = 'interested';
    public const BUSINESS_NOT_INTERESTED = 'not_interested';
    public const BUSINESS_QUALIFIED = 'qualified';
    public const BUSINESS_APPOINTMENT_SET = 'appointment_set';
    public const BUSINESS_ALREADY_CUSTOMER = 'already_customer';
    public const BUSINESS_WRONG_CONTACT = 'wrong_contact';
    public const BUSINESS_NO_DECISION = 'no_decision';
    public const BUSINESS_CALLBACK_REQUESTED = 'callback_requested';
    public const BUSINESS_DO_NOT_CALL = 'do_not_call';

    public const DIRECTION_OPTIONS = [
        self::DIRECTION_OUTBOUND => 'Outbound',
        self::DIRECTION_INBOUND  => 'Inbound',
    ];

    public const CALL_STATUS_OPTIONS = [
        self::CALL_STATUS_INITIATED => 'Initiated',
        self::CALL_STATUS_RINGING => 'Ringing',
        self::CALL_STATUS_ANSWERED => 'Answered',
        self::CALL_STATUS_COMPLETED => 'Completed',
        self::CALL_STATUS_FAILED => 'Failed',
        self::CALL_STATUS_BUSY => 'Busy',
        self::CALL_STATUS_NO_ANSWER => 'No answer',
        self::CALL_STATUS_CANCELLED => 'Cancelled',
    ];

    public const TECHNICAL_OUTCOME_OPTIONS = [
        self::TECH_COMPLETED => 'Completed',
        self::TECH_BUSY => 'Busy',
        self::TECH_NO_ANSWER => 'No answer',
        self::TECH_VOICEMAIL => 'Voicemail',
        self::TECH_FAILED => 'Failed',
        self::TECH_INVALID_NUMBER => 'Invalid number',
        self::TECH_REJECTED => 'Rejected',
        self::TECH_CANCELLED => 'Cancelled',
        self::TECH_ERROR => 'Technical error',
    ];

    public const BUSINESS_OUTCOME_OPTIONS = [
        self::BUSINESS_INTERESTED => 'Interested',
        self::BUSINESS_NOT_INTERESTED => 'Not interested',
        self::BUSINESS_QUALIFIED => 'Qualified',
        self::BUSINESS_APPOINTMENT_SET => 'Appointment set',
        self::BUSINESS_ALREADY_CUSTOMER => 'Already customer',
        self::BUSINESS_WRONG_CONTACT => 'Wrong contact',
        self::BUSINESS_NO_DECISION => 'No decision',
        self::BUSINESS_CALLBACK_REQUESTED => 'Callback requested',
        self::BUSINESS_DO_NOT_CALL => 'Do not call',
    ];

    protected $fillable = [
        'client_id',
        'campaign_id',
        'queue_id',
        'owner_id',
        'source_type',
        'source_id',
        'provider',
        'provider_call_id',
        'provider_leg_id',
        'provider_session_id',
        'phone',
        'direction',
        'call_status',
        'technical_outcome',
        'business_outcome',
        'duration_seconds',
        'started_at',
        'answered_at',
        'ended_at',
        'callback_at',
        'operator_note',
        'ai_summary',
        'transcript',
        'metadata',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'campaign_id' => 'integer',
        'queue_id' => 'integer',
        'owner_id' => 'integer',
        'source_id' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'callback_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'direction_label',
        'call_status_label',
        'technical_outcome_label',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CallCampaign::class, 'campaign_id');
    }

    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(CallQueue::class, 'queue_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function conversationMessages(): HasMany
    {
        return $this->hasMany(CallConversationMessage::class, 'call_log_id')->orderBy('id');
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTION_OPTIONS[$this->direction] ?? ucfirst((string) $this->direction);
    }

    public function getCallStatusLabelAttribute(): string
    {
        return self::CALL_STATUS_OPTIONS[$this->call_status] ?? ucfirst((string) $this->call_status);
    }

    public function getTechnicalOutcomeLabelAttribute(): string
    {
        return self::TECHNICAL_OUTCOME_OPTIONS[$this->technical_outcome] ?? ucfirst((string) $this->technical_outcome);
    }

    public function isAnswered(): bool
    {
        return !is_null($this->answered_at)
            || $this->call_status === self::CALL_STATUS_ANSWERED
            || $this->call_status === self::CALL_STATUS_COMPLETED;
    }

    public function isCompleted(): bool
    {
        return $this->technical_outcome === self::TECH_COMPLETED
            || $this->call_status === self::CALL_STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return in_array($this->call_status, [
                self::CALL_STATUS_FAILED,
                self::CALL_STATUS_BUSY,
                self::CALL_STATUS_NO_ANSWER,
                self::CALL_STATUS_CANCELLED,
            ], true) || in_array($this->technical_outcome, [
                self::TECH_BUSY,
                self::TECH_NO_ANSWER,
                self::TECH_FAILED,
                self::TECH_INVALID_NUMBER,
                self::TECH_REJECTED,
                self::TECH_CANCELLED,
                self::TECH_ERROR,
            ], true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('technical_outcome', self::TECH_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('technical_outcome', [
            self::TECH_BUSY,
            self::TECH_NO_ANSWER,
            self::TECH_FAILED,
            self::TECH_INVALID_NUMBER,
            self::TECH_REJECTED,
            self::TECH_CANCELLED,
            self::TECH_ERROR,
        ]);
    }

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeForQueueItem($query, int $queueId)
    {
        return $query->where('queue_id', $queueId);
    }
}
