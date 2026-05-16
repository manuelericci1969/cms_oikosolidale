<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class  Appointment extends Model
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
