<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemError extends Model
{
    protected $fillable = [
        'environment',
        'level',
        'channel',
        'message',
        'exception_class',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'route_name',
        'user_id',
        'ip',
        'user_agent',
        'request_id',
        'context',
        'extra',
    ];

    protected $casts = [
        'context' => 'array',
        'extra' => 'array',
    ];
}
