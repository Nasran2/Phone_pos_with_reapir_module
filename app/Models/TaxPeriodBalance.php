<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPeriodBalance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(TaxPeriod::class, 'tax_period_id');
    }

    public function source()
    {
        return $this->morphTo();
    }
}
