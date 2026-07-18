<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'store_id',
        'supplier_id',
        'customer_id',
        'tax_type',
        'tax_rate_id',
        'tax_code',
        'taxable_amount',
        'tax_rate',
        'tax_amount',
        'input_or_output',
        'recoverable_amount',
        'non_recoverable_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'taxable_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'recoverable_amount' => 'decimal:2',
        'non_recoverable_amount' => 'decimal:2',
    ];

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
