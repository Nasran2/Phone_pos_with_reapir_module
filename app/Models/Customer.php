<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'points',
        'balance',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
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
