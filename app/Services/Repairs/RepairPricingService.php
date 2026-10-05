<?php

namespace App\Services\Repairs;

use App\Models\Customer;
use App\Models\RepairJob;
use App\Services\CustomerTransactionService;
use App\Support\RepairMoney as Money;

class RepairPricingService
{
    public function refresh(RepairJob $job): RepairJob
    {
        $parts = 0;
        foreach ($job->parts()->where('status', 'Issued')->get() as $part) {
            $parts += Money::cents($part->line_total);
        }
        $services = 0;
        foreach ($job->charges()->get() as $charge) {
            $services += Money::cents($charge->amount);
        }
        $subtotal = $parts + $services;
        $discount = min($subtotal, $job->discount_type === 'percentage' ? Money::percent($subtotal, Money::cents($job->discount_value)) : Money::cents($job->discount_value));
        $net = $subtotal - $discount;
        $tax = $job->tax_mode === 'legacy' ? Money::cents($job->tax_amount) : ($job->tax_mode === 'inclusive' ? Money::multiplyDivide($net, Money::cents($job->tax_rate), 10000 + Money::cents($job->tax_rate), true) : Money::percent($net, Money::cents($job->tax_rate)));
        $total = $net + ($job->tax_mode === 'inclusive' ? 0 : $tax);
        $paid = 0;
        $hold = 0;
        foreach ($job->payments()->get() as $payment) {
            if ($payment->status === 'cleared') {
                $paid += Money::cents($payment->amount);
            }
            if ($payment->status === 'hold') {
                $hold += Money::cents($payment->amount);
            }
        }
        $partsDiscount = $subtotal ? Money::multiplyDivide($discount, $parts, $subtotal) : 0;
        $partsNet = $parts - $partsDiscount;
        $partsTax = $job->tax_mode === 'inclusive' && $net ? Money::multiplyDivide($tax, $partsNet, $net) : 0;
        $revenue = ['parts' => $partsNet - $partsTax, 'services' => $services - ($discount - $partsDiscount) - ($job->tax_mode === 'inclusive' ? $tax - $partsTax : 0), 'tax' => $tax];
        $old = $job->ledger_revenue ?? ['parts' => 0, 'services' => 0, 'tax' => 0];
        $delta = [];
        foreach (['parts' => '4010', 'services' => '4011', 'tax' => '2210'] as $key => $code) {
            $delta[$code] = -($revenue[$key] - ($old[$key] ?? 0));
        }
        $delta['1105'] = -(int) array_sum($delta);
        app(RepairAccountingService::class)->post($job, 'repair_billing', $delta);
        $customer = Customer::withTrashed()->whereKey($job->customer_id)->lockForUpdate()->firstOrFail();
        $due = $total - $paid - $hold;
        $dueChange = $due - Money::cents($job->ledger_due);
        if ($dueChange) {
            app(CustomerTransactionService::class)->recordTransaction($customer, $dueChange / 100, $dueChange > 0 ? 'repair_due' : 'payment', $job->job_no, 'Device service '.$job->job_no, $job);
        }
        $holdChange = $hold - Money::cents($job->ledger_hold);
        if ($holdChange) {
            $customer->setAttribute('cheque_hold_balance', Money::decimal(Money::cents($customer->cheque_hold_balance) + $holdChange));
            $customer->save();
        }
        $job->forceFill(['parts_total' => Money::decimal($parts), 'labor_charge' => Money::decimal($services), 'discount_amount' => Money::decimal($discount), 'tax_amount' => Money::decimal($tax), 'total_amount' => Money::decimal($total), 'paid_amount' => Money::decimal($paid), 'cheque_hold_amount' => Money::decimal($hold), 'balance_amount' => Money::decimal(max(0, $total - $paid)), 'ledger_due' => Money::decimal($due), 'ledger_hold' => Money::decimal($hold), 'ledger_revenue' => $revenue])->save();

        return $job;
    }
}
