<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\DamagedStock;
use App\Services\StockService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamagedStockController extends Controller
{
    public function index(Request $request)
    {
        $query = DamagedStock::with(['product', 'creator']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('damage_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $damagedStocks = $query->latest('id')->paginate(12)->withQueryString();

        $products = Product::with('stock')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock.damaged', compact('damagedStocks', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'damage_type' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string'],
            'status' => ['required', 'string', 'in:Pending,Repaired,Disposed,Returned to Stock'],
            'responsible_person' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpeg,jpg,png,doc,docx'],
            'notes' => ['nullable', 'string'],
        ]);

        $damagedStock = DB::transaction(function () use ($validated, $request) {
            $product = Product::findOrFail($validated['product_id']);
            $quantity = (float) $validated['quantity'];
            $estimatedLoss = $quantity * (float) $product->cost_price;

            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('damaged', 'public');
            }

            $damageNo = $this->generateDamageNo();

            $damagedStock = DamagedStock::create([
                'damage_no' => $damageNo,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'damage_type' => $validated['damage_type'],
                'reason' => $validated['reason'],
                'status' => $validated['status'],
                'estimated_loss' => $estimatedLoss,
                'responsible_person' => $validated['responsible_person'] ?? null,
                'document_path' => $documentPath,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // If initial status is NOT 'Returned to Stock', reduce stock
            if ($validated['status'] !== 'Returned to Stock') {
                $stockService = app(StockService::class);
                $stockService->adjustStock(
                    $product,
                    -$quantity,
                    'damaged',
                    DamagedStock::class,
                    $damagedStock->id,
                    "Damaged Stock Added: {$damageNo}. Type: {$validated['damage_type']}.",
                    $damageNo,
                    $validated['reason']
                );
            }

            return $damagedStock;
        });

        ActivityLog::log('Create', 'Damaged Stock', "Logged damaged stock {$damagedStock->damage_no}");

        return redirect()->route('damaged-stocks.index')->with('success', "Damaged stock {$damagedStock->damage_no} added successfully.");
    }

    public function update(Request $request, DamagedStock $damagedStock)
    {
        $validated = $request->validate([
            'damage_type' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string'],
            'responsible_person' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $damagedStock->update([
            'damage_type' => $validated['damage_type'],
            'reason' => $validated['reason'],
            'responsible_person' => $validated['responsible_person'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLog::log('Update', 'Damaged Stock', "Updated damaged stock details {$damagedStock->damage_no}");

        return redirect()->route('damaged-stocks.index')->with('success', "Damaged stock details updated successfully.");
    }

    public function destroy(DamagedStock $damagedStock)
    {
        DB::transaction(function () use ($damagedStock) {
            // If it was reducing stock, we need to return it back to stock
            if ($damagedStock->status !== 'Returned to Stock') {
                $stockService = app(StockService::class);
                $stockService->adjustStock(
                    $damagedStock->product,
                    (float) $damagedStock->quantity,
                    'damaged_reversal',
                    DamagedStock::class,
                    $damagedStock->id,
                    "Reverted damaged stock on deletion: {$damagedStock->damage_no}.",
                    $damagedStock->damage_no,
                    "Record deleted."
                );
            }
            $damagedStock->delete();
        });

        ActivityLog::log('Delete', 'Damaged Stock', "Deleted damaged stock record {$damagedStock->damage_no}");

        return redirect()->route('damaged-stocks.index')->with('success', "Damaged stock record deleted successfully.");
    }

    public function updateStatus(Request $request, DamagedStock $damagedStock)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Pending,Repaired,Disposed,Returned to Stock'],
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $damagedStock->status;

        if ($oldStatus === $newStatus) {
            return back()->with('success', 'Status remains unchanged.');
        }

        DB::transaction(function () use ($damagedStock, $newStatus, $oldStatus) {
            $stockService = app(StockService::class);
            $product = $damagedStock->product;

            // If moving TO "Returned to Stock" from another status
            if ($newStatus === 'Returned to Stock' && $oldStatus !== 'Returned to Stock') {
                $stockService->adjustStock(
                    $product,
                    (float) $damagedStock->quantity,
                    'returned_to_stock',
                    DamagedStock::class,
                    $damagedStock->id,
                    "Damaged Stock Returned to Inventory. Damage No: {$damagedStock->damage_no}.",
                    $damagedStock->damage_no,
                    "Returned to stock after repair."
                );
            }
            // If moving FROM "Returned to Stock" to another status
            elseif ($oldStatus === 'Returned to Stock' && $newStatus !== 'Returned to Stock') {
                $stockService->adjustStock(
                    $product,
                    -(float) $damagedStock->quantity,
                    'damaged',
                    DamagedStock::class,
                    $damagedStock->id,
                    "Damaged Stock Re-applied. Damage No: {$damagedStock->damage_no}.",
                    $damagedStock->damage_no,
                    "Moved out of returned status to {$newStatus}."
                );
            }

            $damagedStock->update([
                'status' => $newStatus,
            ]);

            ActivityLog::log(
                'Update Status',
                'Damaged Stock',
                "Updated damage record {$damagedStock->damage_no} status from {$oldStatus} to {$newStatus}."
            );
        });

        return back()->with('success', "Damaged stock status updated to {$newStatus}.");
    }

    private function generateDamageNo(): string
    {
        $prefix = 'DMG-' . now()->format('Ymd') . '-';
        $next = DamagedStock::where('damage_no', 'like', $prefix . '%')->count() + 1;
        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (DamagedStock::where('damage_no', $number)->exists());
        return $number;
    }
}
