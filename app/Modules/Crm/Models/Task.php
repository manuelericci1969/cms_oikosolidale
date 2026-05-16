<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'title',
        'description',
        'status',
        'assigned_to_id',
        'created_by_id',
        'taskable_id',
        'taskable_type',
        'due_at',
        'priority',
        'sort_order',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    // Stati possibili
    public const STATUS_START        = 'start';
    public const STATUS_ELABORAZIONE = 'elaborazione';
    public const STATUS_CONCLUSO     = 'concluso';
    public const STATUS_VERIFICATO   = 'verificato';

    public const STATUSES = [
        self::STATUS_START        => 'Start',
        self::STATUS_ELABORAZIONE => 'Elaborazione',
        self::STATUS_CONCLUSO     => 'Concluso',
        self::STATUS_VERIFICATO   => 'Verificato',
    ];

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // Relazioni
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function taskable()
    {
        return $this->morphTo();
    }

    // Scope per board Kanban (facile da riusare)

    public function notes()
    {
        return $this->hasMany(TaskNote::class)->latest();
    }

    public function scopeForBoard($query)
    {
        return $query
            ->with(['assignedTo', 'notes.user'])
            ->orderBy('sort_order')
            ->orderBy('due_at')
            ->orderBy('created_at');
    }
}
