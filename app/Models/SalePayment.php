<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
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

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
