<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallCampaign extends Model
{
    protected $table = 'crm_call_campaigns';

    protected $fillable = [
        'client_id',
        'owner_id',
        'name',
        'provider',
        'status',
        'source_mode',
        'description',
        'script_prompt',
        'filters',
        'settings',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'status_label',
        'provider_label',
        'source_mode_label',
        'list_id',
        'max_attempts',
        'timeout_secs',
    ];

    public const PROVIDER_TELNYX = 'telnyx';

    public const PROVIDER_OPTIONS = [
        self::PROVIDER_TELNYX => 'Telnyx',
    ];

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED  = 'archived';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT     => 'Bozza',
        self::STATUS_ACTIVE    => 'Attiva',
        self::STATUS_PAUSED    => 'In pausa',
        self::STATUS_COMPLETED => 'Completata',
        self::STATUS_ARCHIVED  => 'Archiviata',
    ];

    public const SOURCE_MODE_EMAIL_LIST_CONTACTS = 'email_list_contacts';
    public const SOURCE_MODE_LEADS               = 'leads';
    public const SOURCE_MODE_CUSTOMERS           = 'customers';
    public const SOURCE_MODE_MIXED               = 'mixed';

    public const SOURCE_MODE_OPTIONS = [
        self::SOURCE_MODE_EMAIL_LIST_CONTACTS => 'Contatti da lista email',
        self::SOURCE_MODE_LEADS               => 'Lead',
        self::SOURCE_MODE_CUSTOMERS           => 'Clienti',
        self::SOURCE_MODE_MIXED               => 'Misto',
    ];

    protected function filters(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decodeJsonToArray($value),
            set: fn ($value) => $this->encodeArrayToJson($value),
        );
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decodeJsonToArray($value),
            set: fn ($value) => $this->encodeArrayToJson($value),
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(CallQueue::class, 'campaign_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CallLog::class, 'campaign_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return static::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getProviderLabelAttribute(): string
    {
        return static::PROVIDER_OPTIONS[$this->provider] ?? strtoupper((string) $this->provider);
    }

    public function getSourceModeLabelAttribute(): string
    {
        return static::SOURCE_MODE_OPTIONS[$this->source_mode] ?? (string) $this->source_mode;
    }

    public function getListIdAttribute(): ?int
    {
        $value = data_get($this->filters, 'list_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public function getMaxAttemptsAttribute(): int
    {
        $value = data_get($this->settings, 'max_attempts', 3);

        return max(1, (int) $value);
    }

    public function getTimeoutSecsAttribute(): int
    {
        $value = data_get($this->settings, 'timeout_secs', 30);

        return max(10, (int) $value);
    }

    public function isRunnable(): bool
    {
        return $this->is_active
            && $this->status === self::STATUS_ACTIVE
            && $this->provider === self::PROVIDER_TELNYX
            && $this->source_mode === self::SOURCE_MODE_EMAIL_LIST_CONTACTS;
    }

    public function scopeActive($query)
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true);
    }

    public function scopeRunnable($query)
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true)
            ->where('provider', self::PROVIDER_TELNYX)
            ->where('source_mode', self::SOURCE_MODE_EMAIL_LIST_CONTACTS);
    }

    protected function decodeJsonToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function encodeArrayToJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
