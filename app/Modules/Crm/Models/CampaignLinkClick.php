<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLinkClick extends Model
{
    protected $table = 'crm_campaign_link_clicks';

    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'url',
        'click_count',
        'first_clicked_at',
        'last_clicked_at',
    ];

    protected $casts = [
        'first_clicked_at' => 'datetime',
        'last_clicked_at'  => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient()
    {
        return $this->belongsTo(CampaignRecipient::class);
    }
}
