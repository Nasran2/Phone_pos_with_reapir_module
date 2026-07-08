<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductStoreStock extends Model
{
    use \App\Traits\LogsActivity;

    protected $table = 'product_store_stock';
    
    protected $fillable = ['product_id', 'store_id', 'quantity', 'minimum_stock', 'maximum_stock', 'last_purchase', 'last_sale'];

    protected $casts = [
        'last_purchase' => 'datetime',
        'last_sale' => 'datetime'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
