<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallVoiceSession extends Model
{
    protected $table = 'crm_call_voice_sessions';

    protected $fillable = [
        'client_id',
        'campaign_id',
        'queue_id',
        'call_log_id',
        'provider',
        'provider_call_id',
        'provider_leg_id',
        'provider_session_id',
        'stream_id',
        'status',
        'bridge_mode',
        'started_at',
        'streaming_started_at',
        'closed_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'streaming_started_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function callLog()
    {
        return $this->belongsTo(\App\Modules\Crm\Models\CallLog::class, 'call_log_id');
    }

    public function queueItem()
    {
        return $this->belongsTo(\App\Modules\Crm\Models\CallQueue::class, 'queue_id');
    }

    public function campaign()
    {
        return $this->belongsTo(\App\Modules\Crm\Models\CallCampaign::class, 'campaign_id');
    }

    public function turns(): HasMany
    {
        return $this->hasMany(CallVoiceTurn::class, 'voice_session_id');
    }
}
