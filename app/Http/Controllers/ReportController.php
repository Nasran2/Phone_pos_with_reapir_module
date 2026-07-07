<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Services\PdfExportService;
use App\Services\ExcelExportService;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\DamagedStock;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\CustomerTransaction;
use App\Models\SupplierTransaction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected $reportService;
    protected $pdfService;

    public function __construct(ReportService $reportService, PdfExportService $pdfService)
    {
        $this->reportService = $reportService;
        $this->pdfService = $pdfService;
    }

    public function index()
    {
        return view('reports.index');
    }

    private function handleExport($request, $title, $headers, $rows, $totals)
    {
        if ($request->query('type') === 'pdf') {
            return $this->pdfService->export($title, $headers, $rows, $totals, $request->except(['type', 'page']));
        }
        if ($request->query('type') === 'excel') {
            return ExcelExportService::export($title, $headers, $rows, $totals, $request->except(['type', 'page']));
        }
        return false;
    }

    public function sales(Request $request)
    {
        $query = Sale::with(['customer', 'cashier']);
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);

        if ($request->filled('sale_type') && $request->sale_type !== 'all') {
            $query->where('type', $request->sale_type);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $sales = $query->latest()->paginate(20)->withQueryString();

        // Calculate Summary
        $summaryQuery = clone $query;
        $totalSales = $summaryQuery->sum('grand_total');
        $totalPaid = $summaryQuery->sum('paid_amount');
        $totalDue = $summaryQuery->sum('due_amount');
        $totalDiscount = $summaryQuery->sum('discount');
        $totalInvoices = $summaryQuery->count();
        
        $summary = [
            'Total Sales' => 'Rs. ' . number_format($totalSales, 2),
            'Total Paid' => 'Rs. ' . number_format($totalPaid, 2),
            'Total Due' => 'Rs. ' . number_format($totalDue, 2),
            'Total Invoices' => $totalInvoices,
        ];

        if ($export = $this->handleExport($request, 'Sales Report', ['Invoice No', 'Date', 'Customer', 'Type', 'Total', 'Paid', 'Due'], $query->get()->map(function($s) {
            return [$s->invoice_no, $s->created_at->format('Y-m-d'), $s->customer->name ?? 'Walk-in', ucfirst($s->type), $s->grand_total, $s->paid_amount, $s->due_amount];
        }), ['Total' => '', 'Paid' => $totalPaid, 'Due' => $totalDue])) return $export;

        return view('reports.sales', compact('sales', 'summary'));
    }

    public function retailSales(Request $request)
    {
        $request->merge(['sale_type' => 'retail']);
        return $this->sales($request);
    }

    public function wholesaleSales(Request $request)
    {
        $request->merge(['sale_type' => 'wholesale']);
        return $this->sales($request);
    }

    public function salesReturns(Request $request)
    {
        $query = SaleReturn::with(['customer', 'sale']);
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);

        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);

        $returns = $query->latest()->paginate(20)->withQueryString();

        $summaryQuery = clone $query;
        $totalAmount = $summaryQuery->sum('return_amount');
        $totalReturns = $summaryQuery->count();

        $summary = [
            'Total Returns' => $totalReturns,
            'Total Amount' => 'Rs. ' . number_format($totalAmount, 2),
        ];

        if ($export = $this->handleExport($request, 'Sales Return Report', ['Return No', 'Date', 'Customer', 'Amount', 'Reason'], $query->get()->map(function($r) {
            return [$r->return_no, $r->created_at->format('Y-m-d'), $r->customer->name ?? '-', $r->return_amount, $r->reason];
        }), [])) return $export;

        return view('reports.sales_returns', compact('returns', 'summary'));
    }

    public function purchases(Request $request)
    {
        $query = Purchase::with(['supplier']);
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);

        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        
        $purchases = $query->latest()->paginate(20)->withQueryString();

        $summaryQuery = clone $query;
        $summary = [
            'Total Purchases' => 'Rs. ' . number_format($summaryQuery->sum('grand_total'), 2),
            'Total Paid' => 'Rs. ' . number_format($summaryQuery->sum('paid_amount'), 2),
            'Total Due' => 'Rs. ' . number_format($summaryQuery->sum('due_amount'), 2),
        ];

        if ($export = $this->handleExport($request, 'Purchase Report', ['Purchase No', 'Date', 'Supplier', 'Total', 'Paid', 'Due'], $query->get()->map(function($p) {
            return [$p->purchase_no, $p->created_at->format('Y-m-d'), $p->supplier->name ?? '-', $p->grand_total, $p->paid_amount, $p->due_amount];
        }), [])) return $export;

        return view('reports.purchases', compact('purchases', 'summary'));
    }

    public function purchaseReturns(Request $request)
    {
        $query = PurchaseReturn::with(['supplier']);
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);

        $returns = $query->latest()->paginate(20)->withQueryString();

        $summaryQuery = clone $query;
        $summary = [
            'Total Returns' => $summaryQuery->count(),
            'Total Amount' => 'Rs. ' . number_format($summaryQuery->sum('return_amount'), 2),
        ];

        if ($export = $this->handleExport($request, 'Purchase Return Report', ['Return No', 'Date', 'Supplier', 'Amount', 'Reason'], $query->get()->map(function($r) {
            return [$r->return_no, $r->created_at->format('Y-m-d'), $r->supplier->name ?? '-', $r->return_amount, $r->reason];
        }), [])) return $export;

        return view('reports.purchase_returns', compact('returns', 'summary'));
    }

    public function stock(Request $request)
    {
        $query = Product::with(['category', 'brand', 'stock']);
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        if ($request->filled('brand_id')) $query->where('brand_id', $request->brand_id);

        $products = $query->paginate(20)->withQueryString();

        $summary = [
            'Total Products' => Product::count(),
            'Low Stock Items' => Product::whereHas('stock', function($q) { $q->whereColumn('quantity', '<=', 'products.stock_alert_quantity')->where('quantity', '>', 0); })->count(),
            'Out of Stock' => Product::whereHas('stock', function($q) { $q->where('quantity', '<=', 0); })->orWhereDoesntHave('stock')->count(),
        ];

        if ($export = $this->handleExport($request, 'Stock Report', ['Product', 'Category', 'Stock', 'Cost', 'Retail', 'Wholesale'], $query->get()->map(function($p) {
            return [$p->name, $p->category->name ?? '-', $p->stock->quantity ?? 0, $p->cost_price, $p->retail_price, $p->wholesale_price];
        }), [])) return $export;

        return view('reports.stock', compact('products', 'summary'));
    }

    public function lowStock(Request $request)
    {
        $query = Product::with(['category', 'brand', 'stock'])
            ->whereHas('stock', function($q) {
                $q->whereColumn('quantity', '<=', 'products.stock_alert_quantity');
            })->orWhereDoesntHave('stock');

        $products = $query->paginate(20)->withQueryString();
        
        $summary = ['Low Stock Items' => $products->total()];

        if ($export = $this->handleExport($request, 'Low Stock Report', ['Product', 'Stock', 'Alert Qty'], $query->get()->map(function($p) {
            return [$p->name, $p->stock->quantity ?? 0, $p->stock_alert_quantity];
        }), [])) return $export;

        return view('reports.low_stock', compact('products', 'summary'));
    }

    public function stockMovement(Request $request)
    {
        $query = StockHistory::with(['product', 'creator']);
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);

        $movements = $query->latest()->paginate(20)->withQueryString();

        $summary = ['Total Movements' => $movements->total()];

        if ($export = $this->handleExport($request, 'Stock Movement Report', ['Date', 'Product', 'Type', 'Before', 'Change', 'After'], $query->get()->map(function($m) {
            return [$m->created_at->format('Y-m-d H:i'), $m->product->name ?? '-', $m->type, $m->quantity_before, $m->quantity_change, $m->new_quantity];
        }), [])) return $export;

        return view('reports.stock_movement', compact('movements', 'summary'));
    }

    public function damagedStock(Request $request)
    {
        $query = DamagedStock::with(['product']);
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);

        $damaged = $query->latest()->paginate(20)->withQueryString();

        $summaryQuery = clone $query;
        $summary = [
            'Total Damaged' => $summaryQuery->sum('quantity'),
            'Est. Loss' => 'Rs. ' . number_format($summaryQuery->sum('estimated_loss'), 2),
        ];

        if ($export = $this->handleExport($request, 'Damaged Stock Report', ['Date', 'Product', 'Qty', 'Loss', 'Reason'], $query->get()->map(function($d) {
            return [$d->created_at->format('Y-m-d'), $d->product->name ?? '-', $d->quantity, $d->estimated_loss, $d->reason];
        }), [])) return $export;

        return view('reports.damaged_stock', compact('damaged', 'summary'));
    }

    public function productProfit(Request $request)
    {
        // Simple mock for now, this normally requires complex joins
        return view('reports.product_profit', ['summary' => []]);
    }

    public function profitLoss(Request $request)
    {
        return view('reports.profit_loss', ['summary' => []]);
    }

    public function expenses(Request $request)
    {
        // Assume Expense model exists or use simple view
        return view('reports.expenses', ['summary' => []]);
    }

    public function customerDue(Request $request)
    {
        $query = Customer::where('balance', '>', 0);
        $customers = $query->paginate(20)->withQueryString();
        
        $summary = [
            'Total Due' => 'Rs. ' . number_format($query->sum('balance'), 2),
            'Customers with Due' => $query->count()
        ];

        if ($export = $this->handleExport($request, 'Customer Due Report', ['Customer', 'Phone', 'Due Balance'], $query->get()->map(function($c) {
            return [$c->name, $c->phone, $c->balance];
        }), [])) return $export;

        return view('reports.customer_due', compact('customers', 'summary'));
    }

    public function supplierDue(Request $request)
    {
        $query = Supplier::where('balance', '>', 0);
        $suppliers = $query->paginate(20)->withQueryString();
        
        $summary = [
            'Total Payable' => 'Rs. ' . number_format($query->sum('balance'), 2),
            'Suppliers to Pay' => $query->count()
        ];

        if ($export = $this->handleExport($request, 'Supplier Due Report', ['Supplier', 'Phone', 'Payable Balance'], $query->get()->map(function($s) {
            return [$s->name, $s->phone, $s->balance];
        }), [])) return $export;

        return view('reports.supplier_due', compact('suppliers', 'summary'));
    }

    public function customerPayments(Request $request)
    {
        $query = CustomerTransaction::where('type', 'payment')->with('customer');
        $query = $this->reportService->applyDateFilter($query, 'transaction_date', $request->date_filter, $request->start_date, $request->end_date);
        
        $payments = $query->latest()->paginate(20)->withQueryString();

        $summary = ['Total Collections' => 'Rs. ' . number_format((clone $query)->sum('credit'), 2)];

        if ($export = $this->handleExport($request, 'Customer Payment Report', ['Date', 'Customer', 'Amount', 'Ref'], $query->get()->map(function($p) {
            return [$p->transaction_date->format('Y-m-d'), $p->customer->name ?? '-', $p->credit, $p->reference_no];
        }), [])) return $export;

        return view('reports.customer_payments', compact('payments', 'summary'));
    }

    public function supplierPayments(Request $request)
    {
        $query = SupplierTransaction::where('type', 'payment')->with('supplier');
        $query = $this->reportService->applyDateFilter($query, 'transaction_date', $request->date_filter, $request->start_date, $request->end_date);
        
        $payments = $query->latest()->paginate(20)->withQueryString();

        $summary = ['Total Payments' => 'Rs. ' . number_format((clone $query)->sum('credit'), 2)];

        if ($export = $this->handleExport($request, 'Supplier Payment Report', ['Date', 'Supplier', 'Amount', 'Ref'], $query->get()->map(function($p) {
            return [$p->transaction_date->format('Y-m-d'), $p->supplier->name ?? '-', $p->credit, $p->reference_no];
        }), [])) return $export;

        return view('reports.supplier_payments', compact('payments', 'summary'));
    }

    public function cashBank(Request $request)
    {
        return view('reports.cash_bank', ['summary' => []]);
    }

    public function tax(Request $request)
    {
        return view('reports.tax', ['summary' => []]);
    }

    public function userActivity(Request $request)
    {
        $query = ActivityLog::with('user');
        $query = $this->reportService->applyDateFilter($query, 'created_at', $request->date_filter, $request->start_date, $request->end_date);
        
        $logs = $query->latest()->paginate(20)->withQueryString();
        $summary = ['Total Activities' => $logs->total()];

        if ($export = $this->handleExport($request, 'User Activity Report', ['Date', 'User', 'Module', 'Action', 'Description'], $query->get()->map(function($l) {
            return [$l->created_at->format('Y-m-d H:i'), $l->user->name ?? '-', $l->module, $l->action, $l->description];
        }), [])) return $export;

        return view('reports.user_activity', compact('logs', 'summary'));
    }
}
