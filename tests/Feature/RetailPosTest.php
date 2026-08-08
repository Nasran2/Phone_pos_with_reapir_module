<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('retail pos screen can be rendered for authenticated users', function () {
    $user = createUserWithRole('Super Admin');

    $response = $this->actingAs($user)->get(route('pos.retail'));

    $response->assertOk()
        ->assertSee('name="viewport" content="width=device-width, initial-scale=1.0"', false)
        ->assertSee('h-[100dvh]', false)
        ->assertSee("mobileTab = 'products'", false)
        ->assertSee("mobileTab = 'cart'", false)
        ->assertSee('Due &amp; Print', false)
        ->assertSee('Print A4 Invoice')
        ->assertSee('printDueInvoice()', false);
});

test('retail pos can create a customer quickly', function () {
    $user = createUserWithRole('Super Admin');

    $response = $this->actingAs($user)->post(route('pos.retail.customer'), [
        'name' => 'Retail Customer Test',
        'phone' => '0778888888',
        'email' => 'retailtest@example.com'
    ]);

    $response->assertJson([
        'success' => true
    ]);
    $this->assertDatabaseHas('customers', [
        'name' => 'Retail Customer Test',
        'phone' => '0778888888'
    ]);
});

test('retail pos can process a successful checkout with split payments and imei tracking', function () {
    $user = createUserWithRole('Super Admin');

    // Create smartphone category, product, stock and imei serial
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Smartphones',
        'slug' => 'smartphones',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $productId = DB::table('products')->insertGetId([
        'category_id' => $categoryId,
        'name' => 'iPhone 15 Pro Max',
        'sku' => 'IP15PM-TEST',
        'cost_price' => 180000.00,
        'retail_price' => 245000.00,
        'wholesale_price' => 224000.00,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    DB::table('product_store_stock')->insert([
        'product_id' => $productId,
        'store_id' => 1,
        'quantity' => 5.00,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    DB::table('product_serials')->insert([
        'product_id' => $productId,
        'serial_number' => 'IMEI-TEST-999',
        'status' => 'available',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $customerId = DB::table('customers')->insertGetId([
        'name' => 'Retail Client',
        'phone' => '0776543210',
        'balance' => 0.00,
        'points' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Split payment: Cash Rs. 145,000 + Card Rs. 100,000 = Rs. 245,000 total
    $payload = [
        'customer_id' => $customerId,
        'items' => [
            [
                'product_id' => $productId,
                'name' => 'iPhone 15 Pro Max',
                'quantity' => 1,
                'selling_price' => 245000.00,
                'serial_no' => 'IMEI-TEST-999',
                'line_total' => 245000.00
            ]
        ],
        'total_amount' => 245000.00,
        'discount_amount' => 0.00,
        'tax_amount' => 0.00,
        'payable_amount' => 245000.00,
        'payments' => [
            'Cash' => 145000.00,
            'Card' => 100000.00,
            'Bank Transfer' => 0.00,
            'QR Payment' => 0.00
        ],
        'payment_references' => [
            'Card' => 'TXN-CARD-123'
        ],
        'paid_amount' => 245000.00
    ];

    $response = $this->actingAs($user)->post(route('pos.retail.store'), $payload);

    $response->assertJson([
        'success' => true
    ]);
    expect($response->json('invoice_no'))->toMatch('/^[A-Z0-9]+-\d{12}$/');

    // Check if sales records are stored
    $this->assertDatabaseHas('sales', [
        'customer_id' => $customerId,
        'payable_amount' => 245000.00,
        'paid_amount' => 245000.00,
        'due_amount' => 0.00
    ]);

    // Check stock decremented
    $this->assertDatabaseHas('product_store_stock', [
        'product_id' => $productId,
        'store_id' => 1,
        'quantity' => 4.00
    ]);

    // Check stock movement logged
    $this->assertDatabaseHas('stock_histories', [
        'product_id' => $productId,
        'quantity_change' => -1.00,
        'type' => 'sale'
    ]);

    // Check serial marked sold
    $this->assertDatabaseHas('product_serials', [
        'product_id' => $productId,
        'serial_number' => 'IMEI-TEST-999',
        'status' => 'sold'
    ]);

    // Check split payment rows saved
    $this->assertDatabaseHas('sale_payments', [
        'amount' => 145000.00,
        'payment_method' => 'Cash'
    ]);

    $this->assertDatabaseHas('sale_payments', [
        'amount' => 100000.00,
        'payment_method' => 'Card',
        'reference_no' => 'TXN-CARD-123'
    ]);

    // Check loyalty points calculated: floor(245000 / 1000) = 245 points
    $this->assertDatabaseHas('customers', [
        'id' => $customerId,
        'points' => 245
    ]);
});
