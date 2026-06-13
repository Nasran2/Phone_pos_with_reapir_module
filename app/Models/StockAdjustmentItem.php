<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'current_stock',
        'quantity',
        'new_stock',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'quantity' => 'decimal:2',
        'new_stock' => 'decimal:2',
    ];

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
