<?php

use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Services\ChequeService;
use App\Services\InvoiceNumberService;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;

test('invoice numbers use configured prefix date and daily sequence', function () {
    DB::table('invoice_settings')->updateOrInsert(
        ['key' => 'retail_invoice_prefix'],
        ['value' => 'ING', 'created_at' => now(), 'updated_at' => now()],
    );
    SystemSettings::clearCache();

    $numbers = app(InvoiceNumberService::class);
    $date = now()->format('Ymd');

    expect($numbers->next('retail'))->toBe("ING-{$date}0001")
        ->and($numbers->next('retail'))->toBe("ING-{$date}0002");
});

test('retail and wholesale due print invoices ignore submitted payments and keep stock until final payment', function (string $posType, string $routeName) {
    $user = createUserWithRole('Super Admin');
    $customer = Customer::create([
        'name' => ucfirst($posType).' Pending Customer',
        'balance' => 0,
        'points' => 0,
    ]);
    $product = Product::create([
        'name' => ucfirst($posType).' Pending Product',
        'sku' => strtoupper($posType).'-PENDING-001',
        'barcode' => strtoupper($posType).'-BARCODE-001',
        'cost_price' => 500,
        'retail_price' => 1000,
        'wholesale_price' => 1000,
        'is_active' => true,
    ]);
    ProductStoreStock::create([
        'product_id' => $product->id,
        'store_id' => 1,
        'quantity' => 10,
    ]);

    $payload = [
        'customer_id' => $customer->id,
        'pos_type' => $posType,
        'items' => [[
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 2,
            'selling_price' => 1000,
            'line_total' => 2000,
        ]],
        'total_amount' => 2000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'payable_amount' => 2000,
        'paid_amount' => 2000,
        'checkout_mode' => 'due_print',
        'payment_rows' => [[
            'method' => 'Cash',
            'amount' => 2000,
        ]],
    ];

    $checkout = $this->actingAs($user)->postJson(route($routeName), $payload);

    $checkout->assertOk()->assertJson([
        'success' => true,
        'inventory_status' => 'Awaiting Payment',
        'payment_status' => 'Due',
        'due_amount' => 2000,
    ]);
    $checkout->assertJsonStructure(['print_url']);

    $sale = Sale::findOrFail($checkout->json('sale_id'));
    expect($sale->stock_deducted_at)->toBeNull()
        ->and((float) $sale->paid_amount)->toBe(0.0)
        ->and((float) $sale->due_amount)->toBe(2000.0)
        ->and($sale->invoicePayments()->value('payment_method'))->toBe('Customer Credit / Due');
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(10.0);
    $this->assertDatabaseMissing('stock_histories', [
        'reference_no' => $sale->invoice_no,
        'type' => 'sale',
    ]);

    $sharedUrl = $checkout->json('print_url');
    expect($sharedUrl)->toContain('signature=')
        ->toContain('print=1')
        ->toContain('return_to_pos=1');
    auth()->logout();
    $invoiceResponse = $this->get($sharedUrl);
    $invoiceResponse
        ->assertOk()
        ->assertSee($sale->invoice_no)
        ->assertDontSee('PAYMENT PENDING')
        ->assertSee('Print A4 Invoice')
        ->assertSee('@page { size: A4 portrait;', false)
        ->assertSee('Balance Due')
        ->assertSee('Rs. 1,000.00')
        ->assertSee('Rs. 2,000.00')
        ->assertSee('afterprint', false)
        ->assertSee('window.opener.location.replace(posUrl)', false)
        ->assertSee('window.print()', false);
    expect(str_replace('\/', '/', $invoiceResponse->getContent()))
        ->toContain(route($posType === 'wholesale' ? 'pos.wholesale' : 'pos.retail'));

    $payment = $this->actingAs($user)->post(route('sales.payments.store', $sale), [
        'amount' => 2000,
        'payment_method' => 'Cash',
        'payment_date' => now()->toDateString(),
    ]);

    $payment->assertRedirect()->assertSessionHasNoErrors();
    $sale->refresh();
    expect($sale->stock_deducted_at)->not->toBeNull()
        ->and((float) $sale->paid_amount)->toBe(2000.0)
        ->and((float) $sale->due_amount)->toBe(0.0);
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(8.0);
    $this->assertDatabaseHas('stock_histories', [
        'reference_no' => $sale->invoice_no,
        'quantity_change' => -2,
        'type' => 'sale',
    ]);

    $this->actingAs($user)->post(route('sales.payments.store', $sale), [
        'amount' => 1,
        'payment_method' => 'Cash',
        'payment_date' => now()->toDateString(),
    ])->assertSessionHasErrors('amount');
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(8.0);
})->with([
    'retail' => ['retail', 'pos.retail.store'],
    'wholesale' => ['wholesale', 'pos.wholesale.store'],
]);

test('waiting payment popup supports partial payments and one-time manual stock deduction', function () {
    $user = createUserWithRole('Super Admin');
    $customer = Customer::create([
        'name' => 'Manual Release Customer',
        'balance' => 1000,
        'points' => 0,
    ]);
    $product = Product::create([
        'name' => 'Manual Release Phone',
        'sku' => 'MANUAL-RELEASE-PHONE',
        'cost_price' => 500,
        'retail_price' => 1000,
        'wholesale_price' => 900,
        'is_active' => true,
    ]);
    ProductStoreStock::create([
        'product_id' => $product->id,
        'store_id' => 1,
        'quantity' => 5,
    ]);
    $sale = Sale::create([
        'customer_id' => $customer->id,
        'invoice_no' => 'MANUAL-RELEASE-001',
        'sale_date' => today(),
        'pos_type' => 'retail',
        'total_amount' => 1000,
        'payable_amount' => 1000,
        'paid_amount' => 0,
        'due_amount' => 1000,
        'payment_status' => 'Due',
        'status' => 'final',
        'store_id' => 1,
        'created_by' => $user->id,
    ]);
    $sale->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'cost_price' => 500,
        'selling_price' => 1000,
        'price_type' => 'retail',
        'profit_amount' => 500,
        'subtotal' => 1000,
    ]);

    $this->actingAs($user)->get(route('sales.index'))
        ->assertOk()
        ->assertSee('Receive Payment')
        ->assertSee('Receive Customer Payment')
        ->assertSee('Total Bill')
        ->assertSee('Already Paid')
        ->assertSee('Deduct stock now')
        ->assertDontSee('All Cashiers');

    // An ordinary partial payment keeps inventory waiting.
    $this->post(route('sales.payments.store', $sale), [
        'amount' => 400,
        'payment_method' => 'Cash',
        'payment_date' => today()->toDateString(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale->refresh();
    expect((float) $sale->paid_amount)->toBe(400.0)
        ->and((float) $sale->due_amount)->toBe(600.0)
        ->and($sale->payment_status)->toBe('Partial')
        ->and($sale->stock_deducted_at)->toBeNull();
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(5.0);

    // The explicit checkbox releases the goods while preserving the balance due.
    $this->post(route('sales.payments.store', $sale), [
        'amount' => 100,
        'payment_method' => 'Bank Transfer',
        'payment_date' => today()->toDateString(),
        'reference_no' => 'PARTIAL-RELEASE-REF',
        'deduct_stock_now' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale->refresh();
    expect((float) $sale->paid_amount)->toBe(500.0)
        ->and((float) $sale->due_amount)->toBe(500.0)
        ->and($sale->payment_status)->toBe('Partial')
        ->and($sale->stock_deducted_at)->not->toBeNull();
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(4.0)
        ->and((float) $customer->fresh()->balance)->toBe(500.0);
    $this->assertDatabaseHas('stock_histories', [
        'reference_no' => $sale->invoice_no,
        'quantity_change' => -1,
        'type' => 'sale',
        'description' => 'Stock deducted manually while Rs. 500.00 remained due.',
    ]);

    // Completing payment later must not deduct the same stock a second time.
    $this->post(route('sales.payments.store', $sale), [
        'amount' => 500,
        'payment_method' => 'Cash',
        'payment_date' => today()->toDateString(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $sale->refresh();
    expect((float) $sale->paid_amount)->toBe(1000.0)
        ->and((float) $sale->due_amount)->toBe(0.0)
        ->and($sale->payment_status)->toBe('Paid');
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(4.0)
        ->and(DB::table('stock_histories')->where('reference_no', $sale->invoice_no)->where('type', 'sale')->count())->toBe(1);
});

test('due print invoices require a registered customer', function (string $posType, string $routeName) {
    $user = createUserWithRole('Super Admin');
    $customer = Customer::create(['name' => 'Walk-in Customer', 'balance' => 0]);
    $product = Product::create([
        'name' => ucfirst($posType).' Walk-in Guard Product',
        'sku' => strtoupper($posType).'-WALKIN-GUARD',
        'cost_price' => 250,
        'retail_price' => 500,
        'wholesale_price' => 500,
        'is_active' => true,
    ]);
    ProductStoreStock::create([
        'product_id' => $product->id,
        'store_id' => 1,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($user)->postJson(route($routeName), [
        'customer_id' => $customer->id,
        'pos_type' => $posType,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 500,
        ]],
        'total_amount' => 500,
        'payable_amount' => 500,
        'checkout_mode' => 'due_print',
        'payment_rows' => [['method' => 'Cash', 'amount' => 500]],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    expect(Sale::where('customer_id', $customer->id)->count())->toBe(0)
        ->and((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(3.0);
})->with([
    'retail' => ['retail', 'pos.retail.store'],
    'wholesale' => ['wholesale', 'pos.wholesale.store'],
]);

test('a failed final payment does not record payment or reduce stock below zero', function () {
    $user = createUserWithRole('Super Admin');
    $customer = Customer::create(['name' => 'Stock Guard Customer', 'balance' => 500]);
    $product = Product::create([
        'name' => 'Stock Guard Product',
        'sku' => 'STOCK-GUARD-001',
        'barcode' => 'STOCK-GUARD-BARCODE',
        'cost_price' => 100,
        'retail_price' => 500,
        'wholesale_price' => 500,
    ]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => 0]);

    $sale = Sale::create([
        'customer_id' => $customer->id,
        'invoice_no' => 'PENDING-STOCK-GUARD',
        'sale_date' => today(),
        'pos_type' => 'retail',
        'total_amount' => 500,
        'payable_amount' => 500,
        'paid_amount' => 0,
        'due_amount' => 500,
        'payment_status' => 'Due',
        'status' => 'final',
        'store_id' => 1,
        'source_type' => 'store',
        'created_by' => $user->id,
    ]);
    $sale->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'cost_price' => 100,
        'selling_price' => 500,
        'unit_price' => 500,
        'price_type' => 'retail',
        'profit_amount' => 400,
        'subtotal' => 500,
    ]);

    $this->actingAs($user)->post(route('sales.payments.store', $sale), [
        'amount' => 500,
        'payment_method' => 'Cash',
        'payment_date' => today()->toDateString(),
    ])->assertSessionHasErrors('amount');

    $sale->refresh();
    expect((float) $sale->paid_amount)->toBe(0.0)
        ->and((float) $sale->due_amount)->toBe(500.0)
        ->and($sale->stock_deducted_at)->toBeNull();
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(0.0);
    expect(DB::table('sale_payments')->where('sale_id', $sale->id)->count())->toBe(0);
});

test('cheque-held invoice deducts stock only when the cheque passes', function () {
    $user = createUserWithRole('Super Admin');
    $customer = Customer::create(['name' => 'Cheque Customer', 'balance' => 0]);
    $product = Product::create([
        'name' => 'Cheque Product',
        'sku' => 'CHEQUE-PRODUCT-001',
        'barcode' => 'CHEQUE-BARCODE-001',
        'cost_price' => 500,
        'retail_price' => 1000,
        'wholesale_price' => 900,
    ]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => 4]);

    $checkout = $this->actingAs($user)->postJson(route('pos.retail.store'), [
        'customer_id' => $customer->id,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 1000,
            'line_total' => 1000,
        ]],
        'total_amount' => 1000,
        'payable_amount' => 1000,
        'payment_rows' => [[
            'method' => 'Cheque',
            'amount' => 1000,
            'cheque' => [
                'cheque_number' => 'CHQ-PENDING-001',
                'cheque_date' => today()->toDateString(),
                'bank_name' => 'Test Bank',
                'account_holder' => 'Cheque Customer',
            ],
        ]],
    ])->assertOk()->assertJson([
        'success' => true,
        'inventory_status' => 'Awaiting Payment',
    ]);

    $sale = Sale::findOrFail($checkout->json('sale_id'));
    expect((float) $sale->cheque_hold_amount)->toBe(1000.0)
        ->and($sale->stock_deducted_at)->toBeNull();
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(4.0);

    $cheque = Cheque::where('sale_id', $sale->id)->firstOrFail();
    app(ChequeService::class)->pass($cheque, ['cleared_date' => today()->toDateString()]);

    $sale->refresh();
    expect((float) $sale->cheque_hold_amount)->toBe(0.0)
        ->and((float) $sale->paid_amount)->toBe(1000.0)
        ->and($sale->stock_deducted_at)->not->toBeNull();
    expect((float) ProductStoreStock::where('product_id', $product->id)->value('quantity'))->toBe(3.0);
});
