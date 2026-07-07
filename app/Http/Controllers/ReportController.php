<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\DamagedStock;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    protected ReportService $reportService;

    protected PdfExportService $pdfService;

    public function __construct(ReportService $reportService, PdfExportService $pdfService)
    {
        $this->reportService = $reportService;
        $this->pdfService = $pdfService;
    }

    public function index()
    {
        return view('reports.index');
    }

    private function handleExport(Request $request, string $title, array $headers, $rows, array $totals = [])
    {
        if ($request->query('type') === 'pdf') {
            return $this->pdfService->export($title, $headers, $rows, $totals, $request->except(['type', 'page']));
        }

        if ($request->query('type') === 'excel') {
            return ExcelExportService::export($title, $headers, $rows, $totals, $request->except(['type', 'page']));
        }

        return false;
    }

    private function normalizeDateFilter(Request $request): void
    {
        if (! $request->filled('date_filter') && ! $request->filled('start_date') && ! $request->filled('end_date')) {
            $request->merge(['date_filter' => 'today']);
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function firstColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function value($model, array $columns, mixed $default = null): mixed
    {
        foreach ($columns as $column) {
            $value = $model->getAttribute($column);
            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    private function sumColumn(Builder $query, string $table, array $columns): float
    {
        $column = $this->firstColumn($table, $columns);

        return $column ? (float) (clone $query)->sum($column) : 0.0;
    }

    private function applyDate(Request $request, Builder $query, string $table, array $columns): Builder
    {
        $this->normalizeDateFilter($request);

        $column = $this->firstColumn($table, $columns);
        if (! $column) {
            return $query;
        }

        return $this->reportService->applyDateFilter(
            $query,
            $column,
            $request->date_filter,
            $request->start_date,
            $request->end_date
        );
    }

    private function whereLikeAny(Builder $query, string $table, array $columns, ?string $term): void
    {
        if (! filled($term)) {
            return;
        }

        $available = array_values(array_filter($columns, fn ($column) => $this->hasColumn($table, $column)));
        if ($available === []) {
            return;
        }

        $query->where(function (Builder $subQuery) use ($available, $term) {
            foreach ($available as $column) {
                $subQuery->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs. '.number_format((float) $amount, 2);
    }

    private function numberFromSummaryValue(mixed $value): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }

    private function summaryChartData(array $summary, ?string $barTitle = null, ?string $donutTitle = null, ?string $progressTitle = null): array
    {
        $colors = ['#2563EB', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#06B6D4'];
        $items = collect($summary)
            ->map(fn ($value, $label) => [
                'label' => (string) $label,
                'value' => $this->numberFromSummaryValue($value),
            ])
            ->filter(fn ($item) => $item['value'] > 0)
            ->values()
            ->map(function ($item, $index) use ($colors) {
                $item['color'] = $colors[$index % count($colors)];

                return $item;
            });

        return [
            'bar' => [
                'title' => $barTitle ?? 'Summary Trend',
                'subtitle' => 'Values from the selected filters',
                'type' => 'number',
                'items' => $items->take(8)->values()->all(),
            ],
            'donut' => [
                'title' => $donutTitle ?? 'Summary Mix',
                'subtitle' => 'Distribution from real totals',
                'type' => 'number',
                'items' => $items->take(5)->values()->all(),
            ],
            'progress' => [
                'title' => $progressTitle ?? 'Report Health',
                'subtitle' => 'Top filtered values',
                'type' => 'number',
                'items' => $items->take(4)->values()->all(),
            ],
        ];
    }

    private function salesChartData(Builder $query, string $dateColumn, string $totalColumn, float $totalPaid, float $totalDue, float $totalProfit): array
    {
        $trend = (clone $query)
            ->selectRaw("DATE({$dateColumn}) as label, SUM({$totalColumn}) as value")
            ->groupByRaw("DATE({$dateColumn})")
            ->orderByRaw("DATE({$dateColumn})")
            ->limit(14)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'value' => (float) $row->value,
            ])
            ->values()
            ->all();

        $mix = [];
        if ($this->hasColumn('sales', 'pos_type')) {
            $mix = (clone $query)
                ->selectRaw("pos_type as label, SUM({$totalColumn}) as value")
                ->groupBy('pos_type')
                ->get()
                ->map(fn ($row) => [
                    'label' => ucfirst((string) $row->label),
                    'value' => (float) $row->value,
                    'color' => $row->label === 'wholesale' ? '#8B5CF6' : '#2563EB',
                ])
                ->values()
                ->all();
        }

        return [
            'bar' => [
                'title' => 'Sales Trend',
                'subtitle' => 'Daily sales from selected filters',
                'type' => 'money',
                'items' => $trend,
            ],
            'donut' => [
                'title' => 'Retail vs Wholesale',
                'subtitle' => 'Sales value by channel',
                'type' => 'money',
                'items' => $mix,
            ],
            'progress' => [
                'title' => 'Payment Health',
                'subtitle' => 'Paid, due, and profit totals',
                'type' => 'money',
                'items' => [
                    ['label' => 'Paid', 'value' => $totalPaid, 'color' => '#10B981'],
                    ['label' => 'Due', 'value' => $totalDue, 'color' => '#F59E0B'],
                    ['label' => 'Profit', 'value' => $totalProfit, 'color' => '#2563EB'],
                ],
            ],
            'badges' => [
                'Total Sales' => 'Filtered total',
                'Invoices' => (clone $query)->count().' invoices',
                'Profit' => $this->money($totalProfit),
                'Paid' => $this->money($totalPaid),
                'Due' => $this->money($totalDue),
            ],
        ];
    }

    public function sales(Request $request)
    {
        $query = Sale::with(['customer', 'cashier']);
        $query = $this->applyDate($request, $query, 'sales', ['sale_date', 'created_at']);

        if ($request->filled('sale_type') && $request->sale_type !== 'all' && $this->hasColumn('sales', 'pos_type')) {
            $query->where('pos_type', $request->sale_type);
        }

        if ($request->filled('customer_id') && $this->hasColumn('sales', 'customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('cashier_id') && $this->hasColumn('sales', 'created_by')) {
            $query->where('created_by', $request->cashier_id);
        }

        if ($request->filled('payment_status') && $this->hasColumn('sales', 'payment_status')) {
            $query->whereRaw('LOWER(payment_status) = ?', [strtolower($request->payment_status)]);
        }

        $this->whereLikeAny($query, 'sales', ['invoice_no'], $request->input('search', $request->input('invoice_no')));

        if ($request->filled('payment_method') && Schema::hasTable('sale_payments')) {
            $query->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('payment_method', $request->payment_method));
        }

        $sales = (clone $query)->latest()->paginate(20)->withQueryString();

        $totalSales = $this->sumColumn($query, 'sales', ['payable_amount', 'grand_total', 'total_amount']);
        $totalPaid = $this->sumColumn($query, 'sales', ['paid_amount']);
        $totalDue = $this->sumColumn($query, 'sales', ['due_amount']);
        $totalProfit = $this->sumColumn($query, 'sales', ['total_profit_amount']);

        $summary = [
            'Total Sales' => $this->money($totalSales),
            'Invoices' => (clone $query)->count(),
            'Profit' => $this->money($totalProfit),
            'Paid' => $this->money($totalPaid),
            'Due' => $this->money($totalDue),
        ];
        $chartData = $this->salesChartData(
            $query,
            $this->firstColumn('sales', ['sale_date', 'created_at']) ?? 'created_at',
            $this->firstColumn('sales', ['payable_amount', 'grand_total', 'total_amount']) ?? 'id',
            $totalPaid,
            $totalDue,
            $totalProfit
        );

        if ($export = $this->handleExport($request, 'Sales Report', ['Invoice No', 'Date', 'Customer', 'Type', 'Subtotal', 'Discount', 'Tax', 'Grand Total', 'Paid', 'Due', 'Profit', 'Cashier'], (clone $query)->get()->map(function ($sale) {
            return [
                $sale->invoice_no,
                optional($this->value($sale, ['sale_date', 'created_at']))->format('Y-m-d'),
                $sale->customer->name ?? 'Walk-in',
                ucfirst((string) $this->value($sale, ['pos_type'], 'retail')),
                $this->value($sale, ['total_amount'], 0),
                $this->value($sale, ['discount_amount'], 0),
                $this->value($sale, ['tax_amount'], 0),
                $this->value($sale, ['payable_amount', 'grand_total', 'total_amount'], 0),
                $this->value($sale, ['paid_amount'], 0),
                $this->value($sale, ['due_amount'], 0),
                $this->value($sale, ['total_profit_amount'], 0),
                $sale->cashier->name ?? '-',
            ];
        }), ['Total' => $totalSales, 'Paid' => $totalPaid, 'Due' => $totalDue])) {
            return $export;
        }

        return view('reports.sales', compact('sales', 'summary', 'chartData'));
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
        $query = $this->applyDate($request, $query, 'sale_returns', ['return_date', 'created_at']);

        if ($request->filled('customer_id') && $this->hasColumn('sale_returns', 'customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $returns = (clone $query)->latest()->paginate(20)->withQueryString();
        $totalAmount = $this->sumColumn($query, 'sale_returns', ['total_amount', 'return_amount', 'refund_amount']);

        $summary = [
            'Total Returns' => (clone $query)->count(),
            'Total Amount' => $this->money($totalAmount),
        ];
        $chartData = $this->summaryChartData($summary, 'Sales Return Trend', 'Return Mix', 'Return Health');

        if ($export = $this->handleExport($request, 'Sales Return Report', ['Return No', 'Date', 'Customer', 'Amount', 'Reason'], (clone $query)->get()->map(function ($return) {
            return [
                $return->return_no,
                optional($this->value($return, ['return_date', 'created_at']))->format('Y-m-d'),
                $return->customer->name ?? '-',
                $this->value($return, ['total_amount', 'return_amount', 'refund_amount'], 0),
                $return->reason ?? $return->note,
            ];
        }))) {
            return $export;
        }

        return view('reports.sales_returns', compact('returns', 'summary', 'chartData'));
    }

    public function purchases(Request $request)
    {
        $query = Purchase::with(['supplier']);
        $query = $this->applyDate($request, $query, 'purchases', ['purchase_date', 'created_at']);

        if ($request->filled('supplier_id') && $this->hasColumn('purchases', 'supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $this->whereLikeAny($query, 'purchases', ['purchase_no', 'invoice_no', 'reference_no'], $request->input('search'));

        $purchases = (clone $query)->latest()->paginate(20)->withQueryString();
        $totalPurchases = $this->sumColumn($query, 'purchases', ['grand_total', 'total_amount', 'subtotal']);
        $totalPaid = $this->sumColumn($query, 'purchases', ['paid_amount']);
        $totalDue = $this->sumColumn($query, 'purchases', ['due_amount']);

        $summary = [
            'Total Purchases' => $this->money($totalPurchases),
            'Total Paid' => $this->money($totalPaid),
            'Total Due' => $this->money($totalDue),
        ];
        $chartData = $this->summaryChartData($summary, 'Purchase Totals', 'Purchase Mix', 'Payment Health');

        if ($export = $this->handleExport($request, 'Purchase Report', ['Purchase No', 'Date', 'Supplier', 'Total', 'Paid', 'Due'], (clone $query)->get()->map(function ($purchase) {
            return [
                $this->value($purchase, ['purchase_no', 'invoice_no'], '-'),
                optional($this->value($purchase, ['purchase_date', 'created_at']))->format('Y-m-d'),
                $purchase->supplier->name ?? '-',
                $this->value($purchase, ['grand_total', 'total_amount', 'subtotal'], 0),
                $this->value($purchase, ['paid_amount'], 0),
                $this->value($purchase, ['due_amount'], 0),
            ];
        }), ['Total' => $totalPurchases, 'Paid' => $totalPaid, 'Due' => $totalDue])) {
            return $export;
        }

        return view('reports.purchases', compact('purchases', 'summary', 'chartData'));
    }

    public function purchaseReturns(Request $request)
    {
        $query = PurchaseReturn::with(['supplier']);
        $query = $this->applyDate($request, $query, 'purchase_returns', ['return_date', 'created_at']);

        if ($request->filled('supplier_id') && $this->hasColumn('purchase_returns', 'supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $returns = (clone $query)->latest()->paginate(20)->withQueryString();
        $totalAmount = $this->sumColumn($query, 'purchase_returns', ['total_amount', 'return_amount', 'refund_amount']);

        $summary = [
            'Total Returns' => (clone $query)->count(),
            'Total Amount' => $this->money($totalAmount),
        ];
        $chartData = $this->summaryChartData($summary, 'Purchase Return Totals', 'Return Mix', 'Return Health');

        if ($export = $this->handleExport($request, 'Purchase Return Report', ['Return No', 'Date', 'Supplier', 'Amount', 'Reason'], (clone $query)->get()->map(function ($return) {
            return [
                $return->return_no,
                optional($this->value($return, ['return_date', 'created_at']))->format('Y-m-d'),
                $return->supplier->name ?? '-',
                $this->value($return, ['total_amount', 'return_amount', 'refund_amount'], 0),
                $return->reason ?? $return->note,
            ];
        }))) {
            return $export;
        }

        return view('reports.purchase_returns', compact('returns', 'summary', 'chartData'));
    }

    public function stock(Request $request)
    {
        $query = Product::with(['category', 'brand', 'stock']);

        if ($request->filled('category_id') && $this->hasColumn('products', 'category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id') && $this->hasColumn('products', 'brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $this->whereLikeAny($query, 'products', ['name', 'sku', 'barcode'], $request->input('search'));

        $products = (clone $query)->paginate(20)->withQueryString();

        $summary = [
            'Total Products' => Product::count(),
            'Low Stock Items' => Product::whereHas('stock', function (Builder $stockQuery) {
                $stockQuery->whereColumn('quantity', '<=', 'products.stock_alert_quantity')->where('quantity', '>', 0);
            })->count(),
            'Out of Stock' => Product::whereHas('stock', fn (Builder $stockQuery) => $stockQuery->where('quantity', '<=', 0))->orWhereDoesntHave('stock')->count(),
        ];
        $chartData = $this->summaryChartData($summary, 'Stock Position', 'Stock Mix', 'Inventory Health');

        if ($export = $this->handleExport($request, 'Stock Report', ['Product', 'Category', 'Stock', 'Cost', 'Retail', 'Wholesale'], (clone $query)->get()->map(function ($product) {
            return [
                $product->name,
                $product->category->name ?? '-',
                $product->stock->quantity ?? 0,
                $product->cost_price,
                $product->retail_price,
                $product->wholesale_price,
            ];
        }))) {
            return $export;
        }

        return view('reports.stock', compact('products', 'summary', 'chartData'));
    }

    public function lowStock(Request $request)
    {
        $query = Product::with(['category', 'brand', 'stock'])
            ->where(function (Builder $productQuery) {
                $productQuery
                    ->whereHas('stock', fn (Builder $stockQuery) => $stockQuery->whereColumn('quantity', '<=', 'products.stock_alert_quantity'))
                    ->orWhereDoesntHave('stock');
            });

        $products = (clone $query)->paginate(20)->withQueryString();
        $summary = ['Low Stock Items' => $products->total()];
        $chartData = $this->summaryChartData($summary, 'Low Stock Items', 'Low Stock Mix', 'Reorder Health');

        if ($export = $this->handleExport($request, 'Low Stock Report', ['Product', 'Stock', 'Alert Qty'], (clone $query)->get()->map(function ($product) {
            return [$product->name, $product->stock->quantity ?? 0, $product->stock_alert_quantity];
        }))) {
            return $export;
        }

        return view('reports.low_stock', compact('products', 'summary', 'chartData'));
    }

    public function stockMovement(Request $request)
    {
        $query = StockHistory::with(['product', 'creator']);
        $query = $this->applyDate($request, $query, 'stock_histories', ['created_at']);

        $movements = (clone $query)->latest()->paginate(20)->withQueryString();
        $summary = ['Total Movements' => $movements->total()];
        $chartData = $this->summaryChartData($summary, 'Stock Movement Count', 'Movement Mix', 'Movement Health');

        if ($export = $this->handleExport($request, 'Stock Movement Report', ['Date', 'Product', 'Type', 'Before', 'Change', 'After'], (clone $query)->get()->map(function ($movement) {
            return [
                optional($movement->created_at)->format('Y-m-d H:i'),
                $movement->product->name ?? '-',
                $movement->type,
                $movement->quantity_before ?? '-',
                $movement->quantity_change,
                $movement->new_quantity,
            ];
        }))) {
            return $export;
        }

        return view('reports.stock_movement', compact('movements', 'summary', 'chartData'));
    }

    public function damagedStock(Request $request)
    {
        $query = DamagedStock::with(['product']);
        $query = $this->applyDate($request, $query, 'damaged_stocks', ['created_at']);

        $damaged = (clone $query)->latest()->paginate(20)->withQueryString();

        $summary = [
            'Total Damaged' => $this->sumColumn($query, 'damaged_stocks', ['quantity']),
            'Est. Loss' => $this->money($this->sumColumn($query, 'damaged_stocks', ['estimated_loss'])),
        ];
        $chartData = $this->summaryChartData($summary, 'Damaged Stock Totals', 'Damage Mix', 'Damage Health');

        if ($export = $this->handleExport($request, 'Damaged Stock Report', ['Date', 'Product', 'Qty', 'Loss', 'Reason'], (clone $query)->get()->map(function ($damagedStock) {
            return [
                optional($damagedStock->created_at)->format('Y-m-d'),
                $damagedStock->product->name ?? '-',
                $damagedStock->quantity,
                $damagedStock->estimated_loss,
                $damagedStock->reason,
            ];
        }))) {
            return $export;
        }

        return view('reports.damaged_stock', compact('damaged', 'summary', 'chartData'));
    }

    public function productProfit(Request $request)
    {
        $this->normalizeDateFilter($request);

        $dateColumn = $this->firstColumn('sales', ['sale_date', 'created_at']) ?? 'created_at';
        $quantityColumn = $this->firstColumn('sale_items', ['quantity']);
        $costColumn = $this->firstColumn('sale_items', ['cost_price']);
        $revenueColumn = $this->firstColumn('sale_items', ['line_total', 'subtotal']);
        $profitColumn = $this->firstColumn('sale_items', ['profit_amount']);

        $quantityExpr = $quantityColumn ? "sale_items.{$quantityColumn}" : '0';
        $costExpr = $costColumn ? "sale_items.{$costColumn}" : '0';
        $revenueExpr = $revenueColumn ? "sale_items.{$revenueColumn}" : '0';
        $profitExpr = $profitColumn ? "sale_items.{$profitColumn}" : "({$revenueExpr} - ({$quantityExpr} * {$costExpr}))";

        $data = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->select(
                'products.name as product_name',
                DB::raw("SUM({$quantityExpr}) as sold_qty"),
                DB::raw("SUM({$revenueExpr}) as revenue"),
                DB::raw("SUM({$quantityExpr} * {$costExpr}) as cost"),
                DB::raw("SUM({$profitExpr}) as profit")
            )
            ->when($dateColumn, fn ($query) => $this->reportService->applyDateFilter($query, 'sales.'.$dateColumn, $request->date_filter, $request->start_date, $request->end_date))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('profit')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'Revenue' => $this->money((float) $data->sum('revenue')),
            'Cost' => $this->money((float) $data->sum('cost')),
            'Gross Profit' => $this->money((float) $data->sum('profit')),
        ];
        $chartData = $this->summaryChartData($summary, 'Product Profit Totals', 'Revenue Mix', 'Profit Health');

        if ($export = $this->handleExport($request, 'Product Profit Report', ['Product', 'Sold Qty', 'Revenue', 'Cost', 'Profit'], collect($data->items())->map(fn ($row) => [
            $row->product_name,
            $row->sold_qty,
            $row->revenue,
            $row->cost,
            $row->profit,
        ]))) {
            return $export;
        }

        return view('reports.product_profit', compact('data', 'summary', 'chartData'));
    }

    public function profitLoss(Request $request)
    {
        $salesQuery = $this->applyDate($request, Sale::query(), 'sales', ['sale_date', 'created_at']);
        $expenseQuery = $this->applyDate($request, Expense::query(), 'expenses', ['expense_date', 'created_at']);

        $salesTotalColumn = $this->firstColumn('sales', ['payable_amount', 'total_amount']) ?? 'id';
        $retailQuery = clone $salesQuery;
        $wholesaleQuery = clone $salesQuery;

        if ($this->hasColumn('sales', 'pos_type')) {
            $retailQuery->where('pos_type', 'retail');
            $wholesaleQuery->where('pos_type', 'wholesale');
        }

        $retailSales = $this->hasColumn('sales', $salesTotalColumn) ? (float) $retailQuery->sum($salesTotalColumn) : 0.0;
        $wholesaleSales = $this->hasColumn('sales', 'pos_type') && $this->hasColumn('sales', $salesTotalColumn) ? (float) $wholesaleQuery->sum($salesTotalColumn) : 0.0;
        $cogs = $this->sumColumn($salesQuery, 'sales', ['total_cost_amount']);
        $grossProfit = $this->sumColumn($salesQuery, 'sales', ['total_profit_amount']);
        $expenses = $this->sumColumn($expenseQuery, 'expenses', ['amount']);
        $netProfit = $grossProfit - $expenses;

        $data = collect([
            (object) ['date' => '-', 'category' => 'Income', 'description' => 'Retail Sales', 'amount' => $retailSales],
            (object) ['date' => '-', 'category' => 'Income', 'description' => 'Wholesale Sales', 'amount' => $wholesaleSales],
            (object) ['date' => '-', 'category' => 'COGS', 'description' => 'Cost of Goods Sold', 'amount' => $cogs],
            (object) ['date' => '-', 'category' => 'Expenses', 'description' => 'Operating Expenses', 'amount' => $expenses],
            (object) ['date' => '-', 'category' => 'Net Profit', 'description' => 'Gross Profit minus Expenses', 'amount' => $netProfit],
        ]);

        $summary = [
            'Income' => $this->money($retailSales + $wholesaleSales),
            'Gross Profit' => $this->money($grossProfit),
            'Expenses' => $this->money($expenses),
            'Net Profit' => $this->money($netProfit),
        ];
        $chartData = $this->summaryChartData($summary, 'Income vs Expense', 'Profit & Loss Mix', 'Profit Health');

        if ($export = $this->handleExport($request, 'Profit & Loss Report', ['Category', 'Description', 'Amount'], $data->map(fn ($row) => [$row->category, $row->description, $row->amount]))) {
            return $export;
        }

        return view('reports.profit_loss', compact('data', 'summary', 'chartData'));
    }

    public function expenses(Request $request)
    {
        $query = Expense::with(['category', 'paidFrom', 'creator']);
        $query = $this->applyDate($request, $query, 'expenses', ['expense_date', 'created_at']);

        if ($request->filled('payment_method') && $this->hasColumn('expenses', 'payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $data = (clone $query)->latest()->paginate(20)->withQueryString();
        $summary = [
            'Total Expenses' => $this->money($this->sumColumn($query, 'expenses', ['amount'])),
            'Entries' => (clone $query)->count(),
        ];
        $chartData = $this->summaryChartData($summary, 'Expense Totals', 'Expense Mix', 'Expense Health');

        if ($export = $this->handleExport($request, 'Expense Report', ['Date', 'Category', 'Title', 'Amount', 'Payment Method', 'Paid From', 'Reference'], (clone $query)->get()->map(fn ($expense) => [
            optional($expense->expense_date ?? $expense->created_at)->format('Y-m-d'),
            $expense->category->name ?? '-',
            $expense->title ?? '-',
            $expense->amount,
            $expense->payment_method ?? '-',
            $expense->paidFrom->account_name ?? '-',
            $expense->reference_no ?? '-',
        ]))) {
            return $export;
        }

        return view('reports.expenses', compact('data', 'summary', 'chartData'));
    }

    public function customerDue(Request $request)
    {
        $balanceColumn = $this->firstColumn('customers', ['balance', 'opening_balance']);
        $query = Customer::query();

        if ($balanceColumn) {
            $query->where($balanceColumn, '>', 0);
        }

        $customers = (clone $query)->paginate(20)->withQueryString();
        $summary = [
            'Total Due' => $this->money($balanceColumn ? (clone $query)->sum($balanceColumn) : 0),
            'Customers with Due' => $customers->total(),
        ];
        $chartData = $this->summaryChartData($summary, 'Customer Due Totals', 'Due Mix', 'Collection Health');

        if ($export = $this->handleExport($request, 'Customer Due Report', ['Customer', 'Phone', 'Due Balance'], (clone $query)->get()->map(fn ($customer) => [
            $customer->name,
            $customer->phone,
            $this->value($customer, ['balance', 'opening_balance'], 0),
        ]))) {
            return $export;
        }

        return view('reports.customer_due', compact('customers', 'summary', 'chartData'));
    }

    public function supplierDue(Request $request)
    {
        $balanceColumn = $this->firstColumn('suppliers', ['balance', 'opening_balance']);
        $query = Supplier::query();

        if ($balanceColumn) {
            $query->where($balanceColumn, '>', 0);
        }

        $suppliers = (clone $query)->paginate(20)->withQueryString();
        $summary = [
            'Total Payable' => $this->money($balanceColumn ? (clone $query)->sum($balanceColumn) : 0),
            'Suppliers to Pay' => $suppliers->total(),
        ];
        $chartData = $this->summaryChartData($summary, 'Supplier Payable Totals', 'Payable Mix', 'Supplier Health');

        if ($export = $this->handleExport($request, 'Supplier Due Report', ['Supplier', 'Phone', 'Payable Balance'], (clone $query)->get()->map(fn ($supplier) => [
            $supplier->name,
            $supplier->phone,
            $this->value($supplier, ['balance', 'opening_balance'], 0),
        ]))) {
            return $export;
        }

        return view('reports.supplier_due', compact('suppliers', 'summary', 'chartData'));
    }

    public function customerPayments(Request $request)
    {
        $query = CustomerTransaction::where('credit', '>', 0)->with('customer');
        $query = $this->applyDate($request, $query, 'customer_transactions', ['transaction_date', 'created_at']);

        $payments = (clone $query)->latest()->paginate(20)->withQueryString();
        $summary = ['Total Collections' => $this->money($this->sumColumn($query, 'customer_transactions', ['credit']))];
        $chartData = $this->summaryChartData($summary, 'Customer Collections', 'Collection Mix', 'Collection Health');

        if ($export = $this->handleExport($request, 'Customer Payment Report', ['Date', 'Customer', 'Amount', 'Ref'], (clone $query)->get()->map(fn ($payment) => [
            optional($payment->transaction_date)->format('Y-m-d'),
            $payment->customer->name ?? '-',
            $payment->credit,
            $payment->reference_no,
        ]))) {
            return $export;
        }

        return view('reports.customer_payments', compact('payments', 'summary', 'chartData'));
    }

    public function supplierPayments(Request $request)
    {
        $query = SupplierTransaction::where('credit', '>', 0)->with('supplier');
        $query = $this->applyDate($request, $query, 'supplier_transactions', ['transaction_date', 'created_at']);

        $payments = (clone $query)->latest()->paginate(20)->withQueryString();
        $summary = ['Total Payments' => $this->money($this->sumColumn($query, 'supplier_transactions', ['credit']))];
        $chartData = $this->summaryChartData($summary, 'Supplier Payments', 'Payment Mix', 'Payment Health');

        if ($export = $this->handleExport($request, 'Supplier Payment Report', ['Date', 'Supplier', 'Amount', 'Ref'], (clone $query)->get()->map(fn ($payment) => [
            optional($payment->transaction_date)->format('Y-m-d'),
            $payment->supplier->name ?? '-',
            $payment->credit,
            $payment->reference_no,
        ]))) {
            return $export;
        }

        return view('reports.supplier_payments', compact('payments', 'summary', 'chartData'));
    }

    public function cashBank(Request $request)
    {
        $data = Account::whereIn('account_type', ['Asset'])->latest()->paginate(20)->withQueryString();
        $summary = [
            'Cash & Bank Balance' => $this->money(Account::where('account_type', 'Asset')->sum('current_balance')),
            'Accounts' => $data->total(),
        ];
        $chartData = $this->summaryChartData($summary, 'Cash & Bank Position', 'Account Mix', 'Balance Health');

        if ($export = $this->handleExport($request, 'Cash & Bank Report', ['Account', 'Type', 'Balance', 'Status'], Account::where('account_type', 'Asset')->get()->map(fn ($account) => [
            $account->account_name,
            $account->account_type,
            $account->current_balance,
            $account->status ? 'Active' : 'Inactive',
        ]))) {
            return $export;
        }

        return view('reports.cash_bank', compact('data', 'summary', 'chartData'));
    }

    public function tax(Request $request)
    {
        $salesQuery = $this->applyDate($request, Sale::query(), 'sales', ['sale_date', 'created_at']);
        $purchaseQuery = $this->applyDate($request, Purchase::query(), 'purchases', ['purchase_date', 'created_at']);

        $salesTax = $this->sumColumn($salesQuery, 'sales', ['tax_amount']);
        $purchaseTax = $this->sumColumn($purchaseQuery, 'purchases', ['tax_amount']);

        $data = collect([(object) [
            'period' => ucfirst(str_replace('_', ' ', $request->date_filter ?? 'today')),
            'total_sales' => $this->sumColumn($salesQuery, 'sales', ['payable_amount', 'total_amount']),
            'tax_collected' => $salesTax,
            'tax_paid' => $purchaseTax,
        ]]);

        $summary = [
            'Tax Collected' => $this->money($salesTax),
            'Tax Paid' => $this->money($purchaseTax),
            'Net Tax' => $this->money($salesTax - $purchaseTax),
        ];
        $chartData = $this->summaryChartData($summary, 'Tax Totals', 'Tax Mix', 'Tax Health');

        if ($export = $this->handleExport($request, 'Tax Report', ['Period', 'Total Sales', 'Tax Collected', 'Tax Paid'], $data->map(fn ($row) => [
            $row->period,
            $row->total_sales,
            $row->tax_collected,
            $row->tax_paid,
        ]))) {
            return $export;
        }

        return view('reports.tax', compact('data', 'summary', 'chartData'));
    }

    public function userActivity(Request $request)
    {
        $query = ActivityLog::with('user');
        $query = $this->applyDate($request, $query, 'activity_logs', ['created_at']);

        $logs = (clone $query)->latest()->paginate(20)->withQueryString();
        $summary = ['Total Activities' => $logs->total()];
        $chartData = $this->summaryChartData($summary, 'Activity Count', 'Activity Mix', 'System Health');

        if ($export = $this->handleExport($request, 'User Activity Report', ['Date', 'User', 'Module', 'Action', 'Description'], (clone $query)->get()->map(fn ($log) => [
            optional($log->created_at)->format('Y-m-d H:i'),
            $log->user->name ?? '-',
            $log->module,
            $log->action,
            $log->description,
        ]))) {
            return $export;
        }

        return view('reports.user_activity', compact('logs', 'summary', 'chartData'));
    }
}
