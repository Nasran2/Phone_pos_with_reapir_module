<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HsCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'customs_duty_rate',
        'pal_rate',
        'cess_rate',
        'srid_rate',
        'sscl_rate',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];
}
