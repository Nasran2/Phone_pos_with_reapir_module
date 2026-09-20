<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the POS/ERP Dashboard.
     */
    public function index(Request $request)
    {
        if (! $request->user()->hasPermission('dashboard.view')) {
            if ($request->user()->hasPermission('my_lorry.dashboard')) {
                return redirect()->route('distribution.my-lorry.dashboard');
            }
            if ($request->user()->hasPermission('retail_pos.view')) {
                return redirect()->route('pos.retail');
            }
            abort(403, 'Unauthorized action. You do not have permission to view the dashboard.');
        }

        $today = $request->input('date', date('Y-m-d'));
        $enableMultiStore = Setting::get('enable_multi_store', false);
        $stores = $enableMultiStore ? DB::table('stores')->where('status', true)->get() : collect();
        $storeId = $request->input('store_id');

        // 1. Stats Cards
        $salesQuery = DB::table('sales')->where('sale_date', $today);
        if ($enableMultiStore && $storeId) {
            $salesQuery->where('store_id', $storeId);
        }
        $totalSalesToday = (clone $salesQuery)->sum('payable_amount');
        $retailSalesToday = (clone $salesQuery)->where('pos_type', 'retail')->sum('payable_amount');
        $wholesaleSalesToday = (clone $salesQuery)->where('pos_type', 'wholesale')->sum('payable_amount');
        $totalProfitToday = (clone $salesQuery)->sum('total_profit_amount');

        $purchaseQuery = DB::table('purchases')->where('purchase_date', $today);
        if ($enableMultiStore && $storeId) {
            $purchaseQuery->where('store_id', $storeId);
        }
        $totalPurchaseToday = $purchaseQuery->sum('total_amount');

        // 2. Customer & Supplier Summary
        $totalCustomers = DB::table('customers')->count();
        $customerDue = DB::table('customers')->sum('balance');
        $totalSuppliers = DB::table('suppliers')->count();
        $supplierDue = DB::table('suppliers')->sum('balance');
        $expensesQuery = DB::table('expenses')->where('expense_date', $today);
        if ($enableMultiStore && $storeId) {
            $expensesQuery->where('store_id', $storeId);
        }
        $totalExpensesToday = $expensesQuery->sum('amount');

        // 3. Bank/Cash accounts
        $cashInHand = DB::table('accounts')->where('account_type', 'Asset')->where('account_name', 'like', '%cash%')->sum('current_balance') ?? 0.00;
        $bankBalance = DB::table('accounts')->where('account_type', 'Asset')->where('account_name', 'like', '%bank%')->sum('current_balance') ?? 0.00;

        // 3. Bottom Quick Widgets
        $totalProducts = DB::table('products')->count();

        $stockQuery = DB::table('product_store_stock');
        if ($enableMultiStore && $storeId) {
            $stockQuery->where('store_id', $storeId);
        }
        $totalStockValue = (float) $stockQuery
            ->join('products', 'products.id', '=', 'product_store_stock.product_id')
            ->sum(DB::raw('product_store_stock.quantity * products.cost_price'));

        $ordersQuery = DB::table('sales')->where('sale_date', $today);
        if ($enableMultiStore && $storeId) {
            $ordersQuery->where('store_id', $storeId);
        }
        $todayOrders = $ordersQuery->count();

        $dueQuery = DB::table('sales')->where('due_amount', '>', 0);
        if ($enableMultiStore && $storeId) {
            $dueQuery->where('store_id', $storeId);
        }
        $dueInvoices = $dueQuery->count();
        $unreadNotifications = 0;

        // 4. Recent Sales
        $rsQuery = DB::table('sales')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->select('sales.*', 'customers.name as customer_name')
            ->orderBy('sales.id', 'desc');
        if ($enableMultiStore && $storeId) {
            $rsQuery->where('sales.store_id', $storeId);
        }
        $recentSales = $rsQuery->take(5)->get();

        // 5. Recent Purchases
        $rpQuery = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->select('purchases.*', 'suppliers.name as supplier_name')
            ->orderBy('purchases.id', 'desc');
        if ($enableMultiStore && $storeId) {
            $rpQuery->where('purchases.store_id', $storeId);
        }
        $recentPurchases = $rpQuery->take(5)->get();

        // 6. Low Stock Alerts
        $lsQuery = DB::table('products')
            ->join('product_store_stock', 'product_store_stock.product_id', '=', 'products.id')
            ->whereRaw('product_store_stock.quantity <= products.stock_alert_quantity')
            ->select('products.*', 'product_store_stock.quantity as current_stock')
            ->orderBy('current_stock', 'asc');
        if ($enableMultiStore && $storeId) {
            $lsQuery->where('product_store_stock.store_id', $storeId);
        }
        $lowStockAlerts = $lsQuery->take(5)->get();

        // 7. Sales Overview Line Chart (Dynamic Sales)
        $chartPeriod = $request->input('chart_period', 'today'); // today, week, month, year
        
        if ($chartPeriod === 'today') {
            $chartLabels = ['12 AM', '3 AM', '6 AM', '9 AM', '12 PM', '3 PM', '6 PM', '9 PM'];
            $retailSalesChart = array_fill(0, 8, 0);
            $wholesaleSalesChart = array_fill(0, 8, 0);
            
            $hourExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%H', created_at) AS INTEGER)"
                : 'HOUR(created_at)';
            
            $chartQuery = DB::table('sales')
                ->select('pos_type', DB::raw("FLOOR(($hourExpression) / 3) as group_index"), DB::raw('SUM(payable_amount) as total'))
                ->where('sale_date', $today)
                ->groupBy('pos_type', 'group_index');
        } elseif ($chartPeriod === 'week') {
            $chartLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $retailSalesChart = array_fill(0, 7, 0);
            $wholesaleSalesChart = array_fill(0, 7, 0);
            
            $startOfWeek = \Carbon\Carbon::parse($today)->startOfWeek()->format('Y-m-d');
            $endOfWeek = \Carbon\Carbon::parse($today)->endOfWeek()->format('Y-m-d');
            
            $dayOfWeekExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%w', sale_date) AS INTEGER)" // 0 = Sunday
                : "DAYOFWEEK(sale_date)"; // 1 = Sunday
                
            $chartQuery = DB::table('sales')
                ->select('pos_type', DB::raw("$dayOfWeekExpression as group_index"), DB::raw('SUM(payable_amount) as total'))
                ->whereBetween('sale_date', [$startOfWeek, $endOfWeek])
                ->groupBy('pos_type', 'group_index');
        } elseif ($chartPeriod === 'month') {
            $daysInMonth = \Carbon\Carbon::parse($today)->daysInMonth;
            $chartLabels = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $chartLabels[] = (string) $i;
            }
            $retailSalesChart = array_fill(0, $daysInMonth, 0);
            $wholesaleSalesChart = array_fill(0, $daysInMonth, 0);
            
            $startOfMonth = \Carbon\Carbon::parse($today)->startOfMonth()->format('Y-m-d');
            $endOfMonth = \Carbon\Carbon::parse($today)->endOfMonth()->format('Y-m-d');
            
            $dayExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%d', sale_date) AS INTEGER)"
                : "DAY(sale_date)";
                
            $chartQuery = DB::table('sales')
                ->select('pos_type', DB::raw("$dayExpression as group_index"), DB::raw('SUM(payable_amount) as total'))
                ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
                ->groupBy('pos_type', 'group_index');
        } elseif ($chartPeriod === 'year') {
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $retailSalesChart = array_fill(0, 12, 0);
            $wholesaleSalesChart = array_fill(0, 12, 0);
            
            $startOfYear = \Carbon\Carbon::parse($today)->startOfYear()->format('Y-m-d');
            $endOfYear = \Carbon\Carbon::parse($today)->endOfYear()->format('Y-m-d');
            
            $monthExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%m', sale_date) AS INTEGER)"
                : "MONTH(sale_date)";
                
            $chartQuery = DB::table('sales')
                ->select('pos_type', DB::raw("$monthExpression as group_index"), DB::raw('SUM(payable_amount) as total'))
                ->whereBetween('sale_date', [$startOfYear, $endOfYear])
                ->groupBy('pos_type', 'group_index');
        }

        if ($enableMultiStore && $storeId) {
            $chartQuery->where('store_id', $storeId);
        }

        $chartData = $chartQuery->get();

        foreach ($chartData as $sale) {
            $index = (int) $sale->group_index;
            
            if ($chartPeriod === 'week') {
                $isSqlite = DB::connection()->getDriverName() === 'sqlite';
                if ($isSqlite) {
                    $index = $index === 0 ? 6 : $index - 1;
                } else {
                    $index = $index === 1 ? 6 : $index - 2;
                }
            } elseif ($chartPeriod === 'month') {
                $index = $index - 1; 
            } elseif ($chartPeriod === 'year') {
                $index = $index - 1;
            }
            
            if (isset($chartLabels[$index])) {
                if ($sale->pos_type === 'retail') {
                    $retailSalesChart[$index] += (float) $sale->total;
                } else {
                    $wholesaleSalesChart[$index] += (float) $sale->total;
                }
            }
        }

        // 8. Sales by Payment Method Chart Data
        $paymentMethodsData = clone $salesQuery;
        $paymentMethodsData = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.sale_date', $today);

        if ($enableMultiStore && $storeId) {
            $paymentMethodsData->where('sales.store_id', $storeId);
        }

        $paymentMethodsData = $paymentMethodsData
            ->select('sale_payments.payment_method', DB::raw('SUM(sale_payments.amount) as total'))
            ->groupBy('sale_payments.payment_method')
            ->get();

        $paymentsSummary = [
            'Cash' => 0.00,
            'Card' => 0.00,
            'Bank Transfer' => 0.00,
            'QR / Mobile Pay' => 0.00,
            'Other' => 0.00,
        ];

        // Reconcile real payments from DB into summary
        foreach ($paymentMethodsData as $payment) {
            $method = $payment->payment_method;
            if (array_key_exists($method, $paymentsSummary)) {
                $paymentsSummary[$method] = (float) $payment->total;
            } else {
                $paymentsSummary['Other'] += (float) $payment->total;
            }
        }

        // 9. Upcoming Cheques Alert
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $upcomingChequesQuery = DB::table('cheques')
            ->whereIn('status', ['pending', 'upcoming', 'due_today', 'overdue', 'deposited'])
            ->where('cheque_date', '<=', $tomorrow)
            ->orderBy('cheque_date', 'asc');

        if ($enableMultiStore && $storeId) {
            $upcomingChequesQuery->where('store_id', $storeId);
        }
        $upcomingCheques = $upcomingChequesQuery->get();

        return view('dashboard', compact(
            'totalSalesToday',
            'retailSalesToday',
            'wholesaleSalesToday',
            'totalProfitToday',
            'totalPurchaseToday',
            'totalCustomers',
            'customerDue',
            'totalSuppliers',
            'supplierDue',
            'totalExpensesToday',
            'cashInHand',
            'bankBalance',
            'totalProducts',
            'totalStockValue',
            'todayOrders',
            'dueInvoices',
            'unreadNotifications',
            'recentSales',
            'recentPurchases',
            'lowStockAlerts',
            'chartLabels',
            'retailSalesChart',
            'wholesaleSalesChart',
            'chartPeriod',
            'paymentsSummary',
            'enableMultiStore',
            'stores',
            'storeId',
            'upcomingCheques',
            'today'
        ));
    }
}
