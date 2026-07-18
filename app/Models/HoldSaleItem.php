<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldSaleItem extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'hold_sale_id',
        'product_id',
        'variant_id',
        'quantity',
        'cost_price',
        'selling_price',
        'price_type',
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
        'tax_amount',
        'line_total',
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
