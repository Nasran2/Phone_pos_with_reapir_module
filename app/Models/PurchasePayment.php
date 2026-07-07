<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'purchase_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'note',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
