<?php

use App\Support\RepairMoney as Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only pre-module jobs: new intake UUIDs distinguish every newly created job.
        // Import stored opening figures without reposting accounts or changing customer balances.
        DB::table('repair_jobs')->whereNull('intake_key')->whereNull('ledger_revenue')->orderBy('id')->chunkById(100, function ($jobs) {
            foreach ($jobs as $job) {
                DB::transaction(function () use ($job) {
                    $charges = DB::table('repair_charge_items')->where('repair_job_id', $job->id);
                    if (! $charges->exists()) {
                        foreach (['labor_charge' => 'Historical labor', 'diagnosis_fee' => 'Historical diagnosis', 'service_charge' => 'Historical service', 'software_charge' => 'Historical software', 'other_charge' => 'Historical other charges'] as $field => $name) {
                            if (Money::cents($job->$field)) {
                                DB::table('repair_charge_items')->insert(['repair_job_id' => $job->id, 'name' => $name, 'amount' => $job->$field, 'created_at' => $job->created_at, 'updated_at' => $job->updated_at]);
                            }
                        }
                    }
                    $parts = 0;
                    foreach (DB::table('repair_parts')->where('repair_job_id', $job->id)->where('status', 'Issued')->get() as $part) {
                        $parts += Money::cents($part->line_total);
                        DB::table('repair_parts')->where('id', $part->id)->whereNull('name_snapshot')->update(['name_snapshot' => DB::table('products')->where('id', $part->product_id)->value('name')]);
                    }
                    $services = 0;
                    foreach ($charges->get() as $charge) {
                        $services += Money::cents($charge->amount);
                    }
                    $discount = Money::cents($job->discount_amount);
                    $tax = Money::cents($job->tax_amount);
                    $total = Money::cents($job->total_amount);
                    $adjustment = $total - ($parts + $services - $discount + $tax);
                    if ($adjustment) {
                        DB::table('repair_charge_items')->insert(['repair_job_id' => $job->id, 'name' => 'Historical opening balance adjustment', 'amount' => Money::decimal($adjustment), 'created_at' => $job->created_at, 'updated_at' => $job->updated_at]);
                        $services += $adjustment;
                    }
                    $payments = 0;
                    foreach (DB::table('repair_payments')->where('repair_job_id', $job->id)->where('status', 'cleared')->get() as $payment) {
                        $payments += Money::cents($payment->amount);
                    }
                    $openingPayment = Money::cents($job->paid_amount) - $payments;
                    if ($openingPayment > 0) {
                        DB::table('repair_payments')->insert(['repair_job_id' => $job->id, 'amount' => Money::decimal($openingPayment), 'payment_date' => substr($job->created_at, 0, 10), 'payment_method' => 'Historical', 'notes' => 'Imported stored opening payment; no new account movement.', 'status' => 'cleared', 'created_at' => $job->created_at, 'updated_at' => $job->updated_at]);
                    }
                    $partsDiscount = $parts + $services > 0 ? Money::multiplyDivide($discount, $parts, $parts + $services) : 0;
                    DB::table('repair_jobs')->where('id', $job->id)->update(['tax_mode' => 'legacy', 'ledger_due' => Money::decimal($total - Money::cents($job->paid_amount) - Money::cents($job->cheque_hold_amount)), 'ledger_hold' => $job->cheque_hold_amount, 'ledger_revenue' => json_encode(['parts' => $parts - $partsDiscount, 'services' => $services - ($discount - $partsDiscount), 'tax' => $tax]), 'discount_value' => $job->discount_type === 'fixed' ? $job->discount_amount : $job->discount_value]);
                });
            }
        });
    }

    public function down(): void
    {
        // Opening financial snapshots and history remain intact.
    }
};
