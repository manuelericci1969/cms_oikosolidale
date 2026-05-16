<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallConversationMessage extends Model
{
    protected $table = 'crm_call_conversation_messages';

    protected $fillable = [
        'client_id',
        'campaign_id',
        'queue_id',
        'call_log_id',
        'role',
        'message',
        'metadata',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'campaign_id' => 'integer',
        'queue_id' => 'integer',
        'call_log_id' => 'integer',
        'metadata' => 'array',
    ];

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }
}
