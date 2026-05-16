<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFeedback extends Model
{
    protected $table = 'crm_chatbot_feedback';

    protected $fillable = [
        'client_id',
        'conversation_id',
        'message_id',
        'is_helpful',
        'notes',
    ];

    protected $casts = [
        'client_id'       => 'integer',
        'conversation_id' => 'integer',
        'message_id'      => 'integer',
        'is_helpful'      => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
