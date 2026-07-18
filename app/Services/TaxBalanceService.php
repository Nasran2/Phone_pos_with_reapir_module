<?php

namespace App\Services;

use App\Models\TaxPeriod;
use App\Models\TaxPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxBalanceService
{
    /**
     * Automatically generate missing tax periods for every month since the first transaction.
     */
    public function autoGeneratePeriods()
    {
        $firstTransaction = DB::table('tax_transactions')->orderBy('transaction_date', 'asc')->first();
        
        if (!$firstTransaction) {
            $startDate = Carbon::now()->startOfMonth();
        } else {
            $startDate = Carbon::parse($firstTransaction->transaction_date)->startOfMonth();
        }
        
        $endDate = Carbon::now()->startOfMonth();
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $periodName = $currentDate->format('F Y'); // e.g. July 2026
            
            $exists = TaxPeriod::where('period_name', $periodName)->exists();
            
            if (!$exists) {
                // Find last period to carry forward balances
                $lastPeriod = TaxPeriod::orderBy('end_date', 'desc')->first();
                
                $openingPayable = $lastPeriod ? $lastPeriod->closing_payable_balance : 0;
                $openingCredit = $lastPeriod ? $lastPeriod->closing_credit_balance : 0;
                
                $period = TaxPeriod::create([
                    'period_name' => $periodName,
                    'start_date' => $currentDate->copy()->startOfMonth(),
                    'end_date' => $currentDate->copy()->endOfMonth(),
                    'period_status' => 'Open',
                    'opening_payable_balance' => $openingPayable,
                    'opening_credit_balance' => $openingCredit,
                ]);
                
                $this->calculatePeriodLiability($period);
            }
            
            $currentDate->addMonth();
        }
        
        // Recalculate all open periods just in case
        $openPeriods = TaxPeriod::where('period_status', 'Open')->get();
        foreach ($openPeriods as $period) {
            $this->calculatePeriodLiability($period);
        }
    }

    /**
     * Calculate and update the totals for an open tax period.
     */
    public function calculatePeriodLiability(TaxPeriod $period)
    {
        if ($period->period_status !== 'Open') {
            return false;
        }

        // Sum output tax from transactions within this period
        $outputTax = DB::table('tax_transactions')
            ->whereBetween('transaction_date', [$period->start_date, $period->end_date])
            ->where('input_or_output', 'output')
            ->sum('tax_amount');

        // Sum input tax (recoverable) from transactions within this period
        $inputTax = DB::table('tax_transactions')
            ->whereBetween('transaction_date', [$period->start_date, $period->end_date])
            ->where('input_or_output', 'input')
            ->where('tax_type', 'VAT')
            ->sum('recoverable_amount');

        // Sum import tax
        $importTax = DB::table('tax_transactions')
            ->whereBetween('transaction_date', [$period->start_date, $period->end_date])
            ->where('input_or_output', 'input')
            ->where('tax_type', 'Import VAT')
            ->sum('recoverable_amount');

        // Sum manual adjustments
        $adjustments = DB::table('tax_adjustments')
            ->where('tax_period_id', $period->id)
            ->where('status', 'Approved')
            ->sum('amount'); // + means increase liability, - means decrease

        // Total payments allocated to this period
        $totalPayments = DB::table('tax_payment_allocations')
            ->join('tax_payments', 'tax_payment_allocations.tax_payment_id', '=', 'tax_payments.id')
            ->where('tax_payment_allocations.tax_period_id', $period->id)
            ->where('tax_payments.status', 'Approved')
            ->sum('tax_payment_allocations.allocated_amount');

        // Calculate liability
        // Liability = Opening + Output - Input - Import + Adjustments
        // Negative Liability = Credit
        
        // Opening payable
        $liability = $period->opening_payable_balance;
        // Opening credit reduces liability
        $liability -= $period->opening_credit_balance;
        
        $liability += $outputTax;
        $liability -= $inputTax;
        $liability -= $importTax;
        $liability += $adjustments;
        
        $finalLiability = max(0, $liability);
        $finalCredit = $liability < 0 ? abs($liability) : 0;

        // Apply payments to find closing balance
        $closingPayable = max(0, $finalLiability - $totalPayments);
        $remainingPayment = max(0, $totalPayments - $finalLiability);
        $closingCredit = $finalCredit + $remainingPayment;

        $paymentStatus = 'Unpaid';
        if ($totalPayments >= $finalLiability && $finalLiability > 0) {
            $paymentStatus = 'Paid';
        } elseif ($totalPayments > 0 && $totalPayments < $finalLiability) {
            $paymentStatus = 'Partially Paid';
        } elseif ($finalLiability == 0) {
            $paymentStatus = 'Not Due';
        }

        if ($period->due_date && Carbon::today()->gt($period->due_date) && $closingPayable > 0) {
            $paymentStatus = 'Overdue';
        }

        $period->update([
            'output_tax' => $outputTax,
            'input_tax' => $inputTax,
            'import_tax' => $importTax,
            'adjustments' => $adjustments,
            'total_liability' => $finalLiability,
            'total_payments' => $totalPayments,
            'closing_payable_balance' => $closingPayable,
            'closing_credit_balance' => $closingCredit,
            'payment_status' => $paymentStatus,
        ]);
        
        return $period;
    }
    
    /**
     * Get Daily VAT summary for a given date
     */
    public function getDailyVatSummary($date)
    {
        $transactions = DB::table('tax_transactions')
            ->whereDate('transaction_date', $date)
            ->get();
            
        return [
            'date' => $date,
            'sales_invoices' => $transactions->where('input_or_output', 'output')->unique('reference_id')->count(),
            'purchase_invoices' => $transactions->where('input_or_output', 'input')->unique('reference_id')->count(),
            'taxable_sales' => $transactions->where('input_or_output', 'output')->sum('taxable_amount'),
            'output_vat' => $transactions->where('input_or_output', 'output')->sum('tax_amount'),
            'taxable_purchases' => $transactions->where('input_or_output', 'input')->sum('taxable_amount'),
            'input_vat' => $transactions->where('input_or_output', 'input')->where('tax_type', 'VAT')->sum('recoverable_amount'),
            'import_vat' => $transactions->where('input_or_output', 'input')->where('tax_type', 'Import VAT')->sum('recoverable_amount'),
            'net_vat' => $transactions->where('input_or_output', 'output')->sum('tax_amount') 
                         - $transactions->where('input_or_output', 'input')->sum('recoverable_amount')
        ];
    }
}
