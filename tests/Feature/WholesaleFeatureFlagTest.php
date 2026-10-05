<?php

use App\Exports\ProductImportProductsSheet;
use App\Exports\ProductImportWorkbookExport;
use App\Models\Account;
use App\Models\Customer;
use App\Models\HoldSale;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\JournalService;
use App\Support\Features;
use App\Support\SystemSettings;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\DashboardDemoSeeder;
use Database\Seeders\UserManagementSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Process\Process;

beforeEach(function () {
    config(['features.wholesale' => false]);
    SystemSettings::clearCache();
    $this->actingAs(createUserWithRole('Super Admin'));
});

test('wholesale only endpoints are unavailable', function (string $method, string $route) {
    $this->{$method}(route($route))->assertNotFound();
})->with([
    ['get', 'pos.wholesale'],
    ['post', 'pos.wholesale.store'],
    ['post', 'pos.wholesale.customer'],
    ['get', 'reports.wholesale_sales'],
    ['get', 'distribution.sales'],
]);

test('retail mode pages contain no wholesale wording or navigation', function (string $route) {
    $response = $this->get(route($route))->assertOk();
    expect(strtolower($response->getContent()))->not->toContain('wholesale');
})->with([
    'dashboard', 'products.index', 'stock.index', 'purchases.create', 'sales.index',
    'hold-sales.index', 'sales-returns.index', 'sales-returns.create',
    'reports.index', 'reports.sales', 'reports.stock', 'reports.daily_closing', 'reports.profit_loss',
    'settings.general', 'settings.invoice', 'settings.pos', 'settings.product', 'settings.sales',
    'roles.index', 'permissions.index', 'users.index', 'pos.retail', 'accounting.accounts.index',
    'accounting.journal-entries.index', 'accounting.ledger.index',
    'distribution.dashboard', 'distribution.stock.index', 'distribution.load',
]);

test('login presents retail and repair without wholesale', function () {
    auth()->logout();
    $response = $this->get(route('login'))->assertOk();
    expect(strtolower($response->getContent()))->not->toContain('wholesale');
});

test('crafted channel filters and retail checkout cannot activate wholesale', function (string $route, string $key) {
    $this->get(route($route, [$key => 'wholesale']))->assertNotFound();
})->with([
    ['sales.index', 'sale_type'], ['reports.sales', 'sale_type'],
    ['hold-sales.index', 'sale_type'], ['sales-returns.index', 'sale_type'], ['pos.retail', 'pos_type'],
]);

test('retail mode rejects wholesale checkout and item price types', function () {
    $this->postJson(route('pos.retail.store'), ['pos_type' => 'wholesale'])->assertNotFound();
    $this->postJson(route('pos.retail.store'), ['items' => [['price_type' => 'wholesale']]])->assertNotFound();
    expect(Sale::count())->toBe(0);
});

test('dashboard returns a single sales series and no wholesale comparison', function () {
    $response = $this->get(route('dashboard'))->assertOk();
    expect($response->viewData('salesSeries'))->toHaveCount(1)
        ->and($response->viewData('salesSeries')[0]['name'])->toBe('Sales')
        ->and($response->viewData('wholesaleSalesChart'))->toBe([])
        ->and($response->viewData('changes'))->toHaveCount(4);
});

test('products and variants can be created and edited without wholesale prices', function () {
    $data = ['name' => 'Retail Phone', 'sku' => 'RETAIL-FLAG', 'cost_price' => 100, 'retail_price' => 150,
        'is_active' => true, 'variants' => [['sku' => 'RETAIL-FLAG-V1', 'cost_price' => 100, 'retail_price' => 150, 'stock_quantity' => 0]]];
    $this->from(route('products.index'))->post(route('products.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $product = Product::where('sku', 'RETAIL-FLAG')->firstOrFail();
    $variant = $product->variants()->firstOrFail();
    expect((float) $product->wholesale_price)->toBe(0.0)->and((float) $variant->wholesale_price)->toBe(0.0);

    $product->update(['wholesale_price' => 123]);
    $variant->update(['wholesale_price' => 122]);
    $data['barcode'] = $product->barcode;
    $data['variants'][0]['id'] = $variant->id;
    // Existing values must be preserved even if a crafted request supplies an inactive price.
    $data['wholesale_price'] = 1;
    $data['variants'][0]['wholesale_price'] = 2;
    $this->from(route('products.index'))->put(route('products.update', $product), $data)->assertSessionHasNoErrors()->assertRedirect();
    expect((float) $product->fresh()->wholesale_price)->toBe(123.0)
        ->and((float) $variant->fresh()->wholesale_price)->toBe(122.0);

    unset($data['wholesale_price'], $data['variants'][0]['wholesale_price']);
    $this->put(route('products.update', $product), $data)->assertSessionHasNoErrors();
    expect((float) $product->fresh()->wholesale_price)->toBe(123.0);
});

test('retail purchases preserve product wholesale prices when received', function () {
    $supplier = Supplier::create(['name' => 'Flag Supplier', 'balance' => 0]);
    $product = Product::create(['name' => 'Purchased Phone', 'sku' => 'PUR-FLAG', 'cost_price' => 60, 'retail_price' => 100, 'wholesale_price' => 90]);
    $this->post(route('purchases.store'), [
        'supplier_id' => $supplier->id, 'purchase_date' => today()->toDateString(), 'status' => 'Received', 'store_id' => 1,
        'discount_type' => 'none', 'shipping_handling' => 'purchase_total', 'payment_method' => 'Cash', 'payment_amount' => 0,
        'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 65, 'retail_price' => 110]],
    ])->assertSessionHasNoErrors()->assertRedirect();
    $purchase = Purchase::latest('id')->firstOrFail();
    expect((float) $product->fresh()->wholesale_price)->toBe(90.0)
        ->and((float) $product->fresh()->cost_price)->toBe(65.0);
    foreach (['purchases.show', 'purchases.edit'] as $route) {
        expect(strtolower($this->get(route($route, $purchase))->assertOk()->getContent()))->not->toContain('wholesale');
    }
});

test('retail csv import and workbook export omit wholesale and preserve old prices', function () {
    $product = Product::create(['name' => 'Import Phone', 'sku' => 'IMPORT-FLAG', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    $headings = ['Name', 'SKU', 'Barcode', 'Category', 'Brand', 'Unit', 'Cost Price', 'Retail Price', 'Stock Alert Qty', 'Opening Stock'];
    $csv = implode(',', $headings)."\nImport Phone,IMPORT-FLAG,,,,,12,22,5,3\nNew Phone,NEW-IMPORT-FLAG,,,,,12,22,5,0\n";
    $this->from(route('products.index'))->post(route('products.import'), ['file' => UploadedFile::fake()->createWithContent('retail.csv', $csv)])->assertSessionHasNoErrors();
    expect((float) $product->fresh()->wholesale_price)->toBe(15.0)
        ->and((float) Product::where('sku', 'NEW-IMPORT-FLAG')->firstOrFail()->wholesale_price)->toBe(0.0);
    $sheet = new ProductImportProductsSheet(collect([$product->fresh()]));
    expect($sheet->headings())->toBe($headings)->and($sheet->collection()->first())->toHaveCount(10);
    $export = $this->get(route('products.export'))->assertOk()->streamedContent();
    expect(strtolower($export))->not->toContain('wholesale');
    expect(str_getcsv(strtok(ltrim($export, "\xEF\xBB\xBF"), "\n")))->toBe($headings);
    $this->get(route('products.import-template'))->assertOk();
    $file = tempnam(sys_get_temp_dir(), 'retail-template-');
    try {
        file_put_contents($file, Excel::raw(new ProductImportWorkbookExport, ExcelFormat::XLSX));
        $workbook = IOFactory::load($file);
        expect(array_values($workbook->getSheetByName('Products')->rangeToArray('A1:J1')[0]))->toBe($headings);
        expect($workbook->getSheetByName('Products')->getCell('K1')->getValue())->toBeNull();
    } finally {
        unlink($file);
    }
});

test('old import files containing wholesale prices remain compatible without rewriting them', function () {
    $product = Product::create(['name' => 'Legacy Import', 'sku' => 'LEGACY-FLAG', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 17]);
    $this->post(route('products.import'), ['file' => UploadedFile::fake()->createWithContent('legacy.csv', "Name,SKU,Cost Price,Retail Price,Wholesale Price\nLegacy Import,LEGACY-FLAG,11,21,1\n")])->assertSessionHasNoErrors();
    expect((float) $product->fresh()->wholesale_price)->toBe(17.0);
});

test('daily closing accepts retail totals without a wholesale request field', function () {
    $data = ['date' => today()->toDateString(), 'opening_cash' => 10, 'retail_sales' => 100,
        'customer_due_collections' => 0, 'supplier_payments' => 0, 'expenses' => 0, 'cash_in' => 100, 'cash_out' => 0,
        'expected_cash_balance' => 110, 'actual_cash_counted' => 110];
    $this->post(route('reports.daily_closing.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('daily_closings', ['date' => $data['date'], 'wholesale_sales' => 0]);
});

test('database settings cannot override the master flag and hidden settings survive resets', function () {
    DB::table('pos_settings')->updateOrInsert(['key' => 'default_pos_type'], ['value' => 'wholesale']);
    DB::table('business_settings')->updateOrInsert(['key' => 'login_landing_page'], ['value' => 'pos/wholesale']);
    DB::table('pos_settings')->updateOrInsert(['key' => 'enable_wholesale_pos'], ['value' => '1']);
    expect(SystemSettings::get('default_pos_type'))->toBe('retail')
        ->and(SystemSettings::get('login_landing_page'))->toBe('pos/retail')
        ->and(SystemSettings::bool('enable_wholesale_pos'))->toBeFalse();
    $this->post(route('settings.pos.update'), ['default_pos_type' => 'wholesale'])->assertSessionHasErrors('default_pos_type');
    $this->post(route('settings.pos.reset'))->assertRedirect();
    expect(DB::table('pos_settings')->where('key', 'enable_wholesale_pos')->value('value'))->toBe('1');
});

test('role edits preserve inactive wholesale grants and UI hides existing permission records', function () {
    $permission = Permission::firstOrCreate(['name' => 'wholesale_pos.view'], ['guard_name' => 'web']);
    $role = Role::create(['name' => 'Flag Role', 'guard_name' => 'web', 'description' => 'Retail & Wholesale billing']);
    $role->permissions()->attach($permission);
    expect(strtolower($this->get(route('roles.index'))->assertOk()->getContent()))->not->toContain('wholesale');
    expect(strtolower($this->get(route('permissions.index'))->assertOk()->getContent()))->not->toContain('wholesale');
    $this->put(route('roles.update', $role), ['name' => 'Flag Role', 'permissions' => []])->assertSessionHasNoErrors();
    expect($role->permissions()->whereKey($permission->id)->exists())->toBeTrue();
    $this->seed(UserManagementSeeder::class);
    expect(Permission::find($permission->id))->not->toBeNull();
});

test('retail pos json exposes neither product nor variant wholesale prices', function () {
    $product = Product::create(['name' => 'Payload Phone', 'sku' => 'PAYLOAD-FLAG', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => 5]);
    ProductVariant::create(['product_id' => $product->id, 'sku' => 'PAYLOAD-VAR', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15, 'stock_quantity' => 5, 'is_active' => true]);
    $response = $this->getJson(route('pos.retail'))->assertOk();
    expect($response->json('products'))->not->toBeEmpty();
    expect(strtolower($response->getContent()))->not->toContain('wholesale');
});

test('historical invoices remain stored and readable while wholesale actions are blocked', function () {
    $customer = Customer::create(['name' => 'Historical Customer', 'balance' => 0]);
    $sale = Sale::create(['customer_id' => $customer->id, 'invoice_no' => 'HISTORIC-FLAG', 'sale_date' => today(), 'pos_type' => 'wholesale',
        'total_amount' => 200, 'payable_amount' => 200, 'paid_amount' => 100, 'due_amount' => 100, 'status' => 'final']);
    $hold = HoldSale::create(['customer_id' => $customer->id, 'hold_date' => today(), 'reference_no' => 'HISTORIC-HOLD', 'pos_type' => 'wholesale', 'total_amount' => 100]);
    $this->get(route('sales.index'))->assertDontSee('HISTORIC-FLAG');
    foreach (['sales.show', 'sales.print'] as $route) {
        expect(strtolower($this->get(route($route, $sale))->assertOk()->getContent()))->not->toContain('wholesale');
    }
    $this->post(route('sales.payments.store', $sale), [])->assertNotFound();
    $this->delete(route('sales.destroy', $sale))->assertNotFound();
    $this->post(route('hold-sales.resume', $hold))->assertNotFound();
    $this->get(route('pos.retail', ['edit_sale_id' => $sale->id]))->assertNotFound();
    $this->get(route('sales-returns.create', ['sale_id' => $sale->id]))->assertNotFound();
    expect($sale->fresh()->pos_type)->toBe('wholesale')->and((float) $sale->fresh()->payable_amount)->toBe(200.0)
        ->and($hold->fresh())->not->toBeNull();
    config(['features.wholesale' => true]);
    $this->get(route('sales.index'))->assertSee('HISTORIC-FLAG')->assertSee('Wholesale');
    $this->post(route('hold-sales.resume', $hold))->assertRedirect(route('pos.wholesale', ['hold_id' => $hold->id]));
});

test('fresh accounting seed skips wholesale accounts and existing journals keep balances', function () {
    $this->seed(AccountingSeeder::class);
    expect(Account::where('account_code', '4002')->exists())->toBeFalse();
    config(['features.wholesale' => true]);
    $this->seed(AccountingSeeder::class);
    $income = Account::where('account_code', '4002')->firstOrFail();
    $cash = Account::where('account_code', '1001')->firstOrFail();
    $journal = app(JournalService::class)->createEntry(today(), 'Sale', 100, 'HIST-JRN', 'Wholesale Sale Invoice', [
        ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0], ['account_id' => $income->id, 'debit' => 0, 'credit' => 100],
    ]);
    config(['features.wholesale' => false]);
    expect(strtolower($this->get(route('accounting.journal-entries.index'))->assertOk()->getContent()))->not->toContain('wholesale');
    $this->seed(AccountingSeeder::class);
    expect((float) $income->fresh()->current_balance)->toBe(100.0)->and($journal->fresh()->items)->toHaveCount(2);
});

test('flag restores menus product fields reports and wholesale checkout', function () {
    config(['features.wholesale' => true]);
    $this->get(route('pos.wholesale'))->assertOk();
    $this->get(route('dashboard'))->assertSee('Wholesale POS')->assertSee('Wholesale Sales');
    $this->get(route('products.index'))->assertSee('Wholesale Selling Price');
    $this->get(route('reports.index'))->assertSee('Wholesale Sales');
    $this->get(route('reports.wholesale_sales'))->assertOk();
    $product = Product::create(['name' => 'Restored Phone', 'sku' => 'RESTORE-FLAG', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => 2]);
    $customer = Customer::create(['name' => 'Restored Customer', 'balance' => 0]);
    $payload = ['customer_id' => $customer->id, 'items' => [['product_id' => $product->id, 'quantity' => 1, 'selling_price' => 15]],
        'total_amount' => 15, 'payable_amount' => 15, 'paid_amount' => 15, 'payment_method' => 'Cash'];
    $this->postJson(route('pos.wholesale.store'), $payload)->assertOk()->assertJson(['success' => true]);
    expect(Sale::latest('id')->firstOrFail()->pos_type)->toBe('wholesale');
    $this->postJson(route('pos.wholesale.store'), $payload + ['pos_type' => 'retail'])->assertUnprocessable();
});

test('boolean flag values normalize consistently', function ($value, bool $enabled) {
    config(['features.wholesale' => $value]);
    expect(Features::wholesaleEnabled())->toBe($enabled);
})->with([[true, true], [false, false], ['true', true], ['false', false], [1, true], [0, false], ['1', true], ['0', false]]);

test('lorry wholesale selling is blocked while stock and expense workspaces stay available', function () {
    $user = auth()->user();
    $user->update(['user_type' => 'lorry']);
    $lorry = DB::table('lorries')->insertGetId(['lorry_no' => 'FLAG-LORRY', 'name' => 'Flag Lorry', 'status' => 'active']);
    DB::table('lorry_user_assignments')->insert(['user_id' => $user->id, 'lorry_id' => $lorry, 'is_active' => true,
        'lorry_role' => 'lorry_manager', 'active_from' => today()->toDateString(), 'is_primary' => true, 'can_sell' => true, 'can_create_orders' => true, 'can_add_expenses' => true]);
    $product = Product::create(['name' => 'Lorry Stock Phone', 'sku' => 'LORRY-FLAG', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    DB::table('lorry_stocks')->insert(['product_id' => $product->id, 'lorry_id' => $lorry, 'quantity' => 2]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => 2]);
    foreach (['distribution.my-lorry.dashboard', 'distribution.my-lorry.stock', 'distribution.my-lorry.expenses'] as $route) {
        $response = $this->get(route($route))->assertOk();
        expect(strtolower($response->getContent()))->not->toContain('wholesale', '/my-lorry/order');
    }
    $this->get(route('distribution.my-lorry.order.create'))->assertNotFound();
    $this->post(route('distribution.my-lorry.order.store'), [])->assertNotFound();
    $this->get(route('distribution.my-lorry.sales'))->assertNotFound();
    $response = $this->getJson(route('distribution.product-search', ['q' => 'Lorry Stock']))->assertOk();
    expect(strtolower($response->getContent()))->not->toContain('wholesale');
    expect((float) DB::table('lorry_stocks')->where('lorry_id', $lorry)->value('quantity'))->toBe(2.0);
});

test('sales and stock excel and pdf exports contain only retail columns', function () {
    foreach (['reports.sales', 'reports.stock'] as $route) {
        $response = $this->get(route($route, ['type' => 'excel']))->assertOk();
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $cells = $workbook->getActiveSheet()->toArray();
        expect(strtolower(json_encode($cells)))->not->toContain('wholesale');
        expect(collect($cells)->flatten()->contains($route === 'reports.sales' ? 'Type' : 'Wholesale'))->toBeFalse();
        $pdf = $this->get(route($route, ['type' => 'pdf']))->assertOk();
        expect($pdf->headers->get('content-type'))->toContain('application/pdf');
    }
});

test('cached configuration keeps the master flag independent of subsequent environment changes', function (string $value) {
    $cachePath = tempnam(sys_get_temp_dir(), 'wholesale-config-');
    unlink($cachePath);
    try {
        $environment = ['WHOLESALE' => $value, 'APP_CONFIG_CACHE' => $cachePath, 'PAO_DISABLE' => '1'];
        $cache = new Process([PHP_BINARY, 'artisan', 'config:cache'], base_path(), $environment);
        $cache->mustRun();
        $script = 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Support\Features::wholesaleEnabled() ? "enabled" : "disabled";';
        $environment['WHOLESALE'] = $value === 'false' ? 'true' : 'false';
        $probe = new Process([PHP_BINARY, '-r', $script], base_path(), $environment);
        $probe->mustRun();
        expect($probe->getOutput())->toBe($value === 'true' ? 'enabled' : 'disabled');
    } finally {
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
    }
})->with(['false', 'true']);

test('fresh demo seed respects the selected sales channel mode', function (bool $enabled) {
    config(['features.wholesale' => $enabled]);
    $this->seed(\Database\Seeders\UnitSeeder::class);
    $this->seed(DashboardDemoSeeder::class);
    expect(Sale::where('pos_type', 'wholesale')->count())->toBe($enabled ? 2 : 0)
        ->and(DB::table('sale_items')->where('price_type', 'wholesale')->count())->toBe($enabled ? 2 : 0)
        ->and(ProductStoreStock::count())->toBe(7);
    if (! $enabled) {
        expect((float) DB::table('daily_summaries')->value('wholesale_sales'))->toBe(0.0);
    }
})->with([false, true]);

test('daily closing includes historical receipts without rewriting channel totals', function () {
    $customer = Customer::create(['name' => 'Closing Customer', 'balance' => 0]);
    $sale = Sale::create(['customer_id' => $customer->id, 'invoice_no' => 'HIST-CLOSE', 'sale_date' => today(), 'pos_type' => 'wholesale',
        'total_amount' => 100, 'payable_amount' => 100, 'paid_amount' => 100, 'due_amount' => 0, 'status' => 'final']);
    DB::table('daily_closings')->insert(['date' => today()->toDateString(), 'wholesale_sales' => 100, 'created_by' => auth()->id()]);
    $this->get(route('reports.daily_closing'))->assertOk()->assertViewHas('salesReceipts', 100.0)->assertViewHas('cashIn', 100.0)->assertSee('Sales Receipts')->assertDontSee('Wholesale');
    $this->post(route('reports.daily_closing.store'), ['date' => today()->toDateString(), 'opening_cash' => 0, 'retail_sales' => 0,
        'customer_due_collections' => 0, 'supplier_payments' => 0, 'expenses' => 0, 'cash_in' => 100, 'cash_out' => 0,
        'expected_cash_balance' => 100, 'actual_cash_counted' => 100])->assertSessionHasNoErrors();
    expect((float) DB::table('daily_closings')->value('wholesale_sales'))->toBe(100.0)
        ->and((float) DB::table('daily_closings')->value('cash_in'))->toBe(100.0)
        ->and($sale->fresh()->pos_type)->toBe('wholesale');
});

test('login falls back from stored and intended wholesale destinations', function () {
    DB::table('business_settings')->insert(['key' => 'login_landing_page', 'value' => 'pos/wholesale']);
    SystemSettings::clearCache();
    $user = auth()->user();
    auth()->logout();
    $this->withSession(['url.intended' => route('distribution.my-lorry.sales')])->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasNoErrors()->assertRedirect(route('pos.retail'));
    expect(DB::table('business_settings')->where('key', 'login_landing_page')->value('value'))->toBe('pos/wholesale');
});

test('historical activity descriptions stay readable without advertising the disabled module', function () {
    $id = DB::table('activity_logs')->insertGetId(['user_id' => auth()->id(), 'action' => 'Sale', 'module' => 'Wholesale POS',
        'description' => 'Processed wholesale sale HIST-ACTIVITY', 'created_at' => now()]);
    foreach (['activity-logs.index', 'reports.user_activity'] as $route) {
        $response = $this->get(route($route))->assertOk()->assertSee('HIST-ACTIVITY');
        expect(strtolower($response->getContent()))->not->toContain('wholesale');
    }
    expect(DB::table('activity_logs')->where('id', $id)->value('description'))->toBe('Processed wholesale sale HIST-ACTIVITY');
});

test('user editing omits hidden role metadata while grants stay stored', function () {
    $role = Role::create(['name' => 'Historic Cashier', 'description' => 'Retail & Wholesale POS']);
    auth()->user()->roles()->attach($role);
    expect(strtolower($this->get(route('users.index'))->assertOk()->getContent()))->not->toContain('wholesale');
    expect($role->fresh()->description)->toBe('Retail & Wholesale POS');
});

test('stock movement history hides the channel label without changing its saved note', function () {
    $product = Product::create(['name' => 'History Phone', 'sku' => 'HISTORY-FLAG', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    $id = DB::table('stock_histories')->insertGetId(['product_id' => $product->id, 'store_id' => 1, 'quantity_before' => 2,
        'quantity_change' => -1, 'new_quantity' => 1, 'type' => 'sale', 'note' => 'Wholesale sale - inventory finalized', 'created_by' => auth()->id(), 'created_at' => now()]);
    foreach (['stock.history', 'products.stock-history'] as $route) {
        $response = $this->getJson(route($route, $product))->assertOk();
        expect(strtolower($response->getContent()))->not->toContain('wholesale');
    }
    expect(DB::table('stock_histories')->where('id', $id)->value('note'))->toBe('Wholesale sale - inventory finalized');
});
