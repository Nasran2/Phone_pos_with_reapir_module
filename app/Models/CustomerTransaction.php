<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTransaction extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'customer_id',
        'transactionable_type',
        'transactionable_id',
        'transaction_date',
        'type',
        'debit',
        'credit',
        'balance_after',
        'reference_no',
        'note',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactionable()
    {
        return $this->morphTo();
    }
}
