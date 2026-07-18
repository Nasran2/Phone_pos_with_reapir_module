<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'return_no',
        'return_date',
        'total_amount',
        'refund_method',
        'refund_amount',
        'refund_status',
        'reason',
        'note',
        'created_by',
        'taxable_total',
        'included_tax_total',
        'added_tax_total',
        'tax_total',
        'rounding_amount',
        'payable_total',
        'discount_total',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
