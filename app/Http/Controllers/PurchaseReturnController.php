<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ProductStoreStock;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['purchase', 'supplier'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                    ->orWhereHas('purchase', function ($purchaseQuery) use ($search) {
                        $purchaseQuery->where('purchase_no', 'like', "%{$search}%")
                            ->orWhere('invoice_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('refund_status')) {
            $query->where('refund_status', $request->input('refund_status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('return_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('return_date', '<=', $request->input('date_to'));
        }

        $returns = $query->latest('return_date')->latest('id')->paginate(12)->withQueryString();

        return view('returns.index', compact('returns'));
    }

    public function create()
    {
        return view('returns.form', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
            'return_date' => ['required', 'date'],
            'refund_method' => ['required', Rule::in(['Cash', 'Bank Transfer', 'Card', 'Cheque', 'Supplier Credit'])],
            'refund_status' => ['required', Rule::in(['Pending', 'Refunded', 'Supplier Credit'])],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string'],
            'items' => ['required', 'array'],
            'items.*.purchase_item_id' => ['required', 'exists:purchase_items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $purchase = Purchase::with(['items.product', 'supplier'])->findOrFail($validated['purchase_id']);

        DB::beginTransaction();

        try {
            $returnItems = [];
            $totalAmount = 0;
            $returnedByItem = $this->returnedQuantities($purchase->id);

            foreach ($validated['items'] as $itemInput) {
                $quantity = (float) ($itemInput['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $purchaseItem = $purchase->items->firstWhere('id', (int) $itemInput['purchase_item_id']);
                if (!$purchaseItem) {
                    throw new \RuntimeException('Invalid purchase item selected.');
                }

                $remainingQty = (float) $purchaseItem->quantity - (float) ($returnedByItem[$purchaseItem->id] ?? 0);
                if ($quantity > $remainingQty) {
                    throw new \RuntimeException("Return quantity for {$purchaseItem->product->name} exceeds available quantity.");
                }

                $cost = (float) ($purchaseItem->unit_cost ?: $purchaseItem->cost_price);
                $lineTotal = round($quantity * $cost, 2);
                $totalAmount += $lineTotal;
                $returnItems[] = [
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'quantity' => $quantity,
                    'cost_price' => $cost,
                    'subtotal' => $lineTotal,
                ];
            }

            if (empty($returnItems)) {
                throw new \RuntimeException('Enter at least one return quantity.');
            }

            $returnNo = $this->generateReturnNo();
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'return_no' => $returnNo,
                'return_date' => $validated['return_date'],
                'total_amount' => round($totalAmount, 2),
                'refund_method' => $validated['refund_method'],
                'refund_amount' => min((float) ($validated['refund_amount'] ?? $totalAmount), $totalAmount),
                'refund_status' => $validated['refund_status'],
                'reason' => $validated['reason'],
                'note' => $validated['reason'],
                'created_by' => auth()->id(),
            ]);

            foreach ($returnItems as $item) {
                $purchaseReturn->items()->create($item);
                $stock = ProductStoreStock::firstOrCreate([
                    'product_id' => $item['product_id'],
                    'store_id' => $purchaseReturn->purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1)
                ], ['quantity' => 0]);
                $stock->quantity = max(0, (float) $stock->quantity - (float) $item['quantity']);
                $stock->save();

                StockHistory::create([
                    'product_id' => $item['product_id'],
                    'quantity_change' => -(float) $item['quantity'],
                    'new_quantity' => (float) $stock->quantity,
                    'type' => 'purchase_return',
                    'reference_no' => $returnNo,
                    'note' => $validated['reason'],
                    'created_by' => auth()->id(),
                ]);

                $this->recordStockMovement($item['product_id'], -(float) $item['quantity'], 'purchase_return', $purchaseReturn->id, $returnNo);
            }

            $purchase->due_amount = max(0, (float) $purchase->due_amount - $totalAmount);
            $purchase->save();
            $this->adjustSupplierBalance($purchase->supplier, -$totalAmount, 'purchase_return', $purchaseReturn, $returnNo);

            ActivityLog::log('Create', 'Purchase Module', "Created purchase return {$returnNo}");
            DB::commit();

            return redirect()->route('purchase-returns.show', $purchaseReturn)->with('success', 'Purchase return saved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['return' => 'Unable to save purchase return. ' . $e->getMessage()]);
        }
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['purchase', 'supplier', 'items.product']);

        return view('returns.show', ['return' => $purchaseReturn]);
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['purchase.supplier', 'items']);

        DB::beginTransaction();

        try {
            foreach ($purchaseReturn->items as $item) {
                $stock = ProductStoreStock::firstOrCreate([
                    'product_id' => $item->product_id,
                    'store_id' => $purchaseReturn->purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1)
                ], ['quantity' => 0]);
                $stock->quantity = (float) $stock->quantity + (float) $item->quantity;
                $stock->save();

                StockHistory::create([
                    'product_id' => $item->product_id,
                    'quantity_change' => (float) $item->quantity,
                    'new_quantity' => (float) $stock->quantity,
                    'type' => 'purchase_return_reversal',
                    'reference_no' => $purchaseReturn->return_no,
                    'note' => 'Purchase return processed',
                    'created_by' => auth()->id(),
                    'store_id' => $purchaseReturn->purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1),
                ]);
            }

            if ($purchaseReturn->purchase) {
                $purchaseReturn->purchase->due_amount = (float) $purchaseReturn->purchase->due_amount + (float) $purchaseReturn->total_amount;
                $purchaseReturn->purchase->save();
            }

            $this->adjustSupplierBalance($purchaseReturn->supplier, (float) $purchaseReturn->total_amount, 'purchase_return_deleted', $purchaseReturn, $purchaseReturn->return_no);

            $returnNo = $purchaseReturn->return_no;
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();

            ActivityLog::log('Delete', 'Purchase Module', "Deleted purchase return {$returnNo}");
            DB::commit();

            return redirect()->route('purchase-returns.index')->with('success', 'Purchase return deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Unable to delete purchase return.');
        }
    }

    private function formData(): array
    {
        $purchases = Purchase::with(['supplier', 'items.product'])
            ->whereIn('status', ['Received', 'Partially Received'])
            ->latest('purchase_date')
            ->get();
        $returned = PurchaseReturnItem::query()
            ->select('purchase_item_id', DB::raw('SUM(quantity) as quantity'))
            ->whereNotNull('purchase_item_id')
            ->groupBy('purchase_item_id')
            ->pluck('quantity', 'purchase_item_id');

        $purchaseData = $purchases->map(function (Purchase $purchase) use ($returned) {
            return [
                'id' => $purchase->id,
                'purchase_no' => $purchase->displayNo(),
                'supplier' => $purchase->supplier->name ?? '-',
                'supplier_id' => $purchase->supplier_id,
                'date' => optional($purchase->purchase_date)->format('Y-m-d'),
                'items' => $purchase->items->map(function ($item) use ($returned) {
                    $returnedQty = (float) ($returned[$item->id] ?? 0);

                    return [
                        'purchase_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? '-',
                        'quantity' => (float) $item->quantity,
                        'returned_quantity' => $returnedQty,
                        'remaining_quantity' => max(0, (float) $item->quantity - $returnedQty),
                        'cost_price' => (float) ($item->unit_cost ?: $item->cost_price),
                    ];
                })->values(),
            ];
        })->values();

        return compact('purchaseData');
    }

    private function returnedQuantities(int $purchaseId): array
    {
        return PurchaseReturnItem::query()
            ->join('purchase_items', 'purchase_items.id', '=', 'purchase_return_items.purchase_item_id')
            ->where('purchase_items.purchase_id', $purchaseId)
            ->select('purchase_return_items.purchase_item_id', DB::raw('SUM(purchase_return_items.quantity) as quantity'))
            ->groupBy('purchase_return_items.purchase_item_id')
            ->pluck('quantity', 'purchase_item_id')
            ->toArray();
    }

    private function adjustSupplierBalance(Supplier $supplier, float $amount, string $type, ?object $source = null, ?string $referenceNo = null): void
    {
        if (abs($amount) < 0.01) {
            return;
        }

        $supplier->balance = (float) $supplier->balance + $amount;
        $supplier->save();

        SupplierTransaction::create([
            'supplier_id' => $supplier->id,
            'transactionable_type' => $source ? $source::class : null,
            'transactionable_id' => $source->id ?? null,
            'transaction_date' => now()->toDateString(),
            'type' => $type,
            'debit' => $amount > 0 ? $amount : 0,
            'credit' => $amount < 0 ? abs($amount) : 0,
            'balance_after' => $supplier->balance,
            'reference_no' => $referenceNo,
            'note' => str_replace('_', ' ', $type),
            'created_by' => auth()->id(),
        ]);
    }

    private function recordStockMovement(int $productId, float $quantity, string $type, int $referenceId, string $referenceNo): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        DB::table('stock_movements')->insert([
            'product_id' => $productId,
            'quantity' => $quantity,
            'type' => $type,
            'reference_id' => $referenceId,
            'reference_no' => $referenceNo,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateReturnNo(): string
    {
        $prefix = 'PR-' . now()->format('Ymd') . '-';
        $next = PurchaseReturn::where('return_no', 'like', $prefix . '%')->count() + 1;

        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (PurchaseReturn::where('return_no', $number)->exists());

        return $number;
    }
}
