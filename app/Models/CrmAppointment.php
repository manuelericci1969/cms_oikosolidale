<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmAppointment extends Model
{
    protected $table = 'crm_appointments';

    protected $fillable = [
        'client_id',
        'user_id',
        'lead_id',
        'customer_id',
        'title',
        'description',
        'location',
        'type',
        'status',
        'start_at',
        'end_at',
        'all_day',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
        'all_day'  => 'boolean',
    ];
}
