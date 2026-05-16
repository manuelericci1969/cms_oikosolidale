<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceReminderLog extends Model
{
    use HasFactory;

    protected $table = 'crm_service_reminder_logs';

    protected $fillable = [
        'service_id',
        'customer_id',
        'channel',
        'to_email',
        'to_phone',
        'subject',
        'body',
        'status',
        'tracking_hash',
        'provider_message_id',
        'sent_at',
        'opened_at',
        'error',
    ];

    protected $casts = [
        'sent_at'   => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
