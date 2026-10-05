<?php

namespace App\Services\Repairs;

use App\Models\Account;
use App\Models\Cheque;
use App\Models\RepairJob;
use App\Models\RepairPayment;
use App\Services\ChequeService;
use App\Support\RepairMoney as Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairPaymentService
{
    /** @param array<string, mixed> $data */
    public function add(RepairJob $job, array $data): RepairPayment
    {
        return DB::transaction(function () use ($job, $data) {
            $job = RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            if ($old = RepairPayment::where('request_key', $data['request_key'])->first()) {
                abort_unless($old->repair_job_id === $job->id, 409);

                return $old;
            }
            if ($job->repairStatus?->is_cancelled) {
                throw ValidationException::withMessages(['job' => 'Payments cannot be added to cancelled jobs.']);
            }
            $amount = Money::cents($data['amount']);
            $cheque = $data['payment_method'] === 'Cheque';
            $available = Money::cents($job->balance_amount) - Money::cents($job->cheque_hold_amount);
            if ($amount > $available && ! $data['is_advance']) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the uncovered balance. Mark an intake advance explicitly.']);
            }
            if ($data['is_advance'] && $job->finalized) {
                throw ValidationException::withMessages(['amount' => 'A finalized job cannot receive an intake advance.']);
            }
            $accountId = $data['account_id'] ?? Account::where('account_code', $data['payment_method'] === 'Cash' ? '1001' : '1002')->value('id');
            $invoice = $job->invoicePayments()->create(['payment_method' => $data['payment_method'], 'amount' => Money::decimal($amount), 'cleared_amount' => $cheque ? 0 : Money::decimal($amount), 'hold_amount' => $cheque ? Money::decimal($amount) : 0, 'status' => $cheque ? 'hold' : 'cleared', 'reference_no' => $data['reference_no'] ?? null, 'account_id' => $accountId, 'created_by' => auth()->id()]);
            $payment = $job->payments()->create(['payment_date' => $data['payment_date'], 'cleared_at' => $cheque ? null : $data['payment_date'], 'amount' => Money::decimal($amount), 'payment_method' => $data['payment_method'], 'reference_no' => $data['reference_no'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => auth()->id(), 'status' => $cheque ? 'hold' : 'cleared', 'invoice_payment_id' => $invoice->id, 'request_key' => $data['request_key']]);
            if ($cheque) {
                $record = app(ChequeService::class)->create(array_merge($data['cheque'], ['cheque_type' => 'received', 'amount' => Money::decimal($amount), 'repair_job_id' => $job->id, 'customer_id' => $job->customer_id, 'store_id' => $job->store_id, 'invoice_payment_id' => $invoice->id, 'deposit_account_id' => $accountId]));
                $payment->update(['cheque_id' => $record->id]);
            } else {
                app(RepairAccountingService::class)->payment($job, $amount, $accountId);
            }
            app(RepairPricingService::class)->refresh($job->refresh());
            if ($data['is_advance']) {
                $job->update(['advance_amount' => Money::decimal(Money::cents($job->advance_amount) + $amount)]);
            }
            app(RepairActivityService::class)->log($job, 'Payment received', $data['payment_method'].' '.Money::decimal($amount));

            return $payment;
        }, 3);
    }

    public function syncCheque(Cheque $cheque, string $state, float $charge = 0): void
    {
        // Runs inside ChequeService's transaction, even when the repair UI is disabled.
        $job = RepairJob::withTrashed()->whereKey($cheque->repair_job_id)->lockForUpdate()->firstOrFail();
        $payment = $job->payments()->where('invoice_payment_id', $cheque->invoice_payment_id)->lockForUpdate()->first();
        if (! $payment) {
            throw ValidationException::withMessages(['repair_job_id' => 'Record linked repair cheques through the repair payment workspace.']);
        }
        if (Money::cents($payment->amount) !== Money::cents($cheque->amount)) {
            throw ValidationException::withMessages(['amount' => 'A replacement repair cheque must match the original payment amount. Record a separate payment for a different amount.']);
        }
        if ($payment->status === $state && $state !== 'hold') {
            return;
        }
        $payment->update(['status' => $state, 'cheque_id' => $cheque->id, 'cleared_at' => $state === 'cleared' ? now() : null]);
        $payment->invoicePayment?->update(['status' => $state, 'hold_amount' => $state === 'hold' ? $payment->amount : 0, 'cleared_amount' => $state === 'cleared' ? $payment->amount : 0]);
        if ($charge > 0) {
            $job->charges()->create(['name' => 'Returned cheque fee', 'amount' => $charge]);
        }
        app(RepairPricingService::class)->refresh($job);
        app(RepairActivityService::class)->log($job, 'Cheque '.$state, 'Cheque payment status updated');
    }
}
