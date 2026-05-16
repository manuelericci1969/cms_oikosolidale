<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'autoload'];
    protected $casts = ['autoload' => 'boolean'];

    /**
     * Restituisce tutte le settings in cache come array [key => value],
     * decodificando SEMPRE il JSON salvato in DB.
     */
    public static function allKeyed(): array
    {
        return Cache::rememberForever('settings.kv', function () {
            return self::query()->get(['key', 'value'])->mapWithKeys(function ($s) {
                // In tabella salviamo sempre JSON: decodifica
                $decoded = json_decode($s->value, true);
                $value = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $s->value; // fallback legacy
                return [$s->key => $value];
            })->toArray();
        });
    }

    /**
     * Lettura di una chiave singola con default.
     */
    public static function get(string $key, $default = null)
    {
        $all = self::allKeyed();
        return $all[$key] ?? $default;
    }

    /**
     * Scrittura di una chiave: SALVA SEMPRE JSON (stringhe, numeri, boolean, array…).
     * Invalida la cache e aggiorna una "revision" utile per bustare asset (es. Google Fonts).
     */
    public static function put(string $key, $value, ?string $group = null, bool $autoload = true): void
    {
        // Codifica SEMPRE in JSON (anche per semplici stringhe come "Open Sans")
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // In caso di valori non serializzabili (es. resource/closure), fallback a stringa JSON
        if ($encoded === false) {
            $encoded = json_encode((string) $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        self::query()->updateOrCreate(['key' => $key], [
            'value'    => $encoded,
            'group'    => $group,
            'autoload' => $autoload,
        ]);

        // Invalida cache
        Cache::forget('settings.kv');
        Cache::forget("setting:{$key}");

        // Bump "revision" per cache-busting client (opzionale ma utile)
        Cache::put('settings.rev', (string) now()->timestamp, 60 * 60 * 24 * 365);
    }
}
