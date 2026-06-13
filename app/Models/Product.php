<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'unit_id', 'name', 'sku', 'barcode', 
        'description', 'image', 'cost_price', 'retail_price', 'wholesale_price', 
        'stock_alert_quantity', 'is_active', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'stock_alert_quantity' => 'integer',
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stock()
    {
        return $this->hasOne(ProductStock::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function stock_histories()
    {
        return $this->hasMany(StockHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
