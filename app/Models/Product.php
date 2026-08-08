<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'unit_id', 'name', 'sku', 'barcode',
        'description', 'image', 'cost_price', 'retail_price', 'wholesale_price',
        'stock_alert_quantity', 'is_active', 'created_by', 'updated_by',
        'sales_tax_profile_id', 'purchase_tax_profile_id', 'tax_classification', 'tax_type',
        'tax_mode', 'tax_status',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'stock_alert_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function defaultSalesTaxProfile(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'sales_tax_profile_id');
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function defaultPurchaseTaxProfile(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'purchase_tax_profile_id');
    }

    /**
     * Get the global stock for this product (deprecated in multi-store, use getStockForStore).
     */
    /** @return HasOne<ProductStoreStock, $this> */
    public function stock(): HasOne
    {
        return $this->hasOne(ProductStoreStock::class)->where('store_id', Setting::get('default_store_id', 1));
    }

    /**
     * Get all store stock entries for this product.
     */
    /** @return HasMany<ProductStoreStock, $this> */
    public function storeStocks(): HasMany
    {
        return $this->hasMany(ProductStoreStock::class);
    }

    /**
     * Get stores this product is available in.
     */
    /** @return BelongsToMany<Store, $this> */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'product_store_stock')
            ->withPivot('quantity', 'minimum_stock', 'maximum_stock', 'last_purchase', 'last_sale')
            ->withTimestamps();
    }

    /**
     * Get stock for a specific store.
     */
    public function getStockForStore(?int $storeId = null): float
    {
        if (! $storeId) {
            $storeId = Setting::get('default_store_id', 1);
        }
        $stock = $this->storeStocks()->where('store_id', $storeId)->first();

        return $stock ? (float) $stock->quantity : 0.0;
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<ProductSerial, $this> */
    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    /** @return HasMany<StockHistory, $this> */
    public function stock_histories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
