<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPayment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'cheque_date' => 'date',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function periods()
    {
        return $this->belongsToMany(TaxPeriod::class, 'tax_payment_allocations')
            ->withPivot('allocation_type', 'allocated_amount')
            ->withTimestamps();
    }

    public function allocations()
    {
        return $this->hasMany(TaxPaymentAllocation::class);
    }
    
    public function enterer()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
    
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
