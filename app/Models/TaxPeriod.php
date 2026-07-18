<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPeriod extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'opening_payable_balance' => 'decimal:2',
        'opening_credit_balance' => 'decimal:2',
        'output_tax' => 'decimal:2',
        'input_tax' => 'decimal:2',
        'import_tax' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'total_liability' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'closing_payable_balance' => 'decimal:2',
        'closing_credit_balance' => 'decimal:2',
    ];

    public function balances()
    {
        return $this->hasMany(TaxPeriodBalance::class);
    }

    public function payments()
    {
        return $this->belongsToMany(TaxPayment::class, 'tax_payment_allocations')
            ->withPivot('allocation_type', 'allocated_amount')
            ->withTimestamps();
    }

    public function adjustments()
    {
        return $this->hasMany(TaxAdjustment::class);
    }
    
    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
