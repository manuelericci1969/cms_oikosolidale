<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageComponent extends Model
{
    protected $fillable = [
        'name',
        'key',
        'category',
        'description',
        'schema',
        'template_html',
        'template_css',
        'template_js',
        'preview_html',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schema'     => 'array',
        'is_active'  => 'boolean',
        'is_system'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
