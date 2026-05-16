<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingProfile extends Model
{
    protected $table = 'crm_billing_profiles';

    protected $fillable = [
        'client_id',
        'name',
        'legal_name',
        'vat',
        'tax_code',
        'address',
        'city',
        'zip',
        'province',
        'country',
        'email',
        'phone',
        'pec',
        'sdi',
        'bank_details',
        'is_default',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function snapshot(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'legal_name'   => $this->legal_name ?: $this->name,
            'vat'          => $this->vat,
            'tax_code'     => $this->tax_code,
            'address'      => $this->address,
            'city'         => $this->city,
            'zip'          => $this->zip,
            'province'     => $this->province,
            'country'      => $this->country ?: 'IT',
            'email'        => $this->email,
            'phone'        => $this->phone,
            'pec'          => $this->pec,
            'sdi'          => $this->sdi,
            'bank_details' => $this->bank_details,
        ];
    }

    public function displayName(): string
    {
        $label = $this->legal_name ?: $this->name;

        if ($this->vat) {
            $label .= ' · P.IVA ' . $this->vat;
        }

        return $label;
    }
}
