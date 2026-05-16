<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $table = 'crm_contracts';

    protected $fillable = [
        'client_id',
        'customer_id',
        'quote_id',
        'service_id',
        'number',
        'title',
        'type',
        'status',
        'pdf_path',
        'signed_pdf_path',
        'generated_at',
        'sent_at',
        'accepted_at',
        'signed_at',
        'accepted_ip',
        'accepted_user_agent',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'sent_at'      => 'datetime',
        'accepted_at'  => 'datetime',
        'signed_at'    => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'generated' => 'Generato',
            'sent'      => 'Inviato',
            'accepted'  => 'Accettato',
            'signed'    => 'Firmato',
            'archived'  => 'Archiviato',
            'cancelled' => 'Annullato',
            default     => ucfirst((string) $this->status),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'digital' => 'Digitale',
            'paper'   => 'Cartaceo',
            'renewal' => 'Rinnovo',
            'manual'  => 'Manuale',
            default   => ucfirst((string) $this->type),
        };
    }
}
