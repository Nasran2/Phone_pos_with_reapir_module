<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPaymentAllocation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(TaxPayment::class, 'tax_payment_id');
    }

    public function period()
    {
        return $this->belongsTo(TaxPeriod::class, 'tax_period_id');
    }
}
