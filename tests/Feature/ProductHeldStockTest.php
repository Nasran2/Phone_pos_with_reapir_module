<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Models\Store;
use App\Services\SaleInventoryService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = createUserWithRole('Super Admin');
    $this->actingAs($this->admin);
    $this->customer = Customer::create(['name' => 'Held Stock Customer', 'balance' => 1750]);
    $this->product = Product::create([
        'name' => 'Reserved Phone',
        'sku' => 'HOLD-PHONE',
        'cost_price' => 200,
        'retail_price' => 500,
        'wholesale_price' => 350,
        'stock_alert_quantity' => 15,
        'is_active' => true,
    ]);
    ProductStoreStock::create(['product_id' => $this->product->id, 'store_id' => 1, 'quantity' => 18]);
    $this->makeHeldOrder = function (float $quantity, array $attributes = [], ?int $variantId = null): Sale {
        $sale = Sale::create(array_merge([
            'customer_id' => $this->customer->id,
            'invoice_no' => 'HOLD-'.str()->uuid(),
            'sale_date' => today(),
            'pos_type' => 'wholesale',
            'store_id' => 1,
            'total_amount' => $quantity * 350,
            'payable_amount' => $quantity * 350,
            'due_amount' => $quantity * 350,
            'paid_amount' => 0,
            'payment_status' => 'Due',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ], $attributes));
        $sale->items()->create([
            'product_id' => $this->product->id,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'cost_price' => 200,
            'selling_price' => 350,
            'subtotal' => $quantity * 350,
            'profit_amount' => $quantity * 150,
        ]);

        return $sale;
    };
});

test('product list subtracts customer order holds without changing physical stock', function () {
    ($this->makeHeldOrder)(5);
    foreach (['cancelled', 'draft', 'quotation'] as $status) {
        ($this->makeHeldOrder)(2, ['status' => $status]);
    }
    ($this->makeHeldOrder)(2, ['stock_deducted_at' => now()]);
    $lorryOrder = ($this->makeHeldOrder)(3);
    $lorryOrder->forceFill(['source_type' => 'lorry'])->save();

    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Available Stock')->assertSee('13 pcs')->assertSee('Hold: 5 pcs')->assertSee('Low Stock');
    $product = $response->viewData('products')->firstWhere('id', $this->product->id);
    expect((float) $product->current_stock)->toBe(18.0)
        ->and((float) $product->held_stock)->toBe(5.0)
        ->and($product->available_stock)->toBe(13.0)
        ->and((float) $product->storeStocks->first()->quantity)->toBe(18.0)
        ->and((float) $product->storeStocks->first()->held_quantity)->toBe(5.0)
        ->and($product->storeStocks->first()->available_quantity)->toBe(13.0);
    $this->assertDatabaseHas('product_store_stock', ['product_id' => $this->product->id, 'quantity' => 18]);
});

test('held stock follows each store and the selected store filters', function () {
    $branch = Store::create(['name' => 'Branch Store', 'code' => 'HOLD-BRANCH', 'status' => 'active']);
    ProductStoreStock::create(['product_id' => $this->product->id, 'store_id' => $branch->id, 'quantity' => 10]);
    ($this->makeHeldOrder)(5);
    ($this->makeHeldOrder)(2.5, ['store_id' => $branch->id]);

    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Hold: 2.5 pcs')->assertSee('Total Hold: 7.5 pcs')->assertSee('20.5 pcs');
    $product = $response->viewData('products')->firstWhere('id', $this->product->id);
    expect($product->available_stock)->toBe(20.5)
        ->and($product->storeStocks->firstWhere('store_id', 1)->available_quantity)->toBe(13.0)
        ->and($product->storeStocks->firstWhere('store_id', $branch->id)->available_quantity)->toBe(7.5);

    foreach ([['store_ids' => [$branch->id]], ['store_id' => $branch->id]] as $filter) {
        $response = $this->get(route('products.index', $filter));
        $response->assertOk()->assertSee('Hold: 2.5 pcs')->assertSee('7.5 pcs')->assertDontSee('Total Hold:');
        $product = $response->viewData('products')->firstWhere('id', $this->product->id);
        expect((float) $product->current_stock)->toBe(10.0)
            ->and((float) $product->held_stock)->toBe(2.5)
            ->and($product->available_stock)->toBe(7.5)
            ->and($product->storeStocks)->toHaveCount(1);
    }
});

test('cancelling an order releases its hold and stock deduction does not subtract it twice', function () {
    $sale = ($this->makeHeldOrder)(5);
    $sale->update(['status' => 'cancelled']);
    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Hold: 0 pcs')->assertSee('18 pcs');

    $sale->update(['status' => 'completed']);
    $this->post(route('sales.payments.store', $sale), [
        'amount' => 1750,
        'payment_method' => 'Cash',
        'payment_date' => today()->toDateString(),
    ])->assertRedirect()->assertSessionHasNoErrors();
    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Hold: 0 pcs')->assertSee('13 pcs');
    $product = $response->viewData('products')->firstWhere('id', $this->product->id);
    expect((float) $product->current_stock)->toBe(13.0)
        ->and((float) $product->held_stock)->toBe(0.0)
        ->and($product->available_stock)->toBe(13.0);
    expect(app(SaleInventoryService::class)->finalizeIfPaid($sale))->toBeFalse();
    expect(DB::table('stock_histories')->where('reference_id', $sale->id)->where('type', 'sale')->count())->toBe(1);
});

test('variant reservations use variant inventory instead of subtracting ordinary store stock', function () {
    $variant = $this->product->variants()->create(['sku' => 'HOLD-BLACK', 'color' => 'Black', 'stock_quantity' => 6]);
    ($this->makeHeldOrder)(2, [], $variant->id);
    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Black (all stores)')->assertSee('Available: 4 pcs')->assertSee('Hold: 2 pcs');
    $product = $response->viewData('products')->firstWhere('id', $this->product->id);
    expect($product->available_stock)->toBe(18.0)
        ->and((float) $product->held_stock)->toBe(0.0)
        ->and((float) $product->variants->first()->held_quantity)->toBe(2.0)
        ->and($product->variants->first()->available_quantity)->toBe(4.0);
});

test('partial payments retain holds until an operator releases the stock', function () {
    $sale = ($this->makeHeldOrder)(5);
    $this->post(route('sales.payments.store', $sale), [
        'amount' => 200,
        'payment_method' => 'Cash',
        'payment_date' => today()->toDateString(),
    ])->assertRedirect()->assertSessionHasNoErrors();
    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Hold: 5 pcs')->assertSee('13 pcs');
    $product = $response->viewData('products')->firstWhere('id', $this->product->id);
    expect((float) $product->current_stock)->toBe(18.0)
        ->and($product->available_stock)->toBe(13.0);

    $this->post(route('sales.payments.store', $sale), [
        'amount' => 100,
        'payment_method' => 'Cash',
        'payment_date' => today()->toDateString(),
        'deduct_stock_now' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();
    $response = $this->get(route('products.index'));
    $response->assertOk()->assertSee('Hold: 0 pcs')->assertSee('13 pcs');
    $product = $response->viewData('products')->firstWhere('id', $this->product->id);
    expect((float) $product->current_stock)->toBe(13.0)
        ->and($product->available_stock)->toBe(13.0)
        ->and((float) $sale->fresh()->due_amount)->toBe(1450.0);
});
