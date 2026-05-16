<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'crm_products';

    protected $fillable = [
        'client_id',
        'name',
        'sku',
        'unit',
        'price',
        'tax_rate',
        'max_discount',
        'description',
        'website_url',
        'is_active',
        'is_promo',
        'promo_expires_at',
    ];

    protected $casts = [
        'price'            => 'float',
        'tax_rate'         => 'float',
        'max_discount'     => 'float',
        'is_active'        => 'boolean',
        'is_promo'         => 'boolean',
        'promo_expires_at' => 'date',
    ];

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
