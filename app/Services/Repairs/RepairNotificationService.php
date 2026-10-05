<?php

namespace App\Services\Repairs;

use App\Jobs\SendRepairNotification;
use App\Models\RepairJob;
use App\Support\RepairSettings;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;

class RepairNotificationService
{
    public function event(RepairJob $job, string $event): void
    {
        $message = strtr((string) RepairSettings::get('sms_template'), [
            '{customer}' => $job->customer->name, '{job_no}' => $job->job_no, '{device}' => trim(($job->device ? $job->device->brand : '').' '.($job->device ? $job->device->model : '')),
            '{status}' => $job->status_label, '{balance}' => SystemSettings::money((float) $job->balance_amount), '{business}' => SystemSettings::get('business_name', 'Phone Shop POS', 'business_settings')]);
        $job->notifications()->create(['channel' => 'Internal', 'event' => $event, 'message' => $message, 'status' => 'Sent', 'sent_at' => now()]);
        if (RepairSettings::bool('enable_sms') && SystemSettings::bool('enable_sms', false, 'notification_settings') && $job->customer->phone) {
            $notification = $job->notifications()->create(['channel' => 'SMS', 'event' => $event, 'recipient' => $job->customer->phone, 'message' => $message, 'status' => 'Pending']);
            DB::afterCommit(fn () => SendRepairNotification::dispatch($notification->id));
        }
    }
}
