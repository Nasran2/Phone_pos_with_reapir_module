<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WholesalePosController extends Controller
{
    /**
     * Display the Wholesale POS interface.
     */
    public function index()
    {
        // Fetch Categories
        $categories = DB::table('categories')->get();

        // Fetch Products with current stock levels
        $products = DB::table('products')
            ->leftJoin('product_stock', 'product_stock.product_id', '=', 'products.id')
            ->select('products.*', DB::raw('COALESCE(product_stock.quantity, 0) as stock'))
            ->whereNull('products.deleted_at')
            ->get();

        $variants = DB::table('product_variants')
            ->where('is_active', true)
            ->get()
            ->groupBy('product_id');

        $products = $products->map(function ($product) use ($variants) {
            $productVariants = ($variants[$product->id] ?? collect())->map(function ($variant) {
                $labelParts = array_filter([$variant->color, $variant->storage, $variant->ram]);

                return [
                    'id' => $variant->id,
                    'label' => $labelParts ? implode(' / ', $labelParts) : $variant->sku,
                    'sku' => $variant->sku,
                    'color' => $variant->color,
                    'storage' => $variant->storage,
                    'ram' => $variant->ram,
                    'cost_price' => (float) ($variant->cost_price ?? 0),
                    'retail_price' => (float) ($variant->retail_price ?? 0),
                    'wholesale_price' => (float) ($variant->wholesale_price ?? 0),
                    'stock_quantity' => (float) $variant->stock_quantity,
                ];
            })->values();

            $product->variants = $productVariants;
            if ($productVariants->isNotEmpty()) {
                $product->stock = $productVariants->sum('stock_quantity');
            }

            return $product;
        });

        // Fetch Customers
        $customers = DB::table('customers')
            ->whereNull('deleted_at')
            ->get();

        // Fetch Held Sales
        $holdSales = DB::table('hold_sales')
            ->join('customers', 'customers.id', '=', 'hold_sales.customer_id')
            ->select('hold_sales.*', 'customers.name as customer_name')
            ->orderBy('hold_sales.id', 'desc')
            ->get();
        $resumeHold = request()->filled('hold_id') ? $this->resumeHoldPayload((int) request('hold_id'), 'wholesale') : null;
            
        // Fetch Payment Methods
        $paymentMethods = DB::table('payment_methods')
            ->where('is_active', true)
            ->get();

        return view('pos.wholesale', compact('categories', 'products', 'customers', 'holdSales', 'paymentMethods', 'resumeHold'));
    }

    /**
     * Store a completed sale from the POS.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'pos_type' => 'required|in:retail,wholesale',
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric',
            'payable_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'payment_method' => 'required',
            'hold_id' => 'nullable|integer|exists:hold_sales,id',
        ]);

        DB::beginTransaction();

        try {
            $today = date('Y-m-d');
            $invoiceNo = 'INV-' . strtoupper(uniqid());

            // Calculate cost & profit
            $totalCostAmount = 0;
            foreach ($request->items as $item) {
                $variant = !empty($item['variant_id']) ? DB::table('product_variants')->where('id', $item['variant_id'])->first() : null;
                $cost = $variant ? $variant->cost_price : (DB::table('products')->where('id', $item['product_id'])->value('cost_price') ?: 0);
                $totalCostAmount += $cost * $item['quantity'];
            }
            $totalProfitAmount = $request->payable_amount - $totalCostAmount;

            $dueAmount = max(0, $request->payable_amount - $request->paid_amount);
            $paymentStatus = $dueAmount <= 0.009 ? 'Paid' : ((float) $request->paid_amount > 0 ? 'Partial' : 'Due');

            // 1. Insert Sales Record
            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $request->customer_id,
                'invoice_no' => $invoiceNo,
                'sale_date' => $today,
                'pos_type' => $request->pos_type,
                'total_amount' => $request->total_amount,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'payable_amount' => $request->payable_amount,
                'paid_amount' => $request->paid_amount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'total_cost_amount' => $totalCostAmount,
                'total_profit_amount' => $totalProfitAmount,
                'status' => 'final',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Insert Items & Update Stock
            foreach ($request->items as $item) {
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                $variant = !empty($item['variant_id']) ? DB::table('product_variants')->where('id', $item['variant_id'])->first() : null;
                $itemCost = $variant ? $variant->cost_price : ($product ? $product->cost_price : 0);
                
                $itemBasePrice = $item['selling_price'];
                $itemQty = $item['quantity'];
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemDiscountType = $item['discount_type'] ?? 'fixed';

                $itemSubtotal = $itemBasePrice * $itemQty;
                if ($itemDiscountType === 'percentage') {
                    $itemSubtotal -= ($itemSubtotal * ($itemDiscount / 100));
                } else {
                    $itemSubtotal = max(0, $itemSubtotal - $itemDiscount);
                }

                $effectiveSellingPrice = $itemQty > 0 ? $itemSubtotal / $itemQty : 0;
                $itemProfit = ($effectiveSellingPrice - $itemCost) * $itemQty;

                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $itemQty,
                    'cost_price' => $itemCost,
                    'selling_price' => $effectiveSellingPrice,
                    'price_type' => $request->pos_type,
                    'profit_amount' => $itemProfit,
                    'subtotal' => $itemSubtotal,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if ($variant) {
                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->decrement('stock_quantity', $item['quantity']);
                } else {
                    DB::table('product_stock')
                        ->where('product_id', $item['product_id'])
                        ->decrement('quantity', $item['quantity']);
                }

                // Insert Stock Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'],
                    'type' => 'sale',
                    'reference_id' => $saleId,
                    'reference_no' => $invoiceNo,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 3. Insert Payment
            if ($request->paid_amount > 0) {
                DB::table('sale_payments')->insert([
                    'sale_id' => $saleId,
                    'payment_date' => $today,
                    'amount' => $request->paid_amount,
                    'payment_method' => $request->payment_method,
                    'reference_no' => $request->payment_reference,
                    'note' => 'POS Checkout Payment',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 4. Update Customer Balance if there is a due balance
            if ($dueAmount > 0) {
                DB::table('customers')
                    ->where('id', $request->customer_id)
                    ->increment('balance', $dueAmount);
            }

            if ($request->filled('hold_id')) {
                DB::table('hold_sale_items')->where('hold_sale_id', $request->hold_id)->delete();
                DB::table('hold_sales')->where('id', $request->hold_id)->where('pos_type', 'wholesale')->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'invoice_no' => $invoiceNo,
                'sale_id' => $saleId,
                'change' => max(0, $request->paid_amount - $request->payable_amount)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new customer quickly from POS.
     */
    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email'
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'balance' => 0.00,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $customer = DB::table('customers')->where('id', $customerId)->first();

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    private function resumeHoldPayload(int $holdId, string $posType): ?array
    {
        $hold = DB::table('hold_sales')
            ->leftJoin('customers', 'customers.id', '=', 'hold_sales.customer_id')
            ->select('hold_sales.*', 'customers.name as customer_name')
            ->where('hold_sales.id', $holdId)
            ->where('hold_sales.pos_type', $posType)
            ->first();

        if (!$hold) {
            return null;
        }

        $items = DB::table('hold_sale_items')
            ->join('products', 'products.id', '=', 'hold_sale_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'hold_sale_items.variant_id')
            ->leftJoin('product_stock', 'product_stock.product_id', '=', 'products.id')
            ->where('hold_sale_items.hold_sale_id', $hold->id)
            ->select(
                'hold_sale_items.*',
                'products.name',
                'products.cost_price as product_cost_price',
                DB::raw('COALESCE(product_stock.quantity, 0) as stock'),
                'product_variants.color',
                'product_variants.storage',
                'product_variants.ram',
                'product_variants.stock_quantity'
            )
            ->get()
            ->map(function ($item) {
                $variantLabel = collect([$item->color, $item->storage, $item->ram])->filter()->implode(' / ');

                return [
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'variant_label' => $variantLabel,
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'cost_price' => (float) ($item->cost_price ?: $item->product_cost_price),
                    'selling_price' => (float) $item->selling_price,
                    'discount_amount' => 0,
                    'discount_type' => 'percentage',
                    'line_total' => (float) $item->subtotal,
                    'note' => '',
                    'stock' => (float) ($item->variant_id ? $item->stock_quantity : $item->stock),
                ];
            })
            ->values();

        return [
            'id' => $hold->id,
            'reference_no' => $hold->reference_no,
            'customer_id' => $hold->customer_id,
            'customer_name' => $hold->customer_name,
            'discountPercentage' => 0,
            'taxPercentage' => 15,
            'shippingCost' => 0,
            'items' => $items,
        ];
    }
}
