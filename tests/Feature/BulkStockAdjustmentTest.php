<?php

use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\User;

function adjustmentProduct(string $sku, float $stock): Product
{
    $product = Product::create(['name' => 'Adjustment '.$sku, 'sku' => $sku, 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15, 'is_active' => true]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => $stock]);

    return $product;
}

test('bulk adjustment CRUD keeps signed changes and immutable movement history', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $a = adjustmentProduct('BULK-A', 10);
    $b = adjustmentProduct('BULK-B', 20);
    $payload = ['adjustment_date' => today()->toDateString(), 'adjustment_type' => 'correction', 'reason' => 'Count audit', 'items' => [['product_id' => $a->id, 'quantity' => 5], ['product_id' => $b->id, 'quantity' => -3]]];
    $this->get(route('stock-adjustments.create'))->assertOk();
    $this->post(route('stock-adjustments.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $adjustment = StockAdjustment::latest('id')->firstOrFail();
    expect((float) $a->stock()->first()->quantity)->toBe(15.0);
    expect((float) $b->stock()->first()->quantity)->toBe(17.0);
    $this->get(route('stock-adjustments.show', $adjustment))->assertOk()->assertSee('Movement history');
    $this->get(route('stock-adjustments.edit', $adjustment))->assertOk();
    $payload['items'][0]['quantity'] = 2;
    $payload['items'][1]['quantity'] = -1;
    $this->put(route('stock-adjustments.update', $adjustment), $payload)->assertSessionHasNoErrors();
    expect((float) $a->stock()->first()->quantity)->toBe(12.0);
    expect((float) $b->stock()->first()->quantity)->toBe(19.0);
    $pdf = $this->get(route('stock-adjustments.pdf', $adjustment))->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');
    $this->get(route('stock-adjustments.index', ['type' => 'pdf', 'search' => 'Count audit']))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->delete(route('stock-adjustments.destroy', $adjustment))->assertSessionHasNoErrors();
    expect((float) $a->stock()->first()->quantity)->toBe(10.0);
    expect((float) $b->stock()->first()->quantity)->toBe(20.0);
    expect($adjustment->fresh()->status)->toBe('cancelled');
    expect(StockHistory::where('reference_id', $adjustment->id)->where('reference_type', StockAdjustment::class)->count())->toBe(6);
    $this->delete(route('stock-adjustments.destroy', $adjustment))->assertRedirect();
    expect((float) $a->stock()->first()->quantity)->toBe(10.0);
    $this->putJson(route('stock-adjustments.update', $adjustment), $payload)->assertUnprocessable();
});

test('bulk adjustments reject duplicate products and roll back every row on insufficient stock', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $a = adjustmentProduct('ROLLBACK-A', 10);
    $b = adjustmentProduct('ROLLBACK-B', 1);
    $payload = ['adjustment_date' => today()->toDateString(), 'adjustment_type' => 'correction', 'items' => [['product_id' => $a->id, 'quantity' => 5], ['product_id' => $b->id, 'quantity' => -2]]];
    $this->postJson(route('stock-adjustments.store'), $payload)->assertUnprocessable();
    expect((float) $a->stock()->first()->quantity)->toBe(10.0);
    expect(StockAdjustment::count())->toBe(0);
    $payload['items'][1]['product_id'] = $a->id;
    $this->postJson(route('stock-adjustments.store'), $payload)->assertUnprocessable();
    $this->actingAs(User::factory()->create())->postJson(route('stock-adjustments.store'), $payload)->assertForbidden();
});

test('deleting consumed adjustment stock is blocked and preserves the record', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $product = adjustmentProduct('CONSUMED-A', 0);
    $this->post(route('stock-adjustments.store'), ['adjustment_date' => today()->toDateString(), 'adjustment_type' => 'increase', 'items' => [['product_id' => $product->id, 'quantity' => 5]]])->assertSessionHasNoErrors();
    $adjustment = StockAdjustment::latest('id')->firstOrFail();
    $product->stock()->update(['quantity' => 2]);
    $this->deleteJson(route('stock-adjustments.destroy', $adjustment))->assertUnprocessable();
    expect($adjustment->fresh()->status)->toBe('approved');
    expect((float) $product->stock()->first()->quantity)->toBe(2.0);
});

test('counted stock mode can set zero and deletion restores the original count', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $product = adjustmentProduct('COUNTED-ZERO', 10);
    $this->post(route('stock-adjustments.store'), ['adjustment_date' => today()->toDateString(), 'adjustment_type' => 'opening_stock', 'items' => [['product_id' => $product->id, 'quantity' => 0]]])->assertSessionHasNoErrors();
    expect((float) $product->stock()->first()->quantity)->toBe(0.0);
    $this->delete(route('stock-adjustments.destroy', StockAdjustment::latest('id')->first()))->assertSessionHasNoErrors();
    expect((float) $product->stock()->first()->quantity)->toBe(10.0);
});
