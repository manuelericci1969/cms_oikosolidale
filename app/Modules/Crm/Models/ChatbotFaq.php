<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFaq extends Model
{
    protected $table = 'crm_chatbot_faqs';

    protected $fillable = [
        'client_id',
        'question_pattern',
        'keywords',
        'intent',
        'product_id',
        'answer',
        'priority',
        'is_active',
        'times_used',
    ];

    protected $casts = [
        'client_id'  => 'integer',
        'product_id' => 'integer',
        'priority'   => 'integer',
        'is_active'  => 'boolean',
        'times_used' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function keywordsArray(): array
    {
        if (!$this->keywords) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            preg_split('/[,;\n]+/', (string) $this->keywords) ?: []
        )));
    }
}
