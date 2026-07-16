<?php

namespace App\Http\Controllers;

use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailPosController extends Controller
{
    /**
     * Display the Retail POS interface.
     */
    public function index()
    {
        abort_unless(SystemSettings::bool('enable_retail_pos', true, 'pos_settings'), 403, 'Retail POS is disabled in Settings.');

        // Fetch Categories
        $categories = DB::table('categories')->whereNull('deleted_at')->get();

        $storeId = \App\Models\Setting::get('default_store_id', 1);
        if (request()->has('store_id') && \App\Models\Setting::get('enable_multi_store', false)) {
            $storeId = request('store_id');
        }

        // Fetch Products with stock level and available serials/IMEIs from specific store
        $products = DB::table('products')
            ->leftJoin('product_store_stock', function($join) use ($storeId) {
                $join->on('product_store_stock.product_id', '=', 'products.id')
                     ->where('product_store_stock.store_id', '=', $storeId);
            })
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select('products.*', 'categories.slug as category_slug', DB::raw('COALESCE(product_store_stock.quantity, 0) as stock'))
            ->whereNull('products.deleted_at')
            ->where('products.is_active', true)
            ->get();

        $variants = DB::table('product_variants')
            ->where('is_active', true)
            ->get()
            ->groupBy('product_id');

        // Fetch Available Serials grouped by product_id
        $serials = DB::table('product_serials')
            ->where('status', 'available')
            ->get()
            ->groupBy('product_id');

        $products = $products->map(function ($product) use ($serials, $variants) {
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

            $product->serials = isset($serials[$product->id]) 
                ? $serials[$product->id]->pluck('serial_number')->toArray() 
                : [];
            return $product;
        });

        // Fetch Customers
        $customers = DB::table('customers')
            ->whereNull('deleted_at')
            ->get();

        // Fetch Held Sales (Filter by retail POS type)
        $holdSales = DB::table('hold_sales')
            ->join('customers', 'customers.id', '=', 'hold_sales.customer_id')
            ->select('hold_sales.*', 'customers.name as customer_name')
            ->where('hold_sales.pos_type', 'retail')
            ->orderBy('hold_sales.id', 'desc')
            ->get();
        $resumeHold = request()->filled('hold_id') ? $this->resumeHoldPayload((int) request('hold_id'), 'retail') : null;

        // Fetch Payment Methods
        $paymentMethods = $this->enabledPaymentMethods();

        // Fetch Stores if multi store is enabled
        $enableMultiStore = \App\Models\Setting::get('enable_multi_store', false);
        $stores = $enableMultiStore ? DB::table('stores')->where('status', true)->get() : [];
        $activeStoreId = $storeId;
        $posSettings = SystemSettings::posPayload();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'products' => $products,
                'categories' => $categories,
                'customers' => $customers,
                'holdSales' => $holdSales,
                'resumeHold' => $resumeHold,
            ]);
        }

        return view('pos.retail', compact('categories', 'products', 'customers', 'holdSales', 'paymentMethods', 'resumeHold', 'enableMultiStore', 'stores', 'activeStoreId', 'posSettings'));
    }

    /**
     * Store a completed retail sale from the POS.
     */
    public function store(Request $request, \App\Services\MixedPaymentService $mixedPayments)
    {
        $request->validate([
            'customer_id' => 'required',
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric',
            'payable_amount' => 'required|numeric',
            'payments' => 'nullable|array',
            'payment_rows' => 'nullable|array',
            'paid_amount' => 'nullable|numeric',
            'cheque.cheque_number' => 'nullable|string|max:100',
            'cheque.cheque_date' => 'nullable|date',
            'cheque.bank_name' => 'nullable|string|max:150',
            'cheque.branch_name' => 'nullable|string|max:150',
            'cheque.account_holder_name' => 'nullable|string|max:150',
            'cheque.account_number' => 'nullable|string|max:100',
            'cheque.reference_number' => 'nullable|string|max:100',
            'hold_id' => 'nullable|integer|exists:hold_sales,id',
        ]);

        DB::beginTransaction();

        try {
            $today = date('Y-m-d');
            $invoiceNo = SystemSettings::get('retail_invoice_prefix', 'RETAIL', 'invoice_settings') . '-' . strtoupper(uniqid());

            // Compute total cost price & total profit price
            $totalCostAmount = 0;
            foreach ($request->items as $item) {
                $variant = !empty($item['variant_id']) ? DB::table('product_variants')->where('id', $item['variant_id'])->first() : null;
                $cost = $variant ? $variant->cost_price : (DB::table('products')->where('id', $item['product_id'])->value('cost_price') ?: 0);
                $totalCostAmount += $cost * $item['quantity'];
            }
            $totalProfitAmount = $request->payable_amount - $totalCostAmount;

            $paymentRows = $mixedPayments->rowsFromRequest($request->all(), (float) $request->payable_amount);

            // 1. Insert Sales Record
            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $request->customer_id,
                'invoice_no' => $invoiceNo,
                'sale_date' => $today,
                'pos_type' => 'retail',
                'total_amount' => $request->total_amount,
                'discount_type' => $request->discount_type ?? 'fixed',
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'payable_amount' => $request->payable_amount,
                'paid_amount' => 0,
                'cheque_hold_amount' => 0,
                'due_amount' => $request->payable_amount,
                'payment_status' => 'Due',
                'total_cost_amount' => $totalCostAmount,
                'total_profit_amount' => $totalProfitAmount,
                'store_id' => $request->store_id ?? \App\Models\Setting::get('default_store_id', 1),
                'status' => 'final',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Insert items and update stock/IMEIs
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
                    'price_type' => 'retail',
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
                    $storeId = $request->store_id ?? \App\Models\Setting::get('default_store_id', 1);
                    $storeStock = DB::table('product_store_stock')
                        ->where('product_id', $item['product_id'])
                        ->where('store_id', $storeId)
                        ->first();
                    
                    if ($storeStock) {
                        DB::table('product_store_stock')
                            ->where('id', $storeStock->id)
                            ->decrement('quantity', $item['quantity']);
                    } else {
                        DB::table('product_store_stock')->insert([
                            'product_id' => $item['product_id'],
                            'store_id' => $storeId,
                            'quantity' => -$item['quantity'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                // Log stock movement
                DB::table('stock_histories')->insert([
                    'product_id' => $item['product_id'],
                    'store_id' => $request->store_id ?? \App\Models\Setting::get('default_store_id', 1),
                    'quantity_change' => -$item['quantity'],
                    'new_quantity' => $variant ? 
                        DB::table('product_variants')->where('id', $variant->id)->value('stock_quantity') : 
                        DB::table('product_store_stock')->where('product_id', $item['product_id'])->where('store_id', $request->store_id ?? \App\Models\Setting::get('default_store_id', 1))->value('quantity'),
                    'type' => 'sale',
                    'reference_no' => $invoiceNo,
                    'note' => 'Retail Sale',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Update Serial/IMEI status to sold
                if (!empty($item['serial_no'])) {
                    DB::table('product_serials')
                        ->where('product_id', $item['product_id'])
                        ->where('serial_number', $item['serial_no'])
                        ->update([
                            'status' => 'sold',
                            'sale_id' => $saleId,
                            'updated_at' => now()
                        ]);
                }
            }

            DB::table('customers')->where('id', $request->customer_id)->increment('balance', $request->payable_amount);
            $allocation = $mixedPayments->allocateSale(\App\Models\Sale::findOrFail($saleId), $paymentRows, [
                'store_id' => $request->store_id ?? \App\Models\Setting::get('default_store_id', 1),
            ]);

            // Loyalty Points: 1 point per Rs. 1,000 spent
            $pointsEarned = floor($request->payable_amount / 1000);
            if ($pointsEarned > 0) {
                DB::table('customers')
                    ->where('id', $request->customer_id)
                    ->increment('points', $pointsEarned);
            }

            if ($request->filled('hold_id')) {
                DB::table('hold_sale_items')->where('hold_sale_id', $request->hold_id)->delete();
                DB::table('hold_sales')->where('id', $request->hold_id)->where('pos_type', 'retail')->delete();
            }

            \App\Models\ActivityLog::log('Sale', 'Retail POS', "Processed retail sale {$invoiceNo}");

            DB::commit();

            return response()->json([
                'success' => true,
                'invoice_no' => $invoiceNo,
                'sale_id' => $saleId,
                'change' => max(0, collect($paymentRows)->where('method', 'Cash')->sum('amount') - $request->payable_amount),
                'cheque_hold_amount' => $allocation['hold'],
                'points_earned' => $pointsEarned
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new customer quickly from Retail POS.
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
            'phone' => $request->phone ?? 'N/A',
            'email' => $request->email,
            'balance' => 0.00,
            'points' => 0,
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
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'hold_sale_items.variant_id')
            ->leftJoin('product_store_stock', function($join) {
                $join->on('product_store_stock.product_id', '=', 'products.id')
                     ->where('product_store_stock.store_id', '=', \App\Models\Setting::get('default_store_id', 1)); // Ideally match hold store if multi-store is enabled
            })
            ->where('hold_sale_items.hold_sale_id', $hold->id)
            ->select(
                'hold_sale_items.*',
                'products.name',
                'products.cost_price as product_cost_price',
                'categories.slug as category_slug',
                DB::raw('COALESCE(product_store_stock.quantity, 0) as stock'),
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
                    'discount_type' => 'fixed',
                    'line_total' => (float) $item->subtotal,
                    'note' => '',
                    'stock' => (float) ($item->variant_id ? $item->stock_quantity : $item->stock),
                    'is_smartphone' => $item->category_slug === 'smartphones',
                    'serials' => [],
                    'serial_no' => '',
                ];
            })
            ->values();

        return [
            'id' => $hold->id,
            'reference_no' => $hold->reference_no,
            'customer_id' => $hold->customer_id,
            'customer_name' => $hold->customer_name,
            'discountPercentage' => 0,
            'taxPercentage' => (float) SystemSettings::number('default_sale_tax', 15, 'tax_settings'),
            'shippingCost' => 0,
            'items' => $items,
        ];
    }

    private function enabledPaymentMethods()
    {
        $enabledNames = collect([
            'Cash' => SystemSettings::bool('enable_cash_payment', true, 'business_settings'),
            'Card' => SystemSettings::bool('enable_card_payment', true, 'business_settings'),
            'Bank Transfer' => SystemSettings::bool('enable_bank_transfer', true, 'business_settings'),
            'QR Payment' => SystemSettings::bool('enable_qr_payment', true, 'business_settings'),
            'Cheque' => SystemSettings::bool('enable_cheque_payment', false, 'business_settings'),
        ])->filter()->keys()->all();

        if ($enabledNames === []) {
            return collect();
        }

        return DB::table('payment_methods')
            ->where('is_active', true)
            ->whereIn('name', $enabledNames)
            ->get();
    }
}
