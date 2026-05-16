<?php

namespace App\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $table = 'crm_quote_items';

    protected $fillable = [
        'quote_id',
        'product_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_percent',
        'tax_rate',
        'sort_order',
    ];

    protected $casts = [
        'quantity'         => 'float',
        'unit_price'       => 'float',
        'discount_percent' => 'float',
        'tax_rate'         => 'float',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
