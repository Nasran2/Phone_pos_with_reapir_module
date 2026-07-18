<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::with(['sale', 'customer', 'creator'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                    ->orWhereHas('sale', fn ($saleQuery) => $saleQuery->where('invoice_no', 'like', "%{$search}%"))
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('sale_type')) {
            $query->whereHas('sale', fn ($saleQuery) => $saleQuery->where('pos_type', $request->input('sale_type')));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('return_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('return_date', '<=', $request->input('date_to'));
        }

        $returns = $query->latest('return_date')->latest('id')->paginate(12)->withQueryString();

        return view('sales.returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $salesQuery = Sale::with('customer')
            ->where('status', '!=', 'cancelled')
            ->latest('created_at');

        if ($request->filled('invoice_search')) {
            $search = $request->input('invoice_search');
            $salesQuery->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $sales = $salesQuery->limit(80)->get();

        $selectedSale = null;
        if ($request->filled('sale_id')) {
            $selectedSale = Sale::with(['customer', 'items.product', 'items.variant', 'items.returnedItems'])
                ->find($request->input('sale_id'));
        }
        
        $customers = Customer::orderBy('name')->get();
        $products = \App\Models\Product::with(['variants', 'serials' => function($q) {
            $q->where('status', 'available');
        }])->where('is_active', true)->get();

        return view('sales.returns.form', compact('sales', 'selectedSale', 'customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => ['required', 'in:invoice,old'],
            'sale_id' => ['required_if:source_type,invoice', 'nullable', 'exists:sales,id'],
            'customer_id' => ['required_if:source_type,old', 'nullable', 'exists:customers,id'],
            'return_type' => ['required', 'in:refund,replacement,exchange'],
            'return_date' => ['required', 'date'],
            'refund_method' => ['required', 'string'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.sale_item_id' => ['required_if:source_type,invoice', 'nullable', 'exists:sale_items,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.stock_action' => ['nullable', 'in:return_to_stock,scrap'],
            'exchange_items' => ['required_if:return_type,exchange', 'array'],
            'exchange_items.*.product_id' => ['required_with:exchange_items', 'exists:products,id'],
            'exchange_items.*.unit_price' => ['required_with:exchange_items', 'numeric', 'min:0'],
            'exchange_items.*.quantity' => ['required_with:exchange_items', 'numeric', 'min:1'],
        ]);

        DB::beginTransaction();

        try {
            $sale = null;
            $customerId = $validated['customer_id'] ?? null;
            $returnItemsData = [];
            $totalReturnAmount = 0;
            $totalReturnCost = 0;
            $totalReturnProfit = 0;

            if ($validated['source_type'] === 'invoice') {
                $sale = Sale::with(['customer', 'items.returnedItems', 'items.product'])->findOrFail($validated['sale_id']);
                $customerId = $sale->customer_id;
                
                foreach ($validated['items'] as $inputItem) {
                    $qty = (float) ($inputItem['quantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $saleItem = SaleItem::with(['product', 'variant', 'returnedItems'])->where('sale_id', $sale->id)->findOrFail($inputItem['sale_item_id']);
                    $returnedQty = (float) $saleItem->returnedItems->sum('quantity');
                    $remainingQty = (float) $saleItem->quantity - $returnedQty;

                    if ($qty > $remainingQty + 0.0001) {
                        return back()->withInput()->withErrors(['items' => "Return quantity for {$saleItem->product->name} exceeds available sold quantity."]);
                    }

                    $unitPrice = (float) ($saleItem->unit_price ?: $saleItem->selling_price);
                    $costPrice = (float) $saleItem->cost_price;
                    $lineTotal = round($unitPrice * $qty, 2); // this is unit price * qty
                    $costTotal = round($costPrice * $qty, 2);
                    $profitTotal = round(($unitPrice - $costPrice) * $qty, 2);
                    
                    // Tax calculation proportional to returned qty
                    $ratio = $saleItem->quantity > 0 ? ($qty / $saleItem->quantity) : 0;
                    $calc = [
                        'gross_amount' => round($saleItem->gross_amount * $ratio, 2),
                        'discount_type' => $saleItem->discount_type,
                        'discount_value' => $saleItem->discount_value,
                        'amount_after_discount' => round($saleItem->amount_after_discount * $ratio, 2),
                        'tax_mode' => $saleItem->tax_mode,
                        'tax_status' => $saleItem->tax_status,
                        'tax_rate' => $saleItem->tax_rate,
                        'taxable_amount' => round($saleItem->taxable_amount * $ratio, 2),
                        'included_tax_amount' => round($saleItem->included_tax_amount * $ratio, 2),
                        'added_tax_amount' => round($saleItem->added_tax_amount * $ratio, 2),
                        'tax_amount' => round($saleItem->tax_amount * $ratio, 2),
                        'line_total' => round($saleItem->line_total * $ratio, 2),
                    ];

                    $returnItemsData[] = [
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'variant_id' => $saleItem->variant_id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'cost_price' => $costPrice,
                        'line_total' => $calc['line_total'], // Use tax inclusive line total
                        'cost_total' => $costTotal,
                        'profit_total' => $profitTotal,
                        'stock_action' => $inputItem['stock_action'] ?? 'return_to_stock',
                        'sale_item' => $saleItem,
                        'calc' => $calc,
                    ];
                    $totalReturnAmount += $calc['line_total'];
                    $totalReturnAmount += $lineTotal;
                    $totalReturnCost += $costTotal;
                    $totalReturnProfit += $profitTotal;
                }
            } else {
                // Old Return
                foreach ($validated['items'] as $inputItem) {
                    $qty = (float) ($inputItem['quantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $product = \App\Models\Product::findOrFail($inputItem['product_id']);
                    $unitPrice = (float) ($inputItem['unit_price'] ?? $product->cost_price);
                    
                    // Fallback tax calculation for old return (no sale item)
                    $taxService = app(\App\Services\TaxCalculationService::class);
                    $globalTaxMode = \App\Support\SystemSettings::get('default_tax_mode', 'exclusive', 'tax_settings');
                    $productTaxMode = $product->tax_mode ?? 'use_global';
                    $resolvedTaxMode = $taxService->resolveTaxMode(null, $productTaxMode === 'use_global' ? null : $productTaxMode, $globalTaxMode);
                    
                    $calc = $taxService->calculate([
                        'unit_price' => $unitPrice,
                        'quantity' => $qty,
                        'discount_type' => 'fixed',
                        'discount_value' => 0,
                        'invoice_discount_amount' => 0,
                        'tax_rate' => (float) ($product->tax_rate ?? 0),
                        'tax_mode' => $resolvedTaxMode,
                        'tax_status' => $product->tax_status ?? 'taxable',
                    ]);
                    
                    $lineTotal = $calc['line_total'];
                    
                    $returnItemsData[] = [
                        'sale_item_id' => null,
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'cost_price' => $product->cost_price, // current cost
                        'line_total' => $lineTotal,
                        'cost_total' => round((float)$product->cost_price * $qty, 2),
                        'profit_total' => 0,
                        'stock_action' => $inputItem['stock_action'] ?? 'return_to_stock',
                        'sale_item' => null,
                        'calc' => $calc,
                    ];
                    $totalReturnAmount += $lineTotal;
                    $totalReturnCost += round((float)$product->cost_price * $qty, 2);
                }
            }

            if (empty($returnItemsData)) {
                return back()->withInput()->withErrors(['items' => 'Select at least one item to return.']);
            }

            // Calculate Exchange Items
            $exchangeItemsData = [];
            $totalExchangeAmount = 0;
            $totalExchangeCost = 0;
            $totalExchangeProfit = 0;

            if ($validated['return_type'] === 'exchange') {
                foreach ($validated['exchange_items'] ?? [] as $exItem) {
                    $product = \App\Models\Product::findOrFail($exItem['product_id']);
                    $qty = (float) $exItem['quantity'];
                    $unitPrice = (float) $exItem['unit_price'];
                    $costPrice = (float) $product->cost_price;
                    $lineTotal = round($unitPrice * $qty, 2);
                    $costTotal = round($costPrice * $qty, 2);
                    
                    $exchangeItemsData[] = [
                        'product' => $product,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'cost_price' => $costPrice,
                        'line_total' => $lineTotal,
                        'cost_total' => $costTotal,
                        'profit_amount' => round(($unitPrice - $costPrice) * $qty, 2)
                    ];
                    $totalExchangeAmount += $lineTotal;
                    $totalExchangeCost += $costTotal;
                    $totalExchangeProfit += round(($unitPrice - $costPrice) * $qty, 2);
                }
            } elseif ($validated['return_type'] === 'replacement') {
                // Replacement is effectively an exchange for the EXACT same items being returned
                foreach ($returnItemsData as $retItem) {
                    $product = \App\Models\Product::findOrFail($retItem['product_id']);
                    $exchangeItemsData[] = [
                        'product' => $product,
                        'quantity' => $retItem['quantity'],
                        'unit_price' => $retItem['unit_price'],
                        'cost_price' => $retItem['cost_price'],
                        'line_total' => $retItem['line_total'],
                        'cost_total' => $retItem['cost_total'],
                        'profit_amount' => 0 // Replacement makes no profit
                    ];
                    $totalExchangeAmount += $retItem['line_total'];
                    $totalExchangeCost += $retItem['cost_total'];
                }
            }

            $netDifference = $totalExchangeAmount - $totalReturnAmount;
            
            // Handle Refund/Payment
            $refundAmount = 0;
            $paymentAmount = 0;
            $refundMethod = $validated['refund_method'];
            $paymentMethod = $validated['payment_method'] ?? 'Cash';

            if ($netDifference < 0) {
                // Shop owes customer (Refund)
                $amountToRefund = abs($netDifference);
                if (in_array($refundMethod, ['No Refund', 'Customer Credit'])) {
                    $refundAmount = 0;
                } else {
                    $refundAmount = min((float) ($validated['refund_amount'] ?? $amountToRefund), $amountToRefund);
                }
            } elseif ($netDifference > 0) {
                // Customer owes shop (Payment)
                if ($paymentMethod !== 'Due') {
                    $paymentAmount = min((float) ($validated['payment_amount'] ?? $netDifference), $netDifference);
                }
            }

            // Aggregate Tax Totals
            $taxableTotal = 0;
            $includedTaxTotal = 0;
            $addedTaxTotal = 0;
            $taxTotal = 0;
            foreach ($returnItemsData as $retData) {
                $calc = $retData['calc'];
                $taxableTotal += $calc['taxable_amount'] ?? 0;
                $includedTaxTotal += $calc['included_tax_amount'] ?? 0;
                $addedTaxTotal += $calc['added_tax_amount'] ?? 0;
                $taxTotal += $calc['tax_amount'] ?? 0;
            }

            // Create Return Record
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale ? $sale->id : null,
                'customer_id' => $customerId,
                'return_no' => $this->generateReturnNo(),
                'return_date' => $validated['return_date'],
                'return_type' => $validated['return_type'],
                'total_amount' => $totalReturnAmount,
                'taxable_total' => $taxableTotal,
                'included_tax_total' => $includedTaxTotal,
                'added_tax_total' => $addedTaxTotal,
                'tax_total' => $taxTotal,
                'payable_total' => $totalReturnAmount,
                'refund_method' => $netDifference <= 0 ? $refundMethod : 'No Refund',
                'refund_amount' => $refundAmount,
                'refund_status' => $netDifference < 0 ? $this->refundStatus($refundMethod, $refundAmount, abs($netDifference)) : 'Completed',
                'reason' => $validated['reason'] ?? null,
                'note' => $validated['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create Return Items
            foreach ($returnItemsData as $retData) {
                $calc = $retData['calc'];
                $saleReturn->items()->create([
                    'sale_item_id' => $retData['sale_item_id'],
                    'product_id' => $retData['product_id'],
                    'variant_id' => $retData['variant_id'],
                    'return_type' => $validated['return_type'],
                    'stock_action' => $retData['stock_action'],
                    'quantity' => $retData['quantity'],
                    'unit_price' => $retData['unit_price'],
                    'refund_amount' => $retData['line_total'],
                    'selling_price' => $retData['unit_price'],
                    'cost_price' => $retData['cost_price'],
                    'profit_amount' => $retData['profit_total'],
                    'subtotal' => $retData['line_total'],
                    'gross_amount' => $calc['gross_amount'] ?? 0,
                    'discount_type' => $calc['discount_type'] ?? null,
                    'discount_value' => $calc['discount_value'] ?? 0,
                    'amount_after_discount' => $calc['amount_after_discount'] ?? ($calc['amount_after_item_discount'] ?? 0),
                    'tax_mode' => $calc['tax_mode'] ?? 'exclusive',
                    'tax_status' => $calc['tax_status'] ?? 'taxable',
                    'tax_rate' => $calc['tax_rate'] ?? 0,
                    'taxable_amount' => $calc['taxable_amount'] ?? 0,
                    'included_tax_amount' => $calc['included_tax_amount'] ?? 0,
                    'added_tax_amount' => $calc['added_tax_amount'] ?? 0,
                ]);

                // Restore Stock or Scrap
                if ($retData['stock_action'] === 'scrap') {
                    // Create an Expense
                    $expenseCategory = \App\Models\ExpenseCategory::firstOrCreate(
                        ['name' => 'Scrap & Damaged Returns'],
                        ['description' => 'Cost of scrapped and damaged returns']
                    );
                    \App\Models\Expense::create([
                        'expense_category_id' => $expenseCategory->id,
                        'amount' => $retData['cost_total'],
                        'expense_date' => $validated['return_date'],
                        'reference_no' => $saleReturn->return_no,
                        'note' => 'Scrap return for Product ID ' . $retData['product_id'],
                        'created_by' => auth()->id(),
                    ]);
                    
                    // Create DamagedStock record to keep tracking straight
                    \App\Models\DamagedStock::create([
                        'damage_no' => 'DS-RET-' . rand(1000, 9999) . '-' . $retData['product_id'],
                        'product_id' => $retData['product_id'],
                        'quantity' => $retData['quantity'],
                        'damage_type' => 'Customer Return',
                        'reason' => 'Scrapped during return: ' . $saleReturn->return_no,
                        'status' => 'Scrapped',
                        'estimated_loss' => $retData['cost_total'],
                        'created_by' => auth()->id(),
                    ]);
                } else {
                    if ($retData['sale_item']) {
                        $this->restoreStock($retData['sale_item'], $retData['quantity'], $saleReturn->return_no, clone $sale);
                    } else {
                        // Legacy Return stock restore
                        $this->incrementProductStock($retData['product_id'], $retData['quantity'], $saleReturn->return_no);
                    }
                }
            }

            // If Invoice Return, update original sale
            if ($sale) {
                $oldDue = (float) $sale->due_amount;
                // Only reduce due by return amount if it's a refund and they chose Customer Credit? 
                // Actually, old system applied returns directly to due first. We should keep it simple: Return reduces payable.
                $dueReduction = min($oldDue, $totalReturnAmount);
                
                $sale->total_amount = max(0, (float) $sale->total_amount - $totalReturnAmount);
                $sale->payable_amount = max(0, (float) $sale->payable_amount - $totalReturnAmount);
                
                // If they did NOT give cash refund, then the paid amount stays same (or reduces if it was fully paid)
                // For simplicity, we just recalculate due.
                $sale->paid_amount = max(0, (float) $sale->paid_amount - $refundAmount);
                $sale->due_amount = max(0, (float) $sale->payable_amount - (float) $sale->paid_amount);
                
                $sale->total_cost_amount = max(0, (float) $sale->total_cost_amount - $totalReturnCost);
                $sale->total_profit_amount = max(0, (float) $sale->total_profit_amount - $totalReturnProfit);
                $sale->payment_status = $sale->payable_amount <= 0.009 ? 'Refunded' : $this->paymentStatus($sale);
                $sale->save();
            }

            // Create Exchange Sale if needed
            if (!empty($exchangeItemsData)) {
                $exchangeSale = Sale::create([
                    'customer_id' => $customerId,
                    'invoice_no' => \App\Models\Sale::generateInvoiceNo(),
                    'sale_date' => $validated['return_date'],
                    'pos_type' => 'retail',
                    'total_amount' => $totalExchangeAmount,
                    'discount_amount' => 0,
                    'payable_amount' => $totalExchangeAmount,
                    'paid_amount' => $totalExchangeAmount > 0 ? min($paymentAmount + $totalReturnAmount, $totalExchangeAmount) : 0, // Apply return credit
                    'due_amount' => max(0, $totalExchangeAmount - ($paymentAmount + $totalReturnAmount)),
                    'total_cost_amount' => $totalExchangeCost,
                    'total_profit_amount' => $totalExchangeProfit,
                    'status' => 'final',
                    'payment_status' => 'Paid',
                    'notes' => 'Exchange invoice for Return ' . $saleReturn->return_no,
                    'created_by' => auth()->id(),
                ]);

                foreach ($exchangeItemsData as $exItem) {
                    $newSaleItem = $exchangeSale->items()->create([
                        'product_id' => $exItem['product']->id,
                        'quantity' => $exItem['quantity'],
                        'cost_price' => $exItem['cost_price'],
                        'selling_price' => $exItem['unit_price'],
                        'unit_price' => $exItem['unit_price'],
                        'profit_amount' => $exItem['profit_amount'],
                        'subtotal' => $exItem['line_total'],
                        'line_total' => $exItem['line_total'],
                    ]);
                    
                    // Reduce Stock
                    $this->decrementProductStock($exItem['product']->id, $exItem['quantity'], $exchangeSale->invoice_no);
                }

                $exchangeSale->payment_status = $exchangeSale->due_amount > 0 ? 'Partial' : 'Paid';
                if ($exchangeSale->due_amount == $exchangeSale->payable_amount) $exchangeSale->payment_status = 'Due';
                $exchangeSale->save();

                $saleReturn->update(['exchange_sale_id' => $exchangeSale->id]);
            }

            // Handle Customer Ledger Adjustments
            if ($customerId) {
                $customer = \App\Models\Customer::find($customerId);
                if ($customer) {
                    if ($netDifference < 0 && $refundMethod === 'Customer Credit') {
                        // Shop owes customer, added to due (Negative due = credit)
                        $this->adjustCustomerBalance($customer, $netDifference, 'sale_return', $saleReturn, $saleReturn->return_no);
                    } elseif ($netDifference > 0 && $paymentMethod === 'Due') {
                        // Customer owes shop, added to due
                        $this->adjustCustomerBalance($customer, $netDifference, 'sale_exchange', $exchangeSale ?? $saleReturn, $saleReturn->return_no);
                    }
                }
            }

            ActivityLog::log('Create', 'Sales Return Module', "Processed return/exchange {$saleReturn->return_no}");
            DB::commit();

            return redirect()->route('sales-returns.show', $saleReturn)->with('success', 'Return/Exchange completed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->withErrors(['return' => 'Unable to process return. ' . $e->getMessage()]);
        }
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale', 'customer', 'creator', 'items.product', 'items.variant']);

        return view('sales.returns.show', compact('saleReturn'));
    }

    public function destroy(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale.customer', 'items.saleItem']);

        DB::transaction(function () use ($saleReturn) {
            $sale = $saleReturn->sale;
            foreach ($saleReturn->items as $item) {
                $this->reverseReturnStock($item, $saleReturn->return_no, clone $sale);
            }

            if ($sale) {
                $sale->total_amount = (float) $sale->total_amount + (float) $saleReturn->total_amount;
                $sale->payable_amount = (float) $sale->payable_amount + (float) $saleReturn->total_amount;
                $sale->paid_amount = (float) $sale->paid_amount + (float) $saleReturn->refund_amount;
                $sale->due_amount = max(0, (float) $sale->payable_amount - (float) $sale->paid_amount);
                $sale->total_profit_amount = (float) $sale->total_profit_amount + (float) $saleReturn->items->sum('profit_amount');
                $sale->payment_status = $this->paymentStatus($sale);
                $sale->save();

                if ($sale->customer) {
                    $this->adjustCustomerBalance($sale->customer, (float) $saleReturn->total_amount - (float) $saleReturn->refund_amount, 'sale_return_deleted', $saleReturn, $saleReturn->return_no);
                }
            }

            $returnNo = $saleReturn->return_no;
            $saleReturn->items()->delete();
            $saleReturn->delete();

            ActivityLog::log('Delete', 'Sales Return Module', "Deleted sale return {$returnNo}");
        });

        return redirect()->route('sales-returns.index')->with('success', 'Sale return deleted successfully.');
    }

    private function restoreStock(SaleItem $saleItem, float $quantity, string $referenceNo, Sale $sale): void
    {
        if ($saleItem->variant_id) {
            DB::table('product_variants')->where('id', $saleItem->variant_id)->increment('stock_quantity', $quantity);
            $newQuantity = (float) DB::table('product_variants')->where('id', $saleItem->variant_id)->value('stock_quantity');
        } else {
            $stock = ProductStoreStock::firstOrCreate([
                'product_id' => $saleItem->product_id,
                'store_id' => $sale->store_id ?? \App\Models\Setting::get('default_store_id', 1)
            ], ['quantity' => 0]);
            $stock->quantity = (float) $stock->quantity + $quantity;
            $stock->save();
            $newQuantity = (float) $stock->quantity;
        }

        StockHistory::create([
            'product_id' => $saleItem->product_id,
            'variant_id' => $saleItem->variant_id,
            'quantity_change' => $quantity,
            'new_quantity' => $newQuantity,
            'type' => 'sale_return',
            'reference_no' => $referenceNo,
            'note' => 'Sale return',
            'store_id' => $sale->store_id ?? \App\Models\Setting::get('default_store_id', 1),
            'created_by' => auth()->id(),
        ]);

        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->insert([
                'product_id' => $saleItem->product_id,
                'quantity' => $quantity,
                'type' => 'sale_return',
                'reference_id' => $saleItem->sale_id,
                'reference_no' => $referenceNo,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function reverseReturnStock(object $item, string $referenceNo, Sale $sale): void
    {
        if ($item->variant_id) {
            DB::table('product_variants')->where('id', $item->variant_id)->decrement('stock_quantity', (float) $item->quantity);
            $newQuantity = (float) DB::table('product_variants')->where('id', $item->variant_id)->value('stock_quantity');
        } else {
            $stock = ProductStoreStock::firstOrCreate([
                'product_id' => $item->product_id,
                'store_id' => $sale->store_id ?? \App\Models\Setting::get('default_store_id', 1)
            ], ['quantity' => 0]);
            $stock->quantity = max(0, (float) $stock->quantity - (float) $item->quantity);
            $stock->save();
            $newQuantity = (float) $stock->quantity;
        }

        StockHistory::create([
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'quantity_change' => -(float) $item->quantity,
            'new_quantity' => $newQuantity,
            'type' => 'sale_return_deleted',
            'reference_no' => $referenceNo,
            'note' => 'Sale return processed',
            'created_by' => auth()->id(),
            'store_id' => $sale->store_id ?? \App\Models\Setting::get('default_store_id', 1),
        ]);
    }

    private function incrementProductStock(int $productId, float $quantity, string $returnNo): void
    {
        $product = \App\Models\Product::find($productId);
        if ($product && $product->manage_stock) {
            $product->increment('current_stock', $quantity);

            \App\Models\StockHistory::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'type' => 'sale_return',
                'quantity' => $quantity,
                'reference_type' => SaleReturn::class,
                'reference_id' => null,
                'reference_no' => $returnNo,
                'note' => "Restored stock from Legacy Return {$returnNo}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function decrementProductStock(int $productId, float $quantity, string $invoiceNo): void
    {
        $product = \App\Models\Product::find($productId);
        if ($product && $product->manage_stock) {
            $product->decrement('current_stock', $quantity);

            \App\Models\StockHistory::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'type' => 'sale',
                'quantity' => -$quantity,
                'reference_type' => Sale::class,
                'reference_id' => null,
                'reference_no' => $invoiceNo,
                'note' => "Deducted stock for Exchange Invoice {$invoiceNo}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function adjustCustomerBalance(Customer $customer, float $amount, string $type, ?object $source = null, ?string $referenceNo = null): void
    {
        if (abs($amount) < 0.01) {
            return;
        }

        $customer->balance = (float) $customer->balance + $amount;
        $customer->save();

        CustomerTransaction::create([
            'customer_id' => $customer->id,
            'transactionable_type' => $source ? $source::class : null,
            'transactionable_id' => $source->id ?? null,
            'transaction_date' => now()->toDateString(),
            'type' => $type,
            'debit' => $amount > 0 ? $amount : 0,
            'credit' => $amount < 0 ? abs($amount) : 0,
            'balance_after' => $customer->balance,
            'reference_no' => $referenceNo,
            'note' => str_replace('_', ' ', $type),
            'created_by' => auth()->id(),
        ]);
    }

    private function refundStatus(string $method, float $refundAmount, float $refundableAmount): string
    {
        if ($method === 'No Refund') {
            return 'No Refund';
        }

        if ($method === 'Customer Credit') {
            return 'Credited';
        }

        return $refundAmount >= $refundableAmount - 0.009 ? 'Refunded' : 'Partial';
    }

    private function paymentStatus(Sale $sale): string
    {
        if ($sale->status === 'cancelled') {
            return 'Cancelled';
        }

        if ((float) $sale->due_amount <= 0.009) {
            return 'Paid';
        }

        return (float) $sale->paid_amount > 0 ? 'Partial' : 'Due';
    }

    private function generateReturnNo(): string
    {
        $prefix = 'SR-' . now()->format('Ymd') . '-';
        $next = SaleReturn::where('return_no', 'like', $prefix . '%')->count() + 1;

        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (SaleReturn::where('return_no', $number)->exists());

        return $number;
    }
}
