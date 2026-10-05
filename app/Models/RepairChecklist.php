<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairChecklist extends Model
{
    protected $guarded = [];

    protected $casts = ['items' => 'array'];

    /** @return BelongsTo<RepairJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class, 'repair_job_id');
    }
}
