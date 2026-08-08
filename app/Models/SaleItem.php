<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'sale_id',
        'product_id',
        'variant_id',
        'product_serial_id',
        'quantity',
        'cost_price',
        'selling_price',
        'unit_price',
        'price_type',
        'discount_type',
        'discount_amount',
        'line_total',
        'profit_amount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'tax_classification',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productSerial()
    {
        return $this->belongsTo(ProductSerial::class);
    }

    public function returnedItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function variantLabel(): string
    {
        if (!$this->variant) {
            return '';
        }

        return collect([$this->variant->color, $this->variant->storage, $this->variant->ram])
            ->filter()
            ->implode(' / ') ?: (string) $this->variant->sku;
    }
}
