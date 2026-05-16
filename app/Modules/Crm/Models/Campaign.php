<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Crm\Models\CampaignRecipient;

class Campaign extends Model
{
    protected $table = 'crm_campaigns';

    protected $fillable = [
        'client_id',
        'name',
        'subject',
        'from_name',
        'from_email',
        'reply_to_email',
        'preheader',
        'html_body',
        'text_body',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'open_count',
        'click_count',
        'bounce_count',
        'unsubscribe_count',
        'complaint_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public const STATUS_OPTIONS = [
        'draft'     => 'Bozza',
        'scheduled' => 'Programmato',
        'sending'   => 'In invio',
        'paused'    => 'In pausa',
        'completed' => 'Completato',
        'cancelled' => 'Annullato',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

}
