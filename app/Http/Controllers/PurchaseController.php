<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    private array $statuses = ['Draft', 'Ordered', 'Received', 'Partially Received', 'Cancelled'];

    public function index(Request $request)
    {
        $this->ensurePurchaseModuleEnabled();

        $query = Purchase::with('supplier')->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->input('date_to'));
        }

        $purchases = $query->latest('purchase_date')->latest('id')->paginate(12)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();
        $statuses = $this->statuses;

        return view('purchases.index', compact('purchases', 'suppliers', 'statuses'));
    }

    public function create()
    {
        $this->ensurePurchaseModuleEnabled();

        return view('purchases.form', $this->formData());
    }

    public function store(Request $request)
    {
        $this->ensurePurchaseModuleEnabled();

        $validated = $this->validatePurchase($request);
        $validated['status'] = $request->input('save_action') === 'draft' ? 'Draft' : $validated['status'];

        DB::beginTransaction();

        try {
            $totals = $this->calculateTotals($validated['items'], $validated);
            $documentPath = $request->hasFile('document')
                ? $request->file('document')->store('purchase-documents', 'public')
                : null;

            $purchaseNo = $this->generatePurchaseNo();
            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'invoice_no' => $purchaseNo,
                'supplier_id' => $validated['supplier_id'],
                'reference_no' => $validated['reference_no'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $totals['subtotal'],
                'discount_type' => $validated['discount_type'],
                'discount_amount' => $totals['overall_discount'],
                'tax_amount' => $totals['purchase_tax'],
                'added_tax_total' => $totals['added_tax_total'],
                'included_tax_total' => $totals['included_tax_total'],
                'tax_total' => $totals['added_tax_total'] + $totals['included_tax_total'],
                'shipping_cost' => $totals['shipping_cost'],
                'shipping_handling' => $validated['shipping_handling'],
                'grand_total' => $totals['grand_total'],
                'total_amount' => $totals['grand_total'],
                'paid_amount' => $totals['paid_amount'],
                'due_amount' => $totals['due_amount'],
                'status' => $validated['status'],
                'store_id' => $validated['store_id'] ?? \App\Models\Setting::get('default_store_id', 1),
                'document_path' => $documentPath,
                'notes' => $validated['notes'] ?? null,
                'stock_updated' => false,
                'created_by' => auth()->id(),
            ]);

            $this->syncItems($purchase, $totals['items']);
            $this->syncPayment($purchase, $validated);

            if ($purchase->status === 'Received') {
                $this->applyStock($purchase);
            }

            $this->adjustSupplierBalance($purchase->supplier, (float) $purchase->due_amount, 'purchase_due', $purchase, $purchase->displayNo());
            ActivityLog::log('Create', 'Purchase Module', "Created purchase {$purchase->displayNo()}");

            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase saved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['purchase' => 'Unable to save purchase. ' . $e->getMessage()]);
        }
    }

    public function show(Purchase $purchase)
    {
        $this->ensurePurchaseModuleEnabled();

        $purchase->load(['supplier', 'items.product', 'payments', 'returns.items']);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $this->ensurePurchaseModuleEnabled();

        $purchase->load(['items.product', 'payments', 'supplier']);

        return view('purchases.form', $this->formData($purchase));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $this->ensurePurchaseModuleEnabled();

        $purchase->load(['items', 'supplier']);
        $validated = $this->validatePurchase($request, $purchase);
        $validated['status'] = $request->input('save_action') === 'draft' ? 'Draft' : $validated['status'];

        DB::beginTransaction();

        try {
            $oldSupplier = $purchase->supplier;
            $oldDue = (float) $purchase->due_amount;

            if ($purchase->stock_updated) {
                $this->reverseStock($purchase, 'Purchase edited');
            }

            $totals = $this->calculateTotals($validated['items'], $validated);
            $documentPath = $purchase->document_path;
            if ($request->hasFile('document')) {
                if ($documentPath) {
                    Storage::disk('public')->delete($documentPath);
                }
                $documentPath = $request->file('document')->store('purchase-documents', 'public');
            }

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'reference_no' => $validated['reference_no'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $totals['subtotal'],
                'discount_type' => $validated['discount_type'],
                'discount_amount' => $totals['overall_discount'],
                'tax_amount' => $totals['purchase_tax'],
                'added_tax_total' => $totals['added_tax_total'],
                'included_tax_total' => $totals['included_tax_total'],
                'tax_total' => $totals['added_tax_total'] + $totals['included_tax_total'],
                'shipping_cost' => $totals['shipping_cost'],
                'shipping_handling' => $validated['shipping_handling'],
                'grand_total' => $totals['grand_total'],
                'total_amount' => $totals['grand_total'],
                'paid_amount' => $totals['paid_amount'],
                'due_amount' => $totals['due_amount'],
                'status' => $validated['status'],
                'store_id' => $validated['store_id'] ?? \App\Models\Setting::get('default_store_id', 1),
                'document_path' => $documentPath,
                'notes' => $validated['notes'] ?? null,
                'stock_updated' => false,
            ]);

            $purchase->items()->delete();
            $this->syncItems($purchase, $totals['items']);
            $this->syncPayment($purchase, $validated);

            if ($purchase->status === 'Received') {
                $this->applyStock($purchase->fresh(['items.product']));
            }

            if ($oldSupplier->id !== (int) $purchase->supplier_id) {
                $this->adjustSupplierBalance($oldSupplier, -$oldDue, 'purchase_due_reversal', $purchase, $purchase->displayNo());
                $this->adjustSupplierBalance($purchase->supplier()->first(), (float) $purchase->due_amount, 'purchase_due', $purchase, $purchase->displayNo());
            } else {
                $this->adjustSupplierBalance($oldSupplier, (float) $purchase->due_amount - $oldDue, 'purchase_due_adjustment', $purchase, $purchase->displayNo());
            }

            ActivityLog::log('Update', 'Purchase Module', "Updated purchase {$purchase->displayNo()}");
            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['purchase' => 'Unable to update purchase. ' . $e->getMessage()]);
        }
    }

    public function destroy(Purchase $purchase)
    {
        $this->ensurePurchaseModuleEnabled();

        $purchase->load(['items.product', 'supplier']);

        DB::beginTransaction();

        try {
            if ($purchase->returns()->exists()) {
                return back()->with('error', 'Delete the purchase returns before deleting this purchase.');
            }

            if ($purchase->stock_updated) {
                $this->reverseStock($purchase, 'Purchase deleted');
            }

            $this->adjustSupplierBalance($purchase->supplier, -(float) $purchase->due_amount, 'purchase_deleted', $purchase, $purchase->displayNo());

            if ($purchase->document_path) {
                Storage::disk('public')->delete($purchase->document_path);
            }

            $purchaseNo = $purchase->displayNo();
            $purchase->payments()->delete();
            $purchase->items()->delete();
            $purchase->delete();

            ActivityLog::log('Delete', 'Purchase Module', "Deleted purchase {$purchaseNo}");
            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Unable to delete purchase.');
        }
    }

    public function print(Purchase $purchase)
    {
        $this->ensurePurchaseModuleEnabled();

        $purchase->load(['supplier', 'items.product', 'payments']);

        return view('purchases.print', compact('purchase'));
    }

    public function download(Purchase $purchase)
    {
        $this->ensurePurchaseModuleEnabled();

        $purchase->load(['supplier', 'items.product']);
        $lines = [
            'Supplier: ' . ($purchase->supplier->name ?? '-'),
            'Date: ' . optional($purchase->purchase_date)->format('Y-m-d'),
            'Reference: ' . ($purchase->reference_no ?: '-'),
            'Status: ' . $purchase->status,
            'Grand Total: ' . SystemSettings::money((float) $purchase->grand_total),
            'Paid: ' . SystemSettings::money((float) $purchase->paid_amount),
            'Due: ' . SystemSettings::money((float) $purchase->due_amount),
        ];

        foreach ($purchase->items as $item) {
            $lines[] = ($item->product->name ?? 'Product') . ' x ' . (float) $item->quantity . ' = ' . SystemSettings::money((float) $item->line_total);
        }

        return response($this->simplePdf('Purchase Invoice ' . $purchase->displayNo(), $lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $purchase->displayNo() . '.pdf"',
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        Supplier::create($validated + [
            'balance' => 0,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Supplier added successfully.');
    }

    private function formData(?Purchase $purchase = null): array
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::with('stock')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'cost_price' => (float) $product->cost_price,
                'retail_price' => (float) $product->retail_price,
                'wholesale_price' => (float) $product->wholesale_price,
                'stock' => (float) ($product->stock->quantity ?? 0),
            ])
            ->values();

        return [
            'purchase' => $purchase,
            'suppliers' => $suppliers,
            'products' => $products,
            'statuses' => $this->statuses,
            'paymentMethods' => ['Cash', 'Bank Transfer', 'Card', 'Cheque', 'Credit / Due'],
            'enableMultiStore' => \App\Models\Setting::get('enable_multi_store', false),
            'stores' => \App\Models\Setting::get('enable_multi_store', false) ? DB::table('stores')->where('status', true)->get() : [],
        ];
    }

    private function validatePurchase(Request $request, ?Purchase $purchase = null): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'status' => ['required', Rule::in($this->statuses)],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.profit_margin' => ['nullable', 'numeric', 'min:0'],
            'items.*.retail_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', Rule::in(['none', 'fixed', 'percentage'])],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'purchase_tax' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'shipping_handling' => ['required', Rule::in(['purchase_total', 'divide_product_cost'])],
            'payment_method' => ['required', Rule::in(['Cash', 'Bank Transfer', 'Card', 'Cheque', 'Credit / Due'])],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'store_id' => ['nullable', 'exists:stores,id'],
        ]);
    }

    private function calculateTotals(array $items, array $validated): array
    {
        $taxService = app(\App\Services\TaxCalculationService::class);
        $globalTaxMode = \App\Support\SystemSettings::get('default_tax_mode', 'exclusive', 'tax_settings');
        
        $calculatedItems = [];
        $calculatedSubtotal = 0;
        $calculatedAddedTaxTotal = 0;
        $calculatedIncludedTaxTotal = 0;
        
        $shippingCost = (float) ($validated['shipping_cost'] ?? 0);
        $divideShipping = ($validated['shipping_handling'] ?? 'purchase_total') === 'divide_product_cost';
        $totalQty = max(collect($items)->sum(fn ($item) => (float) ($item['quantity'] ?? 0)), 1);
        $shippingPerUnit = $divideShipping ? ($shippingCost / $totalQty) : 0;
        
        $invoiceDiscountType = $validated['discount_type'] ?? 'none';
        $invoiceDiscountAmount = (float) ($validated['discount_amount'] ?? 0);
        
        $preDiscountGrossTotal = collect($items)->sum(function($item) use ($shippingPerUnit) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            return $qty * ($unitCost + $shippingPerUnit);
        });

        foreach ($items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            $qty = (float) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];
            $effectiveUnitCost = round($unitCost + $shippingPerUnit, 6);
            
            $itemGross = $qty * $effectiveUnitCost;
            $itemShare = $preDiscountGrossTotal > 0 ? ($itemGross / $preDiscountGrossTotal) : 0;
            
            $proRatedInvoiceDiscount = 0;
            if ($invoiceDiscountType === 'percentage' && $invoiceDiscountAmount > 0) {
                $proRatedInvoiceDiscount = $itemGross * ($invoiceDiscountAmount / 100);
            } elseif ($invoiceDiscountType === 'fixed' && $invoiceDiscountAmount > 0) {
                $proRatedInvoiceDiscount = $invoiceDiscountAmount * $itemShare;
            }
            
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $itemDiscountType = $discountPercent > 0 ? 'percentage' : 'fixed';
            $itemDiscountValue = $discountPercent > 0 ? $discountPercent : (float) ($item['discount_amount'] ?? 0);
            
            $itemTaxOverride = $item['tax_mode'] ?? null;
            $productTaxMode = $product->tax_mode ?? 'use_global';
            $resolvedTaxMode = $taxService->resolveTaxMode(
                $itemTaxOverride,
                $productTaxMode === 'use_global' ? null : $productTaxMode,
                $globalTaxMode
            );
            
            $taxStatus = $item['tax_status'] ?? ($product->tax_status ?? 'taxable');
            $taxRate = (float) ($item['tax_percent'] ?? 0);

            $calc = $taxService->calculate([
                'unit_price' => $effectiveUnitCost,
                'quantity' => $qty,
                'discount_type' => $itemDiscountType,
                'discount_value' => $itemDiscountValue,
                'invoice_discount_amount' => $proRatedInvoiceDiscount,
                'tax_rate' => $taxRate,
                'tax_mode' => $resolvedTaxMode,
                'tax_status' => $taxStatus,
            ]);

            $calculatedItems[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'cost_price' => round($effectiveUnitCost, 2),
                'discount_percent' => $discountPercent,
                'discount_amount' => $calc['item_discount_amount'],
                'tax_percent' => $taxRate,
                'tax_amount' => $calc['added_tax_amount'],
                'line_total' => $calc['line_total'],
                'profit_margin' => (float) ($item['profit_margin'] ?? 0),
                'retail_price' => (float) ($item['retail_price'] ?? 0),
                'wholesale_price' => (float) ($item['wholesale_price'] ?? 0),
                'subtotal' => $calc['line_total'],
                'gross_amount' => $calc['gross_amount'],
                'discount_type' => $calc['discount_type'],
                'discount_value' => $calc['discount_value'],
                'amount_after_discount' => $calc['amount_after_item_discount'],
                'tax_mode' => $calc['tax_mode'],
                'tax_status' => $calc['tax_status'],
                'taxable_amount' => $calc['taxable_amount'],
                'included_tax_amount' => $calc['included_tax_amount'],
                'added_tax_amount' => $calc['added_tax_amount'],
            ];
            
            $calculatedSubtotal += $calc['line_total'];
            $calculatedAddedTaxTotal += $calc['added_tax_amount'];
            $calculatedIncludedTaxTotal += $calc['included_tax_amount'];
        }

        $overallDiscount = match ($invoiceDiscountType) {
            'percentage' => round($preDiscountGrossTotal * ($invoiceDiscountAmount / 100), 2),
            'fixed' => min($invoiceDiscountAmount, $preDiscountGrossTotal),
            default => 0,
        };
        
        $purchaseTax = (float) ($validated['purchase_tax'] ?? 0);
        // calculatedSubtotal already subtracted item discounts + prorated invoice discounts and added item taxes
        $grandTotal = round($calculatedSubtotal + $purchaseTax + ($divideShipping ? 0 : $shippingCost), 2);
        $paidAmount = min((float) ($validated['payment_amount'] ?? 0), $grandTotal);

        return [
            'items' => $calculatedItems,
            'subtotal' => round($calculatedSubtotal, 2),
            'overall_discount' => round($overallDiscount, 2),
            'purchase_tax' => round($purchaseTax, 2), // separate header-level tax
            'shipping_cost' => round($shippingCost, 2),
            'grand_total' => $grandTotal,
            'paid_amount' => round($paidAmount, 2),
            'due_amount' => round(max($grandTotal - $paidAmount, 0), 2),
            'added_tax_total' => round($calculatedAddedTaxTotal, 2),
            'included_tax_total' => round($calculatedIncludedTaxTotal, 2),
        ];
    }

    private function syncItems(Purchase $purchase, array $items): void
    {
        foreach ($items as $item) {
            $purchase->items()->create($item);
        }
    }

    private function syncPayment(Purchase $purchase, array $validated): void
    {
        $purchase->payments()->delete();
        $amount = (float) ($validated['payment_amount'] ?? 0);

        if ($amount <= 0 || $validated['payment_method'] === 'Credit / Due') {
            return;
        }

        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'payment_date' => $validated['purchase_date'],
            'amount' => $amount,
            'payment_method' => $validated['payment_method'],
            'reference_no' => $validated['payment_reference'] ?? null,
            'note' => 'Purchase payment',
            'created_by' => auth()->id(),
        ]);
    }

    private function applyStock(Purchase $purchase): void
    {
        if ($purchase->stock_updated) {
            return;
        }

        $purchase->loadMissing('items.product');

        foreach ($purchase->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $stock = ProductStoreStock::firstOrCreate([
                'product_id' => $product->id,
                'store_id' => $purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1)
            ], ['quantity' => 0]);
            $stock->quantity = (float) $stock->quantity + (float) $item->quantity;
            $stock->save();

            $effectiveCost = round((float) ($item->cost_price ?: $item->unit_cost), 2);
            $product->update([
                'cost_price' => $effectiveCost,
                'retail_price' => (float) $item->retail_price > 0 ? $item->retail_price : $product->retail_price,
                'wholesale_price' => (float) $item->wholesale_price > 0 ? $item->wholesale_price : $product->wholesale_price,
                'updated_by' => auth()->id(),
            ]);

            StockHistory::create([
                'product_id' => $product->id,
                'quantity_change' => (float) $item->quantity,
                'new_quantity' => (float) $stock->quantity,
                'type' => 'purchase',
                'reference_no' => $purchase->displayNo(),
                'note' => 'Purchase received',
                'created_by' => auth()->id(),
                'store_id' => $purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1),
            ]);

            $this->recordStockMovement($product->id, (float) $item->quantity, 'purchase', $purchase->id, $purchase->displayNo());
        }

        $purchase->forceFill(['stock_updated' => true])->save();
    }

    private function reverseStock(Purchase $purchase, string $note): void
    {
        $purchase->loadMissing('items.product');

        foreach ($purchase->items as $item) {
            $stock = ProductStoreStock::firstOrCreate([
                'product_id' => $item->product_id,
                'store_id' => $purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1)
            ], ['quantity' => 0]);
            $stock->quantity = max(0, (float) $stock->quantity - (float) $item->quantity);
            $stock->save();

            StockHistory::create([
                'product_id' => $item->product_id,
                'quantity_change' => -(float) $item->quantity,
                'new_quantity' => (float) $stock->quantity,
                'type' => 'purchase_reversal',
                'reference_no' => $purchase->displayNo(),
                'note' => $note,
                'created_by' => auth()->id(),
                'store_id' => $purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1),
            ]);
        }

        $purchase->forceFill(['stock_updated' => false])->save();
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

    private function generatePurchaseNo(): string
    {
        $prefix = SystemSettings::get('purchase_invoice_prefix', 'PO', 'invoice_settings') . '-' . now()->format('Ymd') . '-';
        $next = Purchase::where('purchase_no', 'like', $prefix . '%')->count() + 1;

        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Purchase::where('purchase_no', $number)->orWhere('invoice_no', $number)->exists());

        return $number;
    }

    private function ensurePurchaseModuleEnabled(): void
    {
        abort_unless(SystemSettings::bool('enable_purchase_module', true, 'business_settings'), 403, 'Purchase module is disabled in Settings.');
    }

    private function simplePdf(string $title, array $lines): string
    {
        $text = "BT\n/F1 18 Tf\n50 780 Td\n(" . $this->pdfText($title) . ") Tj\n/F1 11 Tf\n0 -28 Td\n";
        foreach ($lines as $line) {
            $text .= '(' . $this->pdfText($line) . ") Tj\n0 -18 Td\n";
        }
        $text .= "ET";

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            "5 0 obj << /Length " . strlen($text) . " >> stream\n{$text}\nendstream endobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
