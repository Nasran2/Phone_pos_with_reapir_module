<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\LorryStockService;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class DistributionController extends Controller
{
    public function __construct(private readonly LorryStockService $stockService)
    {
    }

    public function dashboard(Request $request)
    {
        $today = now()->toDateString();
        $activeTripIds = DB::table('distribution_trips')->whereIn('status', ['planned', 'active'])->pluck('id');

        $cards = [
            ['label' => 'Active Lorries', 'value' => DB::table('lorries')->whereNull('deleted_at')->where('status', 'active')->count(), 'tone' => 'blue'],
            ['label' => 'Active Trips', 'value' => $activeTripIds->count(), 'tone' => 'emerald'],
            ['label' => 'Lorry Stock Value', 'value' => $this->money($this->lorryStockValue()), 'tone' => 'indigo'],
            ['label' => 'Today Lorry Sales', 'value' => $this->money(DB::table('sales')->where('source_type', 'lorry')->whereDate('sale_date', $today)->sum('payable_amount')), 'tone' => 'amber'],
        ];

        $recentTransfers = $this->transferQuery()->latest('lorry_stock_transfers.id')->limit(8)->get();
        $lowStocks = $this->lorryStockQuery()
            ->whereColumn('lorry_stocks.quantity', '<=', 'lorry_stocks.minimum_stock')
            ->where('lorry_stocks.minimum_stock', '>', 0)
            ->limit(8)
            ->get();

        return $this->page('dashboard', compact('cards', 'recentTransfers', 'lowStocks'));
    }

    public function lorries(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'lorry_no' => ['required', 'string', 'max:50', 'unique:lorries,lorry_no'],
                'name' => ['required', 'string', 'max:255'],
                'plate_no' => ['nullable', 'string', 'max:80'],
                'driver_name' => ['nullable', 'string', 'max:255'],
                'driver_phone' => ['nullable', 'string', 'max:50'],
                'helper_name' => ['nullable', 'string', 'max:255'],
                'route_area' => ['nullable', 'string', 'max:255'],
                'capacity_note' => ['nullable', 'string', 'max:255'],
                'opening_odometer' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
                'notes' => ['nullable', 'string'],
                'assigned_user_id' => ['nullable', Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('is_active', true)->whereExists(function ($roles) {
                        $roles->selectRaw('1')->from('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
                            ->whereColumn('user_roles.user_id', 'users.id')->where('roles.name', 'Lorry Manager');
                    });
                })],
                'assignment_start_date' => ['nullable', 'date'],
                'assignment_end_date' => ['nullable', 'date', 'after_or_equal:assignment_start_date'],
            ]);

            $assignment = collect($data)->only(['assigned_user_id', 'assignment_start_date', 'assignment_end_date'])->all();
            $permissions = collect(['can_sell', 'can_create_orders', 'can_collect', 'can_add_expenses', 'can_view_cost', 'can_view_profit', 'can_reconcile'])
                ->mapWithKeys(fn ($key) => [$key => true])->all();
            $data = collect($data)->except(['assigned_user_id', 'assignment_start_date', 'assignment_end_date'])->all();
            $data['current_odometer'] = $data['opening_odometer'] ?? 0;
            $data['created_by'] = auth()->id();
            $data['created_at'] = now();
            $data['updated_at'] = now();
            $lorryId = DB::table('lorries')->insertGetId($data);
            if (! empty($assignment['assigned_user_id'])) {
                DB::table('lorry_user_assignments')->updateOrInsert(
                    ['user_id' => $assignment['assigned_user_id'], 'lorry_id' => $lorryId],
                    array_merge($permissions, [
                        'lorry_role' => 'lorry_manager', 'is_primary' => true, 'is_active' => true,
                        'active_from' => $assignment['assignment_start_date'] ?? today()->toDateString(),
                        'active_until' => $assignment['assignment_end_date'] ?? null, 'assigned_by' => auth()->id(),
                        'created_at' => now(), 'updated_at' => now(),
                    ])
                );
                DB::table('users')->where('id', $assignment['assigned_user_id'])->update([
                    'user_type' => 'lorry',
                    'default_lorry_id' => $lorryId, 'updated_at' => now(),
                ]);
            }
            ActivityLog::log('Create', 'Distribution', "Created lorry {$data['lorry_no']}");

            return back()->with('success', 'Lorry created successfully.');
        }

        $lorries = DB::table('lorries')
            ->whereNull('deleted_at')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($query) use ($request) {
                $query->where('lorry_no', 'like', "%{$request->q}%")
                    ->orWhere('name', 'like', "%{$request->q}%")
                    ->orWhere('plate_no', 'like', "%{$request->q}%")
                    ->orWhere('driver_name', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('lorry_no')
            ->paginate(15)
            ->withQueryString();

        return $this->page('lorries', compact('lorries'));
    }

    public function showLorry(int $lorry)
    {
        $lorry = DB::table('lorries')->whereNull('deleted_at')->where('id', $lorry)->firstOrFail();
        $stocks = $this->lorryStockQuery()->where('lorry_stocks.lorry_id', $lorry->id)->orderBy('products.name')->paginate(20);
        $trips = DB::table('distribution_trips')->where('lorry_id', $lorry->id)->latest('id')->limit(12)->get();
        $transfers = $this->transferQuery()
            ->where(function ($q) use ($lorry) {
                $q->where('from_lorry_id', $lorry->id)->orWhere('to_lorry_id', $lorry->id);
            })
            ->latest('lorry_stock_transfers.id')
            ->limit(12)
            ->get();

        return $this->page('lorry-profile', compact('lorry', 'stocks', 'trips', 'transfers'));
    }

    public function updateLorry(Request $request, int $lorry)
    {
        $data = $request->validate([
            'lorry_no' => ['required', 'string', 'max:50', Rule::unique('lorries', 'lorry_no')->ignore($lorry)],
            'name' => ['required', 'string', 'max:255'],
            'plate_no' => ['nullable', 'string', 'max:80'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'helper_name' => ['nullable', 'string', 'max:255'],
            'route_area' => ['nullable', 'string', 'max:255'],
            'capacity_note' => ['nullable', 'string', 'max:255'],
            'current_odometer' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $data['updated_at'] = now();
        DB::table('lorries')->where('id', $lorry)->update($data);

        return back()->with('success', 'Lorry updated successfully.');
    }

    public function destroyLorry(int $lorry)
    {
        DB::table('lorries')->where('id', $lorry)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Lorry removed from active list.');
    }

    public function stock(Request $request)
    {
        $stocks = $this->lorryStockQuery()
            ->when($request->filled('lorry_id'), fn ($q) => $q->where('lorry_stocks.lorry_id', $request->lorry_id))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($query) use ($request) {
                $query->where('products.name', 'like', "%{$request->q}%")
                    ->orWhere('products.sku', 'like', "%{$request->q}%")
                    ->orWhere('products.barcode', 'like', "%{$request->q}%");
            }))
            ->orderBy('lorries.lorry_no')
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();

        return $this->page('stock', compact('stocks'));
    }

    public function load(Request $request)
    {
        return $this->transferPage($request, 'store_to_lorry', 'Load Stock to Lorry', 'Move stock from a store into lorry inventory.', 'Load Stock');
    }

    public function returns(Request $request)
    {
        return $this->transferPage($request, 'lorry_to_store', 'Return Stock to Store', 'Bring unsold or returned stock back from a lorry to a store.', 'Return Stock');
    }

    public function lorryTransfer(Request $request)
    {
        return $this->transferPage($request, 'lorry_to_lorry', 'Lorry-to-Lorry Transfer', 'Move available inventory between two lorries.', 'Transfer Stock');
    }

    public function trips(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'lorry_id' => ['required', 'exists:lorries,id'],
                'route_name' => ['nullable', 'string', 'max:255'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'driver_id' => ['nullable', 'exists:users,id'],
                'helper_id' => ['nullable', 'exists:users,id'],
                'opening_odometer' => ['nullable', 'numeric', 'min:0'],
                'opening_cash' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['planned', 'active', 'closed', 'cancelled'])],
                'notes' => ['nullable', 'string'],
            ]);

            $data['trip_no'] = $this->nextNo('TRIP', 'distribution_trips', 'trip_no');
            $data['created_by'] = auth()->id();
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('distribution_trips')->insert($data);

            return back()->with('success', 'Distribution trip created successfully.');
        }

        $trips = $this->tripQuery()
            ->when($request->filled('lorry_id'), fn ($q) => $q->where('distribution_trips.lorry_id', $request->lorry_id))
            ->when($request->filled('status'), fn ($q) => $q->where('distribution_trips.status', $request->status))
            ->latest('distribution_trips.id')
            ->paginate(15)
            ->withQueryString();

        return $this->page('trips', compact('trips'));
    }

    public function updateTripStatus(Request $request, int $trip)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['planned', 'active', 'closed', 'cancelled'])],
            'closing_odometer' => ['nullable', 'numeric', 'min:0'],
            'closing_cash' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::table('distribution_trips')->where('id', $trip)->update(array_merge($data, ['updated_at' => now()]));

        return back()->with('success', 'Trip status updated.');
    }

    public function sales(Request $request)
    {
        $sales = DB::table('sales')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('lorries', 'lorries.id', '=', 'sales.lorry_id')
            ->leftJoin('distribution_trips', 'distribution_trips.id', '=', 'sales.trip_id')
            ->select('sales.*', 'customers.name as customer_name', 'lorries.lorry_no', 'lorries.name as lorry_name', 'distribution_trips.trip_no')
            ->where('sales.source_type', 'lorry')
            ->when($request->filled('lorry_id'), fn ($q) => $q->where('sales.lorry_id', $request->lorry_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sales.sale_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sales.sale_date', '<=', $request->to))
            ->latest('sales.id')
            ->paginate(20)
            ->withQueryString();

        return $this->page('sales', compact('sales'));
    }

    public function saleReturns(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'sale_id' => ['nullable', 'exists:sales,id'],
                'lorry_id' => ['nullable', 'exists:lorries,id'],
                'trip_id' => ['nullable', 'exists:distribution_trips,id'],
                'return_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['draft', 'approved', 'completed'])],
                'reason' => ['nullable', 'string'],
            ]);

            $data['return_no'] = $this->nextNo('LRET', 'lorry_sale_returns', 'return_no');
            $data['created_by'] = auth()->id();
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('lorry_sale_returns')->insert($data);

            return back()->with('success', 'Lorry sale return recorded.');
        }

        $returns = DB::table('lorry_sale_returns')
            ->leftJoin('lorries', 'lorries.id', '=', 'lorry_sale_returns.lorry_id')
            ->leftJoin('sales', 'sales.id', '=', 'lorry_sale_returns.sale_id')
            ->select('lorry_sale_returns.*', 'lorries.lorry_no', 'sales.invoice_no')
            ->latest('lorry_sale_returns.id')
            ->paginate(15)
            ->withQueryString();

        return $this->page('sale-returns', compact('returns'));
    }

    public function expenses(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'expense_date' => ['required', 'date'],
                'title' => ['required', 'string', 'max:255'],
                'amount' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'max:80'],
                'lorry_id' => ['nullable', 'exists:lorries,id'],
                'trip_id' => ['nullable', 'exists:distribution_trips,id'],
                'description' => ['nullable', 'string'],
            ]);

            $categoryId = DB::table('expense_categories')->where('name', 'Lorry Expense')->value('id');
            if (! $categoryId) {
                $categoryId = DB::table('expense_categories')->insertGetId([
                    'name' => 'Lorry Expense',
                    'description' => 'Distribution and lorry operating expenses',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('expenses')->insert([
                'expense_no' => $this->nextNo('LEXP', 'expenses', 'expense_no'),
                'expense_date' => $data['expense_date'],
                'expense_category_id' => $categoryId,
                'title' => $data['title'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'lorry_id' => $data['lorry_id'] ?? null,
                'trip_id' => $data['trip_id'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('success', 'Lorry expense added successfully.');
        }

        $expenses = DB::table('expenses')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->leftJoin('lorries', 'lorries.id', '=', 'expenses.lorry_id')
            ->leftJoin('distribution_trips', 'distribution_trips.id', '=', 'expenses.trip_id')
            ->select('expenses.*', 'expense_categories.name as category_name', 'lorries.lorry_no', 'distribution_trips.trip_no')
            ->whereNotNull('expenses.lorry_id')
            ->orWhereNotNull('expenses.trip_id')
            ->latest('expenses.id')
            ->paginate(15)
            ->withQueryString();

        return $this->page('expenses', compact('expenses'));
    }

    public function collections(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'lorry_id' => ['required', 'exists:lorries,id'],
                'trip_id' => ['nullable', 'exists:distribution_trips,id'],
                'collection_date' => ['required', 'date'],
                'payment_method' => ['required', 'string', 'max:80'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'reference_no' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string'],
            ]);

            $data['collection_no'] = $this->nextNo('LCOL', 'lorry_collections', 'collection_no');
            $data['created_by'] = auth()->id();
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('lorry_collections')->insert($data);

            return back()->with('success', 'Collection recorded successfully.');
        }

        $collections = DB::table('lorry_collections')
            ->leftJoin('lorries', 'lorries.id', '=', 'lorry_collections.lorry_id')
            ->leftJoin('distribution_trips', 'distribution_trips.id', '=', 'lorry_collections.trip_id')
            ->select('lorry_collections.*', 'lorries.lorry_no', 'distribution_trips.trip_no')
            ->latest('lorry_collections.id')
            ->paginate(15)
            ->withQueryString();

        return $this->page('collections', compact('collections'));
    }

    public function reconciliation(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'lorry_id' => ['required', 'exists:lorries,id'],
                'trip_id' => ['nullable', 'exists:distribution_trips,id'],
                'reconciliation_date' => ['required', 'date'],
                'counted_cash' => ['required', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
            ]);

            $totals = $this->reconciliationTotals((int) $data['lorry_id'], $data['trip_id'] ?? null);
            $expected = $totals['sales_total'] + $totals['collections_total'] - $totals['expenses_total'];

            DB::table('lorry_reconciliations')->insert([
                'reconciliation_no' => $this->nextNo('LREC', 'lorry_reconciliations', 'reconciliation_no'),
                'lorry_id' => $data['lorry_id'],
                'trip_id' => $data['trip_id'] ?? null,
                'reconciliation_date' => $data['reconciliation_date'],
                'sales_total' => $totals['sales_total'],
                'collections_total' => $totals['collections_total'],
                'expenses_total' => $totals['expenses_total'],
                'expected_cash' => $expected,
                'counted_cash' => $data['counted_cash'],
                'variance' => $data['counted_cash'] - $expected,
                'status' => 'closed',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('success', 'Daily reconciliation saved.');
        }

        $reconciliations = DB::table('lorry_reconciliations')
            ->leftJoin('lorries', 'lorries.id', '=', 'lorry_reconciliations.lorry_id')
            ->leftJoin('distribution_trips', 'distribution_trips.id', '=', 'lorry_reconciliations.trip_id')
            ->select('lorry_reconciliations.*', 'lorries.lorry_no', 'distribution_trips.trip_no')
            ->latest('lorry_reconciliations.id')
            ->paginate(15)
            ->withQueryString();

        return $this->page('reconciliation', compact('reconciliations'));
    }

    public function reports(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $lorryId = $request->input('lorry_id');

        $salesTotal = DB::table('sales')->where('source_type', 'lorry')
            ->when($lorryId, fn ($q) => $q->where('lorry_id', $lorryId))
            ->whereBetween('sale_date', [$from, $to])
            ->sum('payable_amount');
        $collectionsTotal = DB::table('lorry_collections')
            ->when($lorryId, fn ($q) => $q->where('lorry_id', $lorryId))
            ->whereBetween('collection_date', [$from, $to])
            ->sum('amount');
        $expensesTotal = DB::table('expenses')
            ->when($lorryId, fn ($q) => $q->where('lorry_id', $lorryId))
            ->whereBetween('expense_date', [$from, $to])
            ->where(function ($q) {
                $q->whereNotNull('lorry_id')->orWhereNotNull('trip_id');
            })
            ->sum('amount');

        $summary = [
            ['label' => 'Lorry Sales', 'value' => $this->money($salesTotal)],
            ['label' => 'Collections', 'value' => $this->money($collectionsTotal)],
            ['label' => 'Expenses', 'value' => $this->money($expensesTotal)],
            ['label' => 'Net Cash', 'value' => $this->money($collectionsTotal - $expensesTotal)],
        ];

        $stockByLorry = DB::table('lorry_stocks')
            ->join('lorries', 'lorries.id', '=', 'lorry_stocks.lorry_id')
            ->select('lorries.lorry_no', 'lorries.name', DB::raw('SUM(lorry_stocks.quantity) as qty'), DB::raw('SUM(lorry_stocks.quantity * products.cost_price) as value'))
            ->join('products', 'products.id', '=', 'lorry_stocks.product_id')
            ->groupBy('lorries.id', 'lorries.lorry_no', 'lorries.name')
            ->orderBy('lorries.lorry_no')
            ->get();

        return $this->page('reports', compact('summary', 'from', 'to', 'lorryId', 'stockByLorry'));
    }

    public function settings(Request $request)
    {
        if ($request->isMethod('post')) {
            $settings = $request->validate([
                'enable_distribution_module' => ['nullable', 'boolean'],
                'allow_lorry_sales_without_trip' => ['nullable', 'boolean'],
                'require_lorry_reconciliation' => ['nullable', 'boolean'],
                'prevent_negative_lorry_stock' => ['nullable', 'boolean'],
                'default_lorry_sale_payment_method' => ['nullable', 'string', 'max:80'],
            ]);

            foreach (['enable_distribution_module', 'allow_lorry_sales_without_trip', 'require_lorry_reconciliation', 'prevent_negative_lorry_stock'] as $key) {
                $settings[$key] = $request->boolean($key) ? '1' : '0';
            }

            foreach ($settings as $key => $value) {
                DB::table('business_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => (string) $value, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            return back()->with('success', 'Distribution settings saved.');
        }

        $settings = [
            'enable_distribution_module' => SystemSettings::bool('enable_distribution_module', true, 'business_settings'),
            'allow_lorry_sales_without_trip' => SystemSettings::bool('allow_lorry_sales_without_trip', true, 'business_settings'),
            'require_lorry_reconciliation' => SystemSettings::bool('require_lorry_reconciliation', false, 'business_settings'),
            'prevent_negative_lorry_stock' => SystemSettings::bool('prevent_negative_lorry_stock', true, 'business_settings'),
            'default_lorry_sale_payment_method' => SystemSettings::get('default_lorry_sale_payment_method', 'Cash', 'business_settings'),
        ];

        return $this->page('settings', compact('settings'));
    }

    public function productSearch(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', Rule::in(['store', 'lorry'])],
            'store_id' => ['nullable', 'integer'],
            'lorry_id' => ['nullable', 'integer'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $source = $request->input('source', 'store');
        $storeId = (int) $request->input('store_id', DB::table('stores')->where('is_default', true)->value('id') ?? 1);
        $lorryId = (int) $request->input('lorry_id');

        $products = DB::table('products')
            ->leftJoin('product_store_stock', function ($join) use ($storeId) {
                $join->on('product_store_stock.product_id', '=', 'products.id')
                    ->where('product_store_stock.store_id', '=', $storeId);
            })
            ->when($source === 'lorry' && $lorryId, function ($query) use ($lorryId) {
                $query->leftJoin('lorry_stocks', function ($join) use ($lorryId) {
                    $join->on('lorry_stocks.product_id', '=', 'products.id')
                        ->where('lorry_stocks.lorry_id', '=', $lorryId)
                        ->whereNull('lorry_stocks.variant_id');
                });
            })
            ->whereNull('products.deleted_at')
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('products.name', 'like', "%{$q}%")
                    ->orWhere('products.sku', 'like', "%{$q}%")
                    ->orWhere('products.barcode', 'like', "%{$q}%");
            }))
            ->select('products.id', 'products.name', 'products.sku', 'products.barcode', 'products.cost_price', 'products.wholesale_price', 'products.retail_price',
                DB::raw($source === 'lorry' && $lorryId ? 'COALESCE(lorry_stocks.quantity, 0) as stock' : 'COALESCE(product_store_stock.quantity, 0) as stock'))
            ->orderBy('products.name')
            ->limit(30)
            ->get();

        return response()->json($products);
    }

    private function transferPage(Request $request, string $type, string $title, string $subtitle, string $buttonLabel)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'transfer_date' => ['required', 'date'],
                'trip_id' => ['nullable', 'exists:distribution_trips,id'],
                'notes' => ['nullable', 'string'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'exists:products,id'],
                'items.*.variant_id' => ['nullable', 'integer'],
                'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
                'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
                'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            ];

            if ($type === 'store_to_lorry') {
                $rules['from_store_id'] = ['required', 'exists:stores,id'];
                $rules['to_lorry_id'] = ['required', 'exists:lorries,id'];
            } elseif ($type === 'lorry_to_store') {
                $rules['from_lorry_id'] = ['required', 'exists:lorries,id'];
                $rules['to_store_id'] = ['required', 'exists:stores,id'];
            } else {
                $rules['from_lorry_id'] = ['required', 'exists:lorries,id'];
                $rules['to_lorry_id'] = ['required', 'exists:lorries,id', 'different:from_lorry_id'];
            }

            $data = $request->validate($rules);

            try {
                DB::beginTransaction();
                $transferId = DB::table('lorry_stock_transfers')->insertGetId([
                    'transfer_no' => $this->nextNo($type === 'store_to_lorry' ? 'LOAD' : ($type === 'lorry_to_store' ? 'RET' : 'LTR'), 'lorry_stock_transfers', 'transfer_no'),
                    'transfer_type' => $type,
                    'from_store_id' => $data['from_store_id'] ?? null,
                    'to_store_id' => $data['to_store_id'] ?? null,
                    'from_lorry_id' => $data['from_lorry_id'] ?? null,
                    'to_lorry_id' => $data['to_lorry_id'] ?? null,
                    'trip_id' => $data['trip_id'] ?? null,
                    'transfer_date' => $data['transfer_date'],
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($data['items'] as $item) {
                    DB::table('lorry_stock_transfer_items')->insert([
                        'transfer_id' => $transferId,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'cost_price' => $item['cost_price'] ?? 0,
                        'selling_price' => $item['selling_price'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::commit();
                $this->stockService->completeTransfer($transferId);
                ActivityLog::log('Transfer', 'Distribution', "Completed {$title}");

                return redirect()->route('distribution.stock.index')->with('success', "{$title} completed successfully.");
            } catch (Throwable $e) {
                DB::rollBack();

                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $recentTransfers = $this->transferQuery()->where('transfer_type', $type)->latest('lorry_stock_transfers.id')->limit(10)->get();

        return $this->page('transfer', compact('type', 'title', 'subtitle', 'buttonLabel', 'recentTransfers'));
    }

    private function page(string $page, array $data = [])
    {
        $shared = [
            'page' => $page,
            'menu' => $this->menu(),
            'lorries' => DB::table('lorries')->whereNull('deleted_at')->orderBy('lorry_no')->get(),
            'stores' => DB::table('stores')->where('status', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'trips' => DB::table('distribution_trips')->whereIn('status', ['planned', 'active'])->orderByDesc('id')->get(),
            'users' => DB::table('users')->join('user_roles', 'user_roles.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')->where('users.is_active', true)
                ->where('roles.name', 'Lorry Manager')->select('users.id', 'users.name', 'users.email')->distinct()->orderBy('users.name')->get(),
            'paymentMethods' => DB::table('payment_methods')->where('is_active', true)->orderBy('name')->get(),
        ];

        return view('distribution.page', array_merge($shared, $data));
    }

    private function menu(): array
    {
        return [
            ['label' => 'Distribution Dashboard', 'route' => 'distribution.dashboard', 'permission' => 'distribution.dashboard'],
            ['label' => 'Lorry Management', 'route' => 'distribution.lorries.index', 'permission' => 'lorries.view'],
            ['label' => 'Lorry Stock', 'route' => 'distribution.stock.index', 'permission' => 'lorry_stock.view'],
            ['label' => 'Load Stock to Lorry', 'route' => 'distribution.load', 'permission' => 'lorry_stock.load'],
            ['label' => 'Return Stock to Store', 'route' => 'distribution.returns', 'permission' => 'lorry_stock.return'],
            ['label' => 'Lorry-to-Lorry Transfer', 'route' => 'distribution.lorry-transfer', 'permission' => 'lorry_stock.transfer'],
            ['label' => 'Lorry Sales', 'route' => 'distribution.sales', 'permission' => 'lorry_sales.view'],
            ['label' => 'Lorry Expenses', 'route' => 'distribution.expenses', 'permission' => 'lorry_expenses.view'],
            ['label' => 'Daily Reconciliation', 'route' => 'distribution.reconciliation', 'permission' => 'lorry_reconciliation.view'],
            ['label' => 'Distribution Reports', 'route' => 'distribution.reports', 'permission' => 'distribution_reports.view'],
            ['label' => 'Distribution Settings', 'route' => 'distribution.settings', 'permission' => 'distribution.settings'],
        ];
    }

    private function transferQuery()
    {
        return DB::table('lorry_stock_transfers')
            ->leftJoin('stores as from_store', 'from_store.id', '=', 'lorry_stock_transfers.from_store_id')
            ->leftJoin('stores as to_store', 'to_store.id', '=', 'lorry_stock_transfers.to_store_id')
            ->leftJoin('lorries as from_lorry', 'from_lorry.id', '=', 'lorry_stock_transfers.from_lorry_id')
            ->leftJoin('lorries as to_lorry', 'to_lorry.id', '=', 'lorry_stock_transfers.to_lorry_id')
            ->select('lorry_stock_transfers.*', 'from_store.name as from_store_name', 'to_store.name as to_store_name', 'from_lorry.lorry_no as from_lorry_no', 'to_lorry.lorry_no as to_lorry_no');
    }

    private function lorryStockQuery()
    {
        return DB::table('lorry_stocks')
            ->join('lorries', 'lorries.id', '=', 'lorry_stocks.lorry_id')
            ->join('products', 'products.id', '=', 'lorry_stocks.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'lorry_stocks.variant_id')
            ->select('lorry_stocks.*', 'lorries.lorry_no', 'lorries.name as lorry_name', 'products.name as product_name', 'products.sku', 'products.barcode', 'products.cost_price', 'products.wholesale_price', 'product_variants.sku as variant_sku', 'product_variants.color', 'product_variants.storage', 'product_variants.ram');
    }

    private function tripQuery()
    {
        return DB::table('distribution_trips')
            ->join('lorries', 'lorries.id', '=', 'distribution_trips.lorry_id')
            ->leftJoin('users as drivers', 'drivers.id', '=', 'distribution_trips.driver_id')
            ->select('distribution_trips.*', 'lorries.lorry_no', 'lorries.name as lorry_name', 'drivers.name as driver_user_name');
    }

    private function reconciliationTotals(int $lorryId, ?int $tripId): array
    {
        $salesTotal = DB::table('sales')->where('source_type', 'lorry')->where('lorry_id', $lorryId)
            ->when($tripId, fn ($q) => $q->where('trip_id', $tripId))->sum('payable_amount');
        $collectionsTotal = DB::table('lorry_collections')->where('lorry_id', $lorryId)
            ->when($tripId, fn ($q) => $q->where('trip_id', $tripId))->sum('amount');
        $expensesTotal = DB::table('expenses')->where('lorry_id', $lorryId)
            ->when($tripId, fn ($q) => $q->where('trip_id', $tripId))->sum('amount');

        return compact('salesTotal', 'collectionsTotal', 'expensesTotal') + [
            'sales_total' => (float) $salesTotal,
            'collections_total' => (float) $collectionsTotal,
            'expenses_total' => (float) $expensesTotal,
        ];
    }

    private function lorryStockValue(): float
    {
        return (float) DB::table('lorry_stocks')
            ->join('products', 'products.id', '=', 'lorry_stocks.product_id')
            ->sum(DB::raw('lorry_stocks.quantity * products.cost_price'));
    }

    private function nextNo(string $prefix, string $table, string $column): string
    {
        $last = DB::table($table)->where($column, 'like', "{$prefix}-%")->orderByDesc('id')->value($column);
        $next = $last && preg_match('/-(\d+)$/', $last, $m) ? ((int) $m[1]) + 1 : 1;

        return $prefix . '-' . now()->format('ymd') . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function money(float|int|string|null $amount): string
    {
        return SystemSettings::money((float) $amount);
    }
}
