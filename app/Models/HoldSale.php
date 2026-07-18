<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldSale extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'customer_id',
        'reference_no',
        'hold_date',
        'pos_type',
        'total_amount',
        'discount_amount',
        'estimated_total',
        'note',
        'created_by',
        'taxable_amount',
        'included_tax_amount',
        'added_tax_amount',
        'tax_amount',
        'payable_amount',
    ];

    protected $casts = [
        'hold_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'estimated_total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(HoldSaleItem::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
