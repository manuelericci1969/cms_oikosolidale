<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'disk','path','original_name','mime','size','alt','title','created_by','width','height','variants'
    ];

    protected $casts = [
        'variants' => 'array',
    ];

    // URL file originale
    public function getUrlAttribute(): ?string
    {
        $path = ltrim((string)($this->path ?? ''), '/');
        if ($path === '') return null;
        return Storage::disk('public')->url($path);
    }

    // URL variante
    public function variantUrl(string $key): ?string
    {
        $v = $this->variants[$key] ?? null;
        $path = is_array($v) ? ($v['path'] ?? null) : (is_string($v) ? $v : null);
        if (!$path) return null;

        $clean = ltrim($path, '/');
        if (!Storage::disk('public')->exists($clean)) {
            return null;
        }
        return Storage::disk('public')->url($clean);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
