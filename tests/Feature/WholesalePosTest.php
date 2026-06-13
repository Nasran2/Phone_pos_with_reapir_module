<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('wholesale pos screen can be rendered for authenticated users', function () {
    $user = createUserWithRole('Super Admin');

    $response = $this->actingAs($user)->get(route('pos.wholesale'));

    $response->assertOk();
});

test('pos can create a customer quickly', function () {
    $user = createUserWithRole('Super Admin');

    $response = $this->actingAs($user)->post(route('pos.wholesale.customer'), [
        'name' => 'POS Test Customer',
        'phone' => '0779999999',
        'email' => 'postest@example.com'
    ]);

    $response->assertJson([
        'success' => true
    ]);

    $this->assertDatabaseHas('customers', [
        'name' => 'POS Test Customer',
        'phone' => '0779999999'
    ]);
});

test('pos can process a successful sales checkout', function () {
    $user = createUserWithRole('Super Admin');

    // Setup Category, Product, Stock, Serial, and Customer
    $categoryId = DB::table('categories')->insertGetId([
        'name' => 'Test POS Cat',
        'slug' => 'test-pos-cat',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $productId = DB::table('products')->insertGetId([
        'category_id' => $categoryId,
        'name' => 'POS Test Product',
        'sku' => 'POS-TEST-SKU',
        'cost_price' => 1000.00,
        'retail_price' => 1500.00,
        'wholesale_price' => 1300.00,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    DB::table('product_stock')->insert([
        'product_id' => $productId,
        'quantity' => 10.00,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $customerId = DB::table('customers')->insertGetId([
        'name' => 'POS Test Customer 2',
        'balance' => 0.00,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $payload = [
        'customer_id' => $customerId,
        'pos_type' => 'wholesale',
        'items' => [
            [
                'product_id' => $productId,
                'name' => 'POS Test Product',
                'quantity' => 2,
                'selling_price' => 1300.00,
                'line_total' => 2600.00
            ]
        ],
        'total_amount' => 2600.00,
        'discount_amount' => 0.00,
        'tax_amount' => 0.00,
        'payable_amount' => 2600.00,
        'paid_amount' => 2600.00,
        'payment_method' => 'Cash'
    ];

    $response = $this->actingAs($user)->post(route('pos.wholesale.store'), $payload);

    $response->assertJson([
        'success' => true
    ]);

    // Check if sales records are stored
    $this->assertDatabaseHas('sales', [
        'customer_id' => $customerId,
        'payable_amount' => 2600.00,
        'paid_amount' => 2600.00,
        'due_amount' => 0.00
    ]);

    // Check if stock quantity decreased by 2
    $this->assertDatabaseHas('product_stock', [
        'product_id' => $productId,
        'quantity' => 8.00
    ]);

    // Check stock movements logged
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $productId,
        'quantity' => -2.00,
        'type' => 'sale'
    ]);
});
