<?php

namespace App\Models;

use App\Support\Features;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
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
        'purchase_id',
        'product_id',
        'quantity',
        'unit_cost',
        'cost_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'line_total',
        'profit_margin',
        'retail_price',
        'wholesale_price',
        'subtotal',
        'gross_amount',
        'discount_type',
        'discount_value',
        'amount_after_discount',
        'tax_mode',
        'tax_status',
        'tax_rate',
        'taxable_amount',
        'included_tax_amount',
        'added_tax_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
