<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class CustomerTransactionService
{
    /**
     * Record a customer transaction (debit or credit) and update balance.
     */
    public function recordTransaction(
        Customer $customer,
        float $amount, // Positive for debit (increases due), negative for credit (decreases due/payment)
        string $type, // 'payment', 'due', 'refund', 'opening_balance', etc.
        ?string $referenceNo = null,
        ?string $note = null,
        ?object $source = null,
        ?int $userId = null
    ): CustomerTransaction {
        return DB::transaction(function () use (
            $customer,
            $amount,
            $type,
            $referenceNo,
            $note,
            $source,
            $userId
        ) {
            $customer->balance = (float) $customer->balance + $amount;
            $customer->save();

            $transaction = CustomerTransaction::create([
                'customer_id' => $customer->id,
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id' => $source->id ?? null,
                'transaction_date' => now()->toDateString(),
                'type' => $type,
                'debit' => $amount > 0 ? $amount : 0.00,
                'credit' => $amount < 0 ? abs($amount) : 0.00,
                'balance_after' => $customer->balance,
                'reference_no' => $referenceNo,
                'note' => $note ?: str_replace('_', ' ', $type),
                'created_by' => $userId ?? auth()->id(),
            ]);

            // Add activity log
            $amountFormatted = number_format(abs($amount), 2);
            $actionLabel = $amount > 0 ? "Charged Rs. {$amountFormatted}" : "Received payment of Rs. {$amountFormatted}";
            ActivityLog::log(
                'Customer Transaction',
                'Customers',
                "Customer: {$customer->name}. {$actionLabel}. New Balance: Rs. " . number_format($customer->balance, 2) . ". Ref: {$referenceNo}",
                $userId
            );

            return $transaction;
        });
    }
}
