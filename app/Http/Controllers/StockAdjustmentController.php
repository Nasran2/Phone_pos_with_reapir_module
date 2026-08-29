<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['creator', 'items.product'])->withCount('items');

        if ($request->filled('start_date')) {
            $query->whereDate('adjustment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('adjustment_date', '<=', $request->end_date);
        }
        if ($request->filled('user_id')) {
            $query->where('created_by', $request->user_id);
        }

        $adjustments = $query->latest('id')->paginate(12)->withQueryString();
        $users = \App\Models\User::all();

        return view('stock.adjustments', compact('adjustments', 'users'));
    }

    public function create()
    {
        $products = Product::with('stock')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'current_stock' => (float) ($product->stock->quantity ?? 0),
            ])
            ->values();

        return view('stock.adjustments_create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'adjustment_type' => ['required', 'string', 'in:increase,decrease,correction,opening_stock'],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpeg,jpg,png,doc,docx,xls,xlsx'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'not_in:0'],
        ]);

        $adjustment = DB::transaction(function () use ($validated, $request) {
            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('adjustments', 'public');
            }

            $adjustmentNo = $this->generateAdjustmentNo();

            $adjustment = StockAdjustment::create([
                'adjustment_no' => $adjustmentNo,
                'adjustment_date' => $validated['adjustment_date'],
                'adjustment_type' => $validated['adjustment_type'],
                'reason' => $validated['reason'] ?? '-',
                'notes' => $validated['notes'] ?? null,
                'document_path' => $documentPath,
                'status' => 'approved',
                'created_by' => auth()->id(),
            ]);

            $stockService = app(StockService::class);

            foreach ($validated['items'] as $itemData) {
                $product = Product::with('stock')->findOrFail($itemData['product_id']);
                $currentStock = (float) ($product->stock->quantity ?? 0);
                $qty = (float) $itemData['quantity'];

                $qtyChange = 0;
                if ($validated['adjustment_type'] === 'increase') {
                    $qtyChange = $qty;
                } elseif ($validated['adjustment_type'] === 'decrease') {
                    $qtyChange = -$qty;
                } elseif ($validated['adjustment_type'] === 'correction') {
                    // Can be positive or negative
                    $qtyChange = $qty;
                } elseif ($validated['adjustment_type'] === 'opening_stock') {
                    $qtyChange = $qty - $currentStock;
                }

                $newStock = $currentStock + $qtyChange;

                // Save adjustment item
                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'current_stock' => $currentStock,
                    'quantity' => $qtyChange,
                    'new_stock' => $newStock,
                ]);

                // Adjust stock via service
                $stockService->adjustStock(
                    $product,
                    $qtyChange,
                    'adjustment',
                    StockAdjustment::class,
                    $adjustment->id,
                    "Stock Adjustment: {$adjustmentNo}. Type: " . ucfirst($validated['adjustment_type']) . ". Reason: " . $validated['reason'],
                    $adjustmentNo,
                    $validated['reason']
                );
            }

            return $adjustment;
        });

        return redirect()->route('stock-adjustments.index')->with('success', "Stock adjustment {$adjustment->adjustment_no} recorded and stock values updated successfully.");
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['creator', 'items.product']);
        return view('stock.adjustments_show', compact('stockAdjustment'));
    }

    private function generateAdjustmentNo(): string
    {
        $prefix = 'ADJ-' . now()->format('Ymd') . '-';
        $next = StockAdjustment::where('adjustment_no', 'like', $prefix . '%')->count() + 1;
        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (StockAdjustment::where('adjustment_no', $number)->exists());
        return $number;
    }

    public function report(Request $request)
    {
        $query = StockAdjustmentItem::with(['stockAdjustment.creator', 'product'])
            ->join('stock_adjustments', 'stock_adjustment_items.stock_adjustment_id', '=', 'stock_adjustments.id')
            ->select('stock_adjustment_items.*')
            ->orderBy('stock_adjustments.adjustment_date', 'desc')
            ->orderBy('stock_adjustments.id', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('stock_adjustments.adjustment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('stock_adjustments.adjustment_date', '<=', $request->end_date);
        }
        if ($request->filled('user_id')) {
            $query->where('stock_adjustments.created_by', $request->user_id);
        }

        $items = $query->paginate(15)->withQueryString();
        $users = \App\Models\User::all(); // Provide users for filter dropdown

        return view('stock.adjustments_report', compact('items', 'users'));
    }
}
