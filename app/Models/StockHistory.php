<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use \App\Traits\LogsActivity;

    protected $table = 'stock_histories';

    protected $fillable = [
        'product_id', 'store_id', 'variant_id', 'quantity_before', 'quantity_change', 'new_quantity', 
        'type', 'reference_no', 'note', 'created_by', 'reference_type', 'reference_id', 'description', 'created_at'
    ];

    // Disable default timestamps because migration has useCurrent created_at and no updated_at
    public $timestamps = false;

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'new_quantity' => 'decimal:2',
        'created_at' => 'datetime'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
