<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmAppointmentGoogleEvent extends Model
{
    protected $table = 'crm_appointment_google_events';

    protected $fillable = [
        'appointment_id',
        'google_calendar_account_id',
        'calendar_id',
        'event_id',
        'ical_uid',
        'etag',
        'google_updated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'google_updated_at' => 'datetime',
        'last_synced_at'    => 'datetime',
    ];
}
