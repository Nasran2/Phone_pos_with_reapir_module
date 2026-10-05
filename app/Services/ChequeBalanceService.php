<?php

namespace App\Services;

use App\Models\Cheque;
use App\Services\Repairs\RepairPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChequeBalanceService
{
    public function __construct(private SaleInventoryService $saleInventory) {}

    public function placeOnHold(Cheque $cheque): void
    {
        if ($cheque->repair_job_id && $cheque->invoice_payment_id && $cheque->cheque_type === 'received') {
            app(RepairPaymentService::class)->syncCheque($cheque, 'hold');

            return;
        }
        $amount = (float) $cheque->amount;
        if ($cheque->cheque_type === 'received') {
            if ($cheque->sale) {
                $sale = $cheque->sale()->lockForUpdate()->first();
                if ($amount > (float) $sale->due_amount + .009) {
                    throw ValidationException::withMessages(['amount' => 'Cheque amount cannot exceed the sale balance.']);
                }
                $sale->due_amount = max(0, (float) $sale->due_amount - $amount);
                $sale->cheque_hold_amount = (float) $sale->cheque_hold_amount + $amount;
                $sale->payment_status = $this->saleStatus($sale);
                $sale->save();
            }
            if ($cheque->customer) {
                $party = $cheque->customer()->lockForUpdate()->first();
                $party->balance = max(0, (float) $party->balance - $amount);
                $party->cheque_hold_balance = (float) $party->cheque_hold_balance + $amount;
                $party->save();
            }
        } else {
            if ($cheque->purchase) {
                $purchase = $cheque->purchase()->lockForUpdate()->first();
                if ($amount > (float) $purchase->due_amount + .009) {
                    throw ValidationException::withMessages(['amount' => 'Cheque amount cannot exceed the purchase balance.']);
                }
                $purchase->due_amount = max(0, (float) $purchase->due_amount - $amount);
                $purchase->cheque_hold_amount = (float) $purchase->cheque_hold_amount + $amount;
                $purchase->save();
            }
            if ($cheque->supplier) {
                $party = $cheque->supplier()->lockForUpdate()->first();
                $party->balance = max(0, (float) $party->balance - $amount);
                $party->cheque_hold_balance = (float) $party->cheque_hold_balance + $amount;
                $party->save();
            }
        }
    }

    public function pass(Cheque $cheque): void
    {
        if ($cheque->repair_job_id && $cheque->invoice_payment_id && $cheque->cheque_type === 'received') {
            app(RepairPaymentService::class)->syncCheque($cheque, 'cleared');

            return;
        }
        $amount = (float) $cheque->hold_amount;
        if ($cheque->invoice_payment_id) {
            $cheque->invoicePayment()->update(['status' => 'cleared', 'hold_amount' => 0, 'cleared_amount' => $amount]);
        }
        DB::table('sale_payments')->where('cheque_id', $cheque->id)->update(['status' => 'cleared', 'updated_at' => now()]);
        if ($cheque->cheque_type === 'received') {
            if ($cheque->sale) {
                $sale = $cheque->sale()->lockForUpdate()->first();
                $sale->cheque_hold_amount = max(0, (float) $sale->cheque_hold_amount - $amount);
                $sale->paid_amount = (float) $sale->paid_amount + $amount;
                $sale->payment_status = $this->saleStatus($sale);
                $sale->save();
                try {
                    $this->saleInventory->finalizeIfPaid($sale);
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['status' => $e->getMessage()]);
                }
            }
            if ($cheque->customer) {
                $party = $cheque->customer()->lockForUpdate()->first();
                $party->cheque_hold_balance = max(0, (float) $party->cheque_hold_balance - $amount);
                $party->save();
            }
        } else {
            if ($cheque->purchase) {
                $purchase = $cheque->purchase()->lockForUpdate()->first();
                $purchase->cheque_hold_amount = max(0, (float) $purchase->cheque_hold_amount - $amount);
                $purchase->paid_amount = (float) $purchase->paid_amount + $amount;
                $purchase->save();
            }
            if ($cheque->supplier) {
                $party = $cheque->supplier()->lockForUpdate()->first();
                $party->cheque_hold_balance = max(0, (float) $party->cheque_hold_balance - $amount);
                $party->save();
            }
        }
    }

    public function releaseToDue(Cheque $cheque, float $charge = 0): void
    {
        if ($cheque->repair_job_id && $cheque->invoice_payment_id && $cheque->cheque_type === 'received') {
            app(RepairPaymentService::class)->syncCheque($cheque, 'returned', $charge);

            return;
        }
        $amount = (float) $cheque->hold_amount;
        if ($cheque->invoice_payment_id) {
            $cheque->invoicePayment()->update(['status' => 'returned', 'hold_amount' => 0, 'cleared_amount' => 0]);
        }
        DB::table('sale_payments')->where('cheque_id', $cheque->id)->update(['status' => 'returned', 'updated_at' => now()]);
        if ($cheque->cheque_type === 'received') {
            if ($cheque->sale) {
                $sale = $cheque->sale()->lockForUpdate()->first();
                $sale->cheque_hold_amount = max(0, (float) $sale->cheque_hold_amount - $amount);
                $sale->due_amount = (float) $sale->due_amount + $amount + $charge;
                $sale->payment_status = $this->saleStatus($sale);
                $sale->save();
            }
            if ($cheque->customer) {
                $party = $cheque->customer()->lockForUpdate()->first();
                $party->cheque_hold_balance = max(0, (float) $party->cheque_hold_balance - $amount);
                $party->balance = (float) $party->balance + $amount + $charge;
                $party->save();
            }
        } else {
            if ($cheque->purchase) {
                $purchase = $cheque->purchase()->lockForUpdate()->first();
                $purchase->cheque_hold_amount = max(0, (float) $purchase->cheque_hold_amount - $amount);
                $purchase->due_amount = (float) $purchase->due_amount + $amount;
                $purchase->save();
            }
            if ($cheque->supplier) {
                $party = $cheque->supplier()->lockForUpdate()->first();
                $party->cheque_hold_balance = max(0, (float) $party->cheque_hold_balance - $amount);
                $party->balance = (float) $party->balance + $amount;
                $party->save();
            }
        }
    }

    private function saleStatus($sale): string
    {
        $due = (float) $sale->due_amount;
        $hold = (float) $sale->cheque_hold_amount;
        $paid = (float) $sale->paid_amount;
        if ($due <= .009 && $hold > .009) {
            return 'Cheque Hold';
        }
        if ($due > .009 && $hold > .009 && $paid > .009) {
            return 'Partial + Cheque Hold';
        }
        if ($due > .009 && $hold > .009) {
            return 'Cheque Hold';
        }
        if ($due <= .009 && $hold <= .009) {
            return 'Paid';
        }

        return $paid > .009 ? 'Partial' : 'Due';
    }
}
