<?php

namespace App\Services;

use App\Support\Features;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Generate PREFIX-YYYYMMDD0001 invoice numbers with an independent daily
     * sequence for retail and wholesale sales.
     */
    public function next(string $posType): string
    {
        abort_if($posType === 'wholesale' && ! Features::wholesaleEnabled(), 404);
        $posType = $posType === 'wholesale' ? 'wholesale' : 'retail';
        $settingKey = $posType === 'wholesale' ? 'wholesale_invoice_prefix' : 'retail_invoice_prefix';
        $fallback = $posType === 'wholesale' ? 'WSL' : 'RSL';
        $prefix = strtoupper((string) SystemSettings::get($settingKey, $fallback, 'invoice_settings'));
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix) ?: $fallback;
        $sequenceDate = now()->toDateString();

        DB::table('invoice_sequences')->insertOrIgnore([
            'pos_type' => $posType,
            'sequence_date' => $sequenceDate,
            'prefix' => $prefix,
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('invoice_sequences')
            ->where('pos_type', $posType)
            ->where('sequence_date', $sequenceDate)
            ->where('prefix', $prefix)
            ->lockForUpdate()
            ->first();

        $number = max(1, (int) $sequence->next_number);
        DB::table('invoice_sequences')->where('id', $sequence->id)->update([
            'next_number' => $number + 1,
            'updated_at' => now(),
        ]);

        return sprintf('%s-%s%04d', $prefix, now()->format('Ymd'), $number);
    }
}
