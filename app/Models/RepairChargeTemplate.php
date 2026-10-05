<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairChargeTemplate extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'default_amount' => 'decimal:2'];
}
