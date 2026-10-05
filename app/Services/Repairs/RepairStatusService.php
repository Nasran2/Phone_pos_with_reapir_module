<?php

namespace App\Services\Repairs;

use App\Models\RepairJob;
use App\Models\RepairStatus;
use App\Support\RepairMoney as Money;
use App\Support\RepairSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairStatusService
{
    /** @param array<string, mixed> $data */
    public function change(RepairJob $job, array $data): void
    {
        DB::transaction(function () use ($job, $data) {
            $job = RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            $status = RepairStatus::whereKey($data['repair_status_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            if ($job->repair_status_id === $status->id) {
                return;
            }
            if ($job->repairStatus?->is_delivered || $job->repairStatus?->is_cancelled) {
                throw ValidationException::withMessages(['status' => 'This job is closed. Create a linked warranty visit instead.']);
            }
            if ($status->is_completed) {
                abort_unless(auth()->user()->hasPermission('repair.complete'), 403);
            }
            if ($status->is_delivered) {
                abort_unless(auth()->user()->hasPermission('repair.deliver'), 403);
                if (RepairSettings::bool('require_full_payment') && Money::cents($job->balance_amount) > 0) {
                    throw ValidationException::withMessages(['status' => 'Clear the full balance, including pending cheques, before delivery.']);
                }
            }
            if ($status->is_cancelled) {
                if ($job->finalized) {
                    throw ValidationException::withMessages(['status' => 'A finalized job cannot be cancelled.']);
                }
                $issued = $job->parts()->where('status', 'Issued')->lockForUpdate()->get();
                if ($issued->isNotEmpty() && empty($data['return_parts'])) {
                    throw ValidationException::withMessages(['return_parts' => 'Confirm that issued parts are returned to stock before cancellation.']);
                }
                if ($issued->isNotEmpty()) {
                    abort_unless(auth()->user()->hasPermission('repair.parts'), 403);
                }
                foreach ($issued as $part) {
                    app(RepairInventoryService::class)->changeLocked($job, $part, 'return');
                }
                foreach ($job->parts()->where('status', 'Reserved')->get() as $part) {
                    $part->update(['status' => 'Returned', 'returned_at' => now()]);
                }
                app(RepairPricingService::class)->refresh($job);
            }
            $from = $job->status_label;
            $job->repair_status_id = $status->id;
            // Legacy enum is left intact; the persisted catalog ID is authoritative.
            if ($status->is_completed && ! $job->completed_at) {
                $job->completed_at = now();
            }
            if ($status->is_delivered) {
                $job->delivered_at = now();
                $job->completed_at ??= now();
            }
            $job->save();
            $job->setRelation('repairStatus', $status);
            $job->statusLogs()->create(['from_status' => $from, 'to_status' => $status->name, 'note' => $data['note'] ?? null, 'user_id' => auth()->id()]);
            app(RepairActivityService::class)->log($job, 'Status changed', $from.' → '.$status->name);
            app(RepairNotificationService::class)->event($job, $status->key);
        }, 3);
    }
}
