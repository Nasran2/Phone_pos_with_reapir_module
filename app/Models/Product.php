<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use \App\Traits\LogsActivity;

    use SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'unit_id', 'name', 'sku', 'barcode', 
        'description', 'image', 'cost_price', 'retail_price', 'wholesale_price', 
        'stock_alert_quantity', 'is_active', 'created_by', 'updated_by',
        'sales_tax_profile_id', 'purchase_tax_profile_id', 'tax_classification', 'tax_type'
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

    public function defaultSalesTaxProfile()
    {
        return $this->belongsTo(TaxRate::class, 'sales_tax_profile_id');
    }

    public function defaultPurchaseTaxProfile()
    {
        return $this->belongsTo(TaxRate::class, 'purchase_tax_profile_id');
    }

    /**
     * Get the global stock for this product (deprecated in multi-store, use getStockForStore).
     */
    public function stock()
    {
        return $this->hasOne(ProductStoreStock::class)->where('store_id', Setting::get('default_store_id', 1));
    }

    /**
     * Get all store stock entries for this product.
     */
    public function storeStocks()
    {
        return $this->hasMany(ProductStoreStock::class);
    }

    /**
     * Get stores this product is available in.
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'product_store_stock')
                    ->withPivot('quantity', 'minimum_stock', 'maximum_stock', 'last_purchase', 'last_sale')
                    ->withTimestamps();
    }

    /**
     * Get stock for a specific store.
     */
    public function getStockForStore($storeId = null)
    {
        if (!$storeId) {
            $storeId = Setting::get('default_store_id', 1);
        }
        $stock = $this->storeStocks()->where('store_id', $storeId)->first();
        return $stock ? $stock->quantity : 0;
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
