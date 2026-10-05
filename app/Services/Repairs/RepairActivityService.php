<?php

namespace App\Services\Repairs;

use App\Models\RepairJob;

class RepairActivityService
{
    public function log(RepairJob $job, string $action, ?string $description = null): void
    {
        // Only explicitly supplied operational descriptions; never request/model payloads.
        $job->activities()->create(['action' => $action, 'description' => $description, 'user_id' => auth()->id()]);
    }
}
