<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldSaleItem extends Model
{
    protected $fillable = [
        'hold_sale_id',
        'product_id',
        'variant_id',
        'quantity',
        'cost_price',
        'selling_price',
        'price_type',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function holdSale()
    {
        return $this->belongsTo(HoldSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
