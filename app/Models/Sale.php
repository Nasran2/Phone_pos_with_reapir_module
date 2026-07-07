<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'customer_id',
        'invoice_no',
        'sale_date',
        'pos_type',
        'total_amount',
        'discount_type',
        'discount_amount',
        'tax_amount',
        'payable_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'total_cost_amount',
        'total_profit_amount',
        'status',
        'notes',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'total_cost_amount' => 'decimal:2',
        'total_profit_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'Cancelled';
        }

        if ($this->payment_status) {
            return $this->payment_status;
        }

        if ((float) $this->due_amount <= 0.009) {
            return 'Paid';
        }

        return (float) $this->paid_amount > 0 ? 'Partial' : 'Due';
    }
}
