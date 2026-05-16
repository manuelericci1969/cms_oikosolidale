<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotUnknownQuestion extends Model
{
    protected $table = 'crm_chatbot_unknown_questions';

    protected $fillable = [
        'client_id',
        'conversation_id',
        'message_id',
        'question',
        'intent_detected',
        'source_page',
        'status',
    ];

    protected $casts = [
        'client_id'       => 'integer',
        'conversation_id' => 'integer',
        'message_id'      => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
