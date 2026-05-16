<?php

namespace App\Modules\Crm\Models;

use App\Models\GoogleCalendarAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentGoogleEvent extends Model
{
    protected $table = 'crm_appointment_google_events';

    protected $fillable = [
        'appointment_id','google_calendar_account_id','calendar_id',
        'event_id','ical_uid','etag','google_updated_at','last_synced_at',
    ];

    protected $casts = [
        'google_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendarAccount::class, 'google_calendar_account_id');
    }
}
