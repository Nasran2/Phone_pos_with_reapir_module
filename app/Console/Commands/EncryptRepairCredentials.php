<?php

namespace App\Console\Commands;

use App\Models\RepairDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptRepairCredentials extends Command
{
    protected $signature = 'repairs:encrypt-credentials {--dry-run : Count eligible rows without changing data}';

    protected $description = 'Encrypt legacy device credentials without logging their values';

    public function handle(): int
    {
        $converted = 0;
        $conflicts = 0;
        RepairDevice::where(fn ($q) => $q->whereNotNull('pin')->orWhereNotNull('password')->orWhereNotNull('pattern_lock'))->orderBy('id')->chunkById(100, function ($devices) use (&$converted, &$conflicts) {
            foreach ($devices as $device) {
                DB::transaction(function () use ($device, &$converted, &$conflicts) {
                    $row = RepairDevice::whereKey($device->id)->lockForUpdate()->first();
                    $legacy = array_filter(['PIN' => $row->getRawOriginal('pin'), 'Password' => $row->getRawOriginal('password'), 'Pattern' => $row->getRawOriginal('pattern_lock')], fn ($v) => $v !== null && $v !== '');
                    if (count($legacy) !== 1 || $row->getRawOriginal('unlock_secret')) {
                        $conflicts++;

                        return;
                    }
                    $converted++;
                    if (! $this->option('dry-run')) {
                        $row->unlock_type = array_key_first($legacy);
                        $row->unlock_secret = reset($legacy);
                        $row->pin = null;
                        $row->password = null;
                        $row->pattern_lock = null;
                        $row->save();
                    }
                });
            }
        });
        $this->info(($this->option('dry-run') ? 'Eligible' : 'Encrypted').': '.$converted.'. Conflicting rows retained for review: '.$conflicts.'.');

        return self::SUCCESS;
    }
}
