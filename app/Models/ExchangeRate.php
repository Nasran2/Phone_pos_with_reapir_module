<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['currency_id', 'rate', 'effective_from', 'effective_to', 'created_by'];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
