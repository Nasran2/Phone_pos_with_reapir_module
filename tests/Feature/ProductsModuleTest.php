<?php

use App\Exports\ProductImportWorkbookExport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

test('guest users are redirected to login for products module pages', function () {
    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->get(route('brands.index'))->assertRedirect(route('login'));
    $this->get(route('units.index'))->assertRedirect(route('login'));
});

test('unauthorized users without permissions are blocked from products pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('products.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('categories.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('brands.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('units.index'))->assertStatus(403);
});

test('super admin can access all products module index pages', function () {
    $user = createUserWithRole('Super Admin');

    $this->actingAs($user)->get(route('products.index'))->assertOk();
    $this->actingAs($user)->get(route('categories.index'))->assertOk();
    $this->actingAs($user)->get(route('brands.index'))->assertOk();
    $this->actingAs($user)->get(route('units.index'))->assertOk();
});

test('can create a product with IMEIs and variants', function () {
    Storage::fake('public');
    $admin = createUserWithRole('Super Admin');

    $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
    $brand = Brand::create(['name' => 'Apple', 'slug' => 'apple']);
    $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);

    $imageFile = UploadedFile::fake()->image('iphone.jpg');

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'iPhone 15 Pro',
        'sku' => 'IP15P-128',
        'barcode' => 'BC-1234567',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'cost_price' => 150000.00,
        'retail_price' => 180000.00,
        'wholesale_price' => 170000.00,
        'stock_alert_quantity' => 5,
        'description' => 'Latest iPhone model',
        'logo' => $imageFile,
        'is_active' => '1',
        'store_ids' => [1],
        'opening_stock' => [1 => 0],
        'imeis' => "IMEI-11111111\nIMEI-22222222",
        'variants' => [
            [
                'color' => 'Black',
                'storage' => '128GB',
                'ram' => '8GB',
                'sku' => 'IP15P-128-BLK',
                'cost_price' => 150000.00,
                'retail_price' => 180000.00,
                'wholesale_price' => 170000.00,
                'stock_quantity' => 1,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'name' => 'iPhone 15 Pro',
        'sku' => 'IP15P-128',
        'barcode' => 'BC-1234567',
        'cost_price' => 150000.00,
        'retail_price' => 180000.00,
        'wholesale_price' => 170000.00,
    ]);

    $product = Product::where('sku', 'IP15P-128')->first();
    expect($product->image)->not->toBeNull();

    // Verify stock is matching serials count (2)
    $this->assertDatabaseHas('product_store_stock', [
        'product_id' => $product->id,
        'store_id' => 1,
        'quantity' => 2,
    ]);

    // Verify serial numbers registered
    $this->assertDatabaseHas('product_serials', [
        'product_id' => $product->id,
        'serial_number' => 'IMEI-11111111',
        'status' => 'available',
    ]);
    $this->assertDatabaseHas('product_serials', [
        'product_id' => $product->id,
        'serial_number' => 'IMEI-22222222',
        'status' => 'available',
    ]);

    // Verify variant registered
    $this->assertDatabaseHas('product_variants', [
        'product_id' => $product->id,
        'color' => 'Black',
        'storage' => '128GB',
        'ram' => '8GB',
        'sku' => 'IP15P-128-BLK',
    ]);

    // Verify stock history logging
    $this->assertDatabaseHas('stock_histories', [
        'product_id' => $product->id,
        'type' => 'opening_stock',
    ]);
});

test('can update a product details', function () {
    $admin = createUserWithRole('Super Admin');

    $product = Product::create([
        'name' => 'Original Name',
        'sku' => 'SKU-ORIGINAL',
        'barcode' => 'BC-ORIGINAL',
        'cost_price' => 1000.00,
        'retail_price' => 1200.00,
        'wholesale_price' => 1100.00,
        'stock_alert_quantity' => 3,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('products.update', $product->id), [
        'name' => 'Updated Product Name',
        'sku' => 'SKU-UPDATED',
        'barcode' => 'BC-UPDATED',
        'cost_price' => 1050.00,
        'retail_price' => 1250.00,
        'wholesale_price' => 1150.00,
        'stock_alert_quantity' => 4,
        'description' => 'Updated description',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Product Name',
        'sku' => 'SKU-UPDATED',
        'barcode' => 'BC-UPDATED',
        'cost_price' => 1050.00,
        'retail_price' => 1250.00,
        'wholesale_price' => 1150.00,
        'stock_alert_quantity' => 4,
    ]);
});

test('can delete a product (soft delete)', function () {
    $admin = createUserWithRole('Super Admin');

    $product = Product::create([
        'name' => 'Product to Delete',
        'sku' => 'SKU-DEL',
        'barcode' => 'BC-DEL',
        'cost_price' => 100.00,
        'retail_price' => 120.00,
        'wholesale_price' => 110.00,
    ]);

    $response = $this->actingAs($admin)->delete(route('products.destroy', $product->id));
    $response->assertRedirect(route('products.index'));

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('can manage categories (CRUD)', function () {
    $admin = createUserWithRole('Super Admin');

    // 1. Create
    $response = $this->actingAs($admin)->post(route('categories.store'), [
        'name' => 'Accessories',
        'description' => 'Mobile accessories category',
    ]);
    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', [
        'name' => 'Accessories',
        'slug' => 'accessories',
    ]);

    $category = Category::where('slug', 'accessories')->first();

    // 2. Update
    $response = $this->actingAs($admin)->put(route('categories.update', $category->id), [
        'name' => 'Premium Accessories',
        'description' => 'Updated description',
    ]);
    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Premium Accessories',
    ]);

    // 3. Delete
    $response = $this->actingAs($admin)->delete(route('categories.destroy', $category->id));
    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('can manage brands (CRUD)', function () {
    Storage::fake('public');
    $admin = createUserWithRole('Super Admin');

    // 1. Create
    $logoFile = UploadedFile::fake()->image('samsung.png');
    $response = $this->actingAs($admin)->post(route('brands.store'), [
        'name' => 'Samsung',
        'description' => 'Samsung electronics brand',
        'logo' => $logoFile,
    ]);
    $response->assertRedirect(route('brands.index'));
    $this->assertDatabaseHas('brands', [
        'name' => 'Samsung',
        'slug' => 'samsung',
    ]);

    $brand = Brand::where('slug', 'samsung')->first();
    expect($brand->image)->not->toBeNull();

    // 2. Update
    $response = $this->actingAs($admin)->put(route('brands.update', $brand->id), [
        'name' => 'Samsung Global',
        'description' => 'Updated description',
    ]);
    $response->assertRedirect(route('brands.index'));
    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'Samsung Global',
    ]);

    // 3. Delete
    $response = $this->actingAs($admin)->delete(route('brands.destroy', $brand->id));
    $response->assertRedirect(route('brands.index'));
    $this->assertSoftDeleted('brands', [
        'id' => $brand->id,
    ]);
});

test('can manage units (CRUD)', function () {
    $admin = createUserWithRole('Super Admin');

    // 1. Create
    $response = $this->actingAs($admin)->post(route('units.store'), [
        'name' => 'Box Container',
        'short_name' => 'box-cnt',
    ]);
    $response->assertRedirect(route('units.index'));
    $this->assertDatabaseHas('units', [
        'name' => 'Box Container',
        'short_name' => 'box-cnt',
    ]);

    $unit = Unit::where('short_name', 'box-cnt')->first();

    // 2. Update
    $response = $this->actingAs($admin)->put(route('units.update', $unit->id), [
        'name' => 'Box Large',
        'short_name' => 'box-lg',
    ]);
    $response->assertRedirect(route('units.index'));
    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'name' => 'Box Large',
        'short_name' => 'box-lg',
    ]);

    // 3. Delete
    $response = $this->actingAs($admin)->delete(route('units.destroy', $unit->id));
    $response->assertRedirect(route('units.index'));
    $this->assertDatabaseMissing('units', [
        'id' => $unit->id,
    ]);
});

test('can export products to CSV and import from CSV', function () {
    $admin = createUserWithRole('Super Admin');

    // Create product to export
    $product = Product::create([
        'name' => 'Export Product',
        'sku' => 'EXP-001',
        'barcode' => 'EXPBC-001',
        'cost_price' => 500.00,
        'retail_price' => 600.00,
        'wholesale_price' => 550.00,
        'is_active' => true,
    ]);
    ProductStoreStock::create([
        'product_id' => $product->id,
        'store_id' => 1,
        'quantity' => 10,
    ]);

    // Test Export
    $response = $this->actingAs($admin)->get(route('products.export'));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->streamedContent();
    expect($content)->toContain('Export Product');
    expect($content)->toContain('EXP-001');

    // Test Import
    // Create a temporary CSV file
    $csvData = "ID,Name,SKU,Barcode,Category,Brand,Unit,Cost Price,Retail Price,Wholesale Price,Stock Alert Qty,Status,Opening Stock\n";
    $csvData .= ",Imported Product,IMP-999,IMPBC-999,Audio,Sony,pcs,400.00,500.00,450.00,3,Active,15\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $csvData);

    $importResponse = $this->actingAs($admin)->post(route('products.import'), [
        'file' => $file,
    ]);
    $importResponse->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'name' => 'Imported Product',
        'sku' => 'IMP-999',
        'barcode' => 'IMPBC-999',
        'cost_price' => 400.00,
        'retail_price' => 500.00,
        'wholesale_price' => 450.00,
    ]);

    $importedProduct = Product::where('sku', 'IMP-999')->first();
    $this->assertDatabaseHas('product_store_stock', [
        'product_id' => $importedProduct->id,
        'store_id' => 1,
        'quantity' => 15,
    ]);
});

test('excel import template exposes products and category reference sheets', function () {
    $admin = createUserWithRole('Super Admin');
    $category = Category::create(['name' => 'Visible Phones', 'slug' => 'visible-phones']);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Visible Product',
        'sku' => 'VISIBLE-001',
        'barcode' => 'VISIBLE-BC-001',
        'cost_price' => 100,
        'retail_price' => 150,
        'wholesale_price' => 125,
    ]);

    $this->actingAs($admin)
        ->get(route('products.import-template'))
        ->assertOk()
        ->assertDownload('product_import_template.xlsx');

    $sheets = (new ProductImportWorkbookExport)->sheets();
    expect(collect($sheets)->map->title()->all())->toBe(['Instructions', 'Products', 'Categories', 'Brands', 'Units']);
    expect($sheets[1]->collection()->pluck(0)->all())->toContain('Visible Product');
    expect($sheets[2]->collection()->pluck(1)->all())->toContain('Visible Phones');

    $workbook = ExcelFacade::raw(new ProductImportWorkbookExport, ExcelFormat::XLSX);
    $upload = UploadedFile::fake()->createWithContent('product_import_template.xlsx', $workbook);
    $this->actingAs($admin)->post(route('products.import'), ['file' => $upload])
        ->assertRedirect(route('products.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', ['sku' => 'VISIBLE-001', 'category_id' => $category->id]);
});
