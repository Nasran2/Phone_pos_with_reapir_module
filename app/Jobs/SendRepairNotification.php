<?php

namespace App\Jobs;

use App\Models\RepairNotification;
use App\Support\Features;
use App\Support\RepairSettings;
use App\Support\SystemSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendRepairNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        $record = RepairNotification::find($this->notificationId);
        if (! $record || $record->status !== 'Pending') {
            return;
        }
        if (! Features::repairEnabled() || ! RepairSettings::bool('enable_sms') || ! SystemSettings::bool('enable_sms', false, 'notification_settings')) {
            $record->update(['status' => 'Failed']);

            return;
        }
        $url = SystemSettings::get('sms_api_url', null, 'notification_settings');
        $key = SystemSettings::get('sms_api_key', null, 'notification_settings');
        if (! $url || ! $key) {
            $record->update(['status' => 'Failed']);

            return;
        }
        try {
            $ok = Http::withToken($key)->timeout(10)->post($url, ['to' => $record->recipient, 'message' => $record->message])->successful();
        } catch (\Throwable) {
            $ok = false;
        }
        $record->update(['status' => $ok ? 'Sent' : 'Failed', 'sent_at' => $ok ? now() : null]);
    }
}
