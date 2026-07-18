<?php

namespace App\Http\Controllers;

use App\Support\SystemSettings;
use App\Services\LorryStockService;
use App\Services\LorryAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WholesalePosController extends Controller
{
    /**
     * Display the Wholesale POS interface.
     */
    public function index(LorryAccessService $access)
    {
        abort_unless(SystemSettings::bool('enable_wholesale_pos', true, 'pos_settings'), 403, 'Wholesale POS is disabled in Settings.');

        // Fetch Categories
        $categories = DB::table('categories')->get();

        $storeId = \App\Models\Setting::get('default_store_id', 1);
        if (request()->has('store_id') && \App\Models\Setting::get('enable_multi_store', false)) {
            $storeId = request('store_id');
        }

        $distributionEnabled = SystemSettings::bool('enable_distribution_module', true, 'business_settings');
        $sourceType = request('source_type') === 'lorry' && $distributionEnabled ? 'lorry' : 'store';
        $activeLorryId = request('lorry_id');
        $activeTripId = request('trip_id');
        $lorryAssignment = null;
        if ($access->isLorryUser(auth()->user())) {
            $lorryAssignment = $access->currentAssignment(auth()->user(), request()->integer('lorry_id') ?: null);
            abort_unless($lorryAssignment->can_sell, 403, 'Your lorry assignment does not allow sales.');
            $sourceType = 'lorry';
            $activeLorryId = $lorryAssignment->lorry_id;
            if ($activeTripId) {
                abort_unless(DB::table('distribution_trips')->where('id', $activeTripId)->where('lorry_id', $activeLorryId)->exists(), 403);
            }
        }

        // Fetch Products with current stock levels from the selected source.
        $productsQuery = DB::table('products')
            ->leftJoin('product_store_stock', function($join) use ($storeId) {
                $join->on('product_store_stock.product_id', '=', 'products.id')
                     ->where('product_store_stock.store_id', '=', $storeId);
            });

        if ($sourceType === 'lorry' && $activeLorryId) {
            $productsQuery->leftJoin('lorry_stocks', function ($join) use ($activeLorryId) {
                $join->on('lorry_stocks.product_id', '=', 'products.id')
                    ->where('lorry_stocks.lorry_id', '=', $activeLorryId)
                    ->whereNull('lorry_stocks.variant_id');
            });
        }

        $products = $productsQuery
            ->select('products.*', DB::raw($sourceType === 'lorry' && $activeLorryId ? 'COALESCE(lorry_stocks.quantity, 0) as stock' : 'COALESCE(product_store_stock.quantity, 0) as stock'))
            ->whereNull('products.deleted_at')
            ->get();

        $variantsQuery = DB::table('product_variants')->where('product_variants.is_active', true);
        if ($sourceType === 'lorry' && $activeLorryId) {
            $variantsQuery->leftJoin('lorry_stocks as variant_lorry_stock', function ($join) use ($activeLorryId) {
                $join->on('variant_lorry_stock.variant_id', '=', 'product_variants.id')
                    ->where('variant_lorry_stock.lorry_id', '=', $activeLorryId);
            })->select('product_variants.*', DB::raw('COALESCE(variant_lorry_stock.quantity, 0) as assigned_stock'));
        } else {
            $variantsQuery->select('product_variants.*');
        }
        $variants = $variantsQuery->get()->groupBy('product_id');

        $taxRates = DB::table('tax_rates')->where('is_active', true)->get()->keyBy('id');
        $categoriesList = DB::table('categories')->get()->keyBy('id');
        $defaultVat = $taxRates->where('is_default', true)->where('tax_type', 'VAT')->first();

        $products = $products->map(function ($product) use ($variants, $sourceType, $taxRates, $categoriesList, $defaultVat) {
            $cat = $categoriesList->get($product->category_id);
            // In DB it might be default_sales_tax_profile_id in products as well, let me check the migration...
            // Oh, migration added `sales_tax_profile_id` and `purchase_tax_profile_id` for products, but in my earlier update I added `default_sales_tax_profile_id` to Product fillable! Let's just check both or fix later.
            // Wait, my Product Model has `default_sales_tax_profile_id`. So I'll use that.
            $productTaxId = $product->default_sales_tax_profile_id ?? $product->sales_tax_profile_id ?? null;
            $taxRateId = $productTaxId ?? ($cat->default_sales_tax_profile_id ?? ($defaultVat->id ?? null));
            $taxRate = $taxRateId ? $taxRates->get($taxRateId) : null;
            $product->tax_rate = $taxRate ? (float) $taxRate->rate : 0;
            $product->tax_id = $taxRateId;
            $product->tax_classification = $product->tax_classification ?? ($cat->tax_classification ?? ($taxRate->classification ?? null));

            $productVariants = ($variants[$product->id] ?? collect())->map(function ($variant) use ($sourceType) {
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
                    'stock_quantity' => (float) ($sourceType === 'lorry' ? ($variant->assigned_stock ?? 0) : $variant->stock_quantity),
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
            ->when($lorryAssignment, fn ($q) => $q->whereIn('id', DB::table('sales')->where('lorry_id', $activeLorryId)->select('customer_id')))
            ->get();

        // Fetch Held Sales
        $holdSales = DB::table('hold_sales')
            ->join('customers', 'customers.id', '=', 'hold_sales.customer_id')
            ->select('hold_sales.*', 'customers.name as customer_name')
            ->orderBy('hold_sales.id', 'desc')
            ->get();
        $resumeHold = request()->filled('hold_id') ? $this->resumeHoldPayload((int) request('hold_id'), 'wholesale') : null;
            
        // Fetch Payment Methods
        $paymentMethods = $this->enabledPaymentMethods();

        // Fetch Stores if multi store is enabled
        $enableMultiStore = \App\Models\Setting::get('enable_multi_store', false);
        $stores = $enableMultiStore ? DB::table('stores')->where('status', true)->get() : [];
        $lorries = $distributionEnabled ? DB::table('lorries')->whereNull('deleted_at')->where('status', 'active')->when($lorryAssignment, fn($q) => $q->whereIn('id', $access->allowedIds(auth()->user())))->orderBy('lorry_no')->get() : collect();
        $distributionTrips = $activeLorryId ? DB::table('distribution_trips')->where('lorry_id', $activeLorryId)->whereIn('status', ['planned', 'active'])->orderByDesc('id')->get() : collect();
        $activeStoreId = $storeId;
        $posSettings = SystemSettings::posPayload();

        return view('pos.wholesale', compact('categories', 'products', 'customers', 'holdSales', 'paymentMethods', 'resumeHold', 'enableMultiStore', 'stores', 'activeStoreId', 'posSettings', 'distributionEnabled', 'sourceType', 'lorries', 'activeLorryId', 'distributionTrips', 'activeTripId'));
    }

    /**
     * Store a completed sale from the POS.
     */
    public function store(Request $request, LorryAccessService $access, \App\Services\MixedPaymentService $mixedPayments)
    {
        if ($access->isLorryUser($request->user())) {
            $assignment = $access->currentAssignment($request->user(), $request->integer('lorry_id') ?: null);
            abort_unless($assignment->can_sell, 403, 'Your lorry assignment does not allow sales.');
            $request->merge(['source_type' => 'lorry', 'lorry_id' => $assignment->lorry_id]);
            if ($request->filled('trip_id')) abort_unless(DB::table('distribution_trips')->where('id', $request->trip_id)->where('lorry_id', $assignment->lorry_id)->exists(), 403);
        }
        $request->validate([
            'customer_id' => 'required',
            'pos_type' => 'required|in:retail,wholesale',
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric',
            'payable_amount' => 'required|numeric',
            'paid_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'payment_rows' => 'nullable|array',
            'cheque.cheque_number' => 'nullable|string|max:100',
            'cheque.cheque_date' => 'nullable|date',
            'cheque.bank_name' => 'nullable|string|max:150',
            'cheque.branch_name' => 'nullable|string|max:150',
            'cheque.account_holder_name' => 'nullable|string|max:150',
            'cheque.account_number' => 'nullable|string|max:100',
            'hold_id' => 'nullable|integer|exists:hold_sales,id',
            'source_type' => 'nullable|in:store,lorry',
            'lorry_id' => 'nullable|integer|exists:lorries,id',
            'trip_id' => 'nullable|integer|exists:distribution_trips,id',
        ]);

        DB::beginTransaction();

        try {
            $today = date('Y-m-d');
            $invoiceNo = SystemSettings::get('wholesale_invoice_prefix', 'WSL', 'invoice_settings') . '-' . strtoupper(uniqid());

            // Calculate cost & profit
            $totalCostAmount = 0;
            foreach ($request->items as $item) {
                $variant = !empty($item['variant_id']) ? DB::table('product_variants')->where('id', $item['variant_id'])->first() : null;
                $cost = $variant ? $variant->cost_price : (DB::table('products')->where('id', $item['product_id'])->value('cost_price') ?: 0);
                $totalCostAmount += $cost * $item['quantity'];
            }
            $totalProfitAmount = $request->payable_amount - $totalCostAmount;

            $paymentRows = $mixedPayments->rowsFromRequest($request->all(), (float) $request->payable_amount);

            $sourceType = $request->input('source_type') === 'lorry' ? 'lorry' : 'store';
            if ($sourceType === 'lorry' && ! $request->filled('lorry_id')) {
                throw new \Exception('Please select a lorry before completing a lorry sale.');
            }

            // 1. Insert Sales Record
            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $request->customer_id,
                'invoice_no' => $invoiceNo,
                'sale_date' => $today,
                'pos_type' => $request->pos_type,
                'total_amount' => $request->total_amount,
                'discount_type' => $request->discount_type ?? 'fixed',
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'total_tax_amount' => $request->tax_amount ?? 0,
                'payable_amount' => $request->payable_amount,
                'paid_amount' => 0,
                'cheque_hold_amount' => 0,
                'due_amount' => $request->payable_amount,
                'payment_status' => 'Due',
                'total_cost_amount' => $totalCostAmount,
                'total_profit_amount' => $totalProfitAmount,
                'store_id' => $request->store_id ?? \App\Models\Setting::get('default_store_id', 1),
                'source_type' => $sourceType,
                'lorry_id' => $sourceType === 'lorry' ? $request->lorry_id : null,
                'trip_id' => $sourceType === 'lorry' ? $request->trip_id : null,
                'status' => 'final',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Insert Items & Update Stock
            $taxTransactionsData = [];
            $taxRatesData = DB::table('tax_rates')->get()->keyBy('id');

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

                $itemShare = $request->total_amount > 0 ? ($itemSubtotal / $request->total_amount) : 0;
                $itemDiscountShare = ($request->discount_amount ?? 0) * $itemShare;
                $taxableAmount = max(0, $itemSubtotal - $itemDiscountShare);
                $itemTaxRate = $item['tax_rate'] ?? 0;
                $itemTaxAmount = $taxableAmount * ($itemTaxRate / 100);
                $itemTaxId = $item['tax_id'] ?? null;

                if ($itemTaxAmount > 0 && $itemTaxId) {
                    if (!isset($taxTransactionsData[$itemTaxId])) {
                        $taxTransactionsData[$itemTaxId] = ['taxable_amount' => 0, 'tax_amount' => 0];
                    }
                    $taxTransactionsData[$itemTaxId]['taxable_amount'] += $taxableAmount;
                    $taxTransactionsData[$itemTaxId]['tax_amount'] += $itemTaxAmount;
                }

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
                    'tax_rate' => $itemTaxRate,
                    'tax_amount' => $itemTaxAmount,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if ($sourceType === 'lorry') {
                    app(LorryStockService::class)->issueFromLorry((int) $request->lorry_id, (int) $item['product_id'], $item['variant_id'] ?? null, (float) $item['quantity'], $request->trip_id, $invoiceNo);
                } elseif ($variant) {
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

                if ($sourceType !== 'lorry') {
                    // Insert Stock Movement
                    DB::table('stock_histories')->insert([
                        'product_id' => $item['product_id'],
                        'store_id' => $request->store_id ?? \App\Models\Setting::get('default_store_id', 1),
                        'quantity_change' => -$item['quantity'],
                        'new_quantity' => $variant ?
                            DB::table('product_variants')->where('id', $variant->id)->value('stock_quantity') :
                            DB::table('product_store_stock')->where('product_id', $item['product_id'])->where('store_id', $request->store_id ?? \App\Models\Setting::get('default_store_id', 1))->value('quantity'),
                        'type' => 'sale',
                        'reference_no' => $invoiceNo,
                        'note' => 'Wholesale Sale',
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                    ]);
                }
            }

            // 3. Insert Tax Transactions
            if (!empty($taxTransactionsData)) {
                $storeId = $request->store_id ?? \App\Models\Setting::get('default_store_id', 1);
                foreach ($taxTransactionsData as $taxId => $taxAmtData) {
                    $rateObj = $taxRatesData->get($taxId);
                    if ($rateObj) {
                        DB::table('tax_transactions')->insert([
                            'transaction_date' => $today,
                            'transaction_type' => 'sale',
                            'reference_type' => 'App\Models\Sale',
                            'reference_id' => $saleId,
                            'store_id' => $storeId,
                            'customer_id' => $request->customer_id,
                            'tax_type' => $rateObj->tax_type,
                            'tax_rate_id' => $taxId,
                            'tax_code' => $rateObj->code,
                            'taxable_amount' => $taxAmtData['taxable_amount'],
                            'tax_rate' => $rateObj->rate,
                            'tax_amount' => $taxAmtData['tax_amount'],
                            'input_or_output' => 'output',
                            'status' => 'final',
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // 4. Handle Mixed Payments
            DB::table('customers')->where('id', $request->customer_id)->increment('balance', $request->payable_amount);
            $allocation = $mixedPayments->allocateSale(\App\Models\Sale::findOrFail($saleId), $paymentRows, [
                'store_id' => $request->store_id ?? \App\Models\Setting::get('default_store_id', 1),
                'lorry_id' => $sourceType === 'lorry' ? $request->lorry_id : null,
                'trip_id' => $sourceType === 'lorry' ? $request->trip_id : null,
            ]);

            if ($request->filled('hold_id')) {
                DB::table('hold_sale_items')->where('hold_sale_id', $request->hold_id)->delete();
                DB::table('hold_sales')->where('id', $request->hold_id)->where('pos_type', 'wholesale')->delete();
            }

            \App\Models\ActivityLog::log('Sale', 'Wholesale POS', "Processed wholesale sale {$invoiceNo}");

            DB::commit();

            return response()->json([
                'success' => true,
                'invoice_no' => $invoiceNo,
                'sale_id' => $saleId,
                'change' => max(0, collect($paymentRows)->where('method', 'Cash')->sum('amount') - $request->payable_amount),
                'cheque_hold_amount' => $allocation['hold'],
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
            ->leftJoin('product_store_stock', function($join) {
                $join->on('product_store_stock.product_id', '=', 'products.id')
                     ->where('product_store_stock.store_id', '=', \App\Models\Setting::get('default_store_id', 1));
            })
            ->where('hold_sale_items.hold_sale_id', $hold->id)
            ->select(
                'hold_sale_items.*',
                'products.name',
                'products.cost_price as product_cost_price',
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
