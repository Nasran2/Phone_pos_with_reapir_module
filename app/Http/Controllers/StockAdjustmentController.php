<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Setting;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\Store;
use App\Services\PdfExportService;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d'.($request->filled('start_date') ? '|after_or_equal:start_date' : ''),
            'search' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);
        $query = StockAdjustment::with('creator')->withCount('items')
            ->when($filters['start_date'] ?? null, fn ($q, $v) => $q->whereDate('adjustment_date', '>=', $v))
            ->when($filters['end_date'] ?? null, fn ($q, $v) => $q->whereDate('adjustment_date', '<=', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('created_by', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('adjustment_no', 'like', "%{$v}%")->orWhere('reason', 'like', "%{$v}%")));
        if ($request->query('type') === 'pdf') {
            return app(PdfExportService::class)->export('Stock Adjustment History',
                ['Reference', 'Date', 'Reason', 'Products', 'Status', 'Created by'],
                $query->latest('id')->get()->map(fn ($a) => [$a->adjustment_no, $a->adjustment_date->format('Y-m-d'), $a->reason, $a->items_count, $a->status, $a->creator?->name ?? '-']),
                [], array_filter($filters));
        }

        return view('stock.adjustments', ['adjustments' => $query->latest('id')->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit(StockAdjustment $stockAdjustment)
    {
        abort_if($stockAdjustment->status === 'cancelled', 422, 'Deleted adjustments cannot be edited.');

        return $this->form($stockAdjustment);
    }

    private function storeId(?StockAdjustment $adjustment = null): int
    {
        return (int) (($adjustment ? $this->history($adjustment)->oldest('id')->value('store_id') : null) ?: Setting::get('default_store_id', 1));
    }

    private function history(StockAdjustment $adjustment)
    {
        return StockHistory::with(['product', 'creator'])->where('reference_type', StockAdjustment::class)->where('reference_id', $adjustment->id);
    }

    private function form(?StockAdjustment $adjustment = null)
    {
        $storeId = $this->storeId($adjustment);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'barcode']);
        $stocks = ProductStoreStock::where('store_id', $storeId)->pluck('quantity', 'product_id');
        $products = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'barcode' => $p->barcode, 'stock' => (float) ($stocks[$p->id] ?? 0)]);
        $initialItems = $adjustment?->items->map(fn ($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity])->all() ?? [];
        // Include products since disabled so existing adjustments remain editable.
        if ($adjustment) {
            foreach ($adjustment->items as $item) {
                if (! $products->contains('id', $item->product_id)) {
                    $product = Product::withTrashed()->findOrFail($item->product_id);
                    $products->push(['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'barcode' => $product->barcode, 'stock' => (float) ($stocks[$product->id] ?? 0)]);
                }
            }
        }

        return view('stock.adjustments_create', compact('products', 'adjustment', 'initialItems') + ['storeName' => Store::find($storeId)?->name ?? 'Default store']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'adjustment_type' => 'required|in:increase,decrease,correction,opening_stock',
            'adjustment_date' => 'required|date_format:Y-m-d',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'document' => 'nullable|file|max:5120|mimes:pdf,jpeg,jpg,png,doc,docx,xls,xlsx',
            'items' => 'required|array|min:1|max:200',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.quantity' => 'required|numeric|decimal:0,2|between:-999999999,999999999|'.($request->input('adjustment_type') === 'opening_stock' ? 'min:0' : 'not_in:0'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $adjustment = DB::transaction(function () use ($data, $request) {
            $adjustment = StockAdjustment::create([
                'adjustment_no' => 'ADJ-'.now()->format('Ymd').'-'.strtoupper(substr((string) Str::ulid(), -10)),
                'adjustment_date' => $data['adjustment_date'], 'adjustment_type' => $data['adjustment_type'],
                'reason' => $data['reason'] ?? '-', 'notes' => $data['notes'] ?? null,
                'status' => 'approved', 'created_by' => auth()->id(),
            ]);
            $this->apply($adjustment, $data);
            if ($request->hasFile('document')) {
                $adjustment->update(['document_path' => $request->file('document')->store('adjustments', 'public')]);
            }

            return $adjustment;
        });

        return redirect()->route('stock-adjustments.show', $adjustment)->with('success', 'Stock adjustment saved.');
    }

    public function update(Request $request, StockAdjustment $stockAdjustment)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($stockAdjustment, $data) {
            $adjustment = StockAdjustment::lockForUpdate()->findOrFail($stockAdjustment->id);
            abort_if($adjustment->status === 'cancelled', 422, 'Deleted adjustments cannot be edited.');
            $this->apply($adjustment, $data, true);
            $adjustment->update(collect($data)->only(['adjustment_date', 'adjustment_type', 'reason', 'notes'])->all());
        });

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Adjustment updated and stock reconciled.');
    }

    private function apply(StockAdjustment $adjustment, array $data, bool $editing = false): void
    {
        $storeId = $this->storeId($adjustment);
        $old = $editing ? $adjustment->items->keyBy('product_id') : collect();
        $incoming = collect($data['items'])->keyBy('product_id');
        $ids = $old->keys()->merge($incoming->keys())->unique()->sort()->values();
        foreach ($ids as $id) {
            // Serialize changes to the same product, including creation of a missing stock row.
            $product = Product::withTrashed()->lockForUpdate()->findOrFail($id);
            $stock = ProductStoreStock::firstOrCreate(['product_id' => $id, 'store_id' => $storeId], ['quantity' => 0]);
            $stock = ProductStoreStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
            $oldDelta = (float) ($old->get($id)?->quantity ?? 0);
            $baseline = (float) $stock->quantity - $oldDelta;
            $quantity = (float) ($incoming->get($id)['quantity'] ?? 0);
            $desired = ! $incoming->has($id) ? 0 : match ($data['adjustment_type']) {
                'increase' => abs($quantity),
                'decrease' => -abs($quantity),
                'opening_stock' => $quantity - $baseline,
                default => $quantity,
            };
            $delta = $desired - $oldDelta;
            if ((float) $stock->quantity + $delta < -0.00001) {
                throw ValidationException::withMessages(['items' => "Insufficient stock for {$product->name}. This change would make stock negative."]);
            }
            app(StockService::class)->adjustStock($product, $delta, $editing ? 'adjustment_edit' : 'adjustment',
                StockAdjustment::class, $adjustment->id, $editing ? 'Adjustment edited' : 'Adjustment created',
                $adjustment->adjustment_no, $data['reason'] ?? '-', auth()->id(), $storeId);
            if ($incoming->has($id)) {
                $adjustment->items()->updateOrCreate(['product_id' => $id], ['current_stock' => $baseline, 'quantity' => $desired, 'new_stock' => $baseline + $desired]);
            } else {
                $adjustment->items()->where('product_id', $id)->delete();
            }
        }
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        DB::transaction(function () use ($stockAdjustment) {
            $adjustment = StockAdjustment::lockForUpdate()->findOrFail($stockAdjustment->id);
            if ($adjustment->status === 'cancelled') {
                return;
            }
            $storeId = $this->storeId($adjustment);
            foreach ($adjustment->items()->orderBy('product_id')->get() as $item) {
                $product = Product::withTrashed()->lockForUpdate()->findOrFail($item->product_id);
                $stock = ProductStoreStock::where('product_id', $item->product_id)->where('store_id', $storeId)->lockForUpdate()->first();
                if (! $stock || (float) $stock->quantity - (float) $item->quantity < -0.00001) {
                    throw ValidationException::withMessages(['adjustment' => 'Cannot delete: stock added by this adjustment has already been used.']);
                }
                app(StockService::class)->adjustStock($product, -(float) $item->quantity, 'adjustment_deleted', StockAdjustment::class,
                    $adjustment->id, 'Adjustment deleted; stock reversed', $adjustment->adjustment_no, 'Deleted adjustment', auth()->id(), $storeId);
            }
            // Retain the document and all movements for audit history.
            $adjustment->update(['status' => 'cancelled']);
        });

        return redirect()->route('stock-adjustments.index')->with('success', 'Adjustment deleted; stock reversed and history retained.');
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return view('stock.adjustments_show', [
            'stockAdjustment' => $stockAdjustment->load(['creator', 'items.product']),
            'history' => $this->history($stockAdjustment)->latest('id')->get(),
            'storeName' => Store::find($this->storeId($stockAdjustment))?->name ?? 'Default store',
        ]);
    }

    public function pdf(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['items.product', 'creator']);

        return Pdf::loadView('stock.adjustments_pdf', [
            'adjustment' => $stockAdjustment,
            'storeName' => Store::find($this->storeId($stockAdjustment))?->name ?? 'Default store',
            'history' => $this->history($stockAdjustment)->oldest('id')->get(),
        ])->setPaper('a4')->download($stockAdjustment->adjustment_no.'.pdf');
    }

    public function report(Request $request)
    {
        return $this->index($request);
    }
}
