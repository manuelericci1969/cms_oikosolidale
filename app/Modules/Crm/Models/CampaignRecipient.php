<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    protected $table = 'crm_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'contact_type',   // lead / customer / csv
        'contact_id',     // id lead o customer, se presente
        'email',
        'name',
        'segment',        // es. "Lista Natale 2025"
        'status',         // pending / queued / sent / bounced / unsubscribed / ecc.
        'hash',

        'queued_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'open_count',
        'clicked_at',
        'click_count',
        'bounced_at',
        'complained_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'queued_at'       => 'datetime',
        'sent_at'         => 'datetime',
        'delivered_at'    => 'datetime',
        'opened_at'       => 'datetime',
        'clicked_at'      => 'datetime',
        'bounced_at'      => 'datetime',
        'complained_at'   => 'datetime',
        'unsubscribed_at' => 'datetime',
        'open_count'      => 'integer',
        'click_count'     => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
