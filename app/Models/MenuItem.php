<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'page_id',
        'target',
        'icon',
        'order',
        'is_active',
        'type',     // NEW: 'link' | 'separator'
        'settings', // NEW: JSON
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
        'settings'  => 'array',   // NEW
    ];

    public function menu()     { return $this->belongsTo(Menu::class); }
    public function parent()   { return $this->belongsTo(MenuItem::class, 'parent_id'); }
    public function children() { return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order'); }
    public function page()     { return $this->belongsTo(Page::class); }

    public function getUrlAttribute($value)
    {
        // i separatori non sono cliccabili
        if (($this->type ?? 'link') === 'separator') {
            return null;
        }

        if ($value) return $value; // URL custom

        if ($this->page_id && $this->page) { // URL pagina collegata
            return $this->page->getUrl();
        }

        return '#';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
