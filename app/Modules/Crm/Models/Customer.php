<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'crm_customers';

    protected $fillable = [
        'client_id',
        'owner_id',
        'name',
        'email',
        'pec_email',
        'phone',
        'vat_number',
        'tax_code',
        'sdi_code',
        'billing_address',
        'billing_zip',
        'billing_city',
        'billing_province',
        'billing_country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'customer_id')->latest();
    }
}
