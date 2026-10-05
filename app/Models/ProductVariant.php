<?php

namespace App\Models;

use App\Support\Features;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * @property numeric-string $stock_quantity
 */
class ProductVariant extends Model
{
    use LogsActivity;

    /** @return array<string, mixed> */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();
        if (! Features::wholesaleEnabled()) {
            unset($attributes['wholesale_price']);
        }

        return $attributes;
    }

    protected $fillable = [
        'product_id', 'color', 'storage', 'ram', 'sku',
        'cost_price', 'retail_price', 'wholesale_price', 'stock_quantity', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'is_active' => 'boolean',
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
