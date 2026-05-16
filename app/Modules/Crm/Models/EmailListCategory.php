<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailListCategory extends Model
{
    protected $table = 'crm_email_list_categories';

    protected $fillable = [
        'client_id',
        'name',
    ];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            EmailListContact::class,
            'crm_email_list_contact_category',
            'category_id',
            'contact_id'
        )->withTimestamps();
    }
}
