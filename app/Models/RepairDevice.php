<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairDevice extends Model
{
    protected $guarded = [];

    protected $casts = ['unlock_secret' => 'encrypted', 'physical_condition' => 'array', 'google_lock' => 'boolean', 'apple_id_lock' => 'boolean'];

    protected $hidden = ['unlock_secret', 'pattern_lock', 'password', 'pin'];

    /** @return array<int, string> */
    public function getAccessoriesReceivedAttribute(?string $value): array
    {
        if (! $value) {
            return [];
        }
        $items = json_decode($value, true);
        if (! is_array($items)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return array_values(array_filter($items, 'is_string'));
    }

    /** @param array<int, string>|string|null $value */
    public function setAccessoriesReceivedAttribute(array|string|null $value): void
    {
        $this->attributes['accessories_received'] = is_array($value) ? json_encode($value) : $value;
    }

    /** @return BelongsTo<RepairJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class, 'repair_job_id');
    }
}
