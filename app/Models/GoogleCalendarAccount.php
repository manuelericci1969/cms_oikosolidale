<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarAccount extends Model
{
    protected $fillable = [
        'user_id','google_email','calendar_id','token_json','token_expires_at',
        'last_synced_at','enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokenArray(): array
    {
        $arr = json_decode($this->token_json ?? '[]', true);
        return is_array($arr) ? $arr : [];
    }
}
