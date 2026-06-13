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
        $query = StockAdjustment::with(['creator'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('adjustment_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('adjustment_type', $request->input('type'));
        }

        $adjustments = $query->latest('id')->paginate(12)->withQueryString();

        return view('stock.adjustments', compact('adjustments'));
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
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpeg,jpg,png,doc,docx,xls,xlsx'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
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
                'reason' => $validated['reason'],
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
}
