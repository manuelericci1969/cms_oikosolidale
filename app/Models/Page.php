<?php

namespace App\Models;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'content'     => '[]',
        'meta'        => '[]',
        'editor_mode' => 'structured',
        'visual_json' => '[]',
    ];

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta',
        'status',
        'is_homepage',
        'published_at',
        'created_by',
        'updated_by',
        'editor_mode',
        'visual_html',
        'visual_css',
        'visual_json',
    ];

    protected $casts = [
        'content'      => 'array',
        'meta'         => 'array',
        'published_at' => 'datetime',
        'is_homepage'  => 'boolean',
        'created_by'   => 'integer',
        'updated_by'   => 'integer',
        'visual_json'  => 'array',
    ];

    public function setContentAttribute($value): void
    {
        $arr = $this->coerceToArray($value);
        $this->attributes['content'] = json_encode(
            $arr,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function setMetaAttribute($value): void
    {
        $arr = $this->coerceToArray($value);
        $this->attributes['meta'] = json_encode(
            $arr,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function setVisualJsonAttribute($value): void
    {
        $arr = $this->coerceToArray($value);
        $this->attributes['visual_json'] = json_encode(
            $arr,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function coerceToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $d = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($d)) {
                return $d;
            }
        }

        return [];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            $page->created_by = auth()->id() ?? $page->created_by;

            if ($page->is_homepage) {
                static::query()->update(['is_homepage' => false]);
            }
        });

        static::updating(function ($page) {
            $page->updated_by = auth()->id() ?? $page->updated_by;

            if ($page->isDirty('is_homepage') && $page->is_homepage) {
                static::where('id', '!=', $page->id)->update(['is_homepage' => false]);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'page_id')
            ->with('menu')
            ->orderBy('order');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at
            && $this->published_at->isPast();
    }

    public function getMetaTitle(): string
    {
        return $this->meta['title'] ?? $this->title;
    }

    public function getMetaDescription(): string
    {
        return $this->meta['description'] ?? $this->excerpt ?? '';
    }

    public function getMetaKeywords(): string
    {
        return $this->meta['keywords'] ?? '';
    }

    public function getUrl(): string
    {
        return route('page.show', $this->slug);
    }

    public function isStructuredMode(): bool
    {
        return ($this->editor_mode ?? 'structured') === 'structured';
    }

    public function isVisualMode(): bool
    {
        return ($this->editor_mode ?? 'structured') === 'visual';
    }
}
