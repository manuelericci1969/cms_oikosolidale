<?php

namespace App\Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    protected $table = 'crm_task_notes';

    protected $fillable = [
        'task_id',
        'user_id',
        'note',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
