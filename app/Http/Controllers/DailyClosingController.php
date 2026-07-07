<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\CustomerTransaction;
use App\Models\SupplierTransaction;
use App\Services\PdfExportService;
use App\Services\ExcelExportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyClosingController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $dateStart = Carbon::parse($date)->startOfDay();
        $dateEnd = Carbon::parse($date)->endOfDay();

        // Compute metrics
        $retailSales = Sale::where('type', 'retail')->whereBetween('created_at', [$dateStart, $dateEnd])->sum('paid_amount');
        $wholesaleSales = Sale::where('type', 'wholesale')->whereBetween('created_at', [$dateStart, $dateEnd])->sum('paid_amount');
        $customerCollections = CustomerTransaction::where('type', 'payment')->whereBetween('transaction_date', [$dateStart, $dateEnd])->sum('credit');
        $supplierPayments = SupplierTransaction::where('type', 'payment')->whereBetween('transaction_date', [$dateStart, $dateEnd])->sum('credit');
        
        // Let's assume expenses table exists or is computed somewhere. Using 0 for now as dummy.
        $expenses = 0; 
        
        $cashIn = $retailSales + $wholesaleSales + $customerCollections;
        $cashOut = $supplierPayments + $expenses;
        
        $openingCash = 0; // Fetch from previous day closing if available.
        $expectedCash = $openingCash + $cashIn - $cashOut;

        $summary = [
            'Total Cash In' => 'Rs. ' . number_format($cashIn, 2),
            'Total Cash Out' => 'Rs. ' . number_format($cashOut, 2),
            'Expected Balance' => 'Rs. ' . number_format($expectedCash, 2),
        ];

        return view('reports.daily_closing', compact('date', 'retailSales', 'wholesaleSales', 'customerCollections', 'supplierPayments', 'expenses', 'cashIn', 'cashOut', 'openingCash', 'expectedCash', 'summary'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'opening_cash' => 'required|numeric',
            'retail_sales' => 'required|numeric',
            'wholesale_sales' => 'required|numeric',
            'customer_due_collections' => 'required|numeric',
            'supplier_payments' => 'required|numeric',
            'expenses' => 'required|numeric',
            'cash_in' => 'required|numeric',
            'cash_out' => 'required|numeric',
            'expected_cash_balance' => 'required|numeric',
            'actual_cash_counted' => 'required|numeric',
            'closing_note' => 'nullable|string'
        ]);

        $difference = $request->actual_cash_counted - $request->expected_cash_balance;

        DB::table('daily_closings')->updateOrInsert(
            ['date' => $request->date],
            [
                'opening_cash' => $request->opening_cash,
                'retail_sales' => $request->retail_sales,
                'wholesale_sales' => $request->wholesale_sales,
                'customer_due_collections' => $request->customer_due_collections,
                'supplier_payments' => $request->supplier_payments,
                'expenses' => $request->expenses,
                'purchase_payments' => 0,
                'cash_in' => $request->cash_in,
                'cash_out' => $request->cash_out,
                'expected_cash_balance' => $request->expected_cash_balance,
                'actual_cash_counted' => $request->actual_cash_counted,
                'difference' => $difference,
                'closing_note' => $request->closing_note,
                'created_by' => auth()->id(),
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'Daily closing saved successfully.');
    }
}
