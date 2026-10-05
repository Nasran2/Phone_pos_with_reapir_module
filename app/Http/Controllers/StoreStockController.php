<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockHistory;
use App\Models\Store;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreStockController extends Controller
{
    public function index(Request $request)
    {
        $storeIds = $request->input('store_ids', []);
        if ($request->filled('store_id')) {
            // Fallback for older links using single store_id
            $storeIds = [$request->input('store_id')];
        }

        $query = Product::with(['category', 'brand', 'storeStocks' => function ($q) use ($storeIds) {
            $q->with('store');
            if (! empty($storeIds)) {
                $q->whereIn('store_id', $storeIds);
            }
        }])->select('products.*');

        $stockSubquery = DB::table('product_store_stock')
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('product_id', 'products.id');

        if (! empty($storeIds)) {
            $stockSubquery->whereIn('store_id', $storeIds);

            // Only show products assigned to at least one of the selected stores
            $query->whereHas('storeStocks', function ($q) use ($storeIds) {
                $q->whereIn('store_id', $storeIds);
            });
        }

        $query->selectSub($stockSubquery, 'current_stock');

        // Search by name, SKU, barcode
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // Filter by low stock / out of stock status
        if ($request->filled('stock_status')) {
            $status = $request->input('stock_status');
            $subSql = '('.$stockSubquery->toSql().')';
            $bindings = $stockSubquery->getBindings();

            if ($status === 'low') {
                $query->whereRaw($subSql.' <= products.stock_alert_quantity', $bindings)
                    ->whereRaw($subSql.' > 0', $bindings);
            } elseif ($status === 'out') {
                $query->whereRaw($subSql.' <= 0', $bindings);
            }
        }

        $products = $query->latest('id')->paginate(12)->withQueryString();

        // Calculate Summary Card Metrics
        $totalProducts = Product::count();

        // Calculate Summary Card Metrics based on the selected store (or all stores)
        $stockValueQuery = DB::table('product_store_stock')
            ->join('products', 'products.id', '=', 'product_store_stock.product_id')
            ->whereNull('products.deleted_at');

        if (! empty($storeIds)) {
            $stockValueQuery->whereIn('product_store_stock.store_id', $storeIds);
        }

        $stockValue = $stockValueQuery->sum(DB::raw('product_store_stock.quantity * products.cost_price'));

        // Low stock count based on selected store
        $lowStockQuery = DB::table('products')->whereNull('deleted_at');
        $lowStockQuery->whereRaw('('.$stockSubquery->toSql().') <= products.stock_alert_quantity', $stockSubquery->getBindings());
        $lowStockQuery->whereRaw('('.$stockSubquery->toSql().') > 0', $stockSubquery->getBindings());
        $lowStockItems = $lowStockQuery->count();

        // Out of stock count based on selected store
        $outOfStockQuery = DB::table('products')->whereNull('deleted_at');
        $outOfStockQuery->whereRaw('('.$stockSubquery->toSql().') <= 0', $stockSubquery->getBindings());
        $outOfStockItems = $outOfStockQuery->count();

        $summary = [
            'total_products' => $totalProducts,
            'stock_value' => $stockValue,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
        ];

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('stock.index', compact('products', 'summary', 'categories', 'brands', 'stores'));
    }

    public function history(Product $product)
    {
        try {
            $defaultStoreId = Setting::get('default_store_id', 1);
            $currentStock = DB::table('product_store_stock')
                ->where('product_id', $product->id)
                ->where('store_id', $defaultStoreId)
                ->value('quantity');

            $histories = StockHistory::where('product_id', $product->id)
                ->with(['creator', 'variant'])
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?: '-',
                    'current_stock' => (float) ($currentStock ?? 0),
                ],
                'histories' => $histories->map(function ($history) {
                    $quantityChange = (float) $history->quantity_change;
                    $newQuantity = (float) $history->new_quantity;
                    $quantityBefore = $history->quantity_before;

                    if ($quantityBefore === null) {
                        $quantityBefore = $newQuantity - $quantityChange;
                    }

                    return [
                        'id' => $history->id,
                        'quantity_before' => (float) $quantityBefore,
                        'quantity_change' => $quantityChange,
                        'new_quantity' => $newQuantity,
                        'type' => ucfirst(str_replace('_', ' ', (string) $history->type)),
                        'reference_no' => $history->reference_no ?: '-',
                        'note' => Features::retailText($history->note ?: '-'),
                        'description' => Features::retailText($history->description ?: ($history->note ?: '-')),
                        'creator' => $history->creator ? $history->creator->name : 'System',
                        'date' => $history->created_at ? $history->created_at->format('Y-m-d H:i') : '-',
                    ];
                })->values(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load stock movement logs. Please try again.',
            ], 500);
        }
    }
}
