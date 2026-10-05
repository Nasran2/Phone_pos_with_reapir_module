<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairAssignment extends Model
{
    protected $guarded = [];

    protected $casts = ['assigned_at' => 'datetime'];

    /** @return BelongsTo<RepairJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class, 'repair_job_id');
    }

    /** @return BelongsTo<RepairTechnician, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(RepairTechnician::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
