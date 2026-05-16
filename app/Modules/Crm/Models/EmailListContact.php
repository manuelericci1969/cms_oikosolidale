<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailListContact extends Model
{
    protected $table = 'crm_email_list_contacts';

    protected $fillable = [
        'list_id',
        'contact_type',
        'contact_id',
        'email',
        'name',
        'segment',
        'city',
        'province',
        'region',
        'country',
        'postal_code',
        'phone',
        'whatsapp',
        'website_url',
        'contact_page_url',
        'address',
        'business_type',
        'stars',
        'vat_number',
        'cin_code',
        'contact_role',
        'email_status',
        'source_type',
        'source_url',
        'site_rating',
        'commercial_potential',
        'seo_score',
        'last_verified_at',
        'notes',
        'marketing_consense',
        'unsubscribed_at',
    ];

    protected $casts = [
        'marketing_consense' => 'boolean',
        'stars' => 'integer',
        'seo_score' => 'float',
        'last_verified_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(EmailList::class, 'list_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            EmailListCategory::class,
            'crm_email_list_contact_category',
            'contact_id',
            'category_id'
        )->withTimestamps();
    }
}
