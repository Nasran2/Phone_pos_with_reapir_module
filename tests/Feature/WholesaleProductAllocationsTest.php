<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = createUserWithRole('Super Admin');
    $this->actingAs($this->admin);
    $this->customer = Customer::create(['name' => 'Allocated Customer']);
    $this->product = Product::create(['name' => 'Allocated Phone', 'sku' => 'ALLOC-PHONE', 'cost_price' => 200, 'retail_price' => 500, 'wholesale_price' => 350]);
    ProductStoreStock::create(['product_id' => $this->product->id, 'store_id' => 1, 'quantity' => 18]);
    $this->makeAllocation = function (float $quantity, array $attributes = [], ?int $variantId = null): Sale {
        $sale = Sale::create(array_merge([
            'customer_id' => $this->customer->id, 'invoice_no' => 'ALLOC-'.str()->uuid(),
            'sale_date' => today(), 'pos_type' => 'wholesale', 'store_id' => 1,
            'total_amount' => $quantity * 350, 'payable_amount' => $quantity * 350,
            'due_amount' => $quantity * 350, 'paid_amount' => 0,
            'payment_status' => 'Due', 'status' => 'completed', 'created_by' => $this->admin->id,
        ], $attributes));
        $sale->items()->create([
            'product_id' => $this->product->id, 'variant_id' => $variantId, 'quantity' => $quantity,
            'cost_price' => 200, 'selling_price' => 350, 'subtotal' => $quantity * 350, 'profit_amount' => $quantity * 150,
        ]);

        return $sale;
    };
});

test('wholesale popup lists pending customer bills and combines repeated product lines', function () {
    $sale = ($this->makeAllocation)(2.5);
    $sale->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'cost_price' => 200, 'selling_price' => 350, 'subtotal' => 350, 'profit_amount' => 150]);
    foreach (['cancelled', 'draft', 'quotation'] as $status) {
        ($this->makeAllocation)(1, ['status' => $status]);
    }
    ($this->makeAllocation)(1, ['stock_deducted_at' => now()]);
    $lorrySale = ($this->makeAllocation)(1);
    $lorrySale->forceFill(['source_type' => 'lorry'])->save();

    $response = $this->getJson(route('pos.wholesale'))->assertOk();
    $product = collect($response->json('products'))->firstWhere('id', $this->product->id);
    expect($product['allocations'])->toHaveCount(1)
        ->and($product['allocations'][0]['customer_name'])->toBe('Allocated Customer')
        ->and((float) $product['allocations'][0]['quantity'])->toBe(3.5)
        ->and($product['allocations'][0]['invoice_no'])->toBe($sale->invoice_no)
        ->and($product['allocations'][0]['invoice_url'])->toBe(route('sales.show', $sale))
        ->and($product['allocations'][0]['variant_label'])->toBeNull();
    $this->get($product['allocations'][0]['invoice_url'])->assertOk()->assertSee($sale->invoice_no);
    $this->get(route('pos.wholesale'))->assertOk()->assertSee('Total Allocated Stock')->assertDontSee('Allocated (Hold)')->assertSee('selectedProductAllocations()', false);
});

test('product allocations follow the selected store and disappear when released', function () {
    Setting::set('enable_multi_store', true);
    Store::firstOrCreate(['id' => 1], ['name' => 'Main', 'code' => 'ALLOC-MAIN', 'status' => 'active']);
    $branch = Store::create(['name' => 'Allocation Branch', 'code' => 'ALLOC-BRANCH', 'status' => 'active']);
    ProductStoreStock::create(['product_id' => $this->product->id, 'store_id' => $branch->id, 'quantity' => 10]);
    ($this->makeAllocation)(5);
    $sale = ($this->makeAllocation)(2, ['store_id' => $branch->id]);

    $product = collect($this->getJson(route('pos.wholesale', ['store_id' => $branch->id]))->assertOk()->json('products'))->firstWhere('id', $this->product->id);
    expect($product['allocations'])->toHaveCount(1)->and($product['allocations'][0]['sale_id'])->toBe($sale->id);
    $sale->update(['stock_deducted_at' => now()]);
    $product = collect($this->getJson(route('pos.wholesale', ['store_id' => $branch->id]))->assertOk()->json('products'))->firstWhere('id', $this->product->id);
    expect($product['allocations'])->toBe([]);
});

test('allocated variants retain their quantities and labels on each bill', function () {
    $variant = $this->product->variants()->create(['sku' => 'ALLOC-BLACK', 'color' => 'Black', 'storage' => '128GB', 'stock_quantity' => 6]);
    $sale = ($this->makeAllocation)(2, [], $variant->id);
    ($this->makeAllocation)(1);

    $product = collect($this->getJson(route('pos.wholesale'))->assertOk()->json('products'))->firstWhere('id', $this->product->id);
    expect($product['allocations'])->toHaveCount(2);
    $allocation = collect($product['allocations'])->firstWhere('sale_id', $sale->id);
    expect($allocation['variant_id'])->toBe($variant->id)
        ->and($allocation['variant_label'])->toBe('Black / 128GB')
        ->and((float) $allocation['quantity'])->toBe(2.0);
});

test('lorry product allocations only include bills for the selected lorry', function () {
    $lorryId = DB::table('lorries')->insertGetId(['lorry_no' => 'ALLOC-LORRY', 'name' => 'Allocation Lorry']);
    $otherLorryId = DB::table('lorries')->insertGetId(['lorry_no' => 'ALLOC-OTHER', 'name' => 'Other Lorry']);
    DB::table('lorry_stocks')->insert(['lorry_id' => $lorryId, 'product_id' => $this->product->id, 'quantity' => 10]);
    ($this->makeAllocation)(5);
    $sale = ($this->makeAllocation)(2);
    $sale->forceFill(['source_type' => 'lorry', 'lorry_id' => $lorryId])->save();
    $otherSale = ($this->makeAllocation)(1);
    $otherSale->forceFill(['source_type' => 'lorry', 'lorry_id' => $otherLorryId])->save();

    $product = collect($this->getJson(route('pos.wholesale', ['source_type' => 'lorry', 'lorry_id' => $lorryId]))->assertOk()->json('products'))->firstWhere('id', $this->product->id);
    expect($product['allocations'])->toHaveCount(1)->and($product['allocations'][0]['sale_id'])->toBe($sale->id);
});
