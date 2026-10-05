<?php

namespace App\Services\Repairs;

use App\Models\RepairJob;
use App\Support\RepairSettings;
use Illuminate\Support\Facades\DB;

class RepairNumberService
{
    public function next(): string
    {
        return DB::transaction(function () {
            $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) RepairSettings::get('job_prefix', 'REP'))) ?: 'REP', 0, 20);
            $date = now()->toDateString();
            DB::table('invoice_sequences')->insertOrIgnore(['pos_type' => 'repair', 'sequence_date' => $date, 'prefix' => $prefix, 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $seq = DB::table('invoice_sequences')->where(['pos_type' => 'repair', 'sequence_date' => $date, 'prefix' => $prefix])->lockForUpdate()->first();
            $n = max(1, (int) $seq->next_number);
            do {
                $number = sprintf('%s-%s%04d', $prefix, now()->format('Ymd'), $n++);
            } while (RepairJob::withTrashed()->where('job_no', $number)->exists());
            DB::table('invoice_sequences')->where('id', $seq->id)->update(['next_number' => $n, 'updated_at' => now()]);

            return $number;
        }, 3);
    }
}
