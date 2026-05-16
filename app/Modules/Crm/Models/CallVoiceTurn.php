<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallVoiceTurn extends Model
{
    protected $table = 'crm_call_voice_turns';

    protected $fillable = [
        'voice_session_id',
        'call_log_id',
        'turn_no',
        'role',
        'text',
        'stt_provider',
        'tts_provider',
        'latency_ms',
        'is_final',
        'metadata',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'metadata' => 'array',
    ];

    public function voiceSession(): BelongsTo
    {
        return $this->belongsTo(CallVoiceSession::class, 'voice_session_id');
    }
}
