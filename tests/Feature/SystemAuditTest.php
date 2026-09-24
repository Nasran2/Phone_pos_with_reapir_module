<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ImportShipment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\ChequeService;
use App\Support\SystemSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;

function auditSale(string $invoice, string $date, string $type = 'retail', float $amount = 100): Sale
{
    $customer = Customer::firstOrCreate(['name' => 'Audit Customer'], ['phone' => '0771112222']);

    return Sale::create(['customer_id' => $customer->id, 'invoice_no' => $invoice, 'sale_date' => $date, 'pos_type' => $type, 'total_amount' => $amount, 'payable_amount' => $amount, 'paid_amount' => $amount, 'total_profit_amount' => $amount / 4, 'store_id' => 1, 'created_at' => $date.' 10:00:00']);
}

test('all registered application controller actions exist', function () {
    foreach (Route::getRoutes() as $route) {
        $action = $route->getActionName();
        if (str_starts_with($action, 'App\\') && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            expect(method_exists($class, $method), $action)->toBeTrue();
        }
    }
});

test('all primary module and report entry pages render', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $failures = [];
    foreach (Route::getRoutes() as $route) {
        if (! in_array('GET', $route->methods()) || str_contains($route->uri(), '{') || ! str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\')) {
            continue;
        }
        if (preg_match('/(my-lorry|global-search|export|download|import-template|barcode|calendar\/events|products\/search|customers\/search)/', $route->uri())) {
            continue;
        }
        $response = $this->get('/'.$route->uri());
        if ($response->getStatusCode() >= 500) {
            $failures[] = $route->uri().' => '.substr(strip_tags($response->getContent()), 0, 200);
        }
    }
    expect($failures)->toBe([]);
});

test('dashboard date links totals charts and QR payments agree', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $sale = auditSale('AUDIT-RETAIL', '2026-09-21');
    auditSale('AUDIT-WHOLESALE', '2026-09-21', 'wholesale', 200);
    auditSale('AUDIT-PREVIOUS', '2026-09-20', 'retail', 50);
    DB::table('sale_payments')->insert(['sale_id' => $sale->id, 'payment_date' => '2026-09-21', 'payment_method' => 'QR Payment', 'amount' => 100]);
    $response = $this->get(route('dashboard', ['date' => '2026-09-21']))->assertOk();
    expect((float) $response->viewData('totalSalesToday'))->toBe(300.0);
    expect($response->viewData('recentSales')->pluck('invoice_no')->all())->toBe(['AUDIT-WHOLESALE', 'AUDIT-RETAIL']);
    expect($response->viewData('paymentsSummary')['QR / Mobile Pay'])->toBe(100.0);
    expect($response->viewData('changes')[0])->toBe('+500.0%');
    expect(array_sum($response->viewData('retailSalesChart')))->toBe(100.0);
    $response->assertSee(e(route('reports.sales', ['date_filter' => 'custom', 'start_date' => '2026-09-21', 'end_date' => '2026-09-21', 'sale_type' => 'wholesale'])), false);
    foreach (['today' => 300.0, 'week' => 300.0, 'month' => 350.0, 'year' => 350.0] as $period => $total) {
        $chart = $this->get(route('dashboard', ['date' => '2026-09-21', 'chart_period' => $period]))->assertOk();
        expect(array_sum($chart->viewData('retailSalesChart')) + array_sum($chart->viewData('wholesaleSalesChart')))->toBe($total);
    }
    $this->getJson(route('dashboard', ['chart_period' => 'invalid']))->assertUnprocessable();
    $this->getJson(route('dashboard', ['date' => '2026-99-44']))->assertUnprocessable();
});

test('report custom dates pagination and exports preserve filters', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    for ($i = 0; $i < 23; $i++) {
        auditSale('FILTER-'.$i, '2026-09-21', 'wholesale');
    }
    auditSale('EXCLUDED-RETAIL', '2026-09-21');
    auditSale('EXCLUDED-DATE', '2026-09-20', 'wholesale');
    $filters = ['date_filter' => 'custom', 'start_date' => '2026-09-21', 'end_date' => '2026-09-21', 'sale_type' => 'wholesale'];
    $response = $this->get(route('reports.sales', $filters))->assertOk();
    expect($response->viewData('sales')->total())->toBe(23);
    expect($response->viewData('sales')->nextPageUrl())->toContain('sale_type=wholesale', 'start_date=2026-09-21');
    $export = $this->get(route('reports.sales', $filters + ['type' => 'excel']))->assertOk();
    $cells = collect(IOFactory::load($export->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray())->flatten()->filter()->implode(' ');
    expect($cells)->toContain('FILTER-22')->not->toContain('EXCLUDED-RETAIL', 'EXCLUDED-DATE');
    $this->get(route('reports.sales', $filters + ['type' => 'pdf']))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->getJson(route('reports.sales', ['date_filter' => 'custom', 'start_date' => '2026-09-21', 'end_date' => '2026-09-20']))->assertUnprocessable();
});

test('last month handles month end and includes the first date', function () {
    $this->travelTo(Carbon::parse('2026-03-31 15:00:00'));
    $this->actingAs(createUserWithRole('Super Admin'));
    auditSale('FEB-FIRST', '2026-02-01');
    auditSale('FEB-LAST', '2026-02-28');
    auditSale('MARCH', '2026-03-01');
    $response = $this->get(route('reports.sales', ['date_filter' => 'last_month']))->assertOk();
    expect($response->viewData('sales')->pluck('invoice_no')->sort()->values()->all())->toBe(['FEB-FIRST', 'FEB-LAST']);
});

test('global search respects permissions and searches serials and contact numbers', function () {
    $admin = createUserWithRole('Super Admin');
    $sale = auditSale('SEARCH-INVOICE', '2026-09-21');
    $product = Product::create(['name' => 'Search Phone', 'sku' => 'SEARCH-SKU', 'barcode' => 'SEARCH-BARCODE', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    DB::table('product_serials')->insert(['product_id' => $product->id, 'serial_number' => 'IMEI-123456', 'status' => 'available']);
    foreach (['SEARCH-INVOICE', 'SEARCH-SKU', 'SEARCH-BARCODE', 'IMEI-123456', '0771112222'] as $term) {
        $this->actingAs($admin)->getJson(route('global-search', ['q' => $term]))->assertOk()->assertJsonCount(1, 'results');
    }
    $this->actingAs(User::factory()->create())->getJson(route('global-search', ['q' => 'SEARCH']))->assertOk()->assertJsonCount(0, 'results');
    $this->get(route('reports.sales'))->assertForbidden();
    $this->getJson(route('global-search', ['q' => 'x']))->assertUnprocessable();
});

test('received and issued cheques return once and restore balances', function (string $type) {
    $this->actingAs(createUserWithRole('Super Admin'));
    $party = $type === 'received' ? Customer::create(['name' => 'Cheque Customer', 'balance' => 1000]) : Supplier::create(['name' => 'Cheque Supplier', 'balance' => 1000]);
    $service = app(ChequeService::class);
    $cheque = $service->create(['cheque_type' => $type, $type === 'received' ? 'customer_id' : 'supplier_id' => $party->id, 'cheque_number' => 'RETURN-'.$type, 'cheque_date' => today()->toDateString(), 'amount' => 500, 'bank_name' => 'Audit Bank']);
    expect((float) $party->fresh()->balance)->toBe(500.0);
    expect((float) $party->fresh()->cheque_hold_balance)->toBe(500.0);
    $this->post(route('cheques.return', $cheque), ['return_date' => today()->toDateString(), 'return_reason' => 'Insufficient funds', 'return_charge' => 0])->assertRedirect();
    $this->post(route('cheques.return', $cheque), ['return_date' => today()->toDateString(), 'return_reason' => 'Insufficient funds', 'return_charge' => 0])->assertRedirect();
    expect($cheque->fresh()->status)->toBe('returned');
    expect((float) $party->fresh()->balance)->toBe(1000.0);
    expect((float) $party->fresh()->cheque_hold_balance)->toBe(0.0);
    $this->actingAs(User::factory()->create())->post(route('cheques.pass', $cheque))->assertForbidden();
})->with(['received', 'issued']);

test('purchase receipt increases stock and supplier due with matching report', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $supplier = Supplier::create(['name' => 'Purchase Audit', 'balance' => 0]);
    $product = Product::create(['name' => 'Purchase Stock', 'sku' => 'PURCHASE-STOCK', 'cost_price' => 60, 'retail_price' => 100, 'wholesale_price' => 90]);
    $this->post(route('purchases.store'), [
        'supplier_id' => $supplier->id, 'purchase_date' => today()->toDateString(), 'status' => 'Received', 'store_id' => 1,
        'discount_type' => 'none', 'shipping_handling' => 'purchase_total', 'payment_method' => 'Cash', 'payment_amount' => 50,
        'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 60, 'retail_price' => 100, 'wholesale_price' => 90]],
    ])->assertSessionHasNoErrors()->assertRedirect();
    $purchase = Purchase::latest('id')->firstOrFail();
    expect((float) $purchase->total_amount)->toBe(120.0);
    expect((float) $purchase->due_amount)->toBe(70.0);
    expect((float) $supplier->fresh()->balance)->toBe(70.0);
    expect((float) DB::table('product_store_stock')->where('product_id', $product->id)->sum('quantity'))->toBe(2.0);
    $this->get(route('purchases.show', $purchase))->assertOk();
    $this->get(route('reports.purchases'))->assertOk()->assertSee($purchase->invoice_no);
});

test('expense records balanced journal and appears on dashboard and report', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $cash = Account::create(['account_code' => 'AUDIT-CASH', 'account_name' => 'Audit Cash', 'account_type' => 'Asset', 'opening_balance' => 1000, 'current_balance' => 1000, 'status' => true]);
    $cost = Account::create(['account_code' => 'AUDIT-COST', 'account_name' => 'Audit Expense', 'account_type' => 'Expense', 'status' => true]);
    $category = ExpenseCategory::create(['name' => 'Audit Expenses']);
    $this->post(route('accounting.expenses.store'), ['expense_date' => today()->toDateString(), 'expense_category_id' => $category->id, 'expense_account_id' => $cost->id, 'title' => 'Audit Delivery', 'amount' => 100, 'payment_method' => 'Cash', 'paid_from_account_id' => $cash->id])->assertRedirect()->assertSessionHasNoErrors();
    expect((float) $cash->fresh()->current_balance)->toBe(900.0);
    expect((float) $cost->fresh()->current_balance)->toBe(100.0);
    expect((float) $this->get(route('dashboard'))->assertOk()->viewData('totalExpensesToday'))->toBe(100.0);
    $this->get(route('reports.expenses'))->assertOk()->assertSee('Audit Delivery');
});

test('draft import edits persist items and totals and finalized imports stay locked', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $supplier = Supplier::create(['name' => 'Import Supplier', 'status' => true]);
    $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'is_active' => true]);
    $product = Product::create(['name' => 'Imported Phone', 'sku' => 'IMPORT-TEST', 'is_active' => true, 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15]);
    $payload = ['reference_no' => 'IMPORT-AUDIT', 'shipment_date' => '2026-09-21', 'supplier_id' => $supplier->id, 'currency_id' => $currency->id, 'exchange_rate' => 300, 'store_id' => 1, 'cost_allocation_method' => 'value', 'items' => [['product_id' => $product->id, 'quantity' => 2, 'foreign_unit_price' => 10]]];
    $this->post(route('import-shipments.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $shipment = ImportShipment::latest('id')->firstOrFail();
    $this->get(route('import-shipments.edit', $shipment))->assertOk()->assertSee('IMPORT-AUDIT');
    $payload['items'][0]['quantity'] = 3;
    $this->put(route('import-shipments.update', $shipment), $payload)->assertRedirect()->assertSessionHasNoErrors();
    expect((float) $shipment->fresh()->local_total_amount)->toBe(9000.0);
    expect((float) $shipment->items()->sole()->quantity)->toBe(3.0);
    $this->get(route('import-shipments.show', $shipment))->assertOk();
    $shipment->update(['status' => 'finalized']);
    $this->putJson(route('import-shipments.update', $shipment), $payload)->assertUnprocessable();
    $this->actingAs(User::factory()->create())->get(route('import-shipments.index'))->assertForbidden();
    $this->get(route('accounting.tax-rates.index'))->assertForbidden();
    $this->get(route('hs-codes.index'))->assertForbidden();
});

test('stock reports use selected store quantities in page and export', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    Setting::set('enable_multi_store', '1');
    $store = Store::create(['name' => 'Audit Second Store', 'code' => 'AUDIT-SECOND', 'status' => true]);
    $product = Product::create(['name' => 'Store Scoped Phone', 'sku' => 'STORE-SCOPED', 'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15, 'stock_alert_quantity' => 5]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => 1, 'quantity' => 100]);
    ProductStoreStock::create(['product_id' => $product->id, 'store_id' => $store->id, 'quantity' => 2]);
    foreach (['pos.retail', 'pos.wholesale'] as $route) {
        $response = $this->getJson(route($route, ['store_id' => $store->id]))->assertOk();
        expect((float) collect($response->json('products'))->firstWhere('id', $product->id)['stock'])->toBe(2.0);
    }
    foreach (['reports.stock', 'reports.low_stock'] as $route) {
        $response = $this->get(route($route, ['store_id' => $store->id]))->assertOk();
        expect((float) $response->viewData('products')->first()->report_stock_quantity)->toBe(2.0);
    }
});

test('product profit summaries and workbook include records beyond the current page', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $sale = auditSale('PROFIT-AUDIT', today()->toDateString());
    for ($i = 0; $i < 22; $i++) {
        $product = Product::create(['name' => 'Profit Product '.$i, 'sku' => 'PROFIT-'.$i, 'cost_price' => 60, 'retail_price' => 100, 'wholesale_price' => 90]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'cost_price' => 60, 'selling_price' => 100, 'subtotal' => 100, 'line_total' => 100, 'profit_amount' => 40]);
    }
    $page = $this->get(route('reports.product_profit'))->assertOk();
    expect($page->viewData('data')->total())->toBe(22);
    expect($page->viewData('summary')['Revenue'])->toBe(SystemSettings::money(2200));
    $export = $this->get(route('reports.product_profit', ['type' => 'excel']))->assertOk();
    $rows = IOFactory::load($export->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray();
    expect(collect($rows)->filter(fn ($row) => str_starts_with((string) ($row[0] ?? ''), 'Profit Product '))->count())->toBe(22);
});

test('dashboard excludes held and returned cheques from cleared collections', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $sale = auditSale('PAYMENT-STATUS', today()->toDateString());
    foreach (['hold', 'returned', 'cleared'] as $status) {
        DB::table('sale_payments')->insert(['sale_id' => $sale->id, 'payment_date' => today()->toDateString(), 'payment_method' => 'Cheque', 'status' => $status, 'amount' => 100]);
    }
    $response = $this->get(route('dashboard'))->assertOk();
    expect($response->viewData('paymentsSummary')['Other'])->toBe(100.0);
});

test('tax rate create update and delete redirect to the working named index route', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    $data = ['name' => 'Audit VAT', 'code' => 'AUDIT-VAT', 'tax_type' => 'VAT', 'rate' => 10, 'calculation_method' => 'percentage', 'tax_classification' => 'Standard', 'effective_from' => '2026-09-21', 'is_active' => 1];
    $this->post(route('accounting.tax-rates.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('accounting.tax-rates.index'));
    $rate = TaxRate::where('code', 'AUDIT-VAT')->firstOrFail();
    $data['rate'] = 12;
    $this->put(route('accounting.tax-rates.update', $rate), $data)->assertSessionHasNoErrors()->assertRedirect(route('accounting.tax-rates.index'));
    expect((float) $rate->fresh()->rate)->toBe(12.0);
    $this->delete(route('accounting.tax-rates.destroy', $rate))->assertRedirect(route('accounting.tax-rates.index'));
    $this->assertSoftDeleted('tax_rates', ['id' => $rate->id]);
});

test('account search combines with account type instead of bypassing it', function () {
    $this->actingAs(createUserWithRole('Super Admin'));
    Account::create(['account_code' => 'FILTER-ASSET', 'account_name' => 'Bank Audit', 'account_type' => 'Asset', 'status' => true]);
    Account::create(['account_code' => 'FILTER-EXPENSE', 'account_name' => 'Cost Audit', 'account_type' => 'Expense', 'status' => true]);
    $response = $this->get(route('accounting.accounts.index', ['type' => 'Asset', 'search' => 'FILTER']))->assertOk();
    expect($response->viewData('accounts')->pluck('account_code')->all())->toBe(['FILTER-ASSET']);
});

test('expense viewers cannot create financial entries without create permission', function () {
    $viewer = createUserWithRole('Expense Viewer');
    $permission = Permission::firstOrCreate(['name' => 'expenses.view'], ['guard_name' => 'web']);
    $roleId = DB::table('roles')->where('name', 'Expense Viewer')->value('id');
    DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permission->id]);
    $this->actingAs($viewer)->get(route('accounting.expenses.index'))->assertOk();
    $this->postJson(route('accounting.expenses.store'), [])->assertForbidden();
});
