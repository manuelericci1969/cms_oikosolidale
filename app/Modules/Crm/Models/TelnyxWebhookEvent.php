<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TelnyxWebhookEvent extends Model
{
    protected $table = 'crm_telnyx_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'call_control_id',
        'call_leg_id',
        'call_session_id',
        'call_log_id',
        'occurred_at',
        'headers',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    protected function headers(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decodeJsonToArray($value),
            set: fn ($value) => $this->encodeArrayToJson($value),
        );
    }

    protected function payload(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decodeJsonToArray($value),
            set: fn ($value) => $this->encodeArrayToJson($value),
        );
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
