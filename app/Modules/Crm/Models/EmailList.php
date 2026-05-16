<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailList extends Model
{
    protected $table = 'crm_email_lists';

    protected $fillable = [
        'client_id',
        'name',
        'description',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(EmailListContact::class, 'list_id');
    }
}
