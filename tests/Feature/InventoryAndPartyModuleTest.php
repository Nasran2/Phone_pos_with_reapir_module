<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\CustomerTransaction;
use App\Models\SupplierTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustment;
use App\Models\DamagedStock;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\UserManagementSeeder;

beforeEach(function () {
    // Seed user roles and permissions first
    $this->seed(UserManagementSeeder::class);

    // Clear relevant tables to isolate from dev database state
    DB::table('customer_transactions')->delete();
    DB::table('customers')->delete();
    DB::table('supplier_transactions')->delete();
    DB::table('purchases')->delete();
    DB::table('suppliers')->delete();
    DB::table('stock_histories')->delete();
    DB::table('product_stock')->delete();
    DB::table('products')->delete();
    DB::table('stock_adjustment_items')->delete();
    DB::table('stock_adjustments')->delete();
    DB::table('damaged_stocks')->delete();
});

test('guest users are redirected to login for inventory and party module pages', function () {
    $this->get(route('customers.index'))->assertRedirect(route('login'));
    $this->get(route('suppliers.index'))->assertRedirect(route('login'));
    $this->get(route('stock.index'))->assertRedirect(route('login'));
    $this->get(route('stock-adjustments.index'))->assertRedirect(route('login'));
    $this->get(route('damaged-stocks.index'))->assertRedirect(route('login'));
});

test('unauthorized users without permissions are blocked from inventory and party module pages', function () {
    $user = User::factory()->create(); // no role assigned
    
    $this->actingAs($user)->get(route('customers.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('suppliers.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('stock.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('stock-adjustments.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('damaged-stocks.index'))->assertStatus(403);
});

test('admin can manage customers CRUD', function () {
    $admin = createUserWithRole('Super Admin');
    
    // 1. Create Customer
    $response = $this->actingAs($admin)->post(route('customers.store'), [
        'name' => 'Test Customer',
        'phone' => '0777123456',
        'email' => 'customer@test.com',
        'address' => 'Colombo, Sri Lanka',
        'opening_balance' => 1500.00,
        'credit_limit' => 50000.00,
        'status' => '1',
        'notes' => 'Some test notes'
    ]);
    
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('customers', [
        'name' => 'Test Customer',
        'phone' => '0777123456',
        'opening_balance' => 1500.00,
        'credit_limit' => 50000.00,
        'status' => true,
    ]);
    
    $customer = Customer::where('phone', '0777123456')->first();
    // Ledger has opening balance transaction
    $this->assertDatabaseHas('customer_transactions', [
        'customer_id' => $customer->id,
        'type' => 'opening_balance',
        'debit' => 1500.00,
    ]);
    expect((float)$customer->balance)->toBe(1500.00);

    // 2. Update Customer
    $response = $this->actingAs($admin)->put(route('customers.update', $customer->id), [
        'name' => 'Updated Customer Name',
        'phone' => '0777654321',
        'email' => 'customer_updated@test.com',
        'address' => 'Kandy, Sri Lanka',
        'opening_balance' => 2000.00, // +500 difference
        'credit_limit' => 60000.00,
        'status' => '0',
        'notes' => 'Updated test notes'
    ]);
    
    $response->assertSessionHasNoErrors();
    $customer->refresh();
    expect($customer->name)->toBe('Updated Customer Name');
    expect($customer->phone)->toBe('0777654321');
    expect($customer->status)->toBe(false);
    expect((float)$customer->balance)->toBe(2000.00); // balance adjusted based on opening balance diff

    // 3. Record Payment
    $response = $this->actingAs($admin)->post(route('customers.payments.store', $customer->id), [
        'amount' => 500.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'Cash',
        'reference_no' => 'PAY-12345',
        'note' => 'Paid 500'
    ]);
    $response->assertSessionHasNoErrors();
    $customer->refresh();
    expect((float)$customer->balance)->toBe(1500.00); // 2000 - 500 = 1500

    // 4. Delete Customer
    $response = $this->actingAs($admin)->delete(route('customers.destroy', $customer->id));
    $response->assertSessionHasNoErrors();
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

test('admin can manage suppliers CRUD', function () {
    $admin = createUserWithRole('Super Admin');
    
    // 1. Create Supplier
    $response = $this->actingAs($admin)->post(route('suppliers.store'), [
        'name' => 'Test Supplier',
        'company_name' => 'Supplier Co',
        'phone' => '0112123456',
        'email' => 'supplier@test.com',
        'address' => 'Colombo, Sri Lanka',
        'opening_balance' => 3000.00,
        'bank_details' => 'BOC Account',
        'status' => '1',
        'notes' => 'Test notes'
    ]);
    
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('suppliers', [
        'name' => 'Test Supplier',
        'company_name' => 'Supplier Co',
        'opening_balance' => 3000.00,
        'status' => true,
    ]);
    
    $supplier = Supplier::where('phone', '0112123456')->first();
    // Ledger has opening balance transaction
    $this->assertDatabaseHas('supplier_transactions', [
        'supplier_id' => $supplier->id,
        'type' => 'opening_balance',
        'debit' => 3000.00, // positive balance changes debit
    ]);
    expect((float)$supplier->balance)->toBe(3000.00);

    // 2. Update Supplier
    $response = $this->actingAs($admin)->put(route('suppliers.update', $supplier->id), [
        'name' => 'Updated Supplier Name',
        'company_name' => 'Supplier Co Ltd',
        'phone' => '0112654321',
        'email' => 'supplier_updated@test.com',
        'address' => 'Jaffna, Sri Lanka',
        'opening_balance' => 2500.00, // -500 difference
        'bank_details' => 'BOC Account Updated',
        'status' => '0',
        'notes' => 'Updated notes'
    ]);
    
    $response->assertSessionHasNoErrors();
    $supplier->refresh();
    expect($supplier->name)->toBe('Updated Supplier Name');
    expect($supplier->company_name)->toBe('Supplier Co Ltd');
    expect($supplier->status)->toBe(false);
    expect((float)$supplier->balance)->toBe(2500.00);

    // 3. Record Payment
    $response = $this->actingAs($admin)->post(route('suppliers.payments.store', $supplier->id), [
        'amount' => 1000.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'Bank Transfer',
        'reference_no' => 'PAY-54321',
        'note' => 'Paid 1000'
    ]);
    $response->assertSessionHasNoErrors();
    $supplier->refresh();
    expect((float)$supplier->balance)->toBe(1500.00); // 2500 - 1000 = 1500

    // 4. Delete Supplier
    $response = $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier->id));
    $response->assertSessionHasNoErrors();
    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
});

test('admin can log stock adjustments', function () {
    $admin = createUserWithRole('Super Admin');
    
    $product = Product::create([
        'name' => 'Stock Test Phone',
        'sku' => 'STP-001',
        'barcode' => 'STP-BARCODE',
        'cost_price' => 10000.00,
        'retail_price' => 12000.00,
        'wholesale_price' => 11000.00,
        'is_active' => true,
    ]);
    
    ProductStock::create([
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    // 1. Stock Increase
    $response = $this->actingAs($admin)->post(route('stock-adjustments.store'), [
        'adjustment_type' => 'increase',
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Inventory Audit',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
            ]
        ]
    ]);
    
    $response->assertSessionHasNoErrors();
    $product->refresh();
    expect((float)$product->stock->quantity)->toBe(15.00); // 10 + 5 = 15
    $this->assertDatabaseHas('stock_histories', [
        'product_id' => $product->id,
        'quantity_change' => 5,
        'new_quantity' => 15,
        'type' => 'adjustment',
    ]);

    // 2. Stock Decrease
    $response = $this->actingAs($admin)->post(route('stock-adjustments.store'), [
        'adjustment_type' => 'decrease',
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Discrepancy',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
            ]
        ]
    ]);
    
    $response->assertSessionHasNoErrors();
    $product->refresh();
    expect((float)$product->stock->quantity)->toBe(12.00); // 15 - 3 = 12
});

test('admin can manage damaged stock logs and returns', function () {
    $admin = createUserWithRole('Super Admin');
    
    $product = Product::create([
        'name' => 'Damage Test Acc',
        'sku' => 'DTA-001',
        'barcode' => 'DTA-BARCODE',
        'cost_price' => 500.00,
        'retail_price' => 700.00,
        'wholesale_price' => 600.00,
        'is_active' => true,
    ]);
    
    ProductStock::create([
        'product_id' => $product->id,
        'quantity' => 20,
    ]);

    // 1. Log Damaged Stock (Reduces stock, computes estimated loss)
    $response = $this->actingAs($admin)->post(route('damaged-stocks.store'), [
        'product_id' => $product->id,
        'quantity' => 4,
        'damage_type' => 'Screen Crack',
        'reason' => 'Dropped in showroom',
        'status' => 'Pending',
        'responsible_person' => 'Showroom Staff',
        'notes' => 'Awaiting repair review'
    ]);
    
    $response->assertSessionHasNoErrors();
    $product->refresh();
    expect((float)$product->stock->quantity)->toBe(16.00); // 20 - 4 = 16
    
    $dmg = DamagedStock::where('product_id', $product->id)->first();
    expect((float)$dmg->estimated_loss)->toBe(2000.00); // 4 * 500.00 = 2000.00
    
    // 2. Return Damaged Stock (Increases stock, updates status)
    $response = $this->actingAs($admin)->post(route('damaged-stocks.status', $dmg->id), [
        'status' => 'Returned to Stock'
    ]);
    
    $response->assertSessionHasNoErrors();
    $product->refresh();
    expect((float)$product->stock->quantity)->toBe(20.00); // 16 + 4 = 20
    $dmg->refresh();
    expect($dmg->status)->toBe('Returned to Stock');
});
