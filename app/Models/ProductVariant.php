<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'color', 'storage', 'ram', 'sku', 
        'cost_price', 'retail_price', 'wholesale_price', 'stock_quantity', 'is_active'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock_histories()
    {
        return $this->hasMany(StockHistory::class, 'variant_id');
    }
}
