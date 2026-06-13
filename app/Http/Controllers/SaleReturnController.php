<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\ProductStock;
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

        return view('sales.returns.form', compact('sales', 'selectedSale'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'return_date' => ['required', 'date'],
            'refund_method' => ['required', Rule::in(['Cash', 'Bank Transfer', 'Card', 'Customer Credit', 'No Refund'])],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sale = Sale::with(['customer', 'items.returnedItems'])->findOrFail($validated['sale_id']);

        DB::beginTransaction();

        try {
            $returnItems = [];
            $totalAmount = 0;
            $totalCost = 0;
            $totalProfit = 0;

            foreach ($validated['items'] as $inputItem) {
                $qty = (float) ($inputItem['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $saleItem = SaleItem::with(['product', 'variant', 'returnedItems'])
                    ->where('sale_id', $sale->id)
                    ->findOrFail($inputItem['sale_item_id']);

                $returnedQty = (float) $saleItem->returnedItems->sum('quantity');
                $remainingQty = (float) $saleItem->quantity - $returnedQty;

                if ($qty > $remainingQty + 0.0001) {
                    return back()->withInput()->withErrors([
                        'items' => "Return quantity for {$saleItem->product->name} is higher than remaining sold quantity.",
                    ]);
                }

                $unitPrice = (float) ($saleItem->unit_price ?: $saleItem->selling_price);
                $lineTotal = round($unitPrice * $qty, 2);
                $costTotal = round((float) $saleItem->cost_price * $qty, 2);
                $profitTotal = round(($unitPrice - (float) $saleItem->cost_price) * $qty, 2);

                $returnItems[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'cost_total' => $costTotal,
                    'profit_total' => $profitTotal,
                ];
                $totalAmount += $lineTotal;
                $totalCost += $costTotal;
                $totalProfit += $profitTotal;
            }

            if (empty($returnItems)) {
                return back()->withInput()->withErrors(['items' => 'Select at least one item quantity to return.']);
            }

            $oldDue = (float) $sale->due_amount;
            $dueReduction = min($oldDue, $totalAmount);
            $remainingRefundable = max(0, $totalAmount - $dueReduction);
            $requestedRefund = min((float) ($validated['refund_amount'] ?? 0), $remainingRefundable);
            $refundAmount = in_array($validated['refund_method'], ['No Refund', 'Customer Credit'], true) ? 0 : $requestedRefund;
            $creditAmount = $validated['refund_method'] === 'Customer Credit' ? $remainingRefundable : 0;

            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_no' => $this->generateReturnNo(),
                'return_date' => $validated['return_date'],
                'total_amount' => $totalAmount,
                'refund_method' => $validated['refund_method'],
                'refund_amount' => $refundAmount,
                'refund_status' => $this->refundStatus($validated['refund_method'], $refundAmount, $remainingRefundable),
                'reason' => $validated['reason'] ?? null,
                'note' => $validated['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($returnItems as $returnItem) {
                $saleItem = $returnItem['sale_item'];

                $saleReturn->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'variant_id' => $saleItem->variant_id,
                    'quantity' => $returnItem['quantity'],
                    'unit_price' => $returnItem['unit_price'],
                    'refund_amount' => $returnItem['line_total'],
                    'selling_price' => $returnItem['unit_price'],
                    'cost_price' => $saleItem->cost_price,
                    'profit_amount' => $returnItem['profit_total'],
                    'subtotal' => $returnItem['line_total'],
                ]);

                $this->restoreStock($saleItem, $returnItem['quantity'], $saleReturn->return_no);
            }

            $sale->total_amount = max(0, (float) $sale->total_amount - $totalAmount);
            $sale->payable_amount = max(0, (float) $sale->payable_amount - $totalAmount);
            $sale->paid_amount = max(0, (float) $sale->paid_amount - $refundAmount);
            $sale->due_amount = max(0, $oldDue - $dueReduction);
            $sale->total_cost_amount = max(0, (float) $sale->total_cost_amount - $totalCost);
            $sale->total_profit_amount = max(0, (float) $sale->total_profit_amount - $totalProfit);
            $sale->payment_status = $sale->payable_amount <= 0.009 ? 'Refunded' : $this->paymentStatus($sale);
            $sale->save();

            if ($sale->customer) {
                $customerAdjustment = -$dueReduction - $creditAmount;
                $this->adjustCustomerBalance($sale->customer, $customerAdjustment, 'sale_return', $saleReturn, $saleReturn->return_no);
            }

            ActivityLog::log('Create', 'Sales Return Module', "Created sale return {$saleReturn->return_no}");
            DB::commit();

            return redirect()->route('sales-returns.show', $saleReturn)->with('success', 'Sale return completed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['return' => 'Unable to save return. ' . $e->getMessage()]);
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
                $this->reverseReturnStock($item, $saleReturn->return_no);
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

    private function restoreStock(SaleItem $saleItem, float $quantity, string $referenceNo): void
    {
        if ($saleItem->variant_id) {
            DB::table('product_variants')->where('id', $saleItem->variant_id)->increment('stock_quantity', $quantity);
            $newQuantity = (float) DB::table('product_variants')->where('id', $saleItem->variant_id)->value('stock_quantity');
        } else {
            $stock = ProductStock::firstOrCreate(['product_id' => $saleItem->product_id], ['quantity' => 0]);
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

    private function reverseReturnStock(object $item, string $referenceNo): void
    {
        if ($item->variant_id) {
            DB::table('product_variants')->where('id', $item->variant_id)->decrement('stock_quantity', (float) $item->quantity);
            $newQuantity = (float) DB::table('product_variants')->where('id', $item->variant_id)->value('stock_quantity');
        } else {
            $stock = ProductStock::firstOrCreate(['product_id' => $item->product_id], ['quantity' => 0]);
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
            'note' => 'Sale return deleted',
            'created_by' => auth()->id(),
        ]);
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
