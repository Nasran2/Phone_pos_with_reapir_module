<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairTechnician extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['commission_rate' => 'decimal:2'];

    /** @return HasMany<RepairJob, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(RepairJob::class, 'primary_technician_id');
    }
}
