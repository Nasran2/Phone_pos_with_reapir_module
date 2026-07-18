<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\ProductStoreStock;
use App\Models\ProductSerial;
use App\Models\ProductVariant;
use App\Models\StockHistory;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $storeIds = $request->input('store_ids', []);
        if ($request->filled('store_id')) {
            // Fallback for older links using single store_id
            $storeIds = [$request->input('store_id')];
        }

        $query = Product::with(['category', 'brand', 'unit', 'variants', 'serials', 'storeStocks' => function($q) use ($storeIds) {
            $q->with('store');
            if (!empty($storeIds)) {
                $q->whereIn('store_id', $storeIds);
            }
        }])->select('products.*');

        $stockSubquery = DB::table('product_store_stock')
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('product_id', 'products.id');

        if (!empty($storeIds)) {
            $stockSubquery->whereIn('store_id', $storeIds);
            
            // Only show products assigned to at least one of the selected stores
            $query->whereHas('storeStocks', function($q) use ($storeIds) {
                $q->whereIn('store_id', $storeIds);
            });
        }

        $query->selectSub($stockSubquery, 'current_stock');

        // Search in Name, SKU, Barcode or Serial
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('serials', function ($sq) use ($search) {
                      $sq->where('serial_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('variants', function ($vq) use ($search) {
                      $vq->where('sku', 'like', "%{$search}%")
                         ->orWhere('color', 'like', "%{$search}%")
                         ->orWhere('storage', 'like', "%{$search}%");
                  });
            });
        }

        // Category Filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Brand Filter
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // Status Filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('is_active', $status === 'active');
        }

        $products = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::all();
        $brands = Brand::all();
        $units = Unit::all();
        $stores = \App\Models\Store::all();
        $taxProfiles = \App\Models\TaxRate::where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'brands', 'units', 'stores', 'taxProfiles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'array'],
            'opening_stock.*' => ['nullable', 'numeric', 'min:0'],
            'stock_alert_quantity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'sales_tax_profile_id' => ['nullable', 'exists:tax_rates,id'],
            'purchase_tax_profile_id' => ['nullable', 'exists:tax_rates,id'],
            'tax_classification' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'], // Product Image
            'is_active' => ['nullable', 'boolean'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['exists:stores,id'],
            'imeis' => ['nullable', 'string'], // text area line separated
            'variants' => ['nullable', 'array'],
            'variants.*.color' => ['nullable', 'string'],
            'variants.*.storage' => ['nullable', 'string'],
            'variants.*.ram' => ['nullable', 'string'],
            'variants.*.sku' => ['nullable', 'string', 'unique:product_variants,sku'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.retail_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();

        try {
            // Generate SKU if missing
            $sku = ($validated['sku'] ?? null) ?: 'PROD-' . strtoupper(Str::random(8));
            
            // Barcode Auto Generate
            $barcode = $validated['barcode'] ?? null;
            if (empty($barcode)) {
                $barcode = 'BC-' . time() . rand(100, 999);
            }

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('products', 'public');
            }

            $product = Product::create([
                'category_id' => $validated['category_id'] ?? null,
                'brand_id' => $validated['brand_id'] ?? null,
                'unit_id' => $validated['unit_id'] ?? null,
                'name' => $validated['name'],
                'sku' => $sku,
                'barcode' => $barcode,
                'description' => $validated['description'] ?? null,
                'image' => $logoPath,
                'cost_price' => $validated['cost_price'],
                'retail_price' => $validated['retail_price'],
                'wholesale_price' => $validated['wholesale_price'],
                'stock_alert_quantity' => $validated['stock_alert_quantity'] ?? 5,
                'sales_tax_profile_id' => $validated['sales_tax_profile_id'] ?? null,
                'purchase_tax_profile_id' => $validated['purchase_tax_profile_id'] ?? null,
                'tax_classification' => $validated['tax_classification'] ?? null,
                'is_active' => $request->has('is_active'),
                'created_by' => auth()->id(),
            ]);

            // Save Product Stock
            $storeIds = $request->input('store_ids', [Setting::get('default_store_id', 1)]);
            $openingStockData = $validated['opening_stock'] ?? [];

            foreach ($storeIds as $storeId) {
                $qty = floatval($openingStockData[$storeId] ?? 0);

                ProductStoreStock::create([
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'quantity' => $qty,
                ]);

                // Save Stock History
                if ($qty > 0) {
                    StockHistory::create([
                        'product_id' => $product->id,
                        'store_id' => $storeId,
                        'quantity_change' => $qty,
                        'new_quantity' => $qty,
                        'type' => 'opening_stock',
                        'note' => 'Initial opening stock',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Save Product IMEIs
            if ($request->filled('imeis')) {
                $imeis = array_filter(array_map('trim', explode("\n", $request->imeis)));
                foreach ($imeis as $imei) {
                    if (empty($imei)) continue;
                    ProductSerial::create([
                        'product_id' => $product->id,
                        'serial_number' => $imei,
                        'status' => 'available',
                    ]);
                }
                
                // Re-sync quantity based on serials count if positive
                $imeiCount = count($imeis);
                if ($imeiCount > 0) {
                    $primaryStoreId = $storeIds[0] ?? Setting::get('default_store_id', 1);
                    $stock = ProductStoreStock::firstOrCreate([
                        'product_id' => $product->id,
                        'store_id' => $primaryStoreId,
                    ], ['quantity' => 0]);
                    $stock->quantity = $imeiCount;
                    $stock->save();

                    StockHistory::create([
                        'product_id' => $product->id,
                        'store_id' => $primaryStoreId,
                        'quantity_change' => $imeiCount - $qty,
                        'new_quantity' => $imeiCount,
                        'type' => 'opening_stock',
                        'note' => 'Stock quantity set via IMEI serials count',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Save Variants
            if ($request->has('variants')) {
                foreach ($request->variants as $v) {
                    if (empty($v['sku'])) continue;
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'color' => $v['color'] ?? null,
                        'storage' => $v['storage'] ?? null,
                        'ram' => $v['ram'] ?? null,
                        'sku' => $v['sku'],
                        'cost_price' => $v['cost_price'] ?: $product->cost_price,
                        'retail_price' => $v['retail_price'] ?: $product->retail_price,
                        'wholesale_price' => $v['wholesale_price'] ?: $product->wholesale_price,
                        'stock_quantity' => floatval($v['stock_quantity'] ?? 0),
                        'is_active' => true,
                    ]);

                    $vQty = floatval($v['stock_quantity'] ?? 0);
                    foreach ($storeIds as $storeId) {
                        ProductStoreStock::firstOrCreate([
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'store_id' => $storeId,
                        ], ['quantity' => $vQty]);

                        if ($vQty > 0) {
                            StockHistory::create([
                                'product_id' => $product->id,
                                'variant_id' => $variant->id,
                                'store_id' => $storeId,
                                'quantity_change' => $vQty,
                                'new_quantity' => $vQty,
                                'type' => 'opening_stock',
                                'note' => 'Variant opening stock',
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            ActivityLog::log('Create', 'Products Module', "Created product: {$product->name} (SKU: {$product->sku})");

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'barcode' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'stock_alert_quantity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'default_sales_tax_profile_id' => ['nullable', 'exists:tax_rates,id'],
            'default_purchase_tax_profile_id' => ['nullable', 'exists:tax_rates,id'],
            'tax_classification' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['exists:stores,id'],
            'opening_stock' => ['nullable', 'array'],
            'opening_stock.*' => ['nullable', 'numeric', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'exists:product_variants,id'],
            'variants.*.color' => ['nullable', 'string'],
            'variants.*.storage' => ['nullable', 'string'],
            'variants.*.ram' => ['nullable', 'string'],
            'variants.*.sku' => ['nullable', 'string'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.retail_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->input('variants', []) as $variantInput) {
                if (empty($variantInput['sku'])) {
                    continue;
                }

                $duplicateVariant = ProductVariant::where('sku', $variantInput['sku'])
                    ->when(!empty($variantInput['id']), fn ($query) => $query->where('id', '!=', $variantInput['id']))
                    ->exists();

                if ($duplicateVariant) {
                    throw new \RuntimeException("Variant SKU {$variantInput['sku']} is already used.");
                }
            }

            $updateData = [
                'category_id' => $validated['category_id'] ?? null,
                'brand_id' => $validated['brand_id'] ?? null,
                'unit_id' => $validated['unit_id'] ?? null,
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'barcode' => $validated['barcode'],
                'description' => $validated['description'] ?? null,
                'cost_price' => $validated['cost_price'],
                'retail_price' => $validated['retail_price'],
                'wholesale_price' => $validated['wholesale_price'],
                'stock_alert_quantity' => $validated['stock_alert_quantity'] ?? 5,
                'sales_tax_profile_id' => $validated['sales_tax_profile_id'] ?? null,
                'purchase_tax_profile_id' => $validated['purchase_tax_profile_id'] ?? null,
                'tax_classification' => $validated['tax_classification'] ?? null,
                'is_active' => $request->has('is_active'),
                'updated_by' => auth()->id(),
            ];

            if ($request->hasFile('logo')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $updateData['image'] = $request->file('logo')->store('products', 'public');
            }

            // Detect if prices changed to record price history if needed
            $product->update($updateData);

            $keptVariantIds = [];
            foreach ($request->input('variants', []) as $variantInput) {
                if (empty($variantInput['sku'])) {
                    continue;
                }

                $variantData = [
                    'product_id' => $product->id,
                    'color' => $variantInput['color'] ?? null,
                    'storage' => $variantInput['storage'] ?? null,
                    'ram' => $variantInput['ram'] ?? null,
                    'sku' => $variantInput['sku'],
                    'cost_price' => $variantInput['cost_price'] ?: $product->cost_price,
                    'retail_price' => $variantInput['retail_price'] ?: $product->retail_price,
                    'wholesale_price' => $variantInput['wholesale_price'] ?: $product->wholesale_price,
                    'stock_quantity' => floatval($variantInput['stock_quantity'] ?? 0),
                    'is_active' => true,
                ];

                if (!empty($variantInput['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)->findOrFail($variantInput['id']);
                    $variant->update($variantData);
                } else {
                    $variant = ProductVariant::create($variantData);
                }

                $keptVariantIds[] = $variant->id;
            }

            ProductVariant::where('product_id', $product->id)
                ->when(!empty($keptVariantIds), fn ($query) => $query->whereNotIn('id', $keptVariantIds))
                ->delete();

            // Handle Store assignments
            if ($request->has('store_ids') && is_array($request->input('store_ids'))) {
                $requestedStores = $request->input('store_ids');
                $openingStocks = $request->input('opening_stock', []);

                $currentStoreStocks = ProductStoreStock::where('product_id', $product->id)->get();
                $currentStoreIds = $currentStoreStocks->pluck('store_id')->toArray();

                // Add to new stores
                $storesToAdd = array_diff($requestedStores, $currentStoreIds);
                foreach ($storesToAdd as $storeId) {
                    $qty = floatval($openingStocks[$storeId] ?? 0);
                    ProductStoreStock::create([
                        'product_id' => $product->id,
                        'store_id' => $storeId,
                        'quantity' => $qty,
                    ]);
                    
                    if ($qty > 0) {
                        StockHistory::create([
                            'product_id' => $product->id,
                            'store_id' => $storeId,
                            'transaction_type' => 'opening_stock',
                            'reference_type' => 'product_update',
                            'reference_id' => $product->id,
                            'quantity_before' => 0,
                            'quantity_change' => $qty,
                            'quantity_after' => $qty,
                            'unit_cost' => $product->cost_price,
                            'total_cost' => $qty * $product->cost_price,
                            'user_id' => auth()->id(),
                            'notes' => 'Opening stock assigned on update',
                        ]);
                    }
                }

                // Remove from stores
                $storesToRemove = array_diff($currentStoreIds, $requestedStores);
                foreach ($storesToRemove as $storeId) {
                    $stock = $currentStoreStocks->where('store_id', $storeId)->first();
                    if ($stock && $stock->quantity > 0) {
                        throw new \RuntimeException("Cannot remove product from store ID {$storeId} because it has stock quantity of {$stock->quantity}.");
                    }
                    if ($stock) {
                        $stock->delete();
                    }
                }
            }

            ActivityLog::log('Update', 'Products Module', "Updated product: {$product->name}");

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $productName = $product->name;
        
        DB::beginTransaction();
        try {
            $product->delete();
            ActivityLog::log('Delete', 'Products Module', "Soft deleted product: {$productName}");
            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('products.index')->with('error', 'Failed to delete product.');
        }
    }

    /**
     * Fetch stock history log as JSON.
     */
    public function stockHistory(Product $product)
    {
        $history = StockHistory::with(['variant', 'creator'])
            ->where('product_id', $product->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'variant' => $item->variant ? "({$item->variant->color} / {$item->variant->storage} / {$item->variant->ram})" : '-',
                    'quantity_change' => $item->quantity_change,
                    'new_quantity' => $item->new_quantity,
                    'type' => ucwords(str_replace('_', ' ', $item->type)),
                    'reference_no' => $item->reference_no ?: '-',
                    'note' => $item->note ?: '-',
                    'creator' => $item->creator->name ?? 'System',
                    'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i') : '-'
                ];
            });

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Export all products to CSV.
     */
    public function export()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_export.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Unit', 'Cost Price', 'Retail Price', 'Wholesale Price', 'Stock Alert Qty', 'Status', 'Current Stock']);

            $products = Product::with(['category', 'brand', 'unit', 'stock'])->get();
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->barcode,
                    $product->category->name ?? '',
                    $product->brand->name ?? '',
                    $product->unit->name ?? '',
                    $product->cost_price,
                    $product->retail_price,
                    $product->wholesale_price,
                    $product->stock_alert_quantity,
                    $product->is_active ? 'Active' : 'Inactive',
                    $product->stock->quantity ?? 0
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import products from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        $importedCount = 0;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 12) continue;
                
                $name = $row[1] ?? '';
                $sku = $row[2] ?? '';
                $barcode = $row[3] ?? null;
                $categoryName = $row[4] ?? '';
                $brandName = $row[5] ?? '';
                $unitName = $row[6] ?? '';
                $costPrice = floatval($row[7] ?? 0);
                $retailPrice = floatval($row[8] ?? 0);
                $wholesalePrice = floatval($row[9] ?? 0);
                $alertQty = intval($row[10] ?? 5);
                $isActive = ($row[11] ?? 'Active') === 'Active';
                $openingStock = floatval($row[12] ?? 0);

                if (empty($name) || empty($sku)) continue;

                $category = null;
                if (!empty($categoryName)) {
                    $category = Category::firstOrCreate(['name' => $categoryName], [
                        'slug' => Str::slug($categoryName)
                    ]);
                }
                $brand = null;
                if (!empty($brandName)) {
                    $brand = Brand::firstOrCreate(['name' => $brandName], [
                        'slug' => Str::slug($brandName)
                    ]);
                }
                $unit = null;
                if (!empty($unitName)) {
                    $unit = Unit::firstOrCreate(['name' => $unitName], [
                        'short_name' => Str::lower(substr($unitName, 0, 3))
                    ]);
                }

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $name,
                        'barcode' => $barcode ?: 'BC-' . time() . rand(100, 999),
                        'category_id' => $category?->id,
                        'brand_id' => $brand?->id,
                        'unit_id' => $unit?->id,
                        'cost_price' => $costPrice,
                        'retail_price' => $retailPrice,
                        'wholesale_price' => $wholesalePrice,
                        'stock_alert_quantity' => $alertQty,
                        'is_active' => $isActive,
                        'created_by' => auth()->id()
                    ]
                );

                // Update Stock
                $stock = ProductStoreStock::updateOrCreate(
                    ['product_id' => $product->id, 'store_id' => Setting::get('default_store_id', 1)],
                    ['quantity' => $openingStock]
                );

                if ($openingStock > 0) {
                    StockHistory::create([
                        'product_id' => $product->id,
                        'quantity_change' => $openingStock,
                        'new_quantity' => $openingStock,
                        'type' => 'opening_stock',
                        'note' => 'Imported via CSV',
                        'created_by' => auth()->id(),
                        'store_id' => Setting::get('default_store_id', 1),
                    ]);
                }

                $importedCount++;
            }
            fclose($handle);
            ActivityLog::log('Create', 'Products Module', "Imported {$importedCount} products via CSV.");
            DB::commit();

            return redirect()->route('products.index')->with('success', "Imported {$importedCount} products successfully.");
        } catch (\Exception $e) {
            fclose($handle);
            DB::rollBack();
            return redirect()->route('products.index')->with('error', 'CSV Import failed: ' . $e->getMessage());
        }
    }
}
