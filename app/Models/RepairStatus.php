<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property positive-int $id
 * @property bool $is_completed
 * @property bool $is_delivered
 * @property bool $is_cancelled
 * @property bool $is_ready
 * @property bool $is_waiting_approval
 * @property bool $is_waiting_parts
 */
class RepairStatus extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'is_default' => 'boolean', 'is_completed' => 'boolean', 'is_delivered' => 'boolean', 'is_cancelled' => 'boolean', 'is_ready' => 'boolean', 'is_waiting_approval' => 'boolean', 'is_waiting_parts' => 'boolean'];

    /** @return HasMany<RepairJob, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(RepairJob::class);
    }
}
