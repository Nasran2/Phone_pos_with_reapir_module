<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturn extends Model
{
    use LogsActivity;

    protected $fillable = [
        'sale_id',
        'customer_id',
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

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
