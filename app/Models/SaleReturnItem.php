<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'refund_amount',
        'selling_price',
        'cost_price',
        'profit_amount',
        'subtotal',
        'return_type',
        'stock_action',
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
        'unit_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
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
