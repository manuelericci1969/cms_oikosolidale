<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Service extends Model
{
    use HasFactory;

    protected $table = 'crm_services';

    protected $fillable = [
        'customer_id',
        'product_id',
        'name',
        'type',
        'status',
        'provider_name',
        'provider_website',
        'panel_url',
        'panel_username',
        'panel_password',
        'activated_at',
        'expires_at',
        'auto_renew',

        // prezzi rinnovo
        'renew_price',
        'renew_price_vat_included',
        'renew_price_vat_rate',

        // prezzo contratto (una tantum o altro)
        'renewal_price',
        'renewal_vat_rate',
        'renewal_vat_mode',

        // reminder (se in futuro li vuoi usare, per ora non sono obbligatori)
        'send_reminder',
        'reminder_days_before',
        'reminder_custom_text',

        'notes',
    ];

    protected $casts = [
        'activated_at'             => 'datetime',
        'expires_at'               => 'datetime',
        'auto_renew'               => 'boolean',
        'renew_price'              => 'decimal:2',
        'renewal_price'            => 'decimal:2',
        'renew_price_vat_included' => 'boolean',
        'send_reminder'            => 'boolean',
    ];

    public const TYPE_OPTIONS = [
        'dominio'    => 'Dominio',
        'hosting'    => 'Hosting',
        'server'     => 'Server / VPS',
        'gestionale' => 'Gestionale / Software',
        'webmail'    => 'WebMail / Email',
        'ssl'        => 'Certificato SSL',
        'assistenza' => 'Assistenza',
        'altro'      => 'Altro',
    ];

    public const STATUS_OPTIONS = [
        'active'     => 'Attivo',
        'non_attivo' => 'Non attivo',
        'suspended'  => 'Sospeso',
        'eliminato'  => 'Eliminato',
        'expired'    => 'Scaduto',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reminderLogs()
    {
        return $this->hasMany(\App\Modules\Crm\Models\ServiceReminderLog::class);
    }

    public function paymentLinks()
    {
        return $this->hasMany(ServicePaymentLink::class)->latest();
    }

    public function getRenewPriceNetAttribute(): ?float
    {
        if ($this->renew_price === null) {
            return null;
        }

        $price = (float) $this->renew_price;
        $rate  = (float) ($this->renew_price_vat_rate ?? 0);

        if ($this->renew_price_vat_included && $rate > 0) {
            return $price / (1 + $rate / 100);
        }

        return $price;
    }

    public function getRenewPriceGrossAttribute(): ?float
    {
        if ($this->renew_price === null) {
            return null;
        }

        $price = (float) $this->renew_price;
        $rate  = (float) ($this->renew_price_vat_rate ?? 0);

        if ($this->renew_price_vat_included || $rate <= 0) {
            return $price;
        }

        return $price * (1 + $rate / 100);
    }

    public function scopeForCustomer($query, ?int $customerId)
    {
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
    }

    public function scopeOfType($query, ?string $type)
    {
        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }
    }

    public function getTypeLabelAttribute(): string
    {
        return static::TYPE_OPTIONS[$this->type] ?? ($this->type ?: '-');
    }

    public function getStatusLabelAttribute(): string
    {
        return static::STATUS_OPTIONS[$this->status] ?? ($this->status ?: '-');
    }

    public function getDaysToExpirationAttribute(): ?int
    {
        if (!$this->expires_at instanceof Carbon) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function getStatusStateAttribute(): string
    {
        if ($this->expires_at instanceof Carbon) {
            $days = $this->days_to_expiration;

            if ($days < 0)  return 'scaduto';
            if ($days === 0) return 'scade_oggi';
            if ($days !== null && $days <= 20) return 'in_scadenza';

            return 'attivo';
        }

        return 'senza_scadenza';
    }

    public function getStatusColorAttribute(): string
    {
        $days = $this->days_to_expiration;

        if ($days !== null) {
            if ($days < 0)  return 'danger';
            if ($days === 0) return 'danger';
            if ($days <= 20) return 'warning';
            if ($days <= 31) return 'success';
        }

        return match ($this->status) {
            'active'     => 'success',
            'non_attivo' => 'secondary',
            'suspended'  => 'warning',
            'eliminato'  => 'secondary',
            'expired'    => 'secondary',
            default      => 'secondary',
        };
    }
}
