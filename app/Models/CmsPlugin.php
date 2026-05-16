<?php

// app/Models/CmsPlugin.php (opzionale)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPlugin extends Model
{
    protected $table = 'cms_plugins';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['slug','name','enabled','meta'];
    protected $casts = [
        'enabled' => 'boolean',
        'meta'    => 'array',
    ];
}
