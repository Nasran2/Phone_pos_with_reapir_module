<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['product_id', 'serial_number', 'status', 'purchase_id', 'sale_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
