<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairWarranty extends Model
{
    protected $guarded = [];

    protected $casts = ['starts_at' => 'date', 'expires_at' => 'date'];

    /** @return BelongsTo<RepairJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class, 'repair_job_id');
    }
}
