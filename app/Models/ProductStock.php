<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use \App\Traits\LogsActivity;

    protected $table = 'product_stock';

    protected $fillable = ['product_id', 'quantity'];

    protected $casts = [
        'quantity' => 'decimal:2'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
