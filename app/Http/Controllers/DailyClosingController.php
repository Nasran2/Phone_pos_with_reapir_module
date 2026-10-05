<?php

namespace App\Http\Controllers;

use App\Models\CustomerTransaction;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SupplierTransaction;
use App\Support\Features;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DailyClosingController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $dateStart = Carbon::parse($date)->startOfDay();
        $dateEnd = Carbon::parse($date)->endOfDay();

        $saleDateColumn = Schema::hasColumn('sales', 'sale_date') ? 'sale_date' : 'created_at';
        $saleTypeColumn = Schema::hasColumn('sales', 'pos_type') ? 'pos_type' : (Schema::hasColumn('sales', 'type') ? 'type' : null);
        $salePaidColumn = Schema::hasColumn('sales', 'paid_amount') ? 'paid_amount' : (Schema::hasColumn('sales', 'payable_amount') ? 'payable_amount' : 'total_amount');

        $saleQuery = Sale::query()->whereDate($saleDateColumn, $date);
        $retailSalesQuery = clone $saleQuery;
        $wholesaleSalesQuery = clone $saleQuery;

        if ($saleTypeColumn) {
            $retailSalesQuery->where($saleTypeColumn, 'retail');
            $wholesaleSalesQuery->where($saleTypeColumn, 'wholesale');
        }

        $retailSales = (float) $retailSalesQuery->sum($salePaidColumn);
        $wholesaleSales = Features::wholesaleEnabled() && $saleTypeColumn ? (float) $wholesaleSalesQuery->sum($salePaidColumn) : 0.0;
        $customerCollections = (float) CustomerTransaction::where('credit', '>', 0)->whereBetween('transaction_date', [$dateStart, $dateEnd])->sum('credit');
        $supplierPayments = (float) SupplierTransaction::where('credit', '>', 0)->whereBetween('transaction_date', [$dateStart, $dateEnd])->sum('credit');
        $expenses = Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'amount')
            ? (float) Expense::whereBetween(Schema::hasColumn('expenses', 'expense_date') ? 'expense_date' : 'created_at', [$dateStart, $dateEnd])->sum('amount')
            : 0.0;

        // Historic receipts still contribute to cash reconciliation when the channel is disabled.
        $salesReceipts = Features::wholesaleEnabled() ? $retailSales + $wholesaleSales : (float) $saleQuery->sum($salePaidColumn);
        $cashIn = $salesReceipts + $customerCollections;
        $cashOut = $supplierPayments + $expenses;

        $openingCash = 0; // Fetch from previous day closing if available.
        $expectedCash = $openingCash + $cashIn - $cashOut;

        $summary = [
            'Total Cash In' => 'Rs. '.number_format($cashIn, 2),
            'Total Cash Out' => 'Rs. '.number_format($cashOut, 2),
            'Expected Balance' => 'Rs. '.number_format($expectedCash, 2),
        ];
        $chartData = [
            'bar' => [
                'title' => 'Daily Cash Movement',
                'subtitle' => 'Cash in, cash out, and expected balance',
                'type' => 'money',
                'items' => [
                    ['label' => 'Cash In', 'value' => $cashIn],
                    ['label' => 'Cash Out', 'value' => $cashOut],
                    ['label' => 'Expected', 'value' => $expectedCash],
                ],
            ],
            'donut' => [
                'title' => 'Cash Mix',
                'subtitle' => 'Income and outgoing cash split',
                'type' => 'money',
                'items' => [
                    ['label' => 'Cash In', 'value' => $cashIn, 'color' => '#10B981'],
                    ['label' => 'Cash Out', 'value' => $cashOut, 'color' => '#EF4444'],
                ],
            ],
            'progress' => [
                'title' => 'Closing Health',
                'subtitle' => 'Computed daily totals',
                'type' => 'money',
                'items' => [
                    ['label' => Features::wholesaleEnabled() ? 'Retail Sales' : 'Sales Receipts', 'value' => Features::wholesaleEnabled() ? $retailSales : $salesReceipts, 'color' => '#2563EB'],
                    ...(Features::wholesaleEnabled() ? [['label' => 'Wholesale Sales', 'value' => $wholesaleSales, 'color' => '#8B5CF6']] : []),
                    ['label' => 'Collections', 'value' => $customerCollections, 'color' => '#10B981'],
                    ['label' => 'Supplier Payments', 'value' => $supplierPayments, 'color' => '#F59E0B'],
                ],
            ],
        ];

        return view('reports.daily_closing', compact('date', 'salesReceipts', 'retailSales', 'wholesaleSales', 'customerCollections', 'supplierPayments', 'expenses', 'cashIn', 'cashOut', 'openingCash', 'expectedCash', 'summary', 'chartData'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'opening_cash' => 'required|numeric',
            'retail_sales' => 'required|numeric',
            'wholesale_sales' => Features::wholesaleEnabled() ? 'required|numeric' : 'exclude',
            'customer_due_collections' => 'required|numeric',
            'supplier_payments' => 'required|numeric',
            'expenses' => 'required|numeric',
            'cash_in' => 'required|numeric',
            'cash_out' => 'required|numeric',
            'expected_cash_balance' => 'required|numeric',
            'actual_cash_counted' => 'required|numeric',
            'closing_note' => 'nullable|string',
        ]);

        $difference = $request->actual_cash_counted - $request->expected_cash_balance;

        DB::table('daily_closings')->updateOrInsert(
            ['date' => $request->date],
            [
                'opening_cash' => $request->opening_cash,
                'retail_sales' => $request->retail_sales,
                'wholesale_sales' => Features::wholesaleEnabled() ? $request->wholesale_sales : (DB::table('daily_closings')->where('date', $request->date)->value('wholesale_sales') ?? 0),
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
