<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = ['slug','name','version','author','manifest','enabled'];
    protected $casts = [
        'enabled'  => 'bool',
        'manifest' => 'array',
    ];
}
