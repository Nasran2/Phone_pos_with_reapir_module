<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{
    protected $fillable = [
        'supplier_id',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactionable()
    {
        return $this->morphTo();
    }
}
