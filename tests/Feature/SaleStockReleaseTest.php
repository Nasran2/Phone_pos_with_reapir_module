<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\SaleInventoryService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = createUserWithRole('Super Admin');
    $this->actingAs($this->admin);
    $this->customer = Customer::create(['name' => 'Release Customer', 'balance' => 1000]);
    $this->product = Product::create(['name' => 'Release Phone', 'sku' => 'RELEASE-PHONE', 'cost_price' => 200, 'retail_price' => 500, 'wholesale_price' => 500]);
    ProductStoreStock::create(['product_id' => $this->product->id, 'store_id' => 1, 'quantity' => 10]);
    $this->sale = Sale::create([
        'customer_id' => $this->customer->id, 'invoice_no' => 'RELEASE-001', 'sale_date' => today(),
        'pos_type' => 'wholesale', 'store_id' => 1, 'total_amount' => 1000, 'payable_amount' => 1000,
        'paid_amount' => 0, 'due_amount' => 1000, 'payment_status' => 'Due', 'status' => 'completed', 'created_by' => $this->admin->id,
    ]);
    $this->item = $this->sale->items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'cost_price' => 200, 'selling_price' => 500, 'subtotal' => 1000, 'profit_amount' => 600]);
    $this->pay = function (float $amount, int $release = 1) {
        return $this->post(route('sales.payments.store', $this->sale), ['amount' => $amount, 'payment_method' => 'Cash', 'payment_date' => today()->toDateString(), 'deduct_stock_now' => $release]);
    };
    $this->stockChange = fn (int $release) => $this->put(route('sales.stock-release.update', $this->sale), ['stock_release_enabled' => $release]);
});

test('checked release deducts stock on the first partial payment', function () {
    ($this->pay)(200)->assertRedirect()->assertSessionHasNoErrors();
    expect((float) $this->sale->fresh()->due_amount)->toBe(800.0)
        ->and($this->sale->fresh()->stock_deducted_at)->not->toBeNull();
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 8]);
    ($this->pay)(200)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 8]);
    expect(DB::table('stock_histories')->where('reference_id', $this->sale->id)->where('type', 'sale')->count())->toBe(1);
    $this->get(route('sales.show', $this->sale))->assertOk()->assertSee('Edit stock release');
});

test('unchecking release keeps even fully paid products allocated', function () {
    ($this->pay)(1000, 0)->assertRedirect()->assertSessionHasNoErrors();
    $sale = $this->sale->fresh();
    expect((float) $sale->paid_amount)->toBe(1000.0)->and((float) $sale->due_amount)->toBe(0.0)
        ->and($sale->stock_deducted_at)->toBeNull()->and($sale->stock_release_on_payment)->toBeFalse()
        ->and(app(SaleInventoryService::class)->finalizeIfPaid($sale))->toBeFalse();
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 10]);
    $product = collect($this->getJson(route('pos.wholesale'))->assertOk()->json('products'))->firstWhere('id', $this->product->id);
    expect($product['allocations'])->toHaveCount(1)->and((float) $product['allocations'][0]['quantity'])->toBe(2.0);
    $this->get(route('sales.show', $sale))->assertOk()->assertSee('Fully paid')->assertSee('Edit stock release');
});

test('stock-only editing restores once without changing any payment or customer balance', function () {
    ($this->pay)(1000)->assertRedirect()->assertSessionHasNoErrors();
    $payments = $this->sale->payments()->get()->toArray();
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    $sale = $this->sale->fresh();
    expect($sale->stock_deducted_at)->toBeNull()->and($sale->stock_release_on_payment)->toBeFalse()
        ->and((float) $sale->paid_amount)->toBe(1000.0)->and((float) $sale->due_amount)->toBe(0.0)
        ->and((float) $this->customer->fresh()->balance)->toBe(0.0)->and($sale->payments()->get()->toArray())->toBe($payments);
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 10]);
    expect(DB::table('stock_histories')->where('reference_id', $sale->id)->where('type', 'sale_reallocated')->count())->toBe(1);
    ($this->stockChange)(1)->assertRedirect()->assertSessionHasNoErrors();
    ($this->stockChange)(1)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 8]);
    expect($this->sale->fresh()->payments()->get()->toArray())->toBe($payments);
});

test('stock release restoration covers variants and serials', function () {
    $variant = $this->product->variants()->create(['sku' => 'RELEASE-BLACK', 'color' => 'Black', 'stock_quantity' => 6]);
    $serialId = DB::table('product_serials')->insertGetId(['product_id' => $this->product->id, 'serial_number' => 'RELEASE-IMEI', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()]);
    $this->item->update(['variant_id' => $variant->id, 'quantity' => 1, 'product_serial_id' => $serialId]);
    ($this->pay)(200)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 5]);
    $this->assertDatabaseHas('product_serials', ['id' => $serialId, 'status' => 'sold', 'sale_id' => $this->sale->id]);
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 6]);
    $this->assertDatabaseHas('product_serials', ['id' => $serialId, 'status' => 'available', 'sale_id' => null]);
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 10]);
});

test('lorry stock returns to the original lorry when release is undone', function () {
    $lorryId = DB::table('lorries')->insertGetId(['lorry_no' => 'RELEASE-LORRY', 'name' => 'Release Lorry']);
    DB::table('lorry_stocks')->insert(['lorry_id' => $lorryId, 'product_id' => $this->product->id, 'quantity' => 10]);
    $this->sale->forceFill(['source_type' => 'lorry', 'lorry_id' => $lorryId])->save();
    ($this->pay)(200)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('lorry_stocks', ['lorry_id' => $lorryId, 'product_id' => $this->product->id, 'quantity' => 8]);
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('lorry_stocks', ['lorry_id' => $lorryId, 'product_id' => $this->product->id, 'quantity' => 10]);
});

test('cancelled invoices and invoices with returns cannot restore stock again', function () {
    ($this->pay)(200)->assertRedirect()->assertSessionHasNoErrors();
    $this->sale->update(['status' => 'cancelled']);
    ($this->stockChange)(0)->assertSessionHasErrors('stock_release_enabled');
    $this->sale->update(['status' => 'completed']);
    SaleReturn::create(['sale_id' => $this->sale->id, 'customer_id' => $this->customer->id, 'return_no' => 'RELEASE-RETURN', 'return_date' => today(), 'total_amount' => 500, 'refund_amount' => 0, 'refund_method' => 'Cash', 'created_by' => $this->admin->id]);
    ($this->stockChange)(0)->assertSessionHasErrors('stock_release_enabled');
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 8]);
});

test('stock edit requires payment permission and failed release rolls back the decision', function () {
    $this->actingAs(User::factory()->create());
    ($this->stockChange)(0)->assertForbidden();
    $this->actingAs($this->admin);
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    ProductStoreStock::where('product_id', $this->product->id)->update(['quantity' => 1]);
    ($this->stockChange)(1)->assertSessionHasErrors('stock_release_enabled');
    expect($this->sale->fresh()->stock_release_on_payment)->toBeFalse()->and($this->sale->fresh()->stock_deducted_at)->toBeNull();
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 1]);
});

test('cheque clearance respects an explicit decision to keep products allocated', function () {
    ($this->stockChange)(0)->assertRedirect()->assertSessionHasNoErrors();
    $cheque = app(App\Services\ChequeService::class)->create([
        'cheque_type' => 'received', 'customer_id' => $this->customer->id, 'sale_id' => $this->sale->id,
        'cheque_number' => 'RELEASE-HELD-CHEQUE', 'cheque_date' => today()->toDateString(),
        'bank_name' => 'Release Test Bank', 'amount' => 1000,
    ]);
    app(App\Services\ChequeService::class)->pass($cheque, ['cleared_date' => today()->toDateString()]);
    $sale = $this->sale->fresh();
    expect((float) $sale->paid_amount)->toBe(1000.0)->and((float) $sale->due_amount)->toBe(0.0)
        ->and($sale->stock_deducted_at)->toBeNull()->and($sale->stock_release_on_payment)->toBeFalse();
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 10]);
});
