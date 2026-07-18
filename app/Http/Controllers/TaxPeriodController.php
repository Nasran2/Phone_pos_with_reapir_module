<?php

namespace App\Http\Controllers;

use App\Models\TaxPeriod;
use Illuminate\Http\Request;

class TaxPeriodController extends Controller
{
    public function index()
    {
        $this->authorize('tax_periods.view');
        
        // Auto-generate missing months
        app(\App\Services\TaxBalanceService::class)->autoGeneratePeriods();
        
        $periods = TaxPeriod::orderBy('start_date', 'desc')->paginate(15);
        return view('tax.periods.index', compact('periods'));
    }

    public function create()
    {
        $this->authorize('tax_periods.close'); // Used for creating/closing
        return view('tax.periods.create');
    }

    public function store(Request $request)
    {
        $this->authorize('tax_periods.close');
        
        $validated = $request->validate([
            'period_name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'due_date' => 'nullable|date',
        ]);
        
        // Find last period to carry forward balances
        $lastPeriod = TaxPeriod::orderBy('end_date', 'desc')->first();
        
        $openingPayable = $lastPeriod ? $lastPeriod->closing_payable_balance : 0;
        $openingCredit = $lastPeriod ? $lastPeriod->closing_credit_balance : 0;
        
        TaxPeriod::create(array_merge($validated, [
            'opening_payable_balance' => $openingPayable,
            'opening_credit_balance' => $openingCredit,
            'period_status' => 'Open',
        ]));
        
        return redirect()->route('tax.periods.index')->with('success', 'Tax period created successfully.');
    }

    public function show(TaxPeriod $period)
    {
        $this->authorize('tax_periods.view');
        
        // Refresh calculations if it's open
        if ($period->period_status === 'Open') {
            app(\App\Services\TaxBalanceService::class)->calculatePeriodLiability($period);
        }
        
        $transactions = \Illuminate\Support\Facades\DB::table('tax_transactions')
            ->whereBetween('transaction_date', [$period->start_date, $period->end_date])
            ->get();
            
        $dailySummaries = collect();
        foreach ($transactions->groupBy('transaction_date') as $date => $dailyTransactions) {
            $dailySummaries->push([
                'date' => $date,
                'sales_count' => $dailyTransactions->where('input_or_output', 'output')->unique('reference_id')->count(),
                'purchases_count' => $dailyTransactions->where('input_or_output', 'input')->unique('reference_id')->count(),
                'taxable_sales' => $dailyTransactions->where('input_or_output', 'output')->sum('taxable_amount'),
                'output_vat' => $dailyTransactions->where('input_or_output', 'output')->sum('tax_amount'),
                'taxable_purchases' => $dailyTransactions->where('input_or_output', 'input')->sum('taxable_amount'),
                'input_vat' => $dailyTransactions->where('input_or_output', 'input')->where('tax_type', 'VAT')->sum('recoverable_amount'),
                'import_vat' => $dailyTransactions->where('input_or_output', 'input')->where('tax_type', 'Import VAT')->sum('recoverable_amount'),
            ]);
        }
        
        $dailySummaries = $dailySummaries->sortByDesc('date');
        
        return view('tax.periods.show', compact('period', 'dailySummaries'));
    }
}
