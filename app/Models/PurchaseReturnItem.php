<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'quantity',
        'cost_price',
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
        'subtotal' => 'decimal:2',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
