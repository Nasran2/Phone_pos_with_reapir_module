<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use \App\Traits\LogsActivity;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'points',
        'balance',
        'opening_balance',
        'credit_limit',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'status' => 'boolean',
        'points' => 'integer',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }
}
