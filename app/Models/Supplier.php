<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use \App\Traits\LogsActivity;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'tax_number',
        'balance',
        'cheque_hold_balance',
        'opening_balance',
        'bank_details',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'cheque_hold_balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }
}
