<?php

namespace App\Services\Repairs;

use App\Models\RepairJob;
use App\Models\RepairProblem;
use App\Models\RepairStatus;
use App\Models\RepairTechnician;
use App\Support\RepairSettings;
use App\Support\SystemSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairJobService
{
    /** @param array<string, mixed> $data */
    public function save(array $data, ?RepairJob $job = null): RepairJob
    {
        return DB::transaction(function () use ($data, $job) {
            $new = ! $job;
            if ($new && ($existing = RepairJob::where('intake_key', $data['intake_key'])->first())) {
                return $existing;
            }
            if (! $new) {
                $job = RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
                app(RepairInventoryService::class)->editable($job);
            }
            $fields = collect($data)->only(['customer_id', 'store_id', 'estimated_cost', 'problem_description', 'customer_request', 'urgent', 'is_warranty', 'expected_completion_date'])->all();
            if (! $new && (int) $fields['customer_id'] !== $job->customer_id) {
                throw ValidationException::withMessages(['customer_id' => 'The customer cannot be changed after intake; financial history belongs to the original customer.']);
            }
            if (! $new && (int) $fields['store_id'] !== (int) $job->store_id && $job->parts()->exists()) {
                throw ValidationException::withMessages(['store_id' => 'The store cannot be changed after parts are allocated.']);
            }
            $fields['estimated_cost'] = $fields['estimated_cost'] ?? 0;
            $fields['problem_snapshot'] = array_values(array_unique(array_merge($job ? ($job->problem_snapshot ?? []) : [], RepairProblem::whereIn('id', $data['problem_ids'] ?? [])->where('is_active', true)->pluck('name')->all())));
            if ($new) {
                $status = RepairStatus::where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order')->firstOrFail();
                $number = app(RepairNumberService::class)->next();
                $job = RepairJob::create(array_merge($fields, ['job_no' => $number, 'barcode' => $number, 'repair_status_id' => $status->id, 'intake_key' => $data['intake_key'], 'created_by' => auth()->id(), 'tax_mode' => SystemSettings::get('default_tax_mode', 'exclusive', 'tax_settings'), 'tax_rate' => RepairSettings::bool('enable_tax') && SystemSettings::bool('enable_tax_system', true, 'tax_settings') ? SystemSettings::number('default_sale_tax', 0, 'tax_settings') : 0]));
                $job->statusLogs()->create(['to_status' => $status->name, 'user_id' => auth()->id()]);
                $job->warranty()->create(['warranty_type' => 'Days', 'duration' => (int) RepairSettings::get('warranty_days', 30)]);
            } else {
                $job->update($fields);
            }
            $device = Arr::only($data['device'], ['device_type', 'brand', 'model', 'imei_1', 'imei_2', 'serial_number', 'color', 'storage', 'ram', 'carrier', 'battery_percent', 'google_lock', 'apple_id_lock', 'accessories_received', 'physical_condition']);
            $record = $job->device()->firstOrNew();
            $record->fill($device);
            if ($new || ! empty($data['device']['unlock_secret']) || ($data['clear_credential'] ?? false)) {
                $record->unlock_type = $data['device']['unlock_type'] ?? 'None';
                $record->unlock_secret = $record->unlock_type === 'None' ? null : ($data['device']['unlock_secret'] ?? null);
                $record->pattern_lock = null;
                $record->password = null;
                $record->pin = null;
            }
            $record->save();
            $checklist = $job->checklist()->firstOrNew();
            $items = $checklist->items ?? [];
            $items['before'] = $data['checklist'] ?? [];
            $checklist->items = $items;
            $checklist->custom_items = $data['custom_tests'] ?? null;
            $checklist->save();
            app(RepairActivityService::class)->log($job, $new ? 'Job created' : 'Intake updated');
            if ($new && (float) ($data['advance_amount'] ?? 0) > 0) {
                abort_unless(auth()->user()->hasPermission('repair.payments'), 403);
                app(RepairPaymentService::class)->add($job, ['amount' => $data['advance_amount'], 'payment_method' => $data['advance_method'] ?? 'Cash', 'payment_date' => today()->toDateString(), 'is_advance' => true, 'request_key' => $data['intake_key']]);
                $job->refresh();
            }
            if ($new) {
                app(RepairNotificationService::class)->event($job, 'created');
            }
            if ($new && ! empty($data['images'])) {
                app(RepairImageService::class)->add($job, $data['images'], 'Front');
            }

            return $job->refresh();
        }, 3);
    }

    public function assign(RepairJob $job, int $technicianId): void
    {
        DB::transaction(function () use ($job, $technicianId) {
            $job = RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            app(RepairInventoryService::class)->editable($job);
            $tech = RepairTechnician::whereKey($technicianId)->where('status', 'Active')->firstOrFail();
            if ($job->primary_technician_id === $tech->id) {
                return;
            }
            $job->update(['primary_technician_id' => $tech->id]);
            $job->assignments()->create(['technician_id' => $tech->id, 'assigned_by' => auth()->id(), 'assigned_at' => now()]);
            app(RepairActivityService::class)->log($job, 'Technician assigned', $tech->name);
        }, 3);
    }
}
