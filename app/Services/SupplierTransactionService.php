<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class SupplierTransactionService
{
    /**
     * Record a supplier transaction and update payable balance.
     */
    public function recordTransaction(
        Supplier $supplier,
        float $amount, // Positive for debit (increases payable), negative for credit (decreases payable/payment)
        string $type, // 'payment', 'due', 'refund', 'opening_balance', etc.
        ?string $referenceNo = null,
        ?string $note = null,
        ?object $source = null,
        ?int $userId = null
    ): SupplierTransaction {
        return DB::transaction(function () use (
            $supplier,
            $amount,
            $type,
            $referenceNo,
            $note,
            $source,
            $userId
        ) {
            $supplier->balance = (float) $supplier->balance + $amount;
            $supplier->save();

            $transaction = SupplierTransaction::create([
                'supplier_id' => $supplier->id,
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id' => $source->id ?? null,
                'transaction_date' => now()->toDateString(),
                'type' => $type,
                'debit' => $amount > 0 ? $amount : 0.00,
                'credit' => $amount < 0 ? abs($amount) : 0.00,
                'balance_after' => $supplier->balance,
                'reference_no' => $referenceNo,
                'note' => $note ?: str_replace('_', ' ', $type),
                'created_by' => $userId ?? auth()->id(),
            ]);

            // Add activity log
            $amountFormatted = number_format(abs($amount), 2);
            $actionLabel = $amount > 0 ? "Increased payable by Rs. {$amountFormatted}" : "Paid supplier Rs. {$amountFormatted}";
            ActivityLog::log(
                'Supplier Transaction',
                'Suppliers',
                "Supplier: {$supplier->name}. {$actionLabel}. New Balance: Rs. " . number_format($supplier->balance, 2) . ". Ref: {$referenceNo}",
                $userId
            );

            return $transaction;
        });
    }
}
