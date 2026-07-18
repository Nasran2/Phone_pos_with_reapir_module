<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'default_sales_tax_profile_id',
        'default_purchase_tax_profile_id',
        'tax_classification',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function defaultSalesTaxProfile()
    {
        return $this->belongsTo(TaxRate::class, 'default_sales_tax_profile_id');
    }

    public function defaultPurchaseTaxProfile()
    {
        return $this->belongsTo(TaxRate::class, 'default_purchase_tax_profile_id');
    }
}
