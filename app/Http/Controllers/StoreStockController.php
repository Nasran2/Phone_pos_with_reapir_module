<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreStockController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['stock', 'category', 'brand']);

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
            if ($status === 'low') {
                $query->whereHas('stock', function ($q) {
                    $q->whereColumn('quantity', '<=', 'products.stock_alert_quantity')
                      ->where('quantity', '>', 0);
                });
            } elseif ($status === 'out') {
                $query->where(function ($q) {
                    $q->whereHas('stock', function ($sq) {
                        $sq->where('quantity', '<=', 0);
                    })->orWhereDoesntHave('stock');
                });
            }
        }

        $products = $query->latest('id')->paginate(12)->withQueryString();

        // Calculate Summary Card Metrics
        $totalProducts = Product::count();

        // Stock value = sum of quantity * cost_price
        $stockValue = DB::table('product_stock')
            ->join('products', 'products.id', '=', 'product_stock.product_id')
            ->whereNull('products.deleted_at')
            ->sum(DB::raw('product_stock.quantity * products.cost_price'));

        // Low stock count (quantity <= stock_alert_quantity and quantity > 0)
        $lowStockItems = Product::whereHas('stock', function ($q) {
            $q->whereColumn('quantity', '<=', 'products.stock_alert_quantity')
              ->where('quantity', '>', 0);
        })->count();

        // Out of stock count (quantity <= 0 or doesn't have stock record)
        $outOfStockItems = Product::where(function ($q) {
            $q->whereHas('stock', function ($sq) {
                $sq->where('quantity', '<=', 0);
            })->orWhereDoesntHave('stock');
        })->count();

        $summary = [
            'total_products' => $totalProducts,
            'stock_value' => $stockValue,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
        ];

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('stock.index', compact('products', 'summary', 'categories', 'brands'));
    }

    public function history(Product $product)
    {
        $histories = StockHistory::where('product_id', $product->id)
            ->with(['creator', 'variant'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => (float) ($product->stock->quantity ?? 0),
            ],
            'histories' => $histories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'quantity_before' => (float) $history->quantity_before,
                    'quantity_change' => (float) $history->quantity_change,
                    'new_quantity' => (float) $history->new_quantity,
                    'type' => ucfirst(str_replace('_', ' ', $history->type)),
                    'reference_no' => $history->reference_no ?: '-',
                    'note' => $history->note ?: '-',
                    'description' => $history->description ?: '-',
                    'creator' => $history->creator ? $history->creator->name : 'System',
                    'date' => $history->created_at ? $history->created_at->format('Y-m-d H:i') : '-',
                ];
            }),
        ]);
    }
}
