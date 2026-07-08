<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\StockHistory;
use App\Models\StockTransfer;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromStore', 'toStore', 'creator']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('reference_no', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_store_id')) {
            $query->where('from_store_id', $request->input('from_store_id'));
        }

        if ($request->filled('to_store_id')) {
            $query->where('to_store_id', $request->input('to_store_id'));
        }

        $transfers = $query->latest()->paginate(10)->withQueryString();
        $stores = Store::where('status', true)->get();

        return view('stock_transfers.index', compact('transfers', 'stores'));
    }

    public function create()
    {
        $stores = Store::where('status', true)->get();
        // Load products with their stock broken down by store
        $products = Product::with('storeStocks.store')->where('is_active', true)->get();
        
        return view('stock_transfers.create', compact('stores', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_store_id' => ['required', 'exists:stores,id'],
            'to_store_id' => ['required', 'exists:stores,id', 'different:from_store_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['pending', 'completed'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::beginTransaction();

        try {
            $referenceNo = 'TRF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

            $transfer = StockTransfer::create([
                'reference_no' => $referenceNo,
                'from_store_id' => $validated['from_store_id'],
                'to_store_id' => $validated['to_store_id'],
                'transfer_date' => $validated['transfer_date'],
                'status' => 'pending', // initially always pending until items are added
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                // Check if source store has enough stock
                $stock = ProductStoreStock::where('product_id', $item['product_id'])
                    ->where('store_id', $validated['from_store_id'])
                    ->first();
                
                if (!$stock || $stock->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock in source store for product ID: {$item['product_id']}");
                }

                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            if ($validated['status'] === 'completed') {
                $this->completeTransfer($transfer);
            }

            ActivityLog::log('Create', 'Stock Transfers', "Created stock transfer {$referenceNo}");

            DB::commit();

            return redirect()->route('stock-transfers.show', $transfer)->with('success', 'Stock transfer created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromStore', 'toStore', 'creator', 'items.product']);
        return view('stock_transfers.show', compact('stockTransfer'));
    }

    public function markCompleted(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status === 'completed') {
            return back()->with('error', 'This transfer is already completed.');
        }

        DB::beginTransaction();
        try {
            $this->completeTransfer($stockTransfer);
            ActivityLog::log('Update', 'Stock Transfers', "Completed stock transfer {$stockTransfer->reference_no}");
            DB::commit();

            return back()->with('success', 'Transfer completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status === 'completed') {
            return back()->with('error', 'Cannot delete a completed transfer.');
        }

        $referenceNo = $stockTransfer->reference_no;
        $stockTransfer->items()->delete();
        $stockTransfer->delete();

        ActivityLog::log('Delete', 'Stock Transfers', "Deleted stock transfer {$referenceNo}");

        return redirect()->route('stock-transfers.index')->with('success', 'Stock transfer deleted successfully.');
    }

    private function completeTransfer(StockTransfer $transfer)
    {
        foreach ($transfer->items as $item) {
            // Deduct from Source Store
            $sourceStock = ProductStoreStock::where('product_id', $item->product_id)
                ->where('store_id', $transfer->from_store_id)
                ->first();

            if (!$sourceStock || $sourceStock->quantity < $item->quantity) {
                throw new \Exception("Insufficient stock in source store for {$item->product->name}.");
            }

            $sourceStock->quantity -= $item->quantity;
            $sourceStock->save();

            StockHistory::create([
                'product_id' => $item->product_id,
                'store_id' => $transfer->from_store_id,
                'quantity_change' => -$item->quantity,
                'new_quantity' => $sourceStock->quantity,
                'type' => 'transfer_out',
                'reference_no' => $transfer->reference_no,
                'note' => "Transferred out to {$transfer->toStore->name}",
                'created_by' => auth()->id(),
            ]);

            // Add to Destination Store
            $destStock = ProductStoreStock::firstOrCreate(
                ['product_id' => $item->product_id, 'store_id' => $transfer->to_store_id],
                ['quantity' => 0]
            );

            $destStock->quantity += $item->quantity;
            $destStock->save();

            StockHistory::create([
                'product_id' => $item->product_id,
                'store_id' => $transfer->to_store_id,
                'quantity_change' => $item->quantity,
                'new_quantity' => $destStock->quantity,
                'type' => 'transfer_in',
                'reference_no' => $transfer->reference_no,
                'note' => "Transferred in from {$transfer->fromStore->name}",
                'created_by' => auth()->id(),
            ]);
        }

        $transfer->update(['status' => 'completed']);
    }
}
